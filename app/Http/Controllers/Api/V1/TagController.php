<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\TagResource;
use App\Models\Tag;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;

class TagController extends Controller
{
    public function __construct(
        private ProductService $products,
    ) {}

    public function products(string $slug): JsonResponse
    {
        $tag = Tag::query()->where('slug', $slug)->firstOrFail();
        $products = $this->products->paginateForTag($tag);

        return ApiResource::success([
            'tag' => (new TagResource($tag))->resolve(),
            'products' => [
                'data' => ProductResource::collection($products->items())->resolve(),
                'meta' => [
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                    'per_page' => $products->perPage(),
                    'total' => $products->total(),
                ],
            ],
        ]);
    }
}
