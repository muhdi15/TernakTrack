<?php

namespace Tests\Feature;

use App\Livewire\Animals\AnimalDetail;
use App\Livewire\Animals\AnimalForm;
use App\Livewire\Animals\AnimalList;
use App\Livewire\Animals\HealthRecordForm;
use App\Models\Animal;
use App\Models\Device;
use App\Models\Farm;
use App\Models\Fence;
use App\Models\HealthRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class AnimalManageTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_only_sees_own_animals_in_list(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        Animal::factory()->create(['user_id' => $owner->id, 'name' => 'Sapi A']);
        Animal::factory()->create(['user_id' => $other->id, 'name' => 'Sapi B']);

        $this->actingAs($owner)
            ->get('/animals')
            ->assertOk()
            ->assertSee('Sapi A')
            ->assertDontSee('Sapi B');
    }

    public function test_species_filter_filters_animal_list(): void
    {
        $owner = User::factory()->create();

        Animal::factory()->create(['user_id' => $owner->id, 'species' => 'sapi', 'name' => 'Bos']);
        Animal::factory()->create(['user_id' => $owner->id, 'species' => 'kambing', 'name' => 'Gembel']);

        Livewire::actingAs($owner)
            ->test(AnimalList::class)
            ->set('speciesFilter', 'sapi')
            ->assertSee('Bos')
            ->assertDontSee('Gembel');
    }

    public function test_animal_create_syncs_fences_and_assigns_device(): void
    {
        $user = User::factory()->create();
        $farm = Farm::factory()->create(['user_id' => $user->id]);
        $fenceA = Fence::factory()->create(['user_id' => $user->id, 'fence_type' => 'inclusion']);
        $fenceB = Fence::factory()->create(['user_id' => $user->id, 'fence_type' => 'exclusion']);
        $device = Device::factory()->create(['user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test(AnimalForm::class)
            ->set('name', 'Dobleh')
            ->set('tag_number', 'TT-TEST-0001')
            ->set('species', 'kambing')
            ->set('gender', 'betina')
            ->set('farm_id', $farm->id)
            ->set('device_id', $device->id)
            ->set('selectedFences', [$fenceA->id, $fenceB->id])
            ->call('save');

        $animal = Animal::where('tag_number', 'TT-TEST-0001')->firstOrFail();

        $this->assertEquals($user->id, $animal->user_id);
        $this->assertEquals($device->id, $animal->device_id);
        $this->assertDatabaseHas('animal_fence', [
            'animal_id' => $animal->id,
            'fence_id' => $fenceA->id,
        ]);
        $this->assertNotNull($animal->fences()->first()->pivot->assigned_at);
        $this->assertEquals(2, $animal->fences()->count());
    }

    public function test_animal_create_rejects_device_already_in_use(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->create(['user_id' => $user->id]);
        Animal::factory()->create(['user_id' => $user->id, 'device_id' => $device->id]);

        Livewire::actingAs($user)
            ->test(AnimalForm::class)
            ->set('name', 'Dobel')
            ->set('tag_number', 'TT-TEST-0002')
            ->set('device_id', $device->id)
            ->call('save')
            ->assertHasErrors(['device_id']);
    }

    public function test_animal_edit_updates_and_redirects(): void
    {
        $user = User::factory()->create();
        $animal = Animal::factory()->create(['user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test(AnimalForm::class, ['animal' => $animal])
            ->set('name', 'Ganti Nama')
            ->call('save')
            ->assertRedirect(route('animals.index'));

        $this->assertDatabaseHas('animals', ['id' => $animal->id, 'name' => 'Ganti Nama']);
    }

    public function test_non_owner_cannot_edit_animal(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $animal = Animal::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($other)
            ->get('/animals/'.$animal->id.'/edit')
            ->assertForbidden();
    }

    public function test_owner_can_delete_animal(): void
    {
        $user = User::factory()->create();
        $animal = Animal::factory()->create(['user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test(AnimalList::class)
            ->call('delete', $animal->id);

        $this->assertSoftDeleted('animals', ['id' => $animal->id]);
    }

    public function test_photo_upload_resized_to_800px_and_stored(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('foto.png', 1600, 900);

        Livewire::actingAs($user)
            ->test(AnimalForm::class)
            ->set('name', 'Berfoto')
            ->set('tag_number', 'TT-PHOTO-01')
            ->set('photo', $file)
            ->call('save');

        $animal = Animal::where('tag_number', 'TT-PHOTO-01')->firstOrFail();

        $this->assertNotNull($animal->photo_path);
        Storage::disk('public')->assertExists($animal->photo_path);

        $stored = imagecreatefromstring(Storage::disk('public')->get($animal->photo_path));
        $this->assertEquals(800, imagesx($stored));

        imagedestroy($stored);
    }

    public function test_photo_replacement_deletes_old_file(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $animal = Animal::factory()->create(['user_id' => $user->id, 'photo_path' => 'animals/photos/lama.jpg']);

        Storage::disk('public')->put('animals/photos/lama.jpg', 'x');

        $file = UploadedFile::fake()->image('baru.png', 1200, 600);

        Livewire::actingAs($user)
            ->test(AnimalForm::class, ['animal' => $animal])
            ->set('photo', $file)
            ->call('save');

        Storage::disk('public')->assertMissing('animals/photos/lama.jpg');
        $this->assertNotSame('animals/photos/lama.jpg', $animal->fresh()->photo_path);
    }

    public function test_locate_now_creates_pending_command(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->create(['user_id' => $user->id]);
        $animal = Animal::factory()->create(['user_id' => $user->id, 'device_id' => $device->id]);

        Livewire::actingAs($user)
            ->test(AnimalDetail::class, ['animal' => $animal])
            ->call('locateNow');

        $this->assertDatabaseHas('device_commands', [
            'device_id' => $device->id,
            'command' => 'locate_now',
            'status' => 'pending',
        ]);
    }

    public function test_locate_now_without_device_shows_error(): void
    {
        $user = User::factory()->create();
        $animal = Animal::factory()->create(['user_id' => $user->id, 'device_id' => null]);

        Livewire::actingAs($user)
            ->test(AnimalDetail::class, ['animal' => $animal])
            ->call('locateNow');

        $this->assertDatabaseCount('device_commands', 0);
    }

    public function test_health_record_create_via_modal_component(): void
    {
        $user = User::factory()->create();
        $animal = Animal::factory()->create(['user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test(HealthRecordForm::class, ['animal' => $animal])
            ->call('openCreate')
            ->assertSet('open', true)
            ->set('type', 'vaksinasi')
            ->set('description', 'Vaksin rutin dua bulan')
            ->set('vet_name', 'drh. Budi')
            ->call('save')
            ->assertSet('open', false);

        $this->assertDatabaseHas('health_records', [
            'animal_id' => $animal->id,
            'type' => 'vaksinasi',
            'description' => 'Vaksin rutin dua bulan',
            'vet_name' => 'drh. Budi',
        ]);
    }

    public function test_health_record_edit_via_modal_component(): void
    {
        $user = User::factory()->create();
        $animal = Animal::factory()->create(['user_id' => $user->id]);
        $record = HealthRecord::factory()->create(['animal_id' => $animal->id]);

        Livewire::actingAs($user)
            ->test(HealthRecordForm::class, ['animal' => $animal])
            ->call('openEdit', $record->id)
            ->assertSet('open', true)
            ->set('description', 'Diperbarui')
            ->call('save');

        $this->assertDatabaseHas('health_records', ['id' => $record->id, 'description' => 'Diperbarui']);
    }

    public function test_health_record_delete_from_detail(): void
    {
        $user = User::factory()->create();
        $animal = Animal::factory()->create(['user_id' => $user->id]);
        $record = HealthRecord::factory()->create(['animal_id' => $animal->id]);

        Livewire::actingAs($user)
            ->test(AnimalDetail::class, ['animal' => $animal])
            ->call('deleteHealthRecord', $record->id);

        $this->assertDatabaseMissing('health_records', ['id' => $record->id]);
    }

    public function test_non_owner_cannot_delete_health_record(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $animal = Animal::factory()->create(['user_id' => $owner->id]);
        $record = HealthRecord::factory()->create(['animal_id' => $animal->id]);

        $this->actingAs($other)
            ->get('/animals/'.$animal->id)
            ->assertForbidden();

        $this->assertDatabaseHas('health_records', ['id' => $record->id]);
    }
}
