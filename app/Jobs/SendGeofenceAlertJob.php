<?php

namespace App\Jobs;

use App\Enums\GeofenceEventType;
use App\Models\Alert;
use App\Models\GeofenceEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * SendGeofenceAlertJob
 *
 * Membuat record Alert saat event geofence membutuhkan notifikasi
 * (exit/enter). Baru sebatas simpan di DB agar muncul di bell dashboard —
 * pengiriman via kanal (email/telegram/whatsapp) diisi di SESI 6.
 */
class SendGeofenceAlertJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public GeofenceEvent $event) {}

    public function handle(): void
    {
        $animal = $this->event->animal;
        $fence = $this->event->fence;

        if (! $animal || ! $fence) {
            return;
        }

        $isExit = $this->event->event_type === GeofenceEventType::Exit->value;

        $animalLabel = "{$animal->name} ({$animal->tag_number})";

        Alert::create([
            'user_id' => $animal->user_id,
            'animal_id' => $animal->id,
            'device_id' => $this->event->device_id,
            'geofence_event_id' => $this->event->id,
            'type' => $isExit ? 'fence_exit' : 'fence_enter',
            'severity' => $isExit ? 'critical' : 'warning',
            'title' => $isExit
                ? "KELUAR ZONA: {$animal->name} meninggalkan {$fence->name}"
                : "Masuk Zona: {$animal->name} memasuki {$fence->name}",
            'message' => $isExit
                ? "{$animalLabel} terdeteksi di luar fence {$fence->name} sejauh {$this->event->distance_from_fence_meters} m."
                : "{$animalLabel} terdeteksi masuk ke area fence {$fence->name}.",
            'channels_sent' => [],
            'is_read' => false,
            'read_at' => null,
        ]);
    }
}
