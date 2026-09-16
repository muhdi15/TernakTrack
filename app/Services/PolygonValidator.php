<?php

namespace App\Services;

use InvalidArgumentException;

/**
 * PolygonValidator
 *
 * Validasi polygon fence sebelum disimpan di database (SESI 2 menyerahkan
 * pembuatan fence, SESI 3 ini memvalidasi geometrinya):
 *  - Minimal 3 titik (persyaratan polygon tertutup).
 *  - Tidak menyilang diri sendiri (self-intersection) — seperti angka 8.
 *  - Tidak ada koordinat yang duplikat / terlalu berdekatan.
 *
 * Semua pesan error memakai substring yang konsisten:
 *  - "minimal 3 titik"
 *  - "menyilang"
 *  - "titik terlalu dekat"
 */
class PolygonValidator
{
    /**
     * Jarak minimum antar titik berurutan dalam meter.
     * Di bawah nilai ini dianggap duplikat/tidak berguna.
     */
    public const MIN_DISTANCE_METERS = 5.0;

    /**
     * Cek apakah polygon punya minimal 3 titik.
     *
     * @param  array<mixed>  $points
     */
    public static function hasMinimumPoints(array $points): bool
    {
        return count($points) >= 3;
    }

    /**
     * Deteksi polygon yang menyilang diri sendiri.
     *
     * Dua sisi (edge) dianggap menyilang jika segmen-segmennya
     * berpotongan pada titik yang bukan titik ujung bersama.
     *
     * @param  array<int, array<mixed>|float>  $points
     */
    public static function isSelfIntersecting(array $points): bool
    {
        $polygon = GeofenceService::normalizePolygon($points);
        $count = count($polygon);

        if ($count < 4) {
            return false;
        }

        // Cek setiap pasangan sisi yang TIDAK bersebelahan.
        // Sisi bersebelahan selalu berbagi vertex ujung → bukan perpotongan.
        for ($i = 0; $i < $count; $i++) {
            $iNext = ($i + 1) % $count;

            for ($j = $i + 1; $j < $count; $j++) {
                $jNext = ($j + 1) % $count;

                // Lewati sisi yang berbagi vertex.
                if ($jNext === $i || $jNext === $iNext || $iNext === $j) {
                    continue;
                }

                $pr = $polygon[$i];
                $pq = $polygon[$iNext];
                $pr2 = $polygon[$j];
                $pq2 = $polygon[$jNext];

                if (self::segmentsIntersect(
                    $pr['lat'], $pr['lng'],
                    $pq['lat'], $pq['lng'],
                    $pr2['lat'], $pr2['lng'],
                    $pq2['lat'], $pq2['lng'],
                )) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Cek apakah ada dua titik yang terlalu berdekatan/duplikat.
     *
     * Membandingkan SEMUA pasangan titik (O(n²)); karena polygon fence
     * berukuran kecil, kompleksitas kuadrat tidak masalah. Jarak dihitung
     * dengan Haversine agar akurat untuk koordinat geografis.
     *
     * @param  array<int, array<mixed>|float>  $points
     */
    public static function hasDuplicatePoints(array $points): bool
    {
        $polygon = GeofenceService::normalizePolygon($points);
        $count = count($polygon);

        for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                $distance = GeofenceService::haversine(
                    $polygon[$i]['lat'], $polygon[$i]['lng'],
                    $polygon[$j]['lat'], $polygon[$j]['lng'],
                );

                if ($distance < self::MIN_DISTANCE_METERS) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Validasi lengkap polygon untuk disimpan pada fence.
     *
     * @param  array<int, array<mixed>|float>  $points
     * @return array<int, string> Daftar pesan error (kosong jika valid).
     */
    public static function validateForFence(array $points): array
    {
        $errors = [];

        try {
            if (! self::hasMinimumPoints($points)) {
                $errors[] = 'Polygon harus memiliki minimal 3 titik.';
            }

            if (self::hasDuplicatePoints($points)) {
                $errors[] = 'Terdapat titik terlalu dekat (duplikat) dalam polygon.';
            }

            if (self::isSelfIntersecting($points)) {
                $errors[] = 'Polygon menyilang diri sendiri (membentuk angka 8).';
            }
        } catch (InvalidArgumentException $e) {
            $errors[] = $e->getMessage();
        }

        return $errors;
    }

    /**
     * Uji perpotongan dua segmen (p1→q1) dan (p2→q2).
     *
     * Menggunakan orientasi (cross product) sebagaimana rumus pada spesifikasi:
     *   orientasi(p,q,r) = (q.lng-p.lng)*(r.lat-p.lat) - (q.lat-p.lat)*(r.lng-p.lng)
     *
     * Aturan:
     *  - Jika orientasi dari barisan titik-titik pada kedua segmen > 0 dan < 0,
     *    segmen dipastikan berpotongan.
     *  - Kasus kolinier (orientasi = 0) ditangani lewat on-segment check
     *    (dianggap berpotongan, yang menandai polygon menyilang).
     */
    private static function segmentsIntersect(
        float $pLat, float $pLng,
        float $qLat, float $qLng,
        float $rLat, float $rLng,
        float $sLat, float $sLng,
    ): bool {
        $o1 = self::orientation($pLat, $pLng, $qLat, $qLng, $rLat, $rLng);
        $o2 = self::orientation($pLat, $pLng, $qLat, $qLng, $sLat, $sLng);
        $o3 = self::orientation($rLat, $rLng, $sLat, $sLng, $pLat, $pLng);
        $o4 = self::orientation($rLat, $rLng, $sLat, $sLng, $qLat, $qLng);

        // Kasus umum: orientasi berlawanan tanda pada kedua sisi.
        if ($o1 !== $o2 && $o3 !== $o4) {
            return true;
        }

        // Kasus kolinier: salah satu ujung berada pada segmen lainnya.
        if ($o1 === 0 && self::onSegment($pLat, $pLng, $qLat, $qLng, $rLat, $rLng)) {
            return true;
        }

        if ($o2 === 0 && self::onSegment($pLat, $pLng, $qLat, $qLng, $sLat, $sLng)) {
            return true;
        }

        if ($o3 === 0 && self::onSegment($rLat, $rLng, $sLat, $sLng, $pLat, $pLng)) {
            return true;
        }

        if ($o4 === 0 && self::onSegment($rLat, $rLng, $sLat, $sLng, $qLat, $qLng)) {
            return true;
        }

        return false;
    }

    /**
     * Orientasi titik r terhadap garis p→q.
     *
     *   > 0 : r di KIRI garis (belok kiri / counter-clockwise)
     *   = 0 : r kolinier dengan garis
     *   < 0 : r di KANAN garis (belok kanan / clockwise)
     *
     * Rumus (sesuai spesifikasi):
     *   (q.lng - p.lng) * (r.lat - p.lat) - (q.lat - p.lat) * (r.lng - p.lng)
     */
    private static function orientation(float $pLat, float $pLng, float $qLat, float $qLng, float $rLat, float $rLng): int
    {
        $value = ($qLng - $pLng) * ($rLat - $pLat) - ($qLat - $pLat) * ($rLng - $pLng);

        if ($value > GeofenceService::EPS) {
            return 1;
        }

        if ($value < -GeofenceService::EPS) {
            return -1;
        }

        return 0;
    }

    /**
     * Cek bahwa titik q berada pada segmen p→r (dengan q kolinier).
     */
    private static function onSegment(float $pLat, float $pLng, float $qLat, float $qLng, float $rLat, float $rLng): bool
    {
        return $qLat <= max($pLat, $rLat)
            && $qLat >= min($pLat, $rLat)
            && $qLng <= max($pLng, $rLng)
            && $qLng >= min($pLng, $rLng);
    }
}
