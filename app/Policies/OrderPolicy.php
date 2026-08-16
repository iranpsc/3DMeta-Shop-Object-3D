<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function view(User $user, Order $order)
    {
        return $order->user->is($user) && $order->isPaid();
    }

    public function pay(User $user, Order $order)
    {
        return $order->user->is($user) && ! $order->isPaid();
    }
}
