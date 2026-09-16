<?php

namespace App\Policies;

use App\Models\Animal;
use App\Models\User;

class AnimalPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Animal $animal): bool
    {
        return $animal->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Animal $animal): bool
    {
        return $animal->user_id === $user->id;
    }

    public function delete(User $user, Animal $animal): bool
    {
        return $animal->user_id === $user->id;
    }

    public function restore(User $user, Animal $animal): bool
    {
        return $animal->user_id === $user->id;
    }

    public function forceDelete(User $user, Animal $animal): bool
    {
        return $animal->user_id === $user->id;
    }
}
