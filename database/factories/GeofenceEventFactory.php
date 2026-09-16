<?php

namespace Database\Factories;

use App\Models\Animal;
use App\Models\Device;
use App\Models\Fence;
use App\Models\GeofenceEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GeofenceEvent>
 */
class GeofenceEventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'animal_id' => Animal::factory(),
            'fence_id' => Fence::factory(),
            'device_id' => Device::factory(),
            'event_type' => fake()->randomElement(['exit', 'enter', 'inside', 'outside']),
            'latitude' => fake()->latitude(-7.2, -6.2),
            'longitude' => fake()->longitude(106.5, 107.1),
            'distance_from_fence_meters' => fake()->randomFloat(2, 0, 500),
            'is_acknowledged' => false,
            'acknowledged_by' => null,
            'acknowledged_at' => null,
            'notes' => null,
        ];
    }

    public function exit(): static
    {
        return $this->state(['event_type' => 'exit']);
    }
}
