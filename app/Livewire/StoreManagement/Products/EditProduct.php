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
use App\Models\File;
use Livewire\WithFileUploads;

class EditProduct extends Component
{
    use WithFileUploads;

    public UpdateProduct $form;

    public function mount(Product $product)
    {
        $product->load('tags', 'attributes', 'category', 'images', 'files');

        $this->form->setProduct($product);
    }

    public function update()
    {
        $this->authorize('update', $this->form->getProduct());

        $this->form->update();

        $this->form->getProduct()->load('files');

        $this->dispatch('product-files-cleared');

        session()->flash('success', __('Product updated successfully.'));
    }

    public function removeImage(Image $image)
    {
        $image->delete();

        $this->form->getProduct()->load('images');
    }

    public function removeFile(File $file)
    {
        $this->authorize('update', $this->form->getProduct());

        if ($file->product_id !== $this->form->getProduct()->id) {
            abort(403);
        }

        $file->delete();

        $this->form->getProduct()->load('files');
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

    #[Title('ویرایش محصول')]
    public function render()
    {
        return view('livewire.store-management.products.edit-product')
            ->with('categories', Category::with('children')->get())
            ->with('tags', Tag::all())
            ->with('productAttributes', Attribute::all());
    }
}
