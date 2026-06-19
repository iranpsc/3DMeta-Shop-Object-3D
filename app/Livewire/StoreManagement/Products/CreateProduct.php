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
        $lastSku = Product::select('sku')->orderBy('id', 'desc')->first();

        if ($lastSku) {
            $parts = explode('-', $lastSku->sku);
            $lastSku = '3D-rgb-' . ((int)end($parts) + 1);
        } else {
            $lastSku = '3D-rgb-10000';
        }

        $this->form->sku = $lastSku;
    }

    public function save()
    {
        $this->authorize('create', Product::class);

        $this->form->save();

        session()->flash('success', __('Product created successfully.'));
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
