<?php

namespace App\Livewire;

use App\Models\Product;
use Livewire\Component;

class ProductDetails extends Component
{
    public Product $product;

    public function mount()
    {
        $this->product = $this->product->load([
            'images',
            'files',
            'reviews.user',
            'tags',
            'attributes' => function ($query) {
                $query->whereNotNull('value')
                    ->whereNot('value', '-')
                    ->where('display', 1);
            },
            'category',
        ])->loadCount([
            'reviews as approved_reviews_count' => fn ($query) => $query->where('approved', 1),
            'likes',
            'likes as user_liked' => fn ($query) => $query->where('user_id', auth()->id()),
            'bookmarks as user_bookmarked' => fn ($query) => $query->where('user_id', auth()->id()),
        ])->loadAvg('reviews as rating_avg', 'rating');
    }

    public function addToCart(int $quantity = 1)
    {
        // check if product is already in cart
        if (in_array($this->product->id, array_column(session('cart', []), 'product_id'))) {
            session()->flash('message', $this->product->name . ' قبلا به سبد خرید اضافه شده است.');
            return;
        }

        // Just in case user tries to add 0 quantity
        if($quantity == 0) $quantity = 1;

        session()->push('cart', [
            'product_id' => $this->product->id,
            'quantity' => $quantity
        ]);

        $cartProductsCount = count(session()->get('cart', []));

        $this->dispatch('cartUpdated', compact('cartProductsCount'));

        session()->flash('message', $this->product->name . ' به سبد خرید اضافه شد.');
    }

    public function download(?int $fileId = null)
    {
        if (!auth()->check()) {
            return $this->redirectRoute('login');
        }

        $this->authorize('download', $this->product);

        $file = $fileId
            ? $this->product->files()->findOrFail($fileId)
            : $this->product->files()->firstOrFail();

        // Redirect to the signed download route so the file is streamed by the
        // web server instead of being base64-encoded into the Livewire response
        // (which exhausts memory for large files).
        return $this->redirect($file->url, navigate: false);
    }

    public function toggleLike()
    {
        if (!auth()->check()) {
            return $this->redirectRoute('login');
        }

        if ($this->product->likes()->where('user_id', auth()->user()->id)->exists()) {
            $this->product->likes()->where('user_id', auth()->user()->id)->delete();
        } else {
            $this->product->likes()->create([
                'user_id' => auth()->user()->id,
                'type' => 'like',
                'ip' => request()->ip(),
            ]);
        }
    }

    public function toggleBookmark()
    {
        if (!auth()->check()) {
            return $this->redirectRoute('login');
        }

        if ($this->product->bookmarks()->where('user_id', auth()->user()->id)->exists()) {
            $this->product->bookmarks()->where('user_id', auth()->user()->id)->delete();
        } else {
            $this->product->bookmarks()->create([
                'user_id' => auth()->user()->id,
                'type' => 'bookmark',
                'ip' => request()->ip(),
            ]);
        }
    }

    public function render()
    {
        return view('livewire.product-details')
            ->title($this->product->name);
    }
}
