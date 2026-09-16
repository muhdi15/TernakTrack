<?php

use App\Http\Controllers\Api\V1\LocationController;
use Illuminate\Support\Facades\Route;

// Health check API (tanpa autentikasi device).
Route::get('/', fn () => response()->json([
    'name' => 'TernakTrack API',
    'version' => 'v1',
    'time' => now()->toIso8601String(),
]));

Route::prefix('v1')->middleware('auth.device')->group(function (): void {
    Route::post('/locations', [LocationController::class, 'store'])->middleware('throttle:locations');
    Route::get('/devices/me/fences', [LocationController::class, 'fences'])->middleware('throttle:device');
    Route::get('/devices/me/config', [LocationController::class, 'config'])->middleware('throttle:device');
    Route::post('/devices/me/heartbeat', [LocationController::class, 'heartbeat'])->middleware('throttle:heartbeat');
    Route::get('/devices/me/commands', [LocationController::class, 'commands'])->middleware('throttle:device');
    Route::post('/devices/me/commands/{command}/ack', [LocationController::class, 'ack'])->middleware('throttle:device');
});
