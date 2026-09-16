<?php

namespace App\Services;

use App\Models\LocationLog;
use Illuminate\Support\Collection;

/**
 * MovementStatsService
 *
 * Menghitung statistik jarak tempuh & kecepatan per hari dari log lokasi,
 * dipakai pada grafik "Kecepatan & Jarak Tempuh per Hari (7 hari)".
 */
class MovementStatsService
{
    /**
     * Statistik harian (ascending) untuk N hari terakhir.
     *
     * Jarak tempuh per hari = penjumlahan jarak Haversine antar titik
     * berurutan (diurutkan berdasarkan recorded_at) pada hari yang sama.
     * Kecepatan rata-rata = rata-rata kolom speed_kmh pada hari tersebut.
     *
     * @param  Collection<int, LocationLog>  $logs  Log lokasi (dalam rentang 7 hari).
     * @return array<int, array{
     *     date: string,
     *     label: string,
     *     distance_km: float,
     *     avg_speed_kmh: float|null,
     *     points: int
     * }>
     */
    public static function dailyStats(Collection $logs, int $days = 7): array
    {
        $cursor = now()->startOfDay()->subDays($days - 1);

        $grouped = $logs
            ->sortBy('recorded_at')
            ->groupBy(fn ($log) => $log->recorded_at?->toDateString());

        $stats = [];

        for ($i = 0; $i < $days; $i++) {
            $day = $cursor->copy()->addDays($i);
            $dateKey = $day->toDateString();
            $dayLogs = $grouped->get($dateKey, collect());

            $stats[] = [
                'date' => $dateKey,
                'label' => $day->format('d M'),
                'distance_km' => self::distanceKm($dayLogs),
                'avg_speed_kmh' => self::averageSpeedKmh($dayLogs),
                'points' => $dayLogs->count(),
            ];
        }

        return $stats;
    }

    /**
     * Total jarak tempuh (km) titik-titik berurutan pada satu hari.
     *
     * @param  Collection<int, LocationLog>  $logs
     */
    private static function distanceKm(Collection $logs): float
    {
        if ($logs->count() < 2) {
            return 0.0;
        }

        $ordered = $logs->sortBy('recorded_at')->values();

        $totalMeters = 0.0;

        for ($i = 0; $i < $ordered->count() - 1; $i++) {
            $totalMeters += GeofenceService::haversine(
                (float) $ordered[$i]->latitude,
                (float) $ordered[$i]->longitude,
                (float) $ordered[$i + 1]->latitude,
                (float) $ordered[$i + 1]->longitude,
            );
        }

        return round($totalMeters / 1000, 2);
    }

    /**
     * Rata-rata speed_kmh pada hari tersebut (null jika tidak ada data).
     *
     * @param  Collection<int, LocationLog>  $logs
     */
    private static function averageSpeedKmh(Collection $logs): ?float
    {
        $speeds = $logs
            ->whereNotNull('speed_kmh')
            ->pluck('speed_kmh')
            ->map(fn ($value) => (float) $value);

        if ($speeds->isEmpty()) {
            return null;
        }

        return round($speeds->avg(), 2);
    }
}
