<?php

namespace App\Livewire;

use App\Models\Category;
use Livewire\Component;
use Livewire\WithPagination;

class ProductCategory extends Component
{
    use WithPagination;

    public array $category_link = [];

    public function mount(?string $category_link = null): void
    {
        $this->category_link = array_values(array_filter(explode('/', $category_link ?? '')));
    }

    private function getCategory(): Category
    {
        $slug = $this->category_link[array_key_last($this->category_link)] ?? null;

        $category = Category::query()
            ->where('slug', $slug)
            ->with('children', 'image', 'parent')
            ->first();

        if (! $category) {
            abort(404);
        }

        return $category;
    }

    private function getProducts(Category $category)
    {
        if ($category->children->isEmpty()) {
            return $category->products()
                ->published()
                ->createdByAdmin()
                ->withCount('reviews')
                ->withAvg('reviews as rating_avg', 'rating')
                ->with('oldestImage')
                ->latest()
                ->paginate(16);
        }

        return collect();
    }

    public function render()
    {
        $category = $this->getCategory();

        return view('livewire.product-category', [
            'category' => $category,
            'products' => $this->getProducts($category),
        ])->title($category->name);
    }
}
