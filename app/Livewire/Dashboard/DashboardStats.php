<?php

namespace App\Livewire\Dashboard;

use App\Models\Animal;
use App\Models\Device;
use App\Models\Fence;
use App\Services\AnimalFenceStatusService;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class DashboardStats extends Component
{
    public int $totalDevices = 0;

    public int $activeDevices = 0;

    public int $offlineDevices = 0;

    public int $totalAnimals = 0;

    public int $animalsOutsideFence = 0;

    public int $totalFences = 0;

    public function mount(): void
    {
        $this->refresh();
    }

    public function refresh(): void
    {
        $cacheKey = 'dashboard_stats_'.auth()->id();

        $stats = Cache::remember($cacheKey, 30, function () {
            $user = auth()->user();
            $userId = $user->isAdmin() ? null : $user->id;

            $devices = Device::query()->when($userId, fn ($q) => $q->where('user_id', $userId));
            $animals = Animal::query()->when($userId, fn ($q) => $q->where('user_id', $userId));
            $fences = Fence::query()->when($userId, fn ($q) => $q->where('user_id', $userId))
                ->where('is_active', true);

            $fiveMinutesAgo = now()->subMinutes(5);

            $outsideCount = AnimalFenceStatusService::countOutside($animals->get(['id', 'name']));

            return [
                'totalDevices' => $devices->count(),
                'activeDevices' => (clone $devices)->where('last_seen_at', '>=', $fiveMinutesAgo)->count(),
                'offlineDevices' => (clone $devices)->where(fn ($q) => $q->whereNull('last_seen_at')->orWhere('last_seen_at', '<', $fiveMinutesAgo))->count(),
                'totalAnimals' => $animals->count(),
                'animalsOutsideFence' => $outsideCount,
                'totalFences' => $fences->count(),
            ];
        });

        foreach ($stats as $key => $value) {
            $this->{$key} = $value;
        }
    }

    public function render()
    {
        return view('livewire.dashboard.dashboard-stats');
    }
}
