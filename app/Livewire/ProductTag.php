<?php

namespace App\Livewire;

use App\Models\Tag;
use Livewire\Component;
use Livewire\WithPagination;

class ProductTag extends Component
{
    use WithPagination;

    public Tag $tag;

    private function getProducts()
    {
        return $this->tag->products()
            ->published()
            ->createdByAdmin()
            ->withCount('reviews')
            ->withAvg('reviews as rating_avg', 'rating')
            ->with('oldestImage', 'category.parent')
            ->orderByDesc('created_at')
            ->paginate(12);
    }

    public function render()
    {
        return view('livewire.product-tag', [
            'products' => $this->getProducts(),
        ])->title($this->tag->name);
    }
}
