<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessLocationJob;
use App\Jobs\SendGeofenceAlertJob;
use App\Models\Device;
use App\Models\DeviceBatteryLog;
use App\Models\DeviceCommand;
use App\Models\GeofenceEvent;
use App\Models\LocationLog;
use App\Models\Setting;
use App\Services\GeofenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Endpoint API untuk ESP32 (prefix /api/v1).
 *
 * Semua endpoint (kecuali /api health) dilindungi middleware AuthenticateDevice
 * dan rate limiter per device.
 */
class LocationController extends Controller
{
    /**
     * POST /api/v1/locations — terima laporan titik GPS dari ESP32.
     */
    public function store(Request $request): JsonResponse
    {
        /** @var Device $device */
        $device = $request->attributes->get('device');

        $validator = Validator::make($request->all(), [
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'recorded_at' => ['required', 'date'],
            'accuracy_meters' => ['nullable', 'numeric', 'min:0'],
            'speed_kmh' => ['nullable', 'numeric', 'min:0'],
            'altitude' => ['nullable', 'numeric'],
            'heading' => ['nullable', 'numeric', 'between:0,360'],
            'satellites' => ['nullable', 'integer', 'min:0'],
            'hdop' => ['nullable', 'numeric', 'min:0'],
            'battery_level' => ['nullable', 'integer', 'between:0,100'],
            'battery_voltage' => ['nullable', 'numeric'],
            'firmware_version' => ['nullable', 'string', 'max:32'],
        ]);

        // Aturan ketat spesifikasi: accuracy < 100 m dan speed < 100 km/jam.
        $validator->after(function ($validator): void {
            $data = $validator->getData();

            if (array_key_exists('accuracy_meters', $data) && $data['accuracy_meters'] !== null && (float) $data['accuracy_meters'] >= 100) {
                $validator->errors()->add('accuracy_meters', 'accuracy_meters harus kurang dari 100 meter.');
            }

            if (array_key_exists('speed_kmh', $data) && $data['speed_kmh'] !== null && (float) $data['speed_kmh'] >= 100) {
                $validator->errors()->add('speed_kmh', 'speed_kmh harus kurang dari 100 km/jam.');
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        $animal = $device->animal;

        $locationLog = LocationLog::create([
            'device_id' => $device->id,
            'animal_id' => $animal?->id,
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'altitude' => $data['altitude'] ?? null,
            'speed_kmh' => $data['speed_kmh'] ?? null,
            'heading' => $data['heading'] ?? null,
            'accuracy_meters' => $data['accuracy_meters'] ?? null,
            'satellites' => $data['satellites'] ?? null,
            'hdop' => $data['hdop'] ?? null,
            'recorded_at' => $data['recorded_at'],
            'received_at' => now(),
            'is_valid' => ! isset($data['accuracy_meters']) || (float) $data['accuracy_meters'] < 100,
        ]);

        // Perbarui status perangkat (online, baterai, firmware).
        $device->update([
            'last_seen_at' => now(),
            'battery_level' => $data['battery_level'] ?? $device->battery_level,
            'battery_voltage' => $data['battery_voltage'] ?? $device->battery_voltage,
            'firmware_version' => $data['firmware_version'] ?? $device->firmware_version,
        ]);

        // Catat riwayat baterai untuk grafik 7 hari.
        if (array_key_exists('battery_level', $data) && $data['battery_level'] !== null) {
            DeviceBatteryLog::create([
                'device_id' => $device->id,
                'battery_level' => $data['battery_level'],
                'battery_voltage' => $data['battery_voltage'] ?? null,
                'recorded_at' => $data['recorded_at'],
            ]);
        }

        $events = [];

        if ($animal) {
            $events = GeofenceService::checkAnimalPosition($animal, (float) $data['latitude'], (float) $data['longitude']);

            foreach ($events as $eventData) {
                $event = GeofenceEvent::create([
                    'animal_id' => $animal->id,
                    'fence_id' => $eventData['fence_id'],
                    'device_id' => $device->id,
                    'event_type' => $eventData['event_type'],
                    'latitude' => $eventData['latitude'],
                    'longitude' => $eventData['longitude'],
                    'distance_from_fence_meters' => $eventData['distance_from_fence_meters'],
                    'is_acknowledged' => false,
                ]);

                // Event yang perlu notifikasi → buat Alert (via Job).
                if ($eventData['is_alert']) {
                    SendGeofenceAlertJob::dispatch($event);
                }
            }
        }

        // Hook proses berat (SESI 6), jalan async.
        ProcessLocationJob::dispatch($locationLog);

        return response()->json([
            'status' => 'success',
            'message' => 'Lokasi diterima.',
            'data' => [
                'log_id' => $locationLog->id,
                'geofence_events' => $events,
                'next_interval_seconds' => $this->reportIntervalFor($device),
            ],
        ], 201);
    }

    /**
     * GET /api/v1/devices/me/fences — daftar fence yang di-assign ke hewan.
     */
    public function fences(Request $request): JsonResponse
    {
        /** @var Device $device */
        $device = $request->attributes->get('device');

        $fences = [];

        if ($device->animal) {
            $fences = $device->animal->fences()
                ->get()
                ->map(function ($fence): array {
                    return [
                        'id' => $fence->id,
                        'name' => $fence->name,
                        'fence_type' => $fence->fence_type,
                        'is_active' => $fence->is_active,
                        'version' => $fence->version,
                        'polygon_coordinates' => GeofenceService::normalizePolygon($fence->polygon_coordinates),
                    ];
                })
                ->values()
                ->all();
        }

        return response()->json([
            'status' => 'success',
            'data' => ['fences' => $fences],
        ]);
    }

    /**
     * GET /api/v1/devices/me/config — konfigurasi laporan untuk ESP32.
     */
    public function config(Request $request): JsonResponse
    {
        /** @var Device $device */
        $device = $request->attributes->get('device');

        $settings = Setting::where('user_id', $device->user_id)
            ->pluck('value', 'key');

        return response()->json([
            'status' => 'success',
            'data' => [
                'location_interval_seconds' => (int) ($settings->get('location_report_interval_seconds') ?? 60),
                'low_battery_threshold' => (int) ($settings->get('low_battery_threshold') ?? 20),
                'gps_timeout_seconds' => 120,
                'server_time' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * POST /api/v1/devices/me/heartbeat — laporan status perangkat.
     */
    public function heartbeat(Request $request): JsonResponse
    {
        /** @var Device $device */
        $device = $request->attributes->get('device');

        $validator = Validator::make($request->all(), [
            'battery_level' => ['nullable', 'integer', 'between:0,100'],
            'battery_voltage' => ['nullable', 'numeric'],
            'firmware_version' => ['nullable', 'string', 'max:32'],
            'status' => ['nullable', 'string', 'in:active,inactive,maintenance,lost'],
            'gps_status' => ['nullable', 'string', 'max:50'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        $device->update([
            'last_seen_at' => now(),
            'battery_level' => $data['battery_level'] ?? $device->battery_level,
            'battery_voltage' => $data['battery_voltage'] ?? $device->battery_voltage,
            'firmware_version' => $data['firmware_version'] ?? $device->firmware_version,
        ]);

        // Catat riwayat baterai dari heartbeat.
        if (array_key_exists('battery_level', $data) && $data['battery_level'] !== null) {
            DeviceBatteryLog::create([
                'device_id' => $device->id,
                'battery_level' => $data['battery_level'],
                'battery_voltage' => $data['battery_voltage'] ?? null,
                'recorded_at' => now(),
            ]);
        }

        if (array_key_exists('status', $data) && $data['status'] !== null) {
            $device->update(['status' => $data['status']]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Heartbeat diterima.',
            'data' => [
                'last_seen_at' => $device->last_seen_at?->toIso8601String(),
                'status' => $device->status,
            ],
        ]);
    }

    /**
     * GET /api/v1/devices/me/commands — daftar command pending untuk ESP32.
     */
    public function commands(Request $request): JsonResponse
    {
        /** @var Device $device */
        $device = $request->attributes->get('device');

        $commands = DeviceCommand::where('device_id', $device->id)
            ->where('status', 'pending')
            ->orderBy('id')
            ->get()
            ->map(fn (DeviceCommand $command): array => [
                'id' => $command->id,
                'command' => $command->command,
                'payload' => $command->payload,
                'created_at' => $command->created_at?->toIso8601String(),
            ])
            ->values()
            ->all();

        return response()->json([
            'status' => 'success',
            'data' => ['commands' => $commands],
        ]);
    }

    /**
     * POST /api/v1/devices/me/commands/{command}/ack — konfirmasi eksekusi.
     */
    public function ack(Request $request, int $command): JsonResponse
    {
        /** @var Device $device */
        $device = $request->attributes->get('device');

        $deviceCommand = DeviceCommand::where('device_id', $device->id)
            ->find($command);

        if (! $deviceCommand) {
            return response()->json([
                'status' => 'error',
                'message' => 'Command tidak ditemukan.',
            ], 404);
        }

        if ($deviceCommand->status !== 'pending') {
            return response()->json([
                'status' => 'error',
                'message' => "Command sudah berstatus {$deviceCommand->status}.",
            ], 409);
        }

        $deviceCommand->update([
            'status' => 'executed',
            'executed_at' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Command ditandai selesai.',
            'data' => ['command_id' => $deviceCommand->id],
        ]);
    }

    /**
     * Interval laporan dari setting user device, default 60 detik.
     */
    private function reportIntervalFor(Device $device): int
    {
        $interval = Setting::where('user_id', $device->user_id)
            ->where('key', 'location_report_interval_seconds')
            ->value('value');

        return $interval !== null ? (int) $interval : 60;
    }
}
