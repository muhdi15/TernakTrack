<?php

namespace Tests\Feature;

use App\Models\Alert;
use App\Models\Animal;
use App\Models\Device;
use App\Models\DeviceCommand;
use App\Models\Fence;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class DeviceApiTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'api-test-token-1234567890';

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->device = Device::factory()->create([
            'user_id' => $this->user->id,
            'api_token' => self::TOKEN,
        ]);
    }

    private function authHeaders(): array
    {
        return ['Authorization' => 'Bearer '.self::TOKEN];
    }

    public function test_api_root_is_public(): void
    {
        $this->getJson('/api')
            ->assertOk()
            ->assertJson(['name' => 'TernakTrack API', 'version' => 'v1']);
    }

    public function test_locations_requires_bearer_token(): void
    {
        $this->postJson('/api/v1/locations', $this->validLocation())
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Missing bearer token.');
    }

    public function test_locations_rejects_unknown_token(): void
    {
        $this->postJson('/api/v1/locations', $this->validLocation(), [
            'Authorization' => 'Bearer wrong-token',
        ])->assertUnauthorized();
    }

    public function test_store_location_creates_log_and_updates_device(): void
    {
        $response = $this->postJson('/api/v1/locations', $this->validLocation(), $this->authHeaders());

        $response
            ->assertCreated()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.geofence_events', []);

        $logId = $response->json('data.log_id');

        $this->assertDatabaseHas('location_logs', [
            'id' => $logId,
            'device_id' => $this->device->id,
            'latitude' => -6.26,
            'longitude' => 106.83,
            'is_valid' => true,
        ]);

        $this->assertNotNull($this->device->fresh()->last_seen_at);
        $this->assertSame(85, $this->device->fresh()->battery_level);
    }

    public function test_store_location_validates_coordinates(): void
    {
        $payload = $this->validLocation();
        $payload['latitude'] = 91;

        $this->postJson('/api/v1/locations', $payload, $this->authHeaders())
            ->assertUnprocessable()
            ->assertJsonPath('status', 'error');
    }

    public function test_store_location_rejects_accuracy_above_100m(): void
    {
        $payload = $this->validLocation();
        $payload['accuracy_meters'] = 250;

        $this->postJson('/api/v1/locations', $payload, $this->authHeaders())
            ->assertUnprocessable()
            ->assertJsonPath('errors.accuracy_meters.0', 'accuracy_meters harus kurang dari 100 meter.');
    }

    public function test_store_location_rejects_speed_above_100kmh(): void
    {
        $payload = $this->validLocation();
        $payload['speed_kmh'] = 140.5;

        $this->postJson('/api/v1/locations', $payload, $this->authHeaders())
            ->assertUnprocessable()
            ->assertJsonPath('errors.speed_kmh.0', 'speed_kmh harus kurang dari 100 km/jam.');
    }

    public function test_store_location_generates_exit_event_and_alert(): void
    {
        // Hewan dengan device + fence inclusion aktif sekitar lokasi yang dilaporkan.
        $animal = Animal::factory()->create([
            'user_id' => $this->user->id,
            'device_id' => $this->device->id,
        ]);

        $fence = Fence::factory()->inclusion()->create([
            'user_id' => $this->user->id,
            'polygon_coordinates' => Fence::factory()->squarePolygon(-6.26, 106.83, 0.001),
        ]);
        $animal->fences()->attach($fence, ['assigned_at' => now()]);

        $payload = $this->validLocation();
        // Di luar persegi (pusat -6.26, 106.83, offset ±0.001).
        $payload['latitude'] = -6.2580;
        $payload['longitude'] = 106.8350;

        $response = $this->postJson('/api/v1/locations', $payload, $this->authHeaders());

        $response
            ->assertCreated()
            ->assertJson(fn (AssertableJson $json) => $json
                ->where('status', 'success')
                ->where('data.geofence_events.0.fence_id', $fence->id)
                ->where('data.geofence_events.0.event_type', 'exit')
                ->where('data.geofence_events.0.is_alert', true)
                ->etc());

        $this->assertDatabaseHas('geofence_events', [
            'device_id' => $this->device->id,
            'fence_id' => $fence->id,
            'event_type' => 'exit',
        ]);

        // Job sync (QueueConnection=sync) → Alert langsung terbuat.
        $this->assertDatabaseHas('alerts', [
            'user_id' => $this->user->id,
            'animal_id' => $animal->id,
            'geofence_event_id' => Alert::first()->geofence_event_id,
            'type' => 'fence_exit',
        ]);
    }

    public function test_fences_endpoint_returns_assigned_fences(): void
    {
        $animal = Animal::factory()->create([
            'user_id' => $this->user->id,
            'device_id' => $this->device->id,
        ]);

        $fence = Fence::factory()->inclusion()->create([
            'user_id' => $this->user->id,
            'polygon_coordinates' => Fence::factory()->squarePolygon(-6.26, 106.83, 0.001),
        ]);
        $animal->fences()->attach($fence, ['assigned_at' => now()]);

        $this->getJson('/api/v1/devices/me/fences', $this->authHeaders())
            ->assertOk()
            ->assertJsonPath('data.fences.0.id', $fence->id)
            ->assertJsonPath('data.fences.0.fence_type', 'inclusion')
            ->assertJsonPath('data.fences.0.polygon_coordinates.0.lat', -6.261)
            ->assertJsonCount(4, 'data.fences.0.polygon_coordinates');
    }

    public function test_fences_endpoint_returns_empty_without_animal(): void
    {
        $this->getJson('/api/v1/devices/me/fences', $this->authHeaders())
            ->assertOk()
            ->assertJsonPath('data.fences', []);
    }

    public function test_config_endpoint_returns_intervals(): void
    {
        Setting::create(['user_id' => $this->user->id, 'key' => 'location_report_interval_seconds', 'value' => '30']);
        Setting::create(['user_id' => $this->user->id, 'key' => 'low_battery_threshold', 'value' => '15']);

        $this->getJson('/api/v1/devices/me/config', $this->authHeaders())
            ->assertOk()
            ->assertJsonPath('data.location_interval_seconds', 30)
            ->assertJsonPath('data.low_battery_threshold', 15)
            ->assertJsonPath('data.gps_timeout_seconds', 120);
    }

    public function test_heartbeat_updates_device(): void
    {
        $this->postJson('/api/v1/devices/me/heartbeat', [
            'battery_level' => 42,
            'battery_voltage' => 3.9,
            'firmware_version' => '1.4.0',
        ], $this->authHeaders())
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $fresh = $this->device->fresh();
        $this->assertSame(42, $fresh->battery_level);
        $this->assertSame('1.4.0', $fresh->firmware_version);
        $this->assertNotNull($fresh->last_seen_at);
    }

    public function test_commands_list_and_ack(): void
    {
        $command = DeviceCommand::create([
            'device_id' => $this->device->id,
            'command' => 'take_photo',
            'payload' => ['interval' => 5],
            'status' => 'pending',
        ]);

        $this->getJson('/api/v1/devices/me/commands', $this->authHeaders())
            ->assertOk()
            ->assertJsonCount(1, 'data.commands')
            ->assertJsonPath('data.commands.0.command', 'take_photo');

        $this->postJson('/api/v1/devices/me/commands/'.$command->id.'/ack', [], $this->authHeaders())
            ->assertOk()
            ->assertJsonPath('data.command_id', $command->id);

        $this->assertSame('executed', $command->fresh()->status);
        $this->assertNotNull($command->fresh()->executed_at);
    }

    public function test_ack_unknown_command_returns_404(): void
    {
        $this->postJson('/api/v1/devices/me/commands/999/ack', [], $this->authHeaders())
            ->assertNotFound();
    }

    public function test_ack_executed_command_returns_409(): void
    {
        $command = DeviceCommand::create([
            'device_id' => $this->device->id,
            'command' => 'reboot',
            'payload' => [],
            'status' => 'executed',
        ]);

        $this->postJson('/api/v1/devices/me/commands/'.$command->id.'/ack', [], $this->authHeaders())
            ->assertStatus(409);
    }

    public function test_locations_are_rate_limited_per_device(): void
    {
        // 60 laporan/menit diizinkan; yang ke-61 ditolak.
        for ($i = 0; $i < 60; $i++) {
            $this->postJson('/api/v1/locations', $this->validLocation(), $this->authHeaders());
        }

        $this->postJson('/api/v1/locations', $this->validLocation(), $this->authHeaders())
            ->assertStatus(429);
    }

    /**
     * @return array<string, mixed>
     */
    private function validLocation(): array
    {
        return [
            'latitude' => -6.26,
            'longitude' => 106.83,
            'recorded_at' => '2026-09-15T10:30:00Z',
            'accuracy_meters' => 5.2,
            'speed_kmh' => 2.1,
            'altitude' => 30.5,
            'heading' => 180,
            'satellites' => 10,
            'hdop' => 1.2,
            'battery_level' => 85,
            'battery_voltage' => 4.1,
            'firmware_version' => '1.2.0',
        ];
    }
}
