<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;

class AdminDashboardService
{
    /**
     * @return array{
     *     products_count: int,
     *     orders_total: int,
     *     orders_paid: int,
     *     total_sales: int,
     *     users_count: int,
     * }
     */
    public function summary(): array
    {
        return [
            'products_count' => Product::count(),
            'orders_total' => Order::count(),
            'orders_paid' => Order::where('status', 0)->count(),
            'total_sales' => (int) Order::where('status', 0)->sum('amount'),
            'users_count' => User::count(),
        ];
    }
}
