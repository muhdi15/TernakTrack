<?php

namespace App\Services;

use App\Enums\GeofenceEventType;
use App\Models\Animal;
use InvalidArgumentException;

/**
 * GeofenceService
 *
 * Inti dari logika virtual fence: menentukan posisi hewan terhadap
 * polygon fence memakai algoritma Ray Casting, lalu menghitung jarak
 * (Haversine) dan luas (Shoelace) yang dibutuhkan untuk membuat keputusan
 * keluar/masuk zona.
 */
class GeofenceService
{
    /**
     * Estimasi panjang 1 derajat lintang dalam meter (WGS-84).
     */
    public const METERS_PER_DEGREE_LAT = 111320.0;

    /**
     * Jari-jari bumi rata-rata dalam meter (untuk Haversine).
     */
    public const EARTH_RADIUS_METERS = 6371000.0;

    /**
     * Epsilon koordinat (derajat) untuk toleransi floating point.
     * ~1e-9 derajat ≈ 0.1 mm, cukup untuk uji "titik tepat di tepi".
     */
    public const EPS = 1e-9;

    /**
     * Normalisasi bentuk polygon agar selalu berbentuk array of
     * ['lat' => float, 'lng' => float].
     *
     * Mendukung dua format input:
     *  - ['lat' => x, 'lng' => y]  (format seeder / frontend)
     *  - [x, y]                     (format [lat, lng])
     *
     * @param  array<int, array<mixed>|float>  $points
     * @return array<int, array{lat: float, lng: float}>
     */
    public static function normalizePolygon(array $points): array
    {
        $normalized = [];

        foreach ($points as $index => $point) {
            // Format asosiatif: ['lat' => ..., 'lng' => ...]
            if (is_array($point) && array_key_exists('lat', $point) && array_key_exists('lng', $point)) {
                $normalized[] = [
                    'lat' => (float) $point['lat'],
                    'lng' => (float) $point['lng'],
                ];

                continue;
            }

            // Format indeks: [0 => lat, 1 => lng]
            if (is_array($point) && array_key_exists(0, $point) && array_key_exists(1, $point)) {
                $normalized[] = [
                    'lat' => (float) $point[0],
                    'lng' => (float) $point[1],
                ];

                continue;
            }

            throw new InvalidArgumentException(
                "Titik polygon ke-{$index} tidak valid. Gunakan ['lat'=>x,'lng'=>y] atau [lat, lng]."
            );
        }

        return $normalized;
    }

    /**
     * Uji apakah sebuah titik berada di dalam polygon (Ray Casting).
     *
     * Algoritma:
     *  1) Tembak sinar horizontal ke arah kanan (ke arah longitude +∞) dari titik.
     *  2) Hitung berapa kali sinar itu memotong sisi-sisi polygon.
     *  3) Lintasan = ganjil → titik di dalam; genap → di luar.
     *
     * Edge case yang ditangani:
     *  - Polygon < 3 titik adalah degenerate → return false (tidak berisi apa-apa).
     *  - Titik tepat DI TEPI polygon dianggap DI DALAM (return true).
     *    (Behavior ini didokumentasikan karena pembulatan floating-point;
     *     untuk geofencing, hewan yang "tepat" di pager dianggap aman.)
     *
     * @param  array<int, array<mixed>|float>  $polygon
     */
    public static function isPointInPolygon(float $lat, float $lng, array $polygon): bool
    {
        $points = self::normalizePolygon($polygon);
        $count = count($points);

        // Polygon yang valid minimal punya 3 titik segment.
        if ($count < 3) {
            return false;
        }

        // Mulai dari "di luar" (inside = false).
        $inside = false;

        // Variabel penunjuk ke titik sebelumnya (loop melingkar: sisi terakhir → pertama).
        $previous = $count - 1;

        for ($current = 0; $current < $count; $current++) {
            $yi = $points[$current]['lat'];
            $xi = $points[$current]['lng'];
            $yj = $points[$previous]['lat'];
            $xj = $points[$previous]['lng'];

            // Tepi: jika titik tepat berada di segmen (p_j → p_i), anggap di dalam.
            if (self::isPointOnSegment($lat, $lng, $yi, $xi, $yj, $xj)) {
                return true;
            }

            // Ray casting: sisi memotong garis horizontal y = lat jika
            // kedua ujung sisi berada pada sisi yang berlawanan dari garis tsb.
            // Perbandingan ">" (bukan ">=") menghindari double-count vertex
            // yang persis berada di ketinggian sinar.
            $crossesLatitude = ($yi > $lat) !== ($yj > $lat);

            if ($crossesLatitude) {
                // Titik potong sisi dengan garis horizontal di x (longitude):
                //   x_intersect = xj + (xi - xj) * (lat - yj) / (yi - yj)
                // Titik berada di KIRI titik potong → sinar memotong sisi ini.
                $intersectsAt = $xj + ($xi - $xj) * ($lat - $yj) / ($yi - $yj);

                if ($lng < $intersectsAt) {
                    $inside = ! $inside;
                }
            }

            // Maju satu langkah dalam loop melingkar.
            $previous = $current;
        }

        return $inside;
    }

