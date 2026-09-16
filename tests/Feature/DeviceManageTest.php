<?php

namespace Tests\Feature;

use App\Livewire\Devices\DeviceDetail;
use App\Livewire\Devices\DeviceForm;
use App\Livewire\Devices\DeviceList;
use App\Models\Device;
use App\Models\DeviceBatteryLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DeviceManageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_redirected_to_login(): void
    {
        $this->get('/devices')->assertRedirect('/login');
        $this->get('/devices/create')->assertRedirect('/login');
    }

    public function test_user_only_sees_own_devices_in_list(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        Device::factory()->create(['user_id' => $owner->id, 'name' => 'Tracker Milik Ku']);
        Device::factory()->create(['user_id' => $other->id, 'name' => 'Tracker Orang Lain']);

        $this->actingAs($owner)
            ->get('/devices')
            ->assertOk()
            ->assertSee('Tracker Milik Ku')
            ->assertDontSee('Tracker Orang Lain');
    }

    public function test_admin_sees_all_devices(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create();

        Device::factory()->create(['user_id' => $owner->id, 'name' => 'Tracker A']);
        Device::factory()->create(['user_id' => $owner->id, 'name' => 'Tracker B']);

        $this->actingAs($admin)
            ->get('/devices')
            ->assertOk()
            ->assertSee('Tracker A')
            ->assertSee('Tracker B');
    }

    public function test_search_filters_device_list(): void
    {
        $owner = User::factory()->create();

        Device::factory()->create(['user_id' => $owner->id, 'name' => 'GPS Kambing']);
        Device::factory()->create(['user_id' => $owner->id, 'name' => 'GPS Sapi']);

        Livewire::actingAs($owner)
            ->test(DeviceList::class)
            ->set('search', 'Sapi')
            ->assertSee('GPS Sapi')
            ->assertDontSee('GPS Kambing');
    }

    public function test_device_create_shows_token_once_and_dispatches_modal(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(DeviceForm::class)
            ->set('name', 'Tracker Baru')
            ->set('device_code', 'TT-NEW-001')
            ->set('status', 'active')
            ->call('save')
            ->assertSet('showTokenModal', true);

        $device = Device::where('device_code', 'TT-NEW-001')->firstOrFail();
        $this->assertEquals($user->id, $device->user_id);
        $this->assertSame(64, strlen($device->api_token));
    }

    public function test_device_form_validation_rejects_duplicate_code(): void
    {
        $user = User::factory()->create();
        Device::factory()->create(['user_id' => $user->id, 'device_code' => 'TT-DUP-001']);

        Livewire::actingAs($user)
            ->test(DeviceForm::class)
            ->set('name', 'Duplikat')
            ->set('device_code', 'TT-DUP-001')
            ->call('save')
            ->assertHasErrors(['device_code' => 'unique'])
            ->assertSet('showTokenModal', false);
    }

    public function test_device_edit_updates_and_redirects(): void
    {
        $owner = User::factory()->create();
        $device = Device::factory()->create(['user_id' => $owner->id, 'name' => 'Lama']);

        Livewire::actingAs($owner)
            ->test(DeviceForm::class, ['device' => $device])
            ->set('name', 'Baru')
            ->call('save')
            ->assertRedirect(route('devices.index'));

        $this->assertDatabaseHas('devices', ['id' => $device->id, 'name' => 'Baru']);
    }

    public function test_non_owner_cannot_edit_device(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $device = Device::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($other)
            ->get('/devices/'.$device->id.'/edit')
            ->assertForbidden();
    }

    public function test_non_owner_cannot_delete_device(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $device = Device::factory()->create(['user_id' => $owner->id]);

        Livewire::actingAs($other)
            ->test(DeviceList::class)
            ->call('delete', $device->id)
            ->assertForbidden();
    }

    public function test_owner_can_delete_device(): void
    {
        $owner = User::factory()->create();
        $device = Device::factory()->create(['user_id' => $owner->id]);

        Livewire::actingAs($owner)
            ->test(DeviceList::class)
            ->call('delete', $device->id);

        $this->assertSoftDeleted('devices', ['id' => $device->id]);
    }

    public function test_detail_shows_daily_battery_last_value(): void
    {
        $owner = User::factory()->create();
        $device = Device::factory()->create(['user_id' => $owner->id]);

        DeviceBatteryLog::factory()->create([
            'device_id' => $device->id,
            'battery_level' => 90,
            'recorded_at' => now()->startOfDay()->subMinutes(30),
        ]);
        DeviceBatteryLog::factory()->create([
            'device_id' => $device->id,
            'battery_level' => 40,
            'recorded_at' => now()->startOfDay()->addMinutes(30),
        ]);

        Livewire::actingAs($owner)
            ->test(DeviceDetail::class, ['device' => $device])
            ->assertViewHas('batteryValues', fn (array $values) => $values[6] === 40)
            ->assertViewHas('batteryLabels', fn (array $labels) => count($labels) === 7);
    }

    public function test_non_owner_cannot_view_device_detail(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $device = Device::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($other)
            ->get('/devices/'.$device->id)
            ->assertForbidden();
    }

    public function test_send_command_creates_pending_device_command(): void
    {
        $owner = User::factory()->create();
        $device = Device::factory()->create(['user_id' => $owner->id]);

        Livewire::actingAs($owner)
            ->test(DeviceDetail::class, ['device' => $device])
            ->set('selectedCommand', 'set_interval')
            ->set('intervalSeconds', 120)
            ->call('sendCommand');

        $this->assertDatabaseHas('device_commands', [
            'device_id' => $device->id,
            'command' => 'set_interval',
            'status' => 'pending',
        ]);
    }

    public function test_non_owner_cannot_send_command(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $device = Device::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($other)
            ->get('/devices/'.$device->id)
            ->assertForbidden();

        $this->assertDatabaseCount('device_commands', 0);
    }

    public function test_regenerate_token_creates_new_token(): void
    {
        $owner = User::factory()->create();
        $device = Device::factory()->create(['user_id' => $owner->id]);

        $oldToken = $device->api_token;

        Livewire::actingAs($owner)
            ->test(DeviceDetail::class, ['device' => $device])
            ->call('regenerateToken')
            ->assertSet('showRegenerateModal', true);

        $this->assertNotSame($oldToken, $device->fresh()->api_token);
        $this->assertSame(64, strlen($device->fresh()->api_token));
    }
}
