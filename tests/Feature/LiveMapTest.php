<?php

namespace Tests\Feature;

use App\Livewire\Map\LiveMap;
use App\Models\Animal;
use App\Models\Farm;
use App\Models\Fence;
use App\Models\LocationLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LiveMapTest extends TestCase
{
    use RefreshDatabase;

    public function test_map_page_loads(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/map')
            ->assertOk()
            ->assertSee('Peta Real-time');
    }

    public function test_payload_contains_fences_and_animals(): void
    {
        $user = User::factory()->create();
        $fence = Fence::factory()->create(['user_id' => $user->id, 'name' => 'Zona Inti']);
        $animal = Animal::factory()->create(['user_id' => $user->id, 'name' => 'Sapi Nala']);

        $fence->animals()->sync([$animal->id => ['assigned_at' => now()]]);
        LocationLog::factory()->create([
            'animal_id' => $animal->id,
            'latitude' => -6.26,
            'longitude' => 106.83,
        ]);

        Livewire::actingAs($user)
            ->test(LiveMap::class)
            ->assertViewHas('mapPayload', function ($payload) use ($fence, $animal) {
                $fenceNames = collect($payload['fences'])->pluck('name')->all();
                $animalIds = collect($payload['animals'])->pluck('id')->all();

                return in_array($fence->name, $fenceNames, true)
                    && in_array($animal->id, $animalIds, true);
            });
    }

    public function test_animal_inside_fence_is_marked_inside(): void
    {
        $user = User::factory()->create();
        $fence = Fence::factory()->inclusion()->create(['user_id' => $user->id]);
        $animal = Animal::factory()->create(['user_id' => $user->id]);

        $fence->animals()->sync([$animal->id => ['assigned_at' => now()]]);
        LocationLog::factory()->create([
            'animal_id' => $animal->id,
            'latitude' => -6.26,
            'longitude' => 106.83,
        ]);

        Livewire::actingAs($user)
            ->test(LiveMap::class)
            ->assertViewHas('mapPayload', function ($payload) use ($animal) {
                $item = collect($payload['animals'])->firstWhere('id', $animal->id);

                return $item !== null && $item['status'] === 'inside';
            });
    }

    public function test_trail_populated_when_animal_selected(): void
    {
        $user = User::factory()->create();
        $animal = Animal::factory()->create(['user_id' => $user->id]);

        LocationLog::factory()->count(5)->create([
            'animal_id' => $animal->id,
            'recorded_at' => now()->subMinutes(fake()->randomDigitNotNull()),
        ]);

        Livewire::actingAs($user)
            ->test(LiveMap::class)
            ->set('trailAnimalId', $animal->id)
            ->assertViewHas('mapPayload', fn ($payload) => count($payload['trail']) >= 5);
    }

    public function test_trail_hours_limit_generates_empty_trail_when_out_of_range(): void
    {
        $user = User::factory()->create();
        $animal = Animal::factory()->create(['user_id' => $user->id]);

        LocationLog::factory()->create([
            'animal_id' => $animal->id,
            'recorded_at' => now()->subDays(2),
        ]);

        Livewire::actingAs($user)
            ->test(LiveMap::class)
            ->set('trailAnimalId', $animal->id)
            ->set('trailHours', 1)
            ->assertViewHas('mapPayload', fn ($payload) => count($payload['trail']) === 0);
    }

    public function test_cannot_trail_other_users_animal(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $animal = Animal::factory()->create(['user_id' => $owner->id]);

        LocationLog::factory()->create(['animal_id' => $animal->id]);

        Livewire::actingAs($other)
            ->test(LiveMap::class)
            ->set('trailAnimalId', $animal->id)
            ->assertSet('trailAnimalId', null);
    }

    public function test_farm_filter_scopes_payload(): void
    {
        $user = User::factory()->create();
        $farm = Farm::factory()->create(['user_id' => $user->id]);
        $otherFarm = Farm::factory()->create(['user_id' => $user->id]);

        $included = Fence::factory()->create(['user_id' => $user->id, 'farm_id' => $farm->id, 'name' => 'Zona Kandang A']);
        $excluded = Fence::factory()->create(['user_id' => $user->id, 'farm_id' => $otherFarm->id, 'name' => 'Zona Kandang B']);

        Livewire::actingAs($user)
            ->test(LiveMap::class)
            ->set('farmId', $farm->id)
            ->assertViewHas('mapPayload', function ($payload) use ($included, $excluded) {
                $names = collect($payload['fences'])->pluck('name')->all();

                return in_array($included->name, $names, true)
                    && ! in_array($excluded->name, $names, true);
            });
    }
}