    /**
     * Cek apakah titik (lat, lng) berada di segmen (y1,x1)-(y2,x2).
     *
     * Memakai cross product = 0 (kolinier) dan bounding box (dot product)
     * agar konsisten di logika Ray Casting. Epsilon mengakomodasi
     * floating point. Dihilangkan dari hitungan arah sinar karena kasus ini
     * dikembalikan langsung sebagai "di dalam".
     */
    private static function isPointOnSegment(float $lat, float $lng, float $y1, float $x1, float $y2, float $x2): bool
    {
        // Cross product: (p - a) x (b - a) — harus ~0 agar titik kolinier dengan segmen.
        $cross = ($lat - $y1) * ($x2 - $x1) - ($lng - $x1) * ($y2 - $y1);

        if (abs($cross) > self::EPS) {
            return false;
        }

        // Dot product: proyeksi titik ke arah (b - a) harus berada dalam rentang
        // 0..len² agar titik benar-benar berada di interval segmen, bukan di
        // perpanjangan garisnya.
        $dot = ($lat - $y1) * ($y2 - $y1) + ($lng - $x1) * ($x2 - $x1);

        if ($dot < 0 - self::EPS) {
            return false;
        }

        $lengthSquared = ($y2 - $y1) * ($y2 - $y1) + ($x2 - $x1) * ($x2 - $x1);

        return $dot <= $lengthSquared + self::EPS;
    }

    /**
     * Jarak minimum titik ke polygon (meter).
     *
     * Algoritma:
     *  1) Jika titik DI DALAM polygon → return 0.
     *  2) Jika di luar, cari titik terdekat pada setiap sisi (segment-to-point),
     *     lalu hitung jarak geografisnya dengan rumus Haversine.
     *  3) Kembalikan nilai terkecil dari semua sisi.
     *
     * Untuk mencari titik terdekat pada segmen, koordinat diproyeksikan ke
     * bidang planar "equirectangular" (skala longitude dengan cos(lat) rata-rata)
     * agar interpolasi titik-titiknya masuk akal, sedangkan hasil jaraknya tetap
     * dihitung memakai Haversine sesuai spesifikasi.
     *
     * Degenerate polygon (< 3 titik) → jarak ke vertex terdekat (fallback aman).
     *
     * @param  array<int, array<mixed>|float>  $polygon
     */
    public static function distanceToPolygon(float $lat, float $lng, array $polygon): float
    {
        $points = self::normalizePolygon($polygon);
        $count = count($points);

        // Titik di dalam polygon → jarak ke fence = 0.
        if (self::isPointInPolygon($lat, $lng, $points)) {
            return 0.0;
        }

        // Fallback degenerate polygon: jarak ke vertex terdekat.
        if ($count < 3) {
            return self::minDistanceToVertices($lat, $lng, $points);
        }

        // Proyeksi planar: gunakan cos(lat rata-rata) untuk mendekati rasio
        // meter-per-derajat antara sumbu X (lng) dan sumbu Y (lat).
        $meanLat = 0.0;
        foreach ($points as $point) {
            $meanLat += $point['lat'];
        }
        $meanLat /= $count;
        $lngScale = cos(deg2rad($meanLat));

        // Matriks proyeksi (x = lng ter-skalakan, y = lat).
        $px = $lng * $lngScale;
        $py = $lat;

        $minDistance = PHP_FLOAT_MAX;
        $previous = $count - 1;

        for ($current = 0; $current < $count; $current++) {
            $x1 = $points[$previous]['lng'] * $lngScale;
            $y1 = $points[$previous]['lat'];
            $x2 = $points[$current]['lng'] * $lngScale;
            $y2 = $points[$current]['lat'];

            // Vektor sisi (a = titik awal, b = vektor arah).
            $bx = $x2 - $x1;
            $by = $y2 - $y1;
            $lengthSquared = $bx * $bx + $by * $by;

            // Parameter t (0..1) untuk titik proyeksi titik P ke garis sisi.
            $t = $lengthSquared > 0.0
                ? (($px - $x1) * $bx + ($py - $y1) * $by) / $lengthSquared
                : 0.0;

            // Clamp agar proyeksi tetap berada DI DALAM segmen (bukan garisnya).
            $t = max(0.0, min(1.0, $t));

            // Titik terdekat pada segmen (interpolasi linier di bidang planar).
            $closestLng = ($x1 + $t * $bx) / $lngScale;
            $closestLat = $y1 + $t * $by;

            // Jarak geografis sesungguhnya antara titik dan titik terdekat tsb.
            $distance = self::haversine($lat, $lng, $closestLat, $closestLng);

            if ($distance < $minDistance) {
                $minDistance = $distance;
            }

            $previous = $current;
        }

        return round($minDistance, 2);
    }

