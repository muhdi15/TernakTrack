<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\Fence;

class FenceObserver
{
    public function created(Fence $fence): void
    {
        AuditLog::record($fence, 'created');
    }

    public function updated(Fence $fence): void
    {
        AuditLog::record($fence, 'updated', [
            'old' => $fence->getOriginal(),
            'new' => $fence->getChanges(),
        ]);
    }

    public function deleted(Fence $fence): void
    {
        AuditLog::record($fence, 'deleted');
    }

    public function restored(Fence $fence): void
    {
        AuditLog::record($fence, 'restored');
    }
}
