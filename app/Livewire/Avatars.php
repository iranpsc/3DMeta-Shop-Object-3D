<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Product;
use App\Models\Category;
use App\Models\Tag;
use App\Models\Attribute;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Title;
use App\Jobs\DownloadFileJob;
use Livewire\Attributes\Locked;

class Avatars extends Component
{
    use WithPagination;

    public $name, $avatarImageURL, $avatarUrl, $search;

    protected $rules = [
        'name' => 'required|min:3|max:255',
        'avatarUrl' => 'required|url',
        'avatarImageURL' => 'required|url',
    ];

    public function save()
    {
        $this->validate();

        $category = $this->getOrCreateCategory('avatar', 'Avatars');

        $product = $this->createProduct($category->id);

        // Dispatch jobs to download, store, and save the image and avatar file paths to the database
        DownloadFileJob::dispatch($this->avatarImageURL, $product, 'image');
        DownloadFileJob::dispatch($this->avatarUrl, $product, 'file');

        // Attach random tag and attribute
        $this->attachRandomTag($product);
        $this->attachRandomAttribute($product);

        Auth::user()->products()->attach($product);

        $this->reset();

        session()->flash('success', __('Avatar created successfully.'));
    }

    private function getOrCreateCategory($slug, $name)
    {
        return Category::firstOrCreate(
            ['slug' => $slug],
            ['name' => $name, 'slug' => $slug]
        );
    }

    private function createProduct($categoryId)
    {
        return Product::create([
            'category_id' => $categoryId,
            'sku' => $this->generateSku(),
            'name' => $this->name,
            'slug' => Str::slug($this->name),
            'price' => 0,
            'published' => true,
            'created_by' => 'user',
        ]);
    }

    private function attachRandomTag($product)
    {
        $randomTag = Tag::inRandomOrder()->first();
        $product->tags()->attach($randomTag);
    }

    private function attachRandomAttribute($product)
    {
        $randomAttribute = Attribute::inRandomOrder()->first();
        $product->attributes()->attach($randomAttribute, ['value' => 'Custom Value']);
    }

    private function generateSku()
    {
        $lastSku = Product::select('sku')->orderBy('id', 'desc')->first();

        if ($lastSku) {
            $parts = explode('-', $lastSku->sku);
            $lastSku = '3D-rgb-' . ((int)end($parts) + 1);
        } else {
            $lastSku = '3D-rgb-10000';
        }

        return $lastSku;
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    #[Title('بارگذاری آواتار')]
    public function render()
    {
        $products = Auth::user()->products()->where('category_id', $this->getOrCreateCategory('avatar', 'Avatars')->id)
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%');
            })
            ->whereHas('files')
            ->whereHas('latestImage')
            ->with('files', 'latestImage')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('livewire.avatars', compact('products'));
    }
}
