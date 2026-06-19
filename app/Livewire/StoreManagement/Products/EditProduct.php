<?php

namespace App\Livewire\StoreManagement\Products;

use App\Livewire\Forms\UpdateProduct;
use App\Models\Category;
use Livewire\Attributes\Title;
use Livewire\Component;
use App\Models\Product;
use App\Models\Tag;
use App\Models\Attribute;
use App\Models\Image;
use Livewire\WithFileUploads;

class EditProduct extends Component
{
    use WithFileUploads;

    public UpdateProduct $form;

    public function mount(Product $product)
    {
        $product->load('tags', 'attributes', 'category', 'images');

        $this->form->setProduct($product);
    }

    public function update()
    {
        $this->authorize('update', $this->form->getProduct());

        $this->form->update();

        session()->flash('success', __('Product updated successfully.'));
    }

    public function removeImage(Image $image)
    {
        $image->delete();
    }

    #[Title('ویرایش محصول')]
    public function render()
    {
        return view('livewire.store-management.products.edit-product')
        ->with('categories', Category::select('id', 'name')->get())
        ->with('tags', Tag::all())
        ->with('productAttributes', Attribute::all());
    }
}
