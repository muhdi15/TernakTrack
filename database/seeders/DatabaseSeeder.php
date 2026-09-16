<?php

namespace Database\Seeders;

use App\Models\Alert;
use App\Models\Animal;
use App\Models\CalibrationSession;
use App\Models\Device;
use App\Models\DeviceBatteryLog;
use App\Models\Farm;
use App\Models\Fence;
use App\Models\GeofenceEvent;
use App\Models\HealthRecord;
use App\Models\LocationLog;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedUsers();
        $this->seedFarm();
        $this->seedDevices();
        $this->seedBatteryLogs();
        $this->seedAnimals();
        $this->seedFences();
        $this->assignAnimalsToFences();
        $this->seedLocationLogs();
        $this->seedGeofenceEvents();
        $this->seedAlerts();
        $this->seedHealthRecords();
        $this->seedExtra();
    }

    private function seedUsers(): void
    {
        $admin = User::create([
            'name' => 'Administrator TernakTrack',
            'email' => 'admin@ternaktrack.test',
            'password' => 'password',
            'role' => 'admin',
            'phone' => '081234567890',
            'notification_preferences' => ['email' => true, 'telegram' => true, 'whatsapp' => false],
            'timezone' => 'Asia/Jakarta',
            'language' => 'id',
        ]);

        $peternak = User::create([
            'name' => 'Rudi Hartono',
            'email' => 'rudi@ternaktrack.test',
            'password' => 'password',
            'role' => 'peternak',
            'phone' => '081298765432',
            'telegram_chat_id' => '123456789',
            'notification_preferences' => ['email' => true, 'telegram' => true, 'whatsapp' => true],
            'timezone' => 'Asia/Jakarta',
            'language' => 'id',
        ]);

        Setting::create(['user_id' => $peternak->id, 'key' => 'default_fence_color', 'value' => '#22c55e']);
        Setting::create(['user_id' => $peternak->id, 'key' => 'location_report_interval_seconds', 'value' => '60']);
        Setting::create(['user_id' => $peternak->id, 'key' => 'low_battery_threshold', 'value' => '20']);
    }

    private function seedFarm(): void
    {
        $farm = Farm::create([
            'user_id' => 2,
            'name' => 'Peternakan Sapi Kemang',
            'address' => 'Jl. Kemang Raya No. 12, Jakarta Selatan',
            'latitude' => -6.2600000,
            'longitude' => 106.8300000,
            'timezone' => 'Asia/Jakarta',
            'is_active' => true,
        ]);
    }

    private function seedDevices(): void
    {
        $names = ['GPS-01 Sapi Titan', 'GPS-02 Sapi Betina', 'GPS-03 Kambing Kawanan', 'GPS-04 Domba', 'GPS-05 Cadangan'];
        $statuses = ['active', 'active', 'active', 'maintenance', 'inactive'];

        foreach ($names as $i => $name) {
            Device::create([
                'user_id' => 2,
                'farm_id' => 1,
                'name' => $name,
                'device_code' => 'TT-'.strtoupper(Str::random(8)),
                // Device pertama memakai token tetap agar mudah diuji via curl / dokumentasi.
                'api_token' => $i === 0 ? 'tt_test_api_token_gps01_titan_0000000001' : Str::random(64),
                'status' => $statuses[$i],
                'battery_level' => $statuses[$i] === 'active' ? rand(45, 98) : rand(5, 90),
                'battery_voltage' => number_format(rand(340, 420) / 100, 2),
                'last_seen_at' => $statuses[$i] === 'active' ? now()->subMinutes(rand(1, 30)) : now()->subHours(rand(3, 72)),
                'firmware_version' => '1.2.'.rand(0, 9),
                'notes' => $i === 4 ? 'Belum dipasang ke hewan' : null,
            ]);
        }
    }

    private function seedBatteryLogs(): void
    {
        Device::query()->get()->each(function (Device $device) {
            $start = now()->subDays(7);
            $level = (int) ($device->battery_level ?? 90);
            $voltage = (float) ($device->battery_voltage ?? 4.0);

            for ($i = 0; $i <= 48; $i++) {
                $recordedAt = $start->copy()->addHours((int) ($i * 3.5));
                if ($recordedAt->isFuture()) {
                    break;
                }

                $level = min(100, max(5, $level + rand(-3, 2)));

                DeviceBatteryLog::create([
                    'device_id' => $device->id,
                    'battery_level' => $level,
                    'battery_voltage' => round($voltage + rand(-30, 10) / 100, 3),
                    'recorded_at' => $recordedAt,
                ]);
            }
        });
    }

    private function seedAnimals(): void
    {
        $data = [
            ['Titan', 'TT-SPI-0001', 'sapi', 'jantan', 650.50, 'sehat', 1],
            ['Srikandi', 'TT-SPI-0002', 'sapi', 'betina', 420.00, 'sehat', 2],
            ['Melati', 'TT-SPI-0003', 'sapi', 'betina', 380.25, 'hamil', 3],
            ['Kambing Hitam', 'TT-KMB-0001', 'kambing', 'jantan', 38.20, 'sehat', null],
            ['Dora', 'TT-DMB-0001', 'domba', 'betina', 45.90, 'sehat', 4],
            ['Rambon', 'TT-DMB-0002', 'domba', 'jantan', 52.10, 'sakit', null],
            ['Bima', 'TT-KRB-0001', 'kerbau', 'jantan', 780.00, 'sehat', null],
            ['Putih', 'TT-KRB-0002', 'kerbau', 'betina', 610.40, 'karantina', null],
            ['Ayam Bro', 'TT-LNY-0001', 'lainnya', 'betina', 2.30, 'sehat', null],
            ['Kancil', 'TT-LNY-0002', 'lainnya', 'jantan', 1.80, 'sehat', 5],
        ];

        foreach ($data as $i => [$name, $tag, $species, $gender, $weight, $health, $deviceId]) {
            Animal::create([
                'user_id' => 2,
                'farm_id' => 1,
                'device_id' => $deviceId,
                'name' => $name,
                'tag_number' => $tag,
                'species' => $species,
                'gender' => $gender,
                'birth_date' => now()->subYears(rand(1, 5))->subMonths(rand(0, 11))->format('Y-m-d'),
                'weight_kg' => $weight,
                'health_status' => $health,
                'notes' => null,
            ]);
        }
    }

    private function seedFences(): void
    {
        $fence1 = Fence::create([
            'user_id' => 2,
            'farm_id' => 1,
            'name' => 'Kandang Utama Kemang',
            'description' => 'Zona kandang dan area penggembalaan utama',
            'color' => '#22c55e',
            'fence_type' => 'inclusion',
            'polygon_coordinates' => [
                ['lat' => -6.2575000, 'lng' => 106.8270000],
                ['lat' => -6.2571000, 'lng' => 106.8330000],
                ['lat' => -6.2625000, 'lng' => 106.8334000],
                ['lat' => -6.2629000, 'lng' => 106.8272000],
            ],
            'area_hectares' => 8.75,
            'version' => 1,
            'is_active' => true,
            'alert_on_exit' => true,
            'alert_on_enter' => false,
        ]);

        $fence2 = Fence::create([
            'user_id' => 2,
            'farm_id' => 1,
            'name' => 'Padang Gembala Timur',
            'description' => 'Area gembala tambahan sisi timur',
            'color' => '#3b82f6',
            'fence_type' => 'inclusion',
            'polygon_coordinates' => [
                ['lat' => -6.2640000, 'lng' => 106.8340000],
                ['lat' => -6.2638000, 'lng' => 106.8400000],
                ['lat' => -6.2690000, 'lng' => 106.8400000],
                ['lat' => -6.2692000, 'lng' => 106.8342000],
            ],
            'area_hectares' => 26.40,
            'version' => 1,
            'is_active' => true,
            'alert_on_exit' => true,
            'alert_on_enter' => false,
        ]);

        $fence3 = Fence::create([
            'user_id' => 2,
            'farm_id' => 1,
            'name' => 'Zona Larangan Sungai',
            'description' => 'Area dekat sungai yang tidak boleh dimasuki hewan',
            'color' => '#ef4444',
            'fence_type' => 'exclusion',
            'polygon_coordinates' => [
                ['lat' => -6.2550000, 'lng' => 106.8230000],
                ['lat' => -6.2552000, 'lng' => 106.8270000],
                ['lat' => -6.2600000, 'lng' => 106.8270000],
                ['lat' => -6.2602000, 'lng' => 106.8232000],
            ],
            'area_hectares' => 25.90,
            'version' => 1,
            'is_active' => true,
            'alert_on_exit' => false,
            'alert_on_enter' => true,
        ]);
    }

    private function assignAnimalsToFences(): void
    {
        Animal::query()->get()->each(function (Animal $animal) {
            $animal->fences()->attach(3, ['assigned_at' => now()->subDays(7)]);
            if (in_array($animal->species, ['sapi', 'kerbau'], true)) {
                $animal->fences()->attach(1, ['assigned_at' => now()->subDays(7)]);
            } else {
                $animal->fences()->attach(2, ['assigned_at' => now()->subDays(7)]);
            }
        });
    }

    private function seedLocationLogs(): void
    {
        $activeDevices = Device::where('status', 'active')->get();

        for ($i = 0; $i < 100; $i++) {
            $device = $activeDevices->random();
            $animal = $device->animal;

            $recordedAt = now()->subMinutes(rand(1, 1440));
            $inside = $i % 5 !== 0;
            $breath = rand(0, 40) / 100000;

            LocationLog::create([
                'device_id' => $device->id,
                'animal_id' => $animal?->id,
                'latitude' => $inside ? (-6.2600000 + $breath) : (-6.2650000 + $breath),
                'longitude' => $inside ? (106.8300000 + $breath) : (106.8380000 + $breath),
                'altitude' => round(rand(2800, 3400) / 100, 2),
                'speed_kmh' => round(rand(0, 120) / 10, 2),
                'heading' => round(rand(0, 35900) / 100, 2),
                'accuracy_meters' => round(rand(220, 800) / 100, 2),
                'satellites' => rand(6, 14),
                'hdop' => round(rand(80, 260) / 100, 2),
                'recorded_at' => $recordedAt,
                'received_at' => $recordedAt->addSeconds(rand(1, 20)),
                'is_valid' => rand(0, 9) !== 0,
            ]);
        }
    }

    private function seedGeofenceEvents(): void
    {
        $events = [
            [1, 1, 1, 'exit', -6.2635000, 106.8310000, 42.10],
            [2, 1, 2, 'enter', -6.2589000, 106.8295000, 5.20],
            [3, 1, 3, 'outside', -6.2700000, 106.8200000, 320.75],
            [6, 2, 4, 'inside', -6.2660000, 106.8370000, null],
            [4, 3, 1, 'enter', -6.2558000, 106.8245000, 8.90],
        ];

        foreach ($events as [$animalId, $fenceId, $deviceId, $type, $lat, $lng, $distance]) {
            GeofenceEvent::create([
                'animal_id' => $animalId,
                'fence_id' => $fenceId,
                'device_id' => $deviceId,
                'event_type' => $type,
                'latitude' => $lat,
                'longitude' => $lng,
                'distance_from_fence_meters' => $distance,
                'is_acknowledged' => $type === 'exit',
                'acknowledged_by' => $type === 'exit' ? 2 : null,
                'acknowledged_at' => $type === 'exit' ? now()->subHours(rand(1, 10)) : null,
            ]);
        }
    }

    private function seedAlerts(): void
    {
        $now = now();

        $alerts = [
            [2, null, null, 1, 'fence_exit', 'critical', 'KELUAR ZONA: Titan meninggalkan Kandang Utama', 'Titan (TT-SPI-0001) terdeteksi di luar fence Kandang Utama sejauh 42 m.', true],
            [2, 2, null, 2, 'fence_enter', 'warning', 'Masuk Zona: Srikandi kembali ke Kandang Utama', 'Srikandi terdeteksi masuk kembali ke area fence.', false],
            [2, 3, null, 3, 'fence_enter', 'critical', 'BAHAYA: Melati masuk Zona Larangan Sungai', 'Melati memasuki zona larangan (exclusion fence). Segera periksa!', false],
            [2, null, 1, null, 'low_battery', 'warning', 'Baterai GPS-02 rendah (12%)', 'Baterai GPS-02 Sapi Betina tersisa 12%. Ganti dalam 24 jam.', false],
            [2, null, 2, null, 'device_offline', 'critical', 'GPS-01 tidak terhubung', 'GPS-01 Sapi Titan tidak mengirim data selama 3 jam.', false],
            [2, null, 5, null, 'device_lost', 'critical', 'GPS-05 hilang', 'GPS-05 Cadangan berstatus lost.', false],
            [2, 6, null, null, 'health_reminder', 'info', 'Ingatkan vaksinasi Rambon', 'Sudah waktunya vaksinasi ulang untuk Rambon (TT-DMB-0002).', true],
            [2, null, null, null, 'calibration_complete', 'info', 'Kalibrasi fence selesai', 'Sesi kalibrasi GPS selesai. Fence siap digunakan.', true],
            [1, null, null, null, 'custom', 'info', 'Perawatan terjadwal', 'Server ESP32 akan menjalani maintenance minggu ini.', true],
            [2, 6, null, 4, 'fence_exit', 'warning', 'Rambon keluar area gembala', 'Rambon keluar dari Padang Gembala Timur.', false],
        ];

        $created = 0;
        foreach ($alerts as [$userId, $animalId, $deviceId, $eventId, $type, $severity, $title, $message, $isRead]) {
            Alert::create([
                'user_id' => $userId,
                'animal_id' => $animalId,
                'device_id' => $deviceId,
                'geofence_event_id' => $eventId,
                'type' => $type,
                'severity' => $severity,
                'title' => $title,
                'message' => $message,
                'channels_sent' => ['email', 'telegram'],
                'is_read' => $isRead,
                'read_at' => $isRead ? $now->subHours($created + 1) : null,
            ]);
            $created++;
        }
    }

    private function seedHealthRecords(): void
    {
        HealthRecord::create([
            'animal_id' => 1,
            'record_date' => now()->subMonths(2)->format('Y-m-d'),
            'type' => 'vaksinasi',
            'description' => 'Vaksinasi brucellosis dosis pertama.',
            'vet_name' => 'drh. Andi Wijaya',
            'next_due_date' => now()->addMonths(4)->format('Y-m-d'),
        ]);

        HealthRecord::create([
            'animal_id' => 6,
            'record_date' => now()->subDays(3)->format('Y-m-d'),
            'type' => 'pemeriksaan',
            'description' => 'Pemeriksaan gejala kembung, diberikan probiotik.',
            'vet_name' => 'drh. Budi Santoso',
            'next_due_date' => now()->addWeek()->format('Y-m-d'),
        ]);

        HealthRecord::create([
            'animal_id' => 4,
            'record_date' => now()->subMonth()->format('Y-m-d'),
            'type' => 'pengobatan',
            'description' => 'Pengobatan kutu menggunakan ivermectin.',
            'vet_name' => null,
            'next_due_date' => null,
        ]);
    }

    private function seedExtra(): void
    {
        CalibrationSession::create([
            'user_id' => 2,
            'fence_id' => 1,
            'session_token' => Str::random(64),
            'name' => 'Perbaikan batas timur',
            'points' => [
                ['lat' => -6.25720, 'lng' => 106.83310, 'accuracy' => 3.2, 'recorded_at' => '2026-09-15 09:12:00', 'order' => 1],
                ['lat' => -6.26260, 'lng' => 106.83350, 'accuracy' => 2.8, 'recorded_at' => '2026-09-15 09:14:30', 'order' => 2],
            ],
            'status' => 'used',
            'expires_at' => now()->addDay(),
            'completed_at' => now()->subDay(),
            'device_info' => ['platform' => 'Android', 'browser' => 'Chrome Mobile', 'accuracy_avg' => 3.0],
        ]);

        CalibrationSession::create([
            'user_id' => 2,
            'fence_id' => null,
            'session_token' => Str::random(64),
            'name' => 'Kandang baru Blok C',
            'points' => [],
            'status' => 'draft',
            'expires_at' => now()->addDays(2),
            'device_info' => ['platform' => 'iOS', 'browser' => 'Safari'],
        ]);
    }
}
