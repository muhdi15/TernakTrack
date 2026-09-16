<?php

namespace App\Policies;

use App\Models\Fence;
use App\Models\User;

class FencePolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Fence $fence): bool
    {
        return $fence->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Fence $fence): bool
    {
        return $fence->user_id === $user->id;
    }

    public function delete(User $user, Fence $fence): bool
    {
        return $fence->user_id === $user->id;
    }

    public function restore(User $user, Fence $fence): bool
    {
        return $fence->user_id === $user->id;
    }

    public function forceDelete(User $user, Fence $fence): bool
    {
        return $fence->user_id === $user->id;
    }
}
