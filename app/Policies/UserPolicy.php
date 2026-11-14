<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active && $user->role === Role::Admin;
    }

    public function view(User $user, User $target): bool
    {
        return $user->is_active && ($user->is($target) || $user->role === Role::Admin);
    }

    public function update(User $user, User $target): bool
    {
        return $user->is_active && $user->is($target);
    }
}
