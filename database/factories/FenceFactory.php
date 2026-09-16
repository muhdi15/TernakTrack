<?php

namespace Database\Factories;

use App\Models\Fence;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Fence>
 */
class FenceFactory extends Factory
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
            'name' => fake()->sentence(2),
            'description' => null,
            'color' => '#22c55e',
            'fence_type' => fake()->randomElement(['inclusion', 'exclusion']),
            'polygon_coordinates' => self::squarePolygon(-6.2600000, 106.8300000, 0.001),
            'area_hectares' => null,
            'version' => 1,
            'is_active' => true,
            'alert_on_exit' => true,
            'alert_on_enter' => false,
        ];
    }

    /**
     * Persegi dengan pusat (lat, lng) dan separuh sisi offset derajat.
     *
     * @return array<int, array{lat: float, lng: float}>
     */
    public static function squarePolygon(float $lat, float $lng, float $halfOffset): array
    {
        return [
            ['lat' => $lat - $halfOffset, 'lng' => $lng - $halfOffset],
            ['lat' => $lat - $halfOffset, 'lng' => $lng + $halfOffset],
            ['lat' => $lat + $halfOffset, 'lng' => $lng + $halfOffset],
            ['lat' => $lat + $halfOffset, 'lng' => $lng - $halfOffset],
        ];
    }

    /**
     * Fence berjenis inclusion (zona aman).
     */
    public function inclusion(): static
    {
        return $this->state(fn () => ['fence_type' => 'inclusion']);
    }

    /**
     * Fence berjenis exclusion (zona larangan).
     */
    public function exclusion(): static
    {
        return $this->state(fn () => [
            'fence_type' => 'exclusion',
            'alert_on_exit' => false,
            'alert_on_enter' => true,
        ]);
    }

    /**
     * Gunakan polygon tertentu.
     */
    public function polygon(array $points): static
    {
        return $this->state(fn () => ['polygon_coordinates' => $points]);
    }

    /**
     * Fence non-aktif.
     */
    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
