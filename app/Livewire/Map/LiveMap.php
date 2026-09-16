<?php

namespace App\Livewire\Map;

use App\Models\Animal;
use App\Models\Farm;
use App\Models\Fence;
use App\Models\LocationLog;
use App\Services\AnimalFenceStatusService;
use Livewire\Attributes\Url;
use Livewire\Component;

class LiveMap extends Component
{
    #[Url(except: '')]
    public int $trailHours = 1;

    public ?int $trailAnimalId = null;

    #[Url(except: '')]
    public string $species = '';

    public ?int $farmId = null;

    public function mount(): void
    {
        $this->farmId = session('active_farm_id') ?? $this->farmId;
    }

    public function render()
    {
        $userId = auth()->user()->isAdmin() ? null : auth()->user()->id;

        $farms = Farm::query()
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        if ($this->farmId !== null && ! $farms->contains('id', $this->farmId)) {
            $this->farmId = null;
        }

        $fences = Fence::query()
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->when($this->farmId, fn ($q) => $q->where('farm_id', $this->farmId))
            ->orderBy('name')
            ->get();

        $animals = Animal::query()
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->when($this->farmId, fn ($q) => $q->where('farm_id', $this->farmId))
            ->when($this->species !== '', fn ($q) => $q->where('species', $this->species))
            ->with('device:id,name,last_seen_at')
            ->orderBy('name')
            ->get();

        $animalPayload = $animals->map(function (Animal $animal) {
            $status = AnimalFenceStatusService::resolve($animal);

            return [
                'id' => $animal->id,
                'name' => $animal->name,
                'species' => $animal->species,
                'status' => $status['status'],
                'label' => $status['label'],
                'lat' => $status['lat'],
                'lng' => $status['lng'],
                'updated_at' => $status['updated_at'],
                'device_online' => $animal->device !== null
                    && $animal->device->last_seen_at !== null
                    && $animal->device->last_seen_at->gte(now()->subMinutes(5)),
            ];
        });

        $fencePayload = $fences->map(fn (Fence $fence) => [
            'id' => $fence->id,
            'name' => $fence->name,
            'color' => $fence->color ?: '#22c55e',
            'fence_type' => $fence->fence_type,
            'is_active' => $fence->is_active,
            'area_hectares' => $fence->area_hectares,
            'polygon' => $fence->polygon_coordinates ?: [],
        ]);

        $trail = [];

        if ($this->trailAnimalId !== null) {
            $hasAccess = Animal::query()
                ->when($userId, fn ($q) => $q->where('user_id', auth()->id()))
                ->whereKey($this->trailAnimalId)
                ->exists();

            if ($hasAccess) {
                $trail = LocationLog::query()
                    ->where('animal_id', $this->trailAnimalId)
                    ->where('recorded_at', '>=', now()->subHours(max(1, $this->trailHours)))
                    ->orderBy('recorded_at')
                    ->limit(2000)
                    ->get()
                    ->map(fn ($log) => [
                        'lat' => (float) $log->latitude,
                        'lng' => (float) $log->longitude,
                        't' => $log->recorded_at?->toIso8601String(),
                    ])
                    ->all();
            } else {
                $this->trailAnimalId = null;
            }
        }

        $mapPayload = [
            'fences' => $fencePayload,
            'animals' => $animalPayload,
            'trail' => $trail,
            'trailAnimalId' => $this->trailAnimalId,
        ];

        return view('livewire.map.live-map', [
            'farms' => $farms,
            'animals' => $animals,
            'speciesOptions' => array_values(array_unique($animals->pluck('species')->all())),
            'mapPayload' => $mapPayload,
        ])->layout('layouts.app', [
            'pageTitle' => 'Peta Real-time',
            'currentMenu' => ['label' => 'Peta', 'route' => 'map'],
        ]);
    }
}
