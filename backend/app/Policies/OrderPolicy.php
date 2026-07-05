<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    /** The buyer who placed the order may view/act on it. */
    public function view(User $user, Order $order): bool
    {
        return $order->buyer_id === $user->id;
    }

    public function update(User $user, Order $order): bool
    {
        return $order->buyer_id === $user->id;
    }

    /** The seller who owns the store the order belongs to. */
    public function fulfil(User $user, Order $order): bool
    {
        return $order->store && $order->store->user_id === $user->id;
    }
}
