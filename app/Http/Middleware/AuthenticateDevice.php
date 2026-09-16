<?php

namespace App\Http\Middleware;

use App\Models\Device;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * AuthenticateDevice
 *
 * Autentikasi ESP32 lewat token API (Bearer). Device diambil dari kolom
 * devices.api_token yang unik, lalu disimpan ke request attribute "device"
 * agar bisa dipakai middleware rate limiter dan controller di belakangnya.
 */
class AuthenticateDevice
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $header = $request->header('Authorization', '');

        // Format wajib: "Bearer <token>"
        if (! preg_match('/^Bearer\s+(.+)$/i', $header, $matches) || trim($matches[1]) === '') {
            return response()->json([
                'status' => 'error',
                'message' => 'Missing bearer token.',
            ], 401);
        }

        $device = Device::where('api_token', trim($matches[1]))->first();

        // Token tidak dikenal → 401 (tanpa membocorkan detail).
        if (! $device) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $request->attributes->set('device', $device);

        return $next($request);
    }
}
