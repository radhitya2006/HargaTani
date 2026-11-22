<?php

namespace App\Policies;

use App\Enums\OrderStatus;
use App\Enums\Role;
use App\Models\Order;
use App\Models\User;

final class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active && in_array($user->role, [Role::Pembeli, Role::Petani], true);
    }

    public function create(User $user): bool
    {
        return $user->is_active && $user->role === Role::Pembeli;
    }

    public function view(User $user, Order $order): bool
    {
        return $this->viewAny($user) && match ($user->role) {
            Role::Pembeli => $order->buyer_id === $user->id,
            Role::Petani => $order->farmer_id === $user->id,
            Role::Admin => false,
        };
    }

    public function transition(User $user, Order $order, OrderStatus $status): bool
    {
        return $this->view($user, $order) && match ($status) {
            OrderStatus::Accepted, OrderStatus::Rejected => $user->role === Role::Petani,
            OrderStatus::Completed => $user->role === Role::Pembeli,
            OrderStatus::Cancelled => true,
            OrderStatus::Pending => false,
        };
    }

    public function viewContact(User $user, Order $order): bool
    {
        return $this->view($user, $order) && $order->status === OrderStatus::Accepted;
    }
}
