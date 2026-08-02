<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\AddToCartRequest;
use App\Http\Requests\Api\UpdateCartRequest;
use App\Http\Resources\ApiResource;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(
        private CartService $cart,
    ) {}

    public function show(Request $request): JsonResponse
    {
        return ApiResource::success($this->formatSnapshot($this->cart->snapshot($request)));
    }

    public function store(AddToCartRequest $request, Product $product): JsonResponse
    {
        $result = $this->cart->add(
            $request,
            $product,
            (int) $request->integer('quantity', 1)
        );

        return ApiResource::success(
            $this->formatSnapshot($result['snapshot']),
            $result['message']
        );
    }

    public function update(UpdateCartRequest $request, Product $product): JsonResponse
    {
        $result = $this->cart->update(
            $request,
            $product,
            (int) $request->integer('quantity')
        );

        return ApiResource::success($this->formatSnapshot($result['snapshot']), $result['message']);
    }

    public function destroy(Request $request, Product $product): JsonResponse
    {
        $result = $this->cart->remove($request, $product);

        return ApiResource::success(
            $this->formatSnapshot($result['snapshot']),
            $result['message']
        );
    }

    /**
     * @param  array{
     *     items: array<int, array{product_id: int, quantity: int}>,
     *     products: \Illuminate\Support\Collection<int, Product>,
     *     count: int,
     *     total_price: float
     * }  $snapshot
     * @return array<string, mixed>
     */
    private function formatSnapshot(array $snapshot): array
    {
        return [
            'items' => $snapshot['items'],
            'products' => ProductResource::collection($snapshot['products'])->resolve(),
            'count' => $snapshot['count'],
            'total_price' => $snapshot['total_price'],
        ];
    }
}
