<?php

namespace App\Livewire\Forms;

use App\Models\Product;
use Livewire\Form;
use App\Models\Category;
use Morilog\Jalali\Jalalian;
use Closure;

class UpdateProduct extends Form
{
    public const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'fbx', 'gltf', 'glb', 'bin'];

    public Product $product;

    public $category_id;
    public $sku;
    public $name;
    public $slug;
    public $short_description;
    public $long_description;
    public $stock_status;
    public $quantity;
    public $delivery_time;
    public $customer_can_add_review;
    public $price;
    public $sale_price;
    public $published;
    public $images = [];
    public $files = [];
    public $tags;
    public $attributes;
    public $meta_description;
    public $meta_keywords;

    public function rules()
    {
        return [
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:255|unique:products,sku,' . $this->product->id,
            'slug' => 'required|string|max:255|unique:products,slug,' . $this->product->id,
            'short_description' => 'required|string|max:500',
            'long_description' => 'required|string|max:5000',
            'stock_status' => 'required|boolean',
            'quantity' => [
                'required', 'numeric', 'min:0', function (string $attribute, mixed $value, Closure $fail) {
                    if ((bool)$this->stock_status == false && $value > 0) {
                        $fail(__('The quantity must not be greater than 0 if the stock status is false.'));
                    }
                }, function (string $attribute, mixed $value, Closure $fail) {
                    if ((bool)$this->stock_status == true && $value <= 0) {
                        $fail(__('The quantity must be greater than 0 if the stock status is true.'));
                    }
                }
            ],
            'delivery_time' => 'required|numeric|min:0',
            'customer_can_add_review' => 'required|boolean',
            'price' => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0|lte:price',
            'published' => 'required|boolean',
            'images' => 'nullable|array|max:3',
            'images.*' => 'nullable|image|max:1024',
            'files' => 'nullable|array|max:20',
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
            'meta_description' => 'required|string|max:500',
            'meta_keywords' => 'required|string|max:255',
        ];
    }

    public function setProduct(Product $product)
    {
        $this->product = $product;

        $this->fill(
            $this->product->only([
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
    }

    public function getProduct(): Product
    {
        return $this->product;
    }

    public function update()
    {
        $this->validate();

        $this->product->update(
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

        $this->product->tags()->sync($this->tags);

        foreach ($this->attributes as $attribute) {
            $this->product->attributes()->syncWithoutDetaching([
                $attribute['id'] => [
                    'value' => $attribute['value'],
                ],
            ]);
        }

        if ($this->images) {
            foreach ($this->images as $image) {
                $this->product->images()->create([
                    'path' => $image->store('products', 'public'),
                ]);
            }
        }

        if ($this->files) {
            $uploadPath = $this->getUploadPath();

            if (! file_exists(storage_path('app/' . $uploadPath))) {
                mkdir(storage_path('app/' . $uploadPath), 0777, true);
            }

            foreach ($this->files as $index => $uploadedFile) {
                if (! $this->appendUploadedFile($uploadedFile, $uploadPath, $index)) {
                    return;
                }
            }

            $this->files = [];
        }
    }

    private function appendUploadedFile(array $uploadedFile, string $uploadPath, int $index): bool
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

        $this->product->files()->create([
            'name' => $uploadedFile['name'],
            'path' => $uploadPath . '/' . $uploadedFile['name'],
            'type' => $uploadedFile['mime_type'],
            'size' => $uploadedFile['size'],
        ]);

        return true;
    }

    private function getUploadPath()
    {
        $category = Category::where('id', $this->product->category_id)->with('parent')->first();

        if ($category->parent) {
            return 'download/' . Jalalian::now()->getYear() . '/3d/model/' . $category->parent->slug . '/' . $category->slug;
        }
        return 'download/' . Jalalian::now()->getYear() . '/3d/model/' . $category->slug;
    }
}
