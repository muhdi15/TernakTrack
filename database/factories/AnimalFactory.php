<?php

namespace Database\Factories;

use App\Models\Animal;
use App\Models\Device;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Animal>
 */
class AnimalFactory extends Factory
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
            'farm_id' => null,
            'device_id' => null,
            'name' => fake()->firstName(),
            'tag_number' => 'TT-'.fake()->unique()->numerify('####'),
            'species' => fake()->randomElement(['sapi', 'kambing', 'domba', 'kerbau']),
            'gender' => fake()->randomElement(['jantan', 'betina']),
            'birth_date' => fake()->dateTimeBetween('-5 years', '-1 year')->format('Y-m-d'),
            'weight_kg' => fake()->randomFloat(2, 20, 700),
            'photo_path' => null,
            'health_status' => 'sehat',
            'notes' => null,
        ];
    }

    /**
     * Lengkapi hewan dengan device yang sudah terpasang.
     */
    public function withDevice(?Device $device = null): static
    {
        return $this->state(fn () => ['device_id' => $device?->id]);
    }
}
