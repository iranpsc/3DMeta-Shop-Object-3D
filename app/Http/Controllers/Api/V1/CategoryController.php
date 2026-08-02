<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResource;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductResource;
use App\Services\CategoryService;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function __construct(
        private CategoryService $categories,
        private ProductService $products,
    ) {}

    public function index(): JsonResponse
    {
        $paginator = $this->categories->paginate(12);

        return response()->json([
            'data' => CategoryResource::collection($paginator->items())->resolve(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function popular(Request $request): JsonResponse
    {
        $take = (int) $request->query('take', 12);

        return ApiResource::success(
            CategoryResource::collection($this->categories->popular($take))->resolve()
        );
    }

    public function topLevel(): JsonResponse
    {
        return ApiResource::success(
            CategoryResource::collection($this->categories->topLevel())->resolve()
        );
    }

    public function show(string $slug): JsonResponse
    {
        $category = $this->categories->findBySlugPath($slug);
        $payload = (new CategoryResource($category))->resolve();

        if ($category->children->isEmpty()) {
            $products = $this->products->paginateForCategory($category);
            $payload['products'] = [
                'data' => ProductResource::collection($products->items())->resolve(),
                'meta' => [
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                    'per_page' => $products->perPage(),
                    'total' => $products->total(),
                ],
            ];
        } else {
            $payload['products'] = [];
        }

        return ApiResource::success($payload);
    }
}
