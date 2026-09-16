<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\Device;

class DeviceObserver
{
    public function created(Device $device): void
    {
        AuditLog::record($device, 'created');
    }

    public function updated(Device $device): void
    {
        AuditLog::record($device, 'updated', [
            'old' => $device->getOriginal(),
            'new' => $device->getChanges(),
        ]);
    }

    public function deleted(Device $device): void
    {
        AuditLog::record($device, 'deleted');
    }

    public function restored(Device $device): void
    {
        AuditLog::record($device, 'restored');
    }
}
