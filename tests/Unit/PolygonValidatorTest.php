<?php

namespace Tests\Unit;

use App\Services\GeofenceService;
use App\Services\PolygonValidator;
use Tests\TestCase;

class PolygonValidatorTest extends TestCase
{
    /**
     * @return array<int, array{lat: float, lng: float}>
     */
    private function square(): array
    {
        return [
            ['lat' => -6.261, 'lng' => 106.829],
            ['lat' => -6.261, 'lng' => 106.831],
            ['lat' => -6.259, 'lng' => 106.831],
            ['lat' => -6.259, 'lng' => 106.829],
        ];
    }

    public function test_has_minimum_points_returns_true_for_three_or_more(): void
    {
        $this->assertTrue(PolygonValidator::hasMinimumPoints($this->square()));
        $this->assertTrue(PolygonValidator::hasMinimumPoints([
            ['lat' => 1, 'lng' => 1],
            ['lat' => 2, 'lng' => 2],
            ['lat' => 3, 'lng' => 1],
        ]));
    }

    public function test_has_minimum_points_returns_false_for_less_than_three(): void
    {
        $this->assertFalse(PolygonValidator::hasMinimumPoints([
            ['lat' => 1, 'lng' => 1],
            ['lat' => 2, 'lng' => 2],
        ]));
        $this->assertFalse(PolygonValidator::hasMinimumPoints([]));
    }

    public function test_is_self_intersecting_detects_bowtie(): void
    {
        $bowtie = [
            ['lat' => 0, 'lng' => 0],
            ['lat' => 2, 'lng' => 2],
            ['lat' => 2, 'lng' => 0],
            ['lat' => 0, 'lng' => 2],
        ];

        $this->assertTrue(PolygonValidator::isSelfIntersecting($bowtie));
    }

    public function test_is_self_intersecting_returns_false_for_square(): void
    {
        $this->assertFalse(PolygonValidator::isSelfIntersecting($this->square()));
    }

    public function test_is_self_intersecting_returns_false_for_triangle(): void
    {
        $this->assertFalse(PolygonValidator::isSelfIntersecting([
            ['lat' => 0, 'lng' => 0],
            ['lat' => 2, 'lng' => 0],
            ['lat' => 1, 'lng' => 2],
        ]));
    }

    public function test_has_duplicate_points_detects_exact_duplicate(): void
    {
        $polygon = [
            ['lat' => 0, 'lng' => 0],
            ['lat' => 0, 'lng' => 0],
            ['lat' => 2, 'lng' => 1],
        ];

        $this->assertTrue(PolygonValidator::hasDuplicatePoints($polygon));
    }

    public function test_has_duplicate_points_detects_too_close_points(): void
    {
        // Dua titik berjarak ±2 meter (di bawah threshold 5 meter).
        $close = [
            ['lat' => 0, 'lng' => 0],
            // 1 meter lintang ≈ 0.00000898 derajat → 2 meter ≈ 0.000018 derajat
            ['lat' => 0.000018, 'lng' => 0],
            ['lat' => 2, 'lng' => 1],
        ];

        $this->assertTrue(PolygonValidator::hasDuplicatePoints($close));
    }

    public function test_has_duplicate_points_returns_false_for_valid_square(): void
    {
        $this->assertFalse(PolygonValidator::hasDuplicatePoints($this->square()));
    }

    public function test_validate_for_fence_reports_minimum_points_error(): void
    {
        $errors = PolygonValidator::validateForFence([
            ['lat' => 0, 'lng' => 0],
            ['lat' => 1, 'lng' => 1],
        ]);

        $this->assertCount(1, $errors);
        $this->assertStringContainsString('minimal 3 titik', $errors[0]);
    }

    public function test_validate_for_fence_reports_self_intersecting_error(): void
    {
        $errors = PolygonValidator::validateForFence([
            ['lat' => 0, 'lng' => 0],
            ['lat' => 2, 'lng' => 2],
            ['lat' => 2, 'lng' => 0],
            ['lat' => 0, 'lng' => 2],
        ]);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('menyilang', implode(' ', $errors));
    }

    public function test_validate_for_fence_reports_duplicate_point_error(): void
    {
        $errors = PolygonValidator::validateForFence([
            ['lat' => 0, 'lng' => 0],
            ['lat' => 0, 'lng' => 0],
            ['lat' => 2, 'lng' => 1],
            ['lat' => 1, 'lng' => 2],
        ]);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('titik terlalu dekat', implode(' ', $errors));
    }

    public function test_validate_for_fence_returns_no_errors_for_valid_square(): void
    {
        $this->assertSame([], PolygonValidator::validateForFence($this->square()));
    }

    public function test_validate_for_fence_reports_invalid_point_format(): void
    {
        $errors = PolygonValidator::validateForFence([
            ['lat' => 0],
            ['lat' => 2, 'lng' => 2],
            ['lat' => 2, 'lng' => 0],
        ]);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('tidak valid', implode(' ', $errors));
    }

    public function test_validate_for_fence_with_crossing_edges_detects_intersection(): void
    {
        // Dua sisi TIDAK bersebelahan saling menyilang di titik non-vertex.
        $errors = PolygonValidator::validateForFence([
            ['lat' => 0, 'lng' => 0],
            ['lat' => 2, 'lng' => 2],
            ['lat' => 0, 'lng' => 2],
            ['lat' => 2, 'lng' => 0],
        ]);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('menyilang', implode(' ', $errors));
    }

    public function test_normalize_polygon_handles_both_formats(): void
    {
        $normalized = GeofenceService::normalizePolygon([
            [1.0, 2.0],
            ['lat' => 3, 'lng' => 4],
        ]);

        $this->assertSame([['lat' => 1.0, 'lng' => 2.0], ['lat' => 3.0, 'lng' => 4.0]], $normalized);
    }
}
