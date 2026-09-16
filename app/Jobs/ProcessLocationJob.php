<?php

namespace App\Jobs;

use App\Models\LocationLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * ProcessLocationJob
 *
 * Hook proses berat setelah titik lokasi tersimpan (placeholder SESI 6:
 * analisis pergerakan, prediksi jarak ke fence, dsb). Dikirim dari
 * LocationController dan dijalankan async via queue database.
 */
class ProcessLocationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public LocationLog $locationLog) {}

    public function handle(): void
    {
        Log::debug('ProcessLocationJob diproses.', [
            'log_id' => $this->locationLog->id,
            'device_id' => $this->locationLog->device_id,
            'recorded_at' => $this->locationLog->recorded_at?->toIso8601String(),
        ]);
    }
}
