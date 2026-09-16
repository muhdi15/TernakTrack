<?php

namespace App\Policies;

use App\Models\Animal;
use App\Models\HealthRecord;
use App\Models\User;

class HealthRecordPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, HealthRecord $record): bool
    {
        return $this->owns($user, $record->animal);
    }

    public function create(User $user, Animal $animal): bool
    {
        return $animal->user_id === $user->id;
    }

    public function update(User $user, HealthRecord $record): bool
    {
        return $this->owns($user, $record->animal);
    }

    public function delete(User $user, HealthRecord $record): bool
    {
        return $this->owns($user, $record->animal);
    }

    public function restore(User $user, HealthRecord $record): bool
    {
        return $this->owns($user, $record->animal);
    }

    public function forceDelete(User $user, HealthRecord $record): bool
    {
        return $this->owns($user, $record->animal);
    }

    private function owns(User $user, ?Animal $animal): bool
    {
        return $animal !== null && $animal->user_id === $user->id;
    }
}
