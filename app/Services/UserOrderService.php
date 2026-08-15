<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class UserOrderService
{
    public function paginateForUser(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return Order::query()
            ->where('user_id', $user->id)
            ->with('products')
            ->withSum('products as total_price', 'price')
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /**
     * @return array<string, mixed>
     */
    public function showForUser(User $user, Order $order): array
    {
        abort_unless($order->user_id === $user->id, 403);

        $order->load([
            'products.files',
            'products.users' => fn ($query) => $query->where('users.id', $user->id),
        ]);

        return [
            'order' => $order,
            'products' => $order->products->map(function ($product) {
                $pivot = $product->users->first()?->pivot;

                return [
                    'id' => $product->id,
                    'sku' => $product->sku,
                    'name' => $product->name,
                    'quantity' => $pivot?->quantity ?? 0,
                    'download_count' => $pivot?->download_count ?? 0,
                    'downloaded_at' => $pivot?->downloaded_at?->toIso8601String(),
                    'files' => $product->files->map(fn ($file) => [
                        'id' => $file->id,
                        'name' => $file->name,
                        'url' => $file->url,
                    ])->values(),
                ];
            })->values(),
        ];
    }
}
