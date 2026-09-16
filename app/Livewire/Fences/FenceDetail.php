<?php

namespace App\Livewire\Fences;

use App\Models\Animal;
use App\Models\Fence;
use App\Models\LocationLog;
use App\Services\GeofenceService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class FenceDetail extends Component
{
    public Fence $fence;

    public function mount(Fence $fence): void
    {
        Gate::authorize('view', $fence);

        $this->fence = $fence;
    }

    public function toggleActive(): void
    {
        Gate::authorize('update', $this->fence);

        $this->fence->update(['is_active' => ! $this->fence->is_active]);
        $this->fence->refresh();
    }

    public function deleteFence(): void
    {
        Gate::authorize('delete', $this->fence);

        $name = $this->fence->name;
        $this->fence->delete();

        session()->flash('status', 'Fence "'.$name.'" berhasil dihapus.');

        $this->redirectRoute('fences.index');
    }

    public function render()
    {
        $fence = $this->fence;

        $weekStart = CarbonImmutable::now()->startOfWeek();

        $weekEvents = $fence->geofenceEvents()
            ->where('created_at', '>=', $weekStart)
            ->get()
            ->filter(fn ($event) => in_array($event->event_type, ['exit', 'outside'], true))
            ->values();

        $weekExitsPerDay = collect(range(0, 6))
            ->mapWithKeys(fn ($offset) => [
                $weekStart->addDays($offset)->translatedFormat('l') => $weekEvents
                    ->filter(fn ($event) => $event->created_at->isSameDay($weekStart->addDays($offset)))
                    ->count(),
            ]);

        $unacknowledged = $fence->geofenceEvents()
            ->where('is_acknowledged', false)
            ->whereIn('event_type', ['exit', 'outside'])
            ->count();

        $animals = $fence->animals()
            ->with('device')
            ->orderBy('name')
            ->get();

        $animalsWithStatus = $animals->map(function (Animal $animal) use ($fence) {
            $latest = LocationLog::query()
                ->where('animal_id', $animal->id)
                ->latest('recorded_at')
                ->first();

            $polygon = $fence->polygon_coordinates;

            if ($latest === null || ! is_array($polygon) || count($polygon) < 3) {
                return $this->animalPayload($animal, null, 'unknown', 'Lokasi belum tersedia');
            }

            $inside = GeofenceService::isPointInPolygon(
                (float) $latest->latitude,
                (float) $latest->longitude,
                $polygon,
            );

            if (! $fence->is_active) {
                return $this->animalPayload($animal, $latest, 'inactive', 'Fence nonaktif');
            }

            if ($fence->fence_type === 'exclusion') {
                return $this->animalPayload(
                    $animal,
                    $latest,
                    $inside ? 'outside' : 'inside',
                    $inside ? 'Di dalam zona larangan' : 'Aman di luar zona',
                );
            }

            return $this->animalPayload(
                $animal,
                $latest,
                $inside ? 'inside' : 'outside',
                $inside ? 'Di dalam fence' : 'Keluar dari fence',
            );
        });

        $timeline = $fence->geofenceEvents()
            ->with('animal:id,name')
            ->latest('created_at')
            ->limit(25)
            ->get();

        return view('livewire.fences.fence-detail', [
            'animalsWithStatus' => $animalsWithStatus,
            'weekEvents' => $weekEvents,
            'weekExitsPerDay' => $weekExitsPerDay,
            'unacknowledged' => $unacknowledged,
            'timeline' => $timeline,
            'mapPolygon' => $fence->polygon_coordinates ?: [],
            'mapAnimals' => $animalsWithStatus->map(fn ($item) => [
                'id' => $item['id'],
                'name' => $item['name'],
                'status' => $item['status'],
                'label' => $item['label'],
                'lat' => $item['lat'],
                'lng' => $item['lng'],
            ]),
        ])->layout('layouts.app', [
            'pageTitle' => 'Detail Fence: '.$fence->name,
            'currentMenu' => ['label' => 'Fence', 'route' => 'fences.index'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function animalPayload(Animal $animal, ?LocationLog $latest, string $status, string $label): array
    {
        return [
            'id' => $animal->id,
            'name' => $animal->name,
            'tag_number' => $animal->tag_number,
            'species' => $animal->species,
            'device_name' => $animal->device?->name,
            'status' => $status,
            'label' => $label,
            'lat' => $latest?->latitude !== null ? (float) $latest->latitude : null,
            'lng' => $latest?->longitude !== null ? (float) $latest->longitude : null,
            'updated_at' => $latest?->recorded_at?->toDateTimeString(),
            'latest' => $latest,
        ];
    }
}
