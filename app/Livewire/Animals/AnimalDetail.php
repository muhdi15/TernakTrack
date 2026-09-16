<?php

namespace App\Livewire\Animals;

use App\Models\Animal;
use App\Models\HealthRecord;
use App\Services\MovementStatsService;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\On;
use Livewire\Component;

class AnimalDetail extends Component
{
    public Animal $animal;

    public function mount(Animal $animal): void
    {
        Gate::authorize('view', $animal);

        $this->animal = $animal;
    }

    public function locateNow(): void
    {
        Gate::authorize('view', $this->animal);

        $device = $this->animal->device;

        if ($device === null) {
            session()->flash('error', 'Hewan ini belum terpasang perangkat GPS.');

            return;
        }

        Gate::authorize('update', $device);

        $device->deviceCommands()->create([
            'command' => 'locate_now',
            'payload' => [],
            'status' => 'pending',
        ]);

        session()->flash('status', 'Permintaan lokasi real-time dikirim ke "'.$device->name.'".');
    }

    public function deleteHealthRecord(int $id): void
    {
        $record = HealthRecord::findOrFail($id);

        Gate::authorize('delete', $record);

        $record->delete();

        $this->dispatch('health-record-saved');
    }

    #[On('health-record-saved')]
    public function refreshHealthRecords(): void
    {
        // Mengandalkan re-render otomatis komponen ini.
    }

    public function render()
    {
        $animal = $this->animal;

        $logs7Days = $animal->locationLogs()
            ->where('recorded_at', '>=', now()->subDays(6)->startOfDay())
            ->orderBy('recorded_at')
            ->get();

        $daily = MovementStatsService::dailyStats($logs7Days, 7);

        $movementLabels = array_column($daily, 'label');
        $distanceKm = array_column($daily, 'distance_km');
        $avgSpeedKmh = array_map(fn ($value) => $value === null ? null : (float) $value, array_column($daily, 'avg_speed_kmh'));

        $mapLogs = $animal->locationLogs()
            ->where('recorded_at', '>=', now()->subHours(24))
            ->orderBy('recorded_at')
            ->get(['latitude', 'longitude']);

        $mapPoints = $mapLogs
            ->map(fn ($log) => ['lat' => (float) $log->latitude, 'lng' => (float) $log->longitude])
            ->values()
            ->all();

        $events = $animal->geofenceEvents()
            ->with('fence')
            ->latest()
            ->limit(20)
            ->get();

        $healthRecords = $animal->healthRecords()
            ->orderByDesc('record_date')
            ->get();

        return view('livewire.animals.animal-detail', [
            'animal' => $animal,
            'daily' => $daily,
            'movementLabels' => $movementLabels,
            'distanceKm' => $distanceKm,
            'avgSpeedKmh' => $avgSpeedKmh,
            'mapPoints' => $mapPoints,
            'events' => $events,
            'healthRecords' => $healthRecords,
        ])->layout('layouts.app', [
            'pageTitle' => 'Detail Hewan',
            'currentMenu' => ['label' => 'Hewan', 'route' => 'animals.index'],
        ]);
    }
}
