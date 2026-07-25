<?php

namespace App\Livewire;

use App\Models\Product;
use Livewire\Component;

class ProductItem extends Component
{
    public Product $product;

    public function addToCart(int $quantity = 1)
    {
        // check if product is already in cart
        if (in_array($this->product->id, array_column(session('cart', []), 'product_id'))) {
            session()->flash('message', $this->product->name . ' قبلا به سبد خرید اضافه شده است.');
            return;
        }

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

        return response()->download(storage_path('app/' . $file->path), $file->name);
    }
}
