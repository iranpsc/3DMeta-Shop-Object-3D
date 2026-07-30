<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class CartService
{
    private const SESSION_KEY = 'cart';

    /**
     * @return array<int, array{product_id: int, quantity: int}>
     */
    public function items(Request $request): array
    {
        return array_values($request->session()->get(self::SESSION_KEY, []));
    }

    /**
     * @return array{
     *     items: array<int, array{product_id: int, quantity: int}>,
     *     products: Collection<int, Product>,
     *     count: int,
     *     total_price: float
     * }
     */
    public function snapshot(Request $request): array
    {
        $items = $this->items($request);
        $products = $this->loadProducts($items);

        return [
            'items' => $items,
            'products' => $products,
            'count' => count($items),
            'total_price' => $this->calculateTotal($items, $products),
        ];
    }

    /**
     * @return array{snapshot: array<string, mixed>, message: string}
     */
    public function add(Request $request, Product $product, int $quantity = 1): array
    {
        $items = $this->items($request);

        if (in_array($product->id, array_column($items, 'product_id'), true)) {
            return [
                'snapshot' => $this->snapshot($request),
                'message' => $product->name.' قبلا به سبد خرید اضافه شده است.',
            ];
        }

        if ($quantity < 1) {
            $quantity = 1;
        }

        $items[] = [
            'product_id' => $product->id,
            'quantity' => $quantity,
        ];

        $request->session()->put(self::SESSION_KEY, $items);

        return [
            'snapshot' => $this->snapshot($request),
            'message' => $product->name.' به سبد خرید اضافه شد.',
        ];
    }

    /**
     * @return array{snapshot: array<string, mixed>, message: string|null}
     */
    public function update(Request $request, Product $product, int $quantity): array
    {
        $items = array_map(function (array $item) use ($product, $quantity) {
            if ($item['product_id'] === $product->id) {
                $item['quantity'] = $quantity;
            }

            return $item;
        }, $this->items($request));

        $request->session()->put(self::SESSION_KEY, $items);

        return [
            'snapshot' => $this->snapshot($request),
            'message' => null,
        ];
    }

    /**
     * @return array{snapshot: array<string, mixed>, message: string}
     */
    public function remove(Request $request, Product $product): array
    {
        $items = array_values(array_filter(
            $this->items($request),
            fn (array $item) => $item['product_id'] !== $product->id
        ));

        $request->session()->put(self::SESSION_KEY, $items);

        return [
            'snapshot' => $this->snapshot($request),
            'message' => 'محصول از سبد خرید حذف شد.',
        ];
    }

    public function clear(Request $request): void
    {
        $request->session()->forget(self::SESSION_KEY);
    }

    /**
     * @param  array<int, array{product_id: int, quantity: int}>  $items
     */
    private function loadProducts(array $items): Collection
    {
        $productIds = array_column($items, 'product_id');

        if ($productIds === []) {
            return collect();
        }

        return Product::query()
            ->whereIn('id', $productIds)
            ->with('latestImage')
            ->get();
    }

    /**
     * @param  array<int, array{product_id: int, quantity: int}>  $items
     * @param  Collection<int, Product>  $products
     */
    private function calculateTotal(array $items, Collection $products): float
    {
        $total = 0.0;

        foreach ($products as $product) {
            $cartItem = collect($items)->firstWhere('product_id', $product->id);
            if ($cartItem) {
                $total += $product->final_price * $cartItem['quantity'];
            }
        }

        return $total;
    }
}
