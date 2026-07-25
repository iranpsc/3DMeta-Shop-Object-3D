<?php

namespace App\Livewire\Forms;

use App\Models\Category;
use App\Models\Product;
use Closure;
use Livewire\Form;
use Livewire\WithFileUploads;
use Morilog\Jalali\Jalalian;

class CreateProductForm extends Form
{
    use WithFileUploads;

    public const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'fbx', 'gltf', 'glb', 'bin'];

    public $category_id;
    public $sku;
    public $name;
    public $slug;
    public $short_description;
    public $long_description;
    public $stock_status = 0;
    public $quantity = 0;
    public $delivery_time = 0;
    public $customer_can_add_review = 1;
    public $price;
    public $sale_price;
    public $published = 1;
    public $images = [];
    public $files = [];
    public $tags = [];
    public $attributes = [];
    public $meta_description;
    public $meta_keywords;

    public function rules()
    {
        return [
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:255|unique:products,sku',
            'slug' => 'required|string|max:255|unique:products,slug',
            'short_description' => 'required|string|max:255',
            'long_description' => 'required|string|max:5000',
            'stock_status' => 'nullable|boolean',
            'quantity' => [
                'nullable', 'numeric', 'min:0', function (string $attribute, mixed $value, Closure $fail) {
                    if ((bool)$this->stock_status == false && $value > 0) {
                        $fail(__('The quantity must not be greater than 0 if the stock status is false.'));
                    }
                }, function (string $attribute, mixed $value, Closure $fail) {
                    if ((bool)$this->stock_status == true && $value <= 0) {
                        $fail(__('The quantity must be greater than 0 if the stock status is true.'));
                    }
                }
            ],
            'delivery_time' => 'nullable|numeric|min:0',
            'customer_can_add_review' => 'required|boolean',
            'price' => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0|lte:price',
            'published' => 'required|boolean',
            'images.*' => 'required|image|max:5120',
            'files' => 'required|array|min:1|max:20',
            'files.*.path' => 'required|string',
            'files.*.name' => [
                'required',
                'string',
                function (string $attribute, mixed $value, Closure $fail) {
                    $extension = strtolower(pathinfo((string) $value, PATHINFO_EXTENSION));
                    if (! in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
                        $fail(__('The file extension is not allowed.'));
                    }
                },
            ],
            'files.*.mime_type' => 'required|string',
            'files.*.size' => 'required|string',
            'tags' => 'required|array|min:1',
            'tags.*' => 'required|exists:tags,id',
            'attributes' => 'required|array|min:1',
            'attributes.*.id' => 'required|exists:attributes,id',
            'attributes.*.value' => 'required|string|max:255',
            'meta_description' => 'required|string|max:255',
            'meta_keywords' => 'required|string|max:255',
        ];
    }

    public function save()
    {
        $this->validate();

        $product = Product::create(
            $this->only([
                'category_id',
                'sku',
                'name',
                'slug',
                'short_description',
                'long_description',
                'stock_status',
                'quantity',
                'delivery_time',
                'customer_can_add_review',
                'price',
                'sale_price',
                'published',
                'meta_description',
                'meta_keywords',
            ])
        );

        foreach ($this->images as $image) {
            $product->images()->create([
                'path' => $image->store('products', 'public'),
            ]);
        }

        $uploadPath = $this->getUploadPath();

        if (! file_exists(storage_path('app/' . $uploadPath))) {
            mkdir(storage_path('app/' . $uploadPath), 0777, true);
        }

        foreach ($this->files as $index => $uploadedFile) {
            if (! $this->moveUploadedFile($product, $uploadedFile, $uploadPath, $index)) {
                return;
            }
        }

        $product->tags()->attach($this->tags);

        foreach ($this->attributes as $attribute) {
            $product->attributes()->attach($attribute['id'], [
                'value' => $attribute['value'],
            ]);
        }

        $this->reset();
    }

    private function moveUploadedFile(Product $product, array $uploadedFile, string $uploadPath, int $index): bool
    {
        $inputPath = $uploadedFile['path'] . $uploadedFile['name'];
        if (strpos($inputPath, '..') !== false || strpos($uploadedFile['path'], 'upload/') !== 0) {
            $this->addError("files.{$index}", 'Invalid file path.');
            return false;
        }

        $originalPath = storage_path('app/' . $uploadedFile['path'] . $uploadedFile['name']);

        if (! file_exists($originalPath) || ! str_starts_with(realpath($originalPath), storage_path('app/upload'))) {
            $this->addError("files.{$index}", 'File not found or invalid path.');
            return false;
        }

        $newPath = storage_path('app/' . $uploadPath . '/' . $uploadedFile['name']);

        rename($originalPath, $newPath);

        $product->files()->create([
            'name' => $uploadedFile['name'],
            'path' => $uploadPath . '/' . $uploadedFile['name'],
            'type' => $uploadedFile['mime_type'],
            'size' => $uploadedFile['size'],
        ]);

        return true;
    }

    private function getUploadPath()
    {
        $category = Category::where('id', $this->category_id)->with('parent')->first();

        if ($category->parent) {
            return 'download/' . Jalalian::now()->getYear() . '/3d/model/' . $category->parent->slug . '/' . $category->slug;
        }
        return 'download/' . Jalalian::now()->getYear() . '/3d/model/' . $category->slug;
    }
}
