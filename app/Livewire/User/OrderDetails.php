<?php

namespace App\Livewire\User;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

class OrderDetails extends Component
{
    public Order $order;

    public function mount(Order $order)
    {
        $this->authorize('view', $order);
        $this->order = $order->load('products.users', 'products.files');
    }

    public function download(Product $product, ?int $fileId = null)
    {
        $this->authorize('download', $product);

        $file = $fileId
            ? $product->files()->findOrFail($fileId)
            : $product->files()->firstOrFail();

        $product->users()->updateExistingPivot(Auth::id(), [
            'download_count' => $product->users()->find(auth()->id())->pivot->download_count + 1,
            'downloaded_at' => now(),
        ]);

        return response()->download(storage_path('app/' . $file->path), $file->name);
    }

    #[Title('جزئیات سفارش')]
    public function render()
    {
        return view('livewire.user.order-details');
    }
}
