<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Ticket;
use App\Models\User;

class UserDashboardService
{
    /**
     * @return array<string, mixed>
     */
    public function summary(User $user): array
    {
        $ordersQuery = Order::query()->where('user_id', $user->id);

        $recentOrders = (clone $ordersQuery)
            ->with('products')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(fn (Order $order) => [
                'id' => $order->id,
                'tracking_id' => $order->tracking_id,
                'amount' => $order->amount,
                'status' => $order->status,
                'is_paid' => $order->isPaid(),
                'status_label' => $order->isPaid() ? 'پرداخت شده' : 'پرداخت نشده',
                'product_names' => $order->products->pluck('name')->values(),
                'created_at' => $order->created_at?->toIso8601String(),
            ])
            ->values();

        return [
            'stats' => [
                'orders_total' => (clone $ordersQuery)->count(),
                'orders_paid' => (clone $ordersQuery)->where('status', 0)->count(),
                'orders_unpaid' => (clone $ordersQuery)->where('status', '!=', 0)->count(),
                'products_owned' => $user->products()->count(),
                'tickets_open' => Ticket::query()
                    ->where('user_id', $user->id)
                    ->where('status', 'open')
                    ->count(),
            ],
            'recent_orders' => $recentOrders,
        ];
    }
}
