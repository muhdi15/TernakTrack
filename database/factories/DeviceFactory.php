<?php

namespace Database\Factories;

use App\Models\Device;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Device>
 */
class DeviceFactory extends Factory
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
            'name' => 'GPS-'.strtoupper(Str::random(4)).' '.fake()->word(),
            'device_code' => 'TT-'.strtoupper(Str::random(10)),
            'api_token' => Str::random(64),
            'status' => 'active',
            'battery_level' => fake()->numberBetween(20, 100),
            'battery_voltage' => fake()->randomFloat(2, 3.4, 4.2),
            'last_seen_at' => now(),
            'firmware_version' => '1.2.0',
            'notes' => null,
        ];
    }

    /**
     * Gunakan token API yang sudah ditentukan (untuk testing).
     */
    public function withApiToken(string $token): static
    {
        return $this->state(fn () => ['api_token' => $token]);
    }

    /**
     * Perangkat terakhir terlihat beberapa menit/menit lalu.
     */
    public function offline(int $minutesAgo = 60): static
    {
        return $this->state(fn () => ['last_seen_at' => now()->subMinutes($minutesAgo)]);
    }
}
