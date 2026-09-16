<?php

namespace Tests\Unit;

use App\Models\Animal;
use App\Models\Device;
use App\Models\LocationLog;
use App\Services\MovementStatsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MovementStatsServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_empty_logs_produce_zero_rows_for_seven_days(): void
    {
        $stats = MovementStatsService::dailyStats(collect(), 7);

        $this->assertCount(7, $stats);
        $this->assertSame(0.0, $stats[0]['distance_km']);
        $this->assertSame(0, $stats[0]['points']);
        $this->assertNull($stats[0]['avg_speed_kmh']);
    }

    public function test_distance_accumulates_between_consecutive_points(): void
    {
        $animal = Animal::factory()->create();
        $device = Device::factory()->create();

        $now = now()->startOfDay()->addHours(8);

        LocationLog::factory()->create([
            'animal_id' => $animal->id,
            'device_id' => $device->id,
            'latitude' => -6.200000,
            'longitude' => 106.800000,
            'speed_kmh' => 10,
            'recorded_at' => $now,
        ]);
        LocationLog::factory()->create([
            'animal_id' => $animal->id,
            'device_id' => $device->id,
            'latitude' => -6.199000,
            'longitude' => 106.800000,
            'speed_kmh' => 20,
            'recorded_at' => $now->copy()->addMinutes(5),
        ]);

        $logs = $animal->locationLogs()->get();
        $stats = MovementStatsService::dailyStats($logs, 7);

        // Indeks 6 = hari ini; ~0.001° latitude ≈ 111 m → jarak tempuh ≈ 0.11 km.
        $this->assertEqualsWithDelta(0.11, $stats[6]['distance_km'], 0.01);
        $this->assertEqualsWithDelta(15.0, $stats[6]['avg_speed_kmh'], 0.01);
        $this->assertSame(2, $stats[6]['points']);
    }

    public function test_points_on_different_days_split_correctly(): void
    {
        $animal = Animal::factory()->create();

        LocationLog::factory()->create([
            'animal_id' => $animal->id,
            'latitude' => -6.200000,
            'longitude' => 106.800000,
            'recorded_at' => now()->startOfDay()->addHours(8),
        ]);
        LocationLog::factory()->create([
            'animal_id' => $animal->id,
            'latitude' => -6.200001,
            'longitude' => 106.800001,
            'recorded_at' => now()->startOfDay()->subHours(16),
        ]);

        $stats = MovementStatsService::dailyStats($animal->locationLogs()->get(), 7);

        // Indeks 6 = hari ini (1 titik), indeks 5 = kemarin (1 titik).
        // Tiap hari hanya 1 titik → jarak 0.
        $this->assertSame(0.0, $stats[6]['distance_km']);
        $this->assertSame(1, $stats[6]['points']);
        $this->assertSame(0.0, $stats[5]['distance_km']);
        $this->assertSame(1, $stats[5]['points']);
    }
}