    /**
     * Jarak Haversine antara dua koordinat (meter).
     *
     * @see https://en.wikipedia.org/wiki/Haversine_formula
     */
    public static function haversine(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $deltaLat = deg2rad($lat2 - $lat1);
        $deltaLng = deg2rad($lng2 - $lng1);

        // Haversine: a = sin²(Δφ/2) + cos φ1 · cos φ2 · sin²(Δλ/2)
        $a = sin($deltaLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($deltaLng / 2) ** 2;

        // Jarak = 2R · atan2(√a, √(1−a))
        return 2 * self::EARTH_RADIUS_METERS * atan2(sqrt($a), sqrt(1 - $a));
    }

    /**
     * Jarak minimum titik ke salah satu vertex polygon (fallback degenerate).
     *
     * @param  array<int, array{lat: float, lng: float}>  $points
     */
    private static function minDistanceToVertices(float $lat, float $lng, array $points): float
    {
        $min = PHP_FLOAT_MAX;

        foreach ($points as $point) {
            $min = min($min, self::haversine($lat, $lng, $point['lat'], $point['lng']));
        }

        return $min === PHP_FLOAT_MAX ? 0.0 : round($min, 2);
    }

    /**
     * Luas polygon dalam hektar (rumus Shoelace).
     *
     * Algoritma:
     *  1) Konversi koordinat derajat ke meter pada bidang planar:
     *       y (utara-selatan) = lat · 111320
     *       x (timur-barat)   = lng · 111320 · cos(meanLat)
     *  2) Jumlahkan cross product berurutan (Shoelace / surveyor's formula).
     *  3) |Σ| / 2 → luas m², lalu bagi 10.000 → hektar.
     *
     * @param  array<int, array<mixed>|float>  $polygon
     */
    public static function calculateAreaHectares(array $polygon): float
    {
        $points = self::normalizePolygon($polygon);
        $count = count($points);

        if ($count < 3) {
            return 0.0;
        }

        // Cosinus lintang rata-rata untuk mengonversi derajat longitude → meter.
        $meanLat = 0.0;
        foreach ($points as $point) {
            $meanLat += $point['lat'];
        }
        $meanLat /= $count;
        $lngScale = self::METERS_PER_DEGREE_LAT * cos(deg2rad($meanLat));

        $shoelace = 0.0;

        // Loop melingkar: sisi terakhir menghubungkan titik terakhir → pertama.
        for ($i = 0; $i < $count; $i++) {
            $next = ($i + 1) % $count;

            $x1 = $points[$i]['lng'] * $lngScale;
            $y1 = $points[$i]['lat'] * self::METERS_PER_DEGREE_LAT;

            $x2 = $points[$next]['lng'] * $lngScale;
            $y2 = $points[$next]['lat'] * self::METERS_PER_DEGREE_LAT;

            // Akumulasi istilah Shoelace: Σ (xᵢ·yᵢ₊₁ − xᵢ₊₁·yᵢ).
            $shoelace += $x1 * $y2 - $x2 * $y1;
        }

        // Luas = |Σ| / 2 (m²), konversi: 1 hektar = 10.000 m².
        $areaSquareMeters = abs($shoelace) / 2.0;

        return round($areaSquareMeters / 10000.0, 4);
    }

    /**
     * Evaluasi posisi hewan terhadap SEMUA fence yang di-assign.
     *
     * Aturan fence:
     *  - inclusion : hewan HARUS di dalam polygon. Di luar        → event "exit".
     *  - exclusion : hewan TIDAK BOLEH di dalam polygon. Di dalam → event "enter".
     *
     * Anti-spam: dibandingkan dengan event terakhir pada fence yang sama.
     * Jika status masih sama (mis. terakhir "exit" dan pengecekan baru juga
     * menghasilkan "outside"), tidak dibuat event baru sampai terjadi transisi.
     *
     * Hanya fence aktif (is_active = true) yang dievaluasi.
     *
     * @return array<int, array{
     *     fence_id: int,
     *     fence_type: string,
     *     event_type: string,
     *     latitude: float,
     *     longitude: float,
     *     distance_from_fence_meters: float,
     *     is_alert: bool,
     * }>
     */
    public static function checkAnimalPosition(Animal $animal, float $lat, float $lng): array
    {
        $events = [];

        // Fence yang di-assign & masih aktif.
        $fences = $animal->fences()
            ->where('is_active', true)
            ->get();

        if ($fences->isEmpty()) {
            return $events;
        }

        foreach ($fences as $fence) {
            $polygon = $fence->polygon_coordinates;

            if (! is_array($polygon)) {
                continue;
            }

            // Status spasial sekarang terhadap fence ini.
            $inside = self::isPointInPolygon($lat, $lng, $polygon);

            // Mapping ke type event sesuai jenis fence.
            if ($fence->fence_type === 'inclusion') {
                $newEventType = $inside ? 'inside' : 'exit';
            } else {
                $newEventType = $inside ? 'enter' : 'outside';
            }

            // Jarak ke tepi fence (0 jika di dalam).
            $distance = (float) $inside
                ? 0.0
                : self::distanceToPolygon($lat, $lng, $polygon);

            // Event terakhir untuk pasangan (animal, fence) — basis anti-spam.
            $lastEvent = $fence->geofenceEvents()
                ->where('animal_id', $animal->id)
                ->latest('created_at')
                ->first();

            // Jika status tidak berubah → lewati (hindari spam).
            if ($lastEvent && self::sameStatusFamily($lastEvent->event_type, $newEventType)) {
                continue;
            }

            // "exit"/"enter" memicu notifikasi, di-gate oleh flag fence.
            $isAlert = ($newEventType === 'exit' && $fence->alert_on_exit)
                || ($newEventType === 'enter' && $fence->alert_on_enter);

            $events[] = [
                'fence_id' => $fence->id,
                'fence_type' => $fence->fence_type,
                'event_type' => $newEventType,
                'latitude' => round($lat, 7),
                'longitude' => round($lng, 7),
                'distance_from_fence_meters' => round($distance, 2),
                'is_alert' => $isAlert,
            ];
        }

        return $events;
    }

    /**
     * Kelompokkan event ke "keluarga status": di dalam vs di luar fence.
     *
     *  - Dalam  : inside, enter
     *  - Luar   : exit, outside
     *
     * Dua event yang satu keluarga dianggap status yang sama sehingga
     * tidak menimbulkan event baru (anti spam).
     */
    private static function sameStatusFamily(string $first, string $second): bool
    {
        $insideFamily = [GeofenceEventType::Inside->value, GeofenceEventType::Enter->value];
        $outsideFamily = [GeofenceEventType::Exit->value, GeofenceEventType::Outside->value];

        $firstInside = in_array($first, $insideFamily, true);
        $secondInside = in_array($second, $insideFamily, true);

        return $firstInside === $secondInside;
    }
}
