<?php

namespace App\Services;

use App\Models\Category;
use App\Models\File;
use App\Models\Image;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\ProductImport;
use Morilog\Jalali\Jalalian;

class AdminProductService
{
    public const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'fbx', 'gltf', 'glb', 'bin'];

    public function paginate(?string $search = null, int $perPage = 10): LengthAwarePaginator
    {
        $query = Product::with('category')->latest();

        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        return $query->paginate($perPage);
    }

    public function find(int $id): Product
    {
        return Product::with(['tags', 'attributes', 'category.parent', 'images', 'files'])->findOrFail($id);
    }

    public function nextSku(): string
    {
        $lastSku = Product::where('sku', 'LIKE', '3D-rgb-%')
            ->orderByRaw('CAST(SUBSTRING(sku, 8) AS UNSIGNED) DESC')
            ->value('sku');

        if ($lastSku) {
            $parts = explode('-', $lastSku);
            $lastNumber = (int) end($parts);

            return '3D-rgb-'.($lastNumber + 1);
        }

        return '3D-rgb-10000';
    }

    /**
     * @return array{categories: Collection, tags: Collection, attributes: Collection, next_sku: string}
     */
    public function formData(): array
    {
        return [
            'categories' => Category::with('children')->get(),
            'tags' => \App\Models\Tag::select('id', 'name', 'slug')->get(),
            'attributes' => \App\Models\Attribute::select('id', 'name', 'slug')->get(),
            'next_sku' => $this->nextSku(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, UploadedFile>  $images
     * @param  array<int, array{path: string, name: string, mime_type: string, size: string}>  $files
     * @param  array<int, int>  $tags
     * @param  array<int, array{id: int, value: string}>  $attributes
     */
    public function store(array $data, array $images, array $files, array $tags, array $attributes): Product
    {
        Gate::authorize('create', Product::class);

        $product = Product::create($data);

        foreach ($images as $image) {
            $product->images()->create([
                'path' => $image->store('products', 'public'),
            ]);
        }

        $uploadPath = $this->getUploadPath((int) $data['category_id']);
        $this->ensureUploadDirectory($uploadPath);

        foreach ($files as $index => $uploadedFile) {
            $this->moveUploadedFile($product, $uploadedFile, $uploadPath, $index);
        }

        $product->tags()->attach($tags);

        foreach ($attributes as $attribute) {
            $product->attributes()->attach($attribute['id'], [
                'value' => $attribute['value'],
            ]);
        }

        return $this->find($product->id);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, UploadedFile>  $images
     * @param  array<int, array{path: string, name: string, mime_type: string, size: string}>  $files
     * @param  array<int, int>  $tags
     * @param  array<int, array{id: int, value: string}>  $attributes
     */
    public function update(
        Product $product,
        array $data,
        array $images,
        array $files,
        array $tags,
        array $attributes
    ): Product {
        Gate::authorize('update', $product);

        $product->update($data);
        $product->tags()->sync($tags);

        foreach ($attributes as $attribute) {
            $product->attributes()->syncWithoutDetaching([
                $attribute['id'] => ['value' => $attribute['value']],
            ]);
        }

        foreach ($images as $image) {
            $product->images()->create([
                'path' => $image->store('products', 'public'),
            ]);
        }

        if ($files !== []) {
            $uploadPath = $this->getUploadPath((int) $data['category_id']);
            $this->ensureUploadDirectory($uploadPath);

            foreach ($files as $index => $uploadedFile) {
                $this->moveUploadedFile($product, $uploadedFile, $uploadPath, $index);
            }
        }

        return $this->find($product->id);
    }

    public function delete(Product $product): void
    {
        Gate::authorize('delete', $product);

        $product->images()->delete();
        $product->delete();
    }

    public function removeImage(Product $product, Image $image): Product
    {
        Gate::authorize('update', $product);

        if ($image->product_id !== $product->id) {
            abort(403);
        }

        $image->delete();

        return $this->find($product->id);
    }

    public function removeFile(Product $product, File $file): Product
    {
        Gate::authorize('update', $product);

        if ($file->product_id !== $product->id) {
            abort(403);
        }

        $file->delete();

        return $this->find($product->id);
    }

    public function import(UploadedFile $file): void
    {
        Gate::authorize('import', Product::class);

        Excel::import(new ProductImport, $file);
    }

    private function getUploadPath(int $categoryId): string
    {
        $category = Category::where('id', $categoryId)->with('parent')->firstOrFail();

        if ($category->parent) {
            return 'download/'.Jalalian::now()->getYear().'/3d/model/'.$category->parent->slug.'/'.$category->slug;
        }

        return 'download/'.Jalalian::now()->getYear().'/3d/model/'.$category->slug;
    }

    private function ensureUploadDirectory(string $uploadPath): void
    {
        if (! file_exists(storage_path('app/'.$uploadPath))) {
            mkdir(storage_path('app/'.$uploadPath), 0777, true);
        }
    }

    public function discardTempUpload(string $path, string $name): void
    {
        if (str_contains($path, '..') || str_contains($name, '..') || ! str_starts_with($path, 'upload/')) {
            return;
        }

        $fullPath = storage_path('app/'.$path.$name);

        if (is_file($fullPath) && str_starts_with((string) realpath($fullPath), storage_path('app/upload'))) {
            unlink($fullPath);
        }
    }

    /**
     * @param  array{path: string, name: string, mime_type: string, size: string}  $uploadedFile
     */
    private function moveUploadedFile(Product $product, array $uploadedFile, string $uploadPath, int $index): void
    {
        $inputPath = $uploadedFile['path'].$uploadedFile['name'];
        if (str_contains($inputPath, '..') || ! str_starts_with($uploadedFile['path'], 'upload/')) {
            abort(422, "Invalid file path at index {$index}.");
        }

        $originalPath = storage_path('app/'.$uploadedFile['path'].$uploadedFile['name']);

        if (! file_exists($originalPath) || ! str_starts_with(realpath($originalPath), storage_path('app/upload'))) {
            abort(422, "File not found or invalid path at index {$index}.");
        }

        $newPath = storage_path('app/'.$uploadPath.'/'.$uploadedFile['name']);
        rename($originalPath, $newPath);

        $product->files()->create([
            'name' => $uploadedFile['name'],
            'path' => $uploadPath.'/'.$uploadedFile['name'],
            'type' => $uploadedFile['mime_type'],
            'size' => $uploadedFile['size'],
        ]);
    }
}
