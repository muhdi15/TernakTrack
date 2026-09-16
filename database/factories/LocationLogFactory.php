<?php

namespace Database\Factories;

use App\Models\Animal;
use App\Models\Device;
use App\Models\LocationLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LocationLog>
 */
class LocationLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $recordedAt = now()->subMinutes(fake()->numberBetween(0, 1440));

        return [
            'device_id' => Device::factory(),
            'animal_id' => Animal::factory(),
            'latitude' => fake()->latitude(-7.2, -6.2),
            'longitude' => fake()->longitude(106.5, 107.1),
            'altitude' => fake()->randomFloat(2, 20, 150),
            'speed_kmh' => fake()->randomFloat(2, 0, 30),
            'heading' => fake()->randomFloat(1, 0, 359.9),
            'accuracy_meters' => fake()->randomFloat(1, 2, 40),
            'satellites' => fake()->numberBetween(4, 18),
            'hdop' => fake()->randomFloat(2, 0.5, 6),
            'recorded_at' => $recordedAt,
            'received_at' => $recordedAt->copy()->addSecond(),
            'is_valid' => true,
        ];
    }
}
