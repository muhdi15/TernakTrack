<?php

namespace Database\Factories;

use App\Models\Farm;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Farm>
 */
class FarmFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->name().' Farm',
            'address' => fake()->streetAddress().', '.fake()->city(),
            'latitude' => fake()->latitude(-8.5, -5.5),
            'longitude' => fake()->longitude(104.5, 109.5),
            'timezone' => 'Asia/Jakarta',
            'is_active' => true,
        ];
    }
}
