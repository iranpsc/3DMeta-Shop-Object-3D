<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreReviewReplyRequest;
use App\Http\Requests\Api\StoreReviewRequest;
use App\Http\Resources\ApiResource;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\ReviewResource;
use App\Http\Resources\TagResource;
use App\Models\Product;
use App\Models\Review;
use App\Models\Tag;
use App\Services\CategoryService;
use App\Services\ContactService;
use App\Services\ProductService;
use App\Services\ReviewService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(
        private ProductService $products,
        private CategoryService $categories,
        private ReviewService $reviews,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = [
            'sort' => $request->query('sort', 'newest'),
            'take' => $request->query('take'),
            'search' => $request->query('search'),
            'category' => $request->query('category'),
            'tag' => $request->query('tag'),
            'tags' => $request->query('tags'),
            'price_min' => $request->query('price_min'),
            'price_max' => $request->query('price_max'),
            'per_page' => $request->query('per_page', 15),
        ];

        $result = $this->products->list($filters);

        if ($result instanceof LengthAwarePaginator) {
            return $this->paginatedProducts($result);
        }

        return ApiResource::success(
            ProductResource::collection($result)->resolve()
        );
    }

    public function show(string $sku): JsonResponse
    {
        $product = $this->products->findBySku($sku);

        return ApiResource::success(
            (new ProductResource($product))->resolve()
        );
    }

    public function reviews(string $sku): JsonResponse
    {
        $product = Product::query()
            ->where('sku', $sku)
            ->published()
            ->createdByAdmin()
            ->firstOrFail();

        $payload = $this->reviews->forProduct($product);

        return ApiResource::success([
            'reviews' => ReviewResource::collection($payload['reviews'])->resolve(),
            'rating_breakdown' => $payload['rating_breakdown'],
            'users_count' => $payload['users_count'],
        ]);
    }

    public function storeReview(StoreReviewRequest $request, string $sku): JsonResponse
    {
        $product = Product::query()
            ->where('sku', $sku)
            ->published()
            ->createdByAdmin()
            ->firstOrFail();

        $review = $this->reviews->store($request->user(), $product, $request->validated());

        return ApiResource::success(
            (new ReviewResource($review))->resolve(),
            'نظر شما با موفقیت ثبت شد و پس از تایید نمایش داده خواهد شد.',
            201
        );
    }

    public function storeReviewReply(StoreReviewReplyRequest $request, Review $review): JsonResponse
    {
        $reply = $this->reviews->storeReply($request->user(), $review, $request->validated());

        return ApiResource::success(
            [
                'id' => $reply->id,
                'comment' => $reply->comment,
                'review_id' => $reply->review_id,
            ],
            'پاسخ شما با موفقیت ثبت شد و پس از تایید نمایش داده خواهد شد.',
            201
        );
    }

    public function storeFilters(): JsonResponse
    {
        return ApiResource::success([
            'categories' => CategoryResource::collection($this->categories->forStoreFilter())->resolve(),
            'tags' => TagResource::collection(Tag::paginate(10)->getCollection())->resolve(),
        ]);
    }

    private function paginatedProducts(LengthAwarePaginator $paginator): JsonResponse
    {
        return response()->json([
            'data' => ProductResource::collection($paginator->items())->resolve(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'links' => [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ],
        ]);
    }
}
