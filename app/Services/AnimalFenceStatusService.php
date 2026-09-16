<?php

namespace App\Services;

use App\Models\Animal;
use App\Models\LocationLog;
use Illuminate\Support\Collection;

/**
 * AnimalFenceStatusService
 *
 * Menentukan status spasial hewan SEKARANG berdasarkan lokasi terakhir
 * (LocationLog) terhadap fence aktif yang di-assign. Dipakai bersama oleh
 * LiveMap (warna marker) dan DashboardStats (jumlah hewan di luar fence)
 * agar kedua layar selalu konsisten.
 *
 * Semantic status:
 *  - inside   : aman, berada tepat sesuai zona (hijau).
 *  - outside  : melanggar (keluar fence inclusion / masuk zona exclusion).
 *  - unassigned: lokasi ada, tapi tidak ada fence aktif (abu-abu).
 *  - unknown  : tidak ada lokasi terakhir (abu-abu).
 */
class AnimalFenceStatusService
{
    /**
     * Resolusi status satu hewan.
     *
     * @return array{status: string, label: string, lat: float|null, lng: float|null, updated_at: string|null}
     */
    public static function resolve(Animal $animal): array
    {
        $latest = LocationLog::query()
            ->where('animal_id', $animal->id)
            ->latest('recorded_at')
            ->first();

        if ($latest === null) {
            return [
                'status' => 'unknown',
                'label' => 'Lokasi belum tersedia',
                'lat' => null,
                'lng' => null,
                'updated_at' => null,
            ];
        }

        $lat = (float) $latest->latitude;
        $lng = (float) $latest->longitude;
        $updatedAt = $latest->recorded_at?->toDateTimeString();

        $activeFences = $animal->fences()->where('is_active', true)->get();

        if ($activeFences->isEmpty()) {
            return [
                'status' => 'unassigned',
                'label' => 'Belum ada fence aktif',
                'lat' => $lat,
                'lng' => $lng,
                'updated_at' => $updatedAt,
            ];
        }

        foreach ($activeFences as $fence) {
            $inside = GeofenceService::isPointInPolygon($lat, $lng, $fence->polygon_coordinates);
            $violates = $fence->fence_type === 'inclusion' ? ! $inside : $inside;

            if ($violates) {
                return [
                    'status' => 'outside',
                    'label' => $fence->fence_type === 'inclusion'
                        ? 'Keluar dari fence'
                        : 'Di dalam zona larangan',
                    'lat' => $lat,
                    'lng' => $lng,
                    'updated_at' => $updatedAt,
                ];
            }
        }

        return [
            'status' => 'inside',
            'label' => 'Di dalam fence',
            'lat' => $lat,
            'lng' => $lng,
            'updated_at' => $updatedAt,
        ];
    }

    /**
     * Jumlah hewan (dari koleksi yang sudah ter-scope) yang saat ini
     * berada di luar / melanggar fence aktif.
     *
     * @param  Collection<int, Animal>  $animals
     */
    public static function countOutside(Collection $animals): int
    {
        return $animals
            ->filter(fn (Animal $animal) => self::resolve($animal)['status'] === 'outside')
            ->count();
    }
}
