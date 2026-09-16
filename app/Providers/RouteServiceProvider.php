<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $deviceId = function (Request $request): string {
            // Setelah AuthenticateDevice berjalan, atribut "device" tersedia
            // sehingga rate limit dikunci per device (bukan per IP).
            $device = $request->attributes->get('device');

            return (string) ($device?->id ?? $request->ip());
        };

        // Laporan lokasi: paling rapat, ESP32 biasanya 10-60 detik sekali.
        RateLimiter::for('locations', fn (Request $request): Limit => Limit::perMinute(60)->by($deviceId($request)));

        // Heartbeat: lebih jarang.
        RateLimiter::for('heartbeat', fn (Request $request): Limit => Limit::perMinute(12)->by($deviceId($request)));

        // Endpoint lain: ambil config, fences, daftar command.
        RateLimiter::for('device', fn (Request $request): Limit => Limit::perMinute(30)->by($deviceId($request)));
    }
}
