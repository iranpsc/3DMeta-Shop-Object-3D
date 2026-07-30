<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class CategoryService
{
    /**
     * Popular categories with published products (Home).
     */
    public function popular(int $take = 12): Collection
    {
        return Category::with('parent', 'image')
            ->whereHas('products', function ($query) {
                $query->published();
            })
            ->withCount('products')
            ->orderByDesc('products_count')
            ->take($take)
            ->get();
    }

    /**
     * Paginated categories that have published admin products.
     */
    public function paginate(int $perPage = 12): LengthAwarePaginator
    {
        return Category::with('parent', 'image')
            ->whereHas('products', function ($query) {
                $query->published()->createdByAdmin();
            })
            ->withCount('products')
            ->orderByDesc('products_count')
            ->paginate($perPage);
    }

    /**
     * Top-level categories for slider (parity with TopLevelCategorySlider).
     */
    public function topLevel(): Collection
    {
        return Category::whereNull('parent_id')
            ->with('image', 'children')
            ->get();
    }

    /**
     * Resolve category by nested slug path (last segment).
     */
    public function findBySlugPath(string $slugPath): Category
    {
        $segments = array_values(array_filter(explode('/', $slugPath)));
        $slug = $segments[array_key_last($segments)] ?? null;

        $category = Category::query()
            ->where('slug', $slug)
            ->with('children', 'image', 'parent')
            ->first();

        if (! $category) {
            abort(404);
        }

        return $category;
    }

    /**
     * Store sidebar categories (parity with Store::categories).
     */
    public function forStoreFilter(): Collection
    {
        return Category::whereHas('products', function ($query) {
            $query->published();
        })
            ->select('id', 'name', 'slug')
            ->with('image')
            ->withCount('products')
            ->orderByDesc('products_count')
            ->get();
    }
}
