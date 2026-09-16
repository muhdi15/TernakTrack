<?php

namespace Database\Factories;

use App\Models\Animal;
use App\Models\HealthRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HealthRecord>
 */
class HealthRecordFactory extends Factory
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
            'record_date' => now()->subDays(fake()->numberBetween(0, 60)),
            'type' => fake()->randomElement(['pemeriksaan', 'vaksinasi', 'pengobatan', 'penimbangan', 'lainnya']),
            'description' => fake()->sentence(10),
            'vet_name' => fake()->optional()->name(),
            'next_due_date' => fake()->optional()->dateTimeBetween('+1 week', '+3 months'),
        ];
    }
}
