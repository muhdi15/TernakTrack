<?php

namespace App\Enums;

/**
 * Jenis event geofence yang tersimpan di tabel geofence_events.
 */
enum GeofenceEventType: string
{
    case Exit = 'exit';
    case Enter = 'enter';
    case Inside = 'inside';
    case Outside = 'outside';

    /**
     * True jika event menggambarkan hewan BERADA DI LUAR fence.
     */
    public function isOutside(): bool
    {
        return in_array($this, [self::Exit, self::Outside], true);
    }
}
