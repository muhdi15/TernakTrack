<?php

namespace Tests\Feature;

use App\Livewire\Fences\FenceDetail;
use App\Livewire\Fences\FenceForm;
use App\Livewire\Fences\FenceList;
use App\Models\Animal;
use App\Models\Fence;
use App\Models\LocationLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FenceManageTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_only_sees_own_fences_in_list(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        Fence::factory()->create(['user_id' => $owner->id, 'name' => 'Kandang Utara']);
        Fence::factory()->create(['user_id' => $other->id, 'name' => 'Zona Rahasia']);

        $this->actingAs($owner)
            ->get('/fences')
            ->assertOk()
            ->assertSee('Kandang Utara')
            ->assertDontSee('Zona Rahasia');
    }

    public function test_create_fence_computes_area_and_syncs_animals(): void
    {
        $user = User::factory()->create();
        $animalA = Animal::factory()->create(['user_id' => $user->id]);
        $animalB = Animal::factory()->create(['user_id' => $user->id]);

        $polygon = [
            ['lat' => -6.26, 'lng' => 106.83],
            ['lat' => -6.26, 'lng' => 106.84],
            ['lat' => -6.25, 'lng' => 106.84],
            ['lat' => -6.25, 'lng' => 106.83],
        ];

        Livewire::actingAs($user)
            ->test(FenceForm::class)
            ->set('name', 'Padang Hijau')
            ->set('fenceType', 'inclusion')
            ->set('color', '#16a34a')
            ->set('selectedAnimals', [$animalA->id, $animalB->id])
            ->set('polygon', json_encode($polygon))
            ->call('save')
            ->assertRedirect(route('fences.index'));

        $fence = Fence::where('name', 'Padang Hijau')->firstOrFail();

        $this->assertEquals($user->id, $fence->user_id);
        $this->assertGreaterThan(0.0, (float) $fence->area_hectares);
        $this->assertEquals(1, $fence->version);
        $this->assertEquals(2, $fence->animals()->count());
        $this->assertNotNull($fence->animals()->first()->pivot->assigned_at);

        $this->assertDatabaseHas('animal_fence', ['animal_id' => $animalA->id, 'fence_id' => $fence->id]);
    }

    public function test_create_rejects_self_intersecting_polygon(): void
    {
        $user = User::factory()->create();

        $bowtie = [
            ['lat' => -6.2600, 'lng' => 106.8300],
            ['lat' => -6.2500, 'lng' => 106.8400],
            ['lat' => -6.2600, 'lng' => 106.8400],
            ['lat' => -6.2500, 'lng' => 106.8300],
        ];

        Livewire::actingAs($user)
            ->test(FenceForm::class)
            ->set('name', 'Angka 8')
            ->set('polygon', json_encode($bowtie))
            ->call('save')
            ->assertHasErrors(['polygon']);

        $this->assertDatabaseMissing('fences', ['name' => 'Angka 8']);
    }

    public function test_create_rejects_polygon_with_less_than_three_points(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(FenceForm::class)
            ->set('name', 'Segaris')
            ->set('polygon', json_encode([
                ['lat' => -6.26, 'lng' => 106.83],
                ['lat' => -6.25, 'lng' => 106.84],
            ]))
            ->call('save')
            ->assertHasErrors(['polygon']);

        $this->assertDatabaseMissing('fences', ['name' => 'Segaris']);
    }

    public function test_edit_increments_version_when_polygon_changes(): void
    {
        $user = User::factory()->create();
        $fence = Fence::factory()->create(['user_id' => $user->id]);

        $newPolygon = [
            ['lat' => -6.26, 'lng' => 106.83],
            ['lat' => -6.26, 'lng' => 106.841],
            ['lat' => -6.249, 'lng' => 106.841],
            ['lat' => -6.249, 'lng' => 106.83],
        ];

        Livewire::actingAs($user)
            ->test(FenceForm::class, ['fence' => $fence])
            ->set('name', 'Nama Baru')
            ->set('polygon', json_encode($newPolygon))
            ->call('save')
            ->assertRedirect(route('fences.index'));

        $this->assertEquals(2, $fence->fresh()->version);
    }

    public function test_edit_keeps_version_when_only_metadata_changes(): void
    {
        $user = User::factory()->create();
        $fence = Fence::factory()->create(['user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test(FenceForm::class, ['fence' => $fence])
            ->set('name', 'Nama Baru Saja')
            ->set('polygon', json_encode($fence->polygon_coordinates))
            ->call('save')
            ->assertRedirect(route('fences.index'));

        $this->assertEquals(1, $fence->fresh()->version);
    }

    public function test_toggle_active_flips_status(): void
    {
        $user = User::factory()->create();
        $fence = Fence::factory()->create(['user_id' => $user->id, 'is_active' => true]);

        Livewire::actingAs($user)
            ->test(FenceList::class)
            ->call('toggleActive', $fence->id);

        $this->assertFalse($fence->fresh()->is_active);
    }

    public function test_list_delete_soft_deletes_fence(): void
    {
        $user = User::factory()->create();
        $fence = Fence::factory()->create(['user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test(FenceList::class)
            ->call('delete', $fence->id);

        $this->assertSoftDeleted('fences', ['id' => $fence->id]);
    }

    public function test_non_owner_cannot_view_fence_detail(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $fence = Fence::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($other)
            ->get('/fences/'.$fence->id)
            ->assertForbidden();
    }

    public function test_detail_marks_animal_inside_and_outside(): void
    {
        $user = User::factory()->create();
        $fence = Fence::factory()->inclusion()->create(['user_id' => $user->id]);

        $inside = Animal::factory()->create(['user_id' => $user->id]);
        $outside = Animal::factory()->create(['user_id' => $user->id]);

        $fence->animals()->sync([
            $inside->id => ['assigned_at' => now()],
            $outside->id => ['assigned_at' => now()],
        ]);

        // Titik di dalam polygon factory (pusat square).
        LocationLog::factory()->create([
            'animal_id' => $inside->id,
            'latitude' => -6.26,
            'longitude' => 106.83,
        ]);

        // Titik jelas di luar polygon.
        LocationLog::factory()->create([
            'animal_id' => $outside->id,
            'latitude' => -6.29,
            'longitude' => 106.80,
        ]);

        Livewire::actingAs($user)
            ->test(FenceDetail::class, ['fence' => $fence])
            ->assertViewHas('animalsWithStatus', function ($items) use ($inside, $outside) {
                $byId = collect($items)->keyBy('id');

                return $byId->get($inside->id)['status'] === 'inside'
                    && $byId->get($outside->id)['status'] === 'outside';
            });
    }
}
