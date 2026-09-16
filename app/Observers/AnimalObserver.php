<?php

namespace App\Observers;

use App\Models\Animal;
use App\Models\AuditLog;

class AnimalObserver
{
    public function created(Animal $animal): void
    {
        AuditLog::record($animal, 'created');
    }

    public function updated(Animal $animal): void
    {
        AuditLog::record($animal, 'updated', [
            'old' => $animal->getOriginal(),
            'new' => $animal->getChanges(),
        ]);
    }

    public function deleted(Animal $animal): void
    {
        AuditLog::record($animal, 'deleted');
    }

    public function restored(Animal $animal): void
    {
        AuditLog::record($animal, 'restored');
    }
}
