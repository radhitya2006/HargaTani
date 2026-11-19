<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Product;
use App\Models\User;

final class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active && $user->role === Role::Petani;
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function view(User $user, Product $product): bool
    {
        return $this->viewAny($user) && $product->farmer_id === $user->id;
    }

    public function update(User $user, Product $product): bool
    {
        return $this->view($user, $product);
    }
}
