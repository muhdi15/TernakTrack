<?php

namespace Tests\Feature;

use App\Livewire\Dashboard\DashboardStats;
use App\Models\Animal;
use App\Models\Fence;
use App\Models\LocationLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_animals_outside_fence_counted_from_latest_position(): void
    {
        Cache::flush();

        $user = User::factory()->create();
        $fence = Fence::factory()->create(['user_id' => $user->id]);

        $inside = Animal::factory()->create(['user_id' => $user->id]);
        $outside = Animal::factory()->create(['user_id' => $user->id]);
        $unassigned = Animal::factory()->create(['user_id' => $user->id]);

        $fence->animals()->sync([
            $inside->id => ['assigned_at' => now()],
            $outside->id => ['assigned_at' => now()],
        ]);

        LocationLog::factory()->create(['animal_id' => $inside->id, 'latitude' => -6.26, 'longitude' => 106.83]);
        LocationLog::factory()->create(['animal_id' => $outside->id, 'latitude' => -6.29, 'longitude' => 106.80]);

        Livewire::actingAs($user)
            ->test(DashboardStats::class)
            ->assertSet('animalsOutsideFence', 1)
            ->assertSet('totalAnimals', 3)
            ->assertSet('totalFences', 1);
    }

    public function test_animal_without_location_is_not_counted_as_outside(): void
    {
        Cache::flush();

        $user = User::factory()->create();
        $fence = Fence::factory()->create(['user_id' => $user->id]);
        $animal = Animal::factory()->create(['user_id' => $user->id]);

        $fence->animals()->sync([$animal->id => ['assigned_at' => now()]]);

        Livewire::actingAs($user)
            ->test(DashboardStats::class)
            ->assertSet('animalsOutsideFence', 0);
    }
}
