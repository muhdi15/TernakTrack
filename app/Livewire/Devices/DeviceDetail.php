<?php

namespace App\Livewire\Devices;

use App\Models\Device;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Component;

class DeviceDetail extends Component
{
    public Device $device;

    public string $selectedCommand = 'reboot';

    public int $intervalSeconds = 60;

    public bool $showRegenerateModal = false;

    public ?string $regeneratedToken = null;

    public function mount(Device $device): void
    {
        Gate::authorize('view', $device);

        $this->device = $device;
    }

    public function sendCommand(): void
    {
        Gate::authorize('update', $this->device);

        $payload = match ($this->selectedCommand) {
            'set_interval' => ['interval_seconds' => $this->intervalSeconds],
            default => [],
        };

        $this->device->deviceCommands()->create([
            'command' => $this->selectedCommand,
            'payload' => $payload,
            'status' => 'pending',
        ]);

        $labels = [
            'reboot' => 'Restart Perangkat',
            'set_interval' => 'Ubah Interval Laporan',
            'locate_now' => 'Lacak Sekarang',
        ];

        session()->flash('status', 'Perintah "'.($labels[$this->selectedCommand] ?? $this->selectedCommand).'" dikirim dan menunggu perangkat mengambilnya.');
    }

    public function regenerateToken(): void
    {
        Gate::authorize('update', $this->device);

        $this->device->forceFill(['api_token' => Str::random(64)])->save();

        $this->regeneratedToken = $this->device->fresh()->api_token;
        $this->showRegenerateModal = true;
    }

    public function closeRegenerateModal(): void
    {
        $this->showRegenerateModal = false;
        $this->regeneratedToken = null;
    }

    public function render()
    {
        $device = $this->device;

        // Riwayat baterai 7 hari → satu nilai per hari (nilai terakhir).
        $batteryLogs = $device->batteryLogs()
            ->where('recorded_at', '>=', now()->subDays(6)->startOfDay())
            ->orderBy('recorded_at')
            ->get();

        $dailyBattery = $batteryLogs->groupBy(fn ($log) => $log->recorded_at->toDateString());

        $batteryLabels = [];
        $batteryValues = [];

        for ($i = 6; $i >= 0; $i--) {
            $day = now()->subDays($i)->toDateString();
            $lastLog = $dailyBattery->get($day)?->last();

            $batteryLabels[] = now()->subDays($i)->format('d M');
            $batteryValues[] = $lastLog?->battery_level;
        }

        // Titik rute 24 jam terakhir untuk peta Leaflet.
        $routeLogs = $device->locationLogs()
            ->where('recorded_at', '>=', now()->subHours(24))
            ->orderBy('recorded_at')
            ->get(['latitude', 'longitude']);

        $routePoints = $routeLogs
            ->map(fn ($log) => ['lat' => (float) $log->latitude, 'lng' => (float) $log->longitude])
            ->values()
            ->all();

        $commands = $device->deviceCommands()
            ->latest()
            ->limit(20)
            ->get();

        return view('livewire.devices.device-detail', [
            'device' => $device,
            'batteryLabels' => $batteryLabels,
            'batteryValues' => $batteryValues,
            'routePoints' => $routePoints,
            'commands' => $commands,
        ])->layout('layouts.app', [
            'pageTitle' => 'Detail Perangkat',
            'currentMenu' => ['label' => 'Perangkat', 'route' => 'devices.index'],
        ]);
    }
}
