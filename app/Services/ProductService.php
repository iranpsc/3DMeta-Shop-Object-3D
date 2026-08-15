<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use App\Models\Tag;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

class ProductService
{
    /**
     * List published admin products for home (take) or store (paginate).
     *
     * @param  array{
     *     sort?: string|null,
     *     take?: int|null,
     *     search?: string|null,
     *     category?: string|null,
     *     tag?: string|null,
     *     tags?: array<int, string>|null,
     *     price_min?: int|null,
     *     price_max?: int|null,
     *     per_page?: int|null,
     * }  $filters
     */
    public function list(array $filters = []): Collection|LengthAwarePaginator
    {
        $sort = $filters['sort'] ?? 'newest';
        $take = isset($filters['take']) ? (int) $filters['take'] : null;

        $query = Product::published()
            ->createdByAdmin()
            ->withAvg('reviews as rating_avg', 'rating');

        if ($take !== null) {
            $query->with('latestImage');
        } else {
            $query->withCount('reviews')->with('oldestImage', 'category.parent');
        }

        $this->applyListingFilters($query, $filters);
        $this->applySort($query, $sort);

        if ($take !== null) {
            return $query->take($take)->get();
        }

        return $query->orderByDesc('id')->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Latest products for home tabs (parity with Livewire Home::changeTab).
     */
    public function latestProducts(string $sort = 'newest', int $take = 15): Collection
    {
        $sortKey = match ($sort) {
            'score', 'order-by-score' => 'score',
            'sales', 'order-by-sales' => 'sales',
            default => 'newest',
        };

        return $this->list([
            'sort' => $sortKey,
            'take' => $take,
        ]);
    }

    /**
     * Find a published product by SKU with detail relations.
     */
    public function findBySku(string $sku): Product
    {
        $product = Product::query()
            ->where('sku', $sku)
            ->published()
            ->createdByAdmin()
            ->firstOrFail();

        $product->load([
            'images',
            'files',
            'reviews.user',
            'tags',
            'attributes' => function ($query) {
                $query->whereNotNull('value')
                    ->whereNot('value', '-')
                    ->where('display', 1);
            },
            'category.parent',
        ])->loadCount([
            'reviews as approved_reviews_count' => fn ($query) => $query->where('approved', 1),
            'likes',
            'likes as user_liked' => fn ($query) => $query->where('user_id', auth()->id()),
            'bookmarks as user_bookmarked' => fn ($query) => $query->where('user_id', auth()->id()),
        ])->loadAvg('reviews as rating_avg', 'rating');

        $product->setRelation(
            'similar_products',
            $this->similarProducts($product)
        );

        return $product;
    }

    /**
     * Similar products in the same category (parity with SimilarProducts Livewire).
     */
    public function similarProducts(Product $product, int $limit = 10): SupportCollection
    {
        if (! $product->category_id) {
            return collect();
        }

        return Product::query()
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->inRandomOrder()
            ->with('category.parent', 'oldestImage')
            ->limit($limit)
            ->get();
    }

    /**
     * Products for a category leaf (parity with ProductCategory).
     */
    public function paginateForCategory(Category $category): LengthAwarePaginator
    {
        return $category->products()
            ->published()
            ->createdByAdmin()
            ->withCount('reviews')
            ->withAvg('reviews as rating_avg', 'rating')
            ->with('oldestImage', 'category.parent')
            ->latest()
            ->paginate(16);
    }

    /**
     * Products for a tag (parity with ProductTag).
     */
    public function paginateForTag(Tag $tag): LengthAwarePaginator
    {
        return $tag->products()
            ->published()
            ->createdByAdmin()
            ->withCount('reviews')
            ->withAvg('reviews as rating_avg', 'rating')
            ->with('oldestImage', 'category.parent')
            ->orderByDesc('created_at')
            ->paginate(12);
    }

    /**
     * @param  array{
     *     search?: string|null,
     *     category?: string|null,
     *     tag?: string|null,
     *     tags?: array<int, string>|null,
     *     price_min?: int|null,
     *     price_max?: int|null,
     * }  $filters
     */
    private function applyListingFilters(Builder $query, array $filters): void
    {
        $search = $filters['search'] ?? null;
        if ($search) {
            // Match Livewire Store::loadProducts (including ungrouped orWhere).
            $query->where('name', 'like', '%'.$search.'%')
                ->orWhere('sku', 'like', '%'.$search.'%');
        }

        $tags = $filters['tags'] ?? null;
        if (! $tags && ! empty($filters['tag'])) {
            $tags = array_filter(array_map('trim', explode(',', (string) $filters['tag'])));
        }

        if ($tags) {
            $query->whereHas('tags', function ($q) use ($tags) {
                $q->whereIn('slug', $tags);
            });
        }

        if (! empty($filters['category'])) {
            $query->whereHas('category', function ($q) use ($filters) {
                $q->where('slug', $filters['category']);
            });
        }

        $min = (int) ($filters['price_min'] ?? 0);
        $max = (int) ($filters['price_max'] ?? 9000000);
        if ($min > 0 || $max < 9000000) {
            $query->whereBetween('price', [$min, $max]);
        }
    }

    private function applySort(Builder $query, string $sort): void
    {
        match ($sort) {
            'score', 'order-by-score' => $query->orderBy('rating_avg', 'desc'),
            'sales', 'order-by-sales', 'most-sales' => $query
                ->withCount('sales')
                ->orderByDesc('sales_count'),
            'cheapest' => $query->orderBy('price'),
            'most-expensive' => $query->orderByDesc('price'),
            default => $query->orderByDesc('created_at'),
        };
    }
}
