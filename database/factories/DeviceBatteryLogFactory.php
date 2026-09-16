<?php

namespace Database\Factories;

use App\Models\Device;
use App\Models\DeviceBatteryLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeviceBatteryLog>
 */
class DeviceBatteryLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'device_id' => Device::factory(),
            'battery_level' => fake()->numberBetween(20, 100),
            'battery_voltage' => fake()->randomFloat(3, 3.4, 4.2),
            'recorded_at' => now()->subHours(fake()->numberBetween(0, 168)),
        ];
    }
}
