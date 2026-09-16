<?php

namespace Tests\Unit;

use App\Models\Animal;
use App\Models\Device;
use App\Models\Fence;
use App\Models\GeofenceEvent;
use App\Models\User;
use App\Services\GeofenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class GeofenceServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Pusat persegi pengujian.
     */
    private const CENTER_LAT = -6.2600000;

    private const CENTER_LNG = 106.8300000;

    private const HALF_OFFSET = 0.001;

    /**
     * @return array<int, array{lat: float, lng: float}>
     */
    private function square(): array
    {
        return Fence::factory()->squarePolygon(self::CENTER_LAT, self::CENTER_LNG, self::HALF_OFFSET);
    }

    public function test_point_inside_polygon_returns_true(): void
    {
        $this->assertTrue(GeofenceService::isPointInPolygon(self::CENTER_LAT, self::CENTER_LNG, $this->square()));
    }

    public function test_point_outside_polygon_returns_false(): void
    {
        $this->assertFalse(GeofenceService::isPointInPolygon(self::CENTER_LAT + 0.003, self::CENTER_LNG, $this->square()));
    }

    public function test_point_on_boundary_is_treated_as_inside(): void
    {
        $this->assertTrue(GeofenceService::isPointInPolygon(self::CENTER_LAT - self::HALF_OFFSET, self::CENTER_LNG, $this->square()));
    }

    public function test_polygon_with_less_than_three_points_returns_false(): void
    {
        $polygon = [
            ['lat' => -6.26, 'lng' => 106.83],
            ['lat' => -6.261, 'lng' => 106.831],
        ];

        $this->assertFalse(GeofenceService::isPointInPolygon(-6.2605, 106.8305, $polygon));
    }

    public function test_array_index_format_is_supported(): void
    {
        $polygon = [
            [-6.261, 106.829],
            [-6.261, 106.831],
            [-6.259, 106.831],
            [-6.259, 106.829],
        ];

        $this->assertTrue(GeofenceService::isPointInPolygon(-6.26, 106.83, $polygon));
    }

    public function test_polygon_with_invalid_point_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);

        GeofenceService::isPointInPolygon(-6.26, 106.83, [
            ['lat' => -6.261],
            ['lat' => -6.261, 'lng' => 106.831],
            ['lat' => -6.259, 'lng' => 106.831],
        ]);
    }

    public function test_distance_to_polygon_is_zero_when_inside(): void
    {
        $this->assertSame(0.0, GeofenceService::distanceToPolygon(self::CENTER_LAT, self::CENTER_LNG, $this->square()));
    }

    public function test_distance_to_polygon_of_outside_point(): void
    {
        $distance = GeofenceService::distanceToPolygon(self::CENTER_LAT + 0.0015, self::CENTER_LNG, $this->square());

        $this->assertGreaterThan(50.0, $distance);
        $this->assertLessThan(60.0, $distance);
    }

    public function test_distance_to_polygon_of_far_outside_point(): void
    {
        $distance = GeofenceService::distanceToPolygon(self::CENTER_LAT, self::CENTER_LNG + 0.003, $this->square());

        $this->assertGreaterThan(200.0, $distance);
        $this->assertLessThan(230.0, $distance);
    }

    public function test_distance_to_polygon_supports_indexed_format(): void
    {
        $distance = GeofenceService::distanceToPolygon(self::CENTER_LAT - 0.0015, self::CENTER_LNG, $this->square());

        $this->assertGreaterThan(0.0, $distance);
    }

    public function test_haversine_of_one_degree_latitude(): void
    {
        $distance = GeofenceService::haversine(-6.26, 106.83, -5.26, 106.83);

        $this->assertGreaterThan(111000.0, $distance);
        $this->assertLessThan(111700.0, $distance);
    }

    public function test_calculate_area_hectares_of_square(): void
    {
        $area = GeofenceService::calculateAreaHectares($this->square());

        $this->assertGreaterThan(4.8, $area);
        $this->assertLessThan(5.0, $area);
    }

    public function test_calculate_area_hectares_of_triangle(): void
    {
        $polygon = [
            ['lat' => -6.261, 'lng' => 106.829],
            ['lat' => -6.261, 'lng' => 106.831],
            ['lat' => -6.259, 'lng' => 106.831],
        ];

        $area = GeofenceService::calculateAreaHectares($polygon);

        $this->assertGreaterThan(2.2, $area);
        $this->assertLessThan(2.7, $area);
    }

    public function test_calculate_area_hectares_returns_zero_for_degenerate(): void
    {
        $this->assertSame(0.0, GeofenceService::calculateAreaHectares([
            ['lat' => -6.26, 'lng' => 106.83],
            ['lat' => -6.261, 'lng' => 106.831],
        ]));
    }

    public function test_check_animal_position_inclusion_fence_inside(): void
    {
        [$animal, $fence] = $this->createAnimalWithFence('inclusion');

        $events = GeofenceService::checkAnimalPosition($animal, self::CENTER_LAT, self::CENTER_LNG);

        $this->assertCount(1, $events);
        $this->assertSame($fence->id, $events[0]['fence_id']);
        $this->assertSame('inside', $events[0]['event_type']);
        $this->assertFalse($events[0]['is_alert']);
        $this->assertSame(0.0, $events[0]['distance_from_fence_meters']);
    }

    public function test_check_animal_position_inclusion_fence_outside_triggers_exit(): void
    {
        [$animal, $fence] = $this->createAnimalWithFence('inclusion');

        $events = GeofenceService::checkAnimalPosition($animal, self::CENTER_LAT + 0.002, self::CENTER_LNG);

        $this->assertCount(1, $events);
        $this->assertSame('exit', $events[0]['event_type']);
        $this->assertTrue($events[0]['is_alert']);
        $this->assertGreaterThan(0.0, $events[0]['distance_from_fence_meters']);
    }

    public function test_check_animal_position_exclusion_fence_inside_triggers_enter(): void
    {
        [$animal, $fence] = $this->createAnimalWithFence('exclusion');

        $events = GeofenceService::checkAnimalPosition($animal, self::CENTER_LAT, self::CENTER_LNG);

        $this->assertCount(1, $events);
        $this->assertSame('enter', $events[0]['event_type']);
        $this->assertTrue($events[0]['is_alert']);
    }

    public function test_check_animal_position_exclusion_fence_outside_is_safe(): void
    {
        [$animal, $fence] = $this->createAnimalWithFence('exclusion');

        $events = GeofenceService::checkAnimalPosition($animal, self::CENTER_LAT + 0.002, self::CENTER_LNG);

        $this->assertCount(1, $events);
        $this->assertSame('outside', $events[0]['event_type']);
        $this->assertFalse($events[0]['is_alert']);
    }

    public function test_check_animal_position_exit_does_not_spam_same_status(): void
    {
        [$animal, $fence, $device] = $this->createAnimalWithFence('inclusion');

        // Simulasikan event "exit" yang sudah tersimpan di DB (hasil pengiriman sebelumnya).
        GeofenceEvent::create([
            'animal_id' => $animal->id,
            'fence_id' => $fence->id,
            'device_id' => $device->id,
            'event_type' => 'exit',
            'latitude' => self::CENTER_LAT + 0.002,
            'longitude' => self::CENTER_LNG,
            'distance_from_fence_meters' => 200.0,
            'is_acknowledged' => false,
        ]);

        $secondRun = GeofenceService::checkAnimalPosition($animal, self::CENTER_LAT + 0.002, self::CENTER_LNG);

        $this->assertCount(0, $secondRun);
    }

    public function test_check_animal_position_emits_event_after_status_change(): void
    {
        [$animal, $fence, $device] = $this->createAnimalWithFence('inclusion');

        // Simulasikan event "exit" yang sudah tersimpan di DB.
        GeofenceEvent::create([
            'animal_id' => $animal->id,
            'fence_id' => $fence->id,
            'device_id' => $device->id,
            'event_type' => 'exit',
            'latitude' => self::CENTER_LAT + 0.002,
            'longitude' => self::CENTER_LNG,
            'distance_from_fence_meters' => 200.0,
            'is_acknowledged' => false,
        ]);

        $secondRun = GeofenceService::checkAnimalPosition($animal, self::CENTER_LAT, self::CENTER_LNG);

        $this->assertCount(1, $secondRun);
        $this->assertSame('inside', $secondRun[0]['event_type']);
    }

    public function test_check_animal_position_skips_inactive_fence(): void
    {
        $user = User::factory()->create();
        $animal = Animal::factory()->create(['user_id' => $user->id]);
        $fence = Fence::factory()->inactive()->create(['user_id' => $user->id]);
        $animal->fences()->attach($fence, ['assigned_at' => now()]);

        $events = GeofenceService::checkAnimalPosition($animal, self::CENTER_LAT, self::CENTER_LNG);

        $this->assertCount(0, $events);
    }

    public function test_check_animal_position_without_fences_returns_empty(): void
    {
        $animal = Animal::factory()->create();

        $this->assertSame([], GeofenceService::checkAnimalPosition($animal, self::CENTER_LAT, self::CENTER_LNG));
    }

    /**
     * @return array{0: Animal, 1: Fence, 2: Device}
     */
    private function createAnimalWithFence(string $fenceType): array
    {
        $user = User::factory()->create();
        $animal = Animal::factory()->create(['user_id' => $user->id]);

        $device = Device::factory()->create([
            'user_id' => $user->id,
        ]);

        $animal->update(['device_id' => $device->id]);

        $fence = Fence::factory()
            ->{$fenceType}()
            ->create(['user_id' => $user->id]);
        $animal->fences()->attach($fence, ['assigned_at' => now()]);

        return [$animal, $fence, $device];
    }
}
