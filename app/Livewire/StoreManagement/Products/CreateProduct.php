<?php

namespace App\Livewire\StoreManagement\Products;

use App\Livewire\Forms\CreateProductForm;
use Livewire\Attributes\Title;
use Livewire\Component;
use App\Models\Category;
use App\Models\Attribute;
use App\Models\Tag;
use Livewire\WithFileUploads;
use App\Models\Product;

class CreateProduct extends Component
{
    use WithFileUploads;

    public CreateProductForm $form;

    public function mount()
    {
        // Fetch the SKU with the highest numeric value at the end
        $lastSku = Product::where('sku', 'LIKE', '3D-rgb-%')
            ->orderByRaw("CAST(SUBSTRING(sku, 8) AS UNSIGNED) DESC")
            ->value('sku');

        if ($lastSku) {
            // Extract the number at the end and increment it
            $parts = explode('-', $lastSku);
            $lastNumber = (int) end($parts);
            $nextNumber = $lastNumber + 1;
            $nextSku = '3D-rgb-' . $nextNumber;
        } else {
            $nextSku = '3D-rgb-10000';
        }

        $this->form->sku = $nextSku;
    }

    public function save()
    {
        $this->authorize('create', Product::class);

        $this->form->save();

        $this->dispatch('product-files-cleared');

        session()->flash('success', __('Product created successfully.'));
    }

    public function discardTempUpload(string $path, string $name): void
    {
        if (str_contains($path, '..') || str_contains($name, '..') || ! str_starts_with($path, 'upload/')) {
            return;
        }

        $fullPath = storage_path('app/' . $path . $name);

        if (is_file($fullPath) && str_starts_with(realpath($fullPath), storage_path('app/upload'))) {
            unlink($fullPath);
        }
    }


    #[Title('محصول جدید')]
    public function render()
    {
        return view('livewire.store-management.products.create-product')
            ->with([
                'categories' => Category::with('children')->get(),
                'tags' => Tag::select('id', 'name')->get(),
                'productAttributes' => Attribute::select('id', 'name')->get(),
            ]);
    }
}
