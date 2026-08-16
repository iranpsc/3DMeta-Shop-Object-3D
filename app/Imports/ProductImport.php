<?php

namespace App\Imports;

use App\Models\Attribute;
use App\Models\Category;
use App\Models\Image;
use App\Models\Product;
use App\Models\Tag;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class ProductImport implements ShouldQueue, ToArray, WithChunkReading
{
    /**
     * Import an array of products.
     *
     * @param  array  $array  The array of products to import.
     * @return void
     */
    public function array(array $array)
    {
        foreach ($array as $rowNumber => $row) {
            if ($rowNumber === 0) {
                continue; // Skip the header row
            }

            if (empty($row[0])) {
                Log::warning('Row '.($rowNumber + 1).' is empty. Skipping...');

                continue;
            }

            $categories = explode(',', $row[11]);
            $tags = explode(',', $row[12]);
            $images = explode(',', $row[13]);
            $file = $row[14];

            $categoriesIds = $this->createCategories($categories);
            $tagsIds = $this->createTags($tags);

            $product = $this->createOrUpdateProduct($row, $categoriesIds);

            $this->syncTags($product, $tagsIds);
            $this->syncAttributes($product, $row);
            $this->createImages($product, $images);
            $this->createFile($product, $file);
        }
    }

    /**
     * Create categories and return their IDs.
     *
     * @param  array  $categories  The array of category names.
     * @return array The array of category IDs.
     */
    private function createCategories(array $categories): array
    {
        $categoriesIds = [];

        foreach ($categories as $index => $category) {
            $category = trim($category);
            $parent_id = $index > 0 ? $categoriesIds[$index - 1] : null;

            $category = Category::firstOrCreate([
                'name' => $category,
                'parent_id' => $parent_id,
            ], [
                'slug' => str_replace(' ', '-', $category),
            ]);

            $categoriesIds[] = $category->id;
        }

        return $categoriesIds;
    }

    /**
     * Create tags and return their IDs.
     *
     * @param  array  $tags  The array of tag names.
     * @return array The array of tag IDs.
     */
    private function createTags(array $tags): array
    {
        $tagsIds = [];

        foreach ($tags as $tag) {
            $tag = trim($tag);

            $tag = Tag::firstOrCreate(
                ['name' => trim($tag)],
                ['slug' => str_replace(' ', '-', $tag)]
            );

            $tagsIds[] = $tag->id;
        }

        return $tagsIds;
    }

    /**
     * Create or update a product.
     *
     * @param  array  $row  The array representing the product data.
     * @param  array  $categoriesIds  The array of category IDs.
     * @return Product The created or updated product.
     */
    private function createOrUpdateProduct(array $row, array $categoriesIds): Product
    {
        return Product::updateOrCreate(
            ['sku' => $row[0]],
            [
                'category_id' => $categoriesIds[count($categoriesIds) - 1],
                'name' => $row[1],
                'slug' => str_replace(' ', '-', $row[1]),
                'published' => $row[2],
                'short_description' => $row[3],
                'long_description' => $row[4],
                'stock_status' => $row[5],
                'quantity' => $row[6],
                'delivery_time' => $row[7],
                'customer_can_add_review' => $row[8],
                'sale_price' => $row[9],
                'price' => $row[10],
            ]
        );
    }

    /**
     * Sync the tags of a product.
     *
     * @param  Product  $product  The product to sync tags for.
     * @param  array  $tagsIds  The array of tag IDs.
     */
    private function syncTags(Product $product, array $tagsIds): void
    {
        $product->tags()->sync($tagsIds);
    }

    /**
     * Sync the attributes of a product.
     *
     * @param  Product  $product  The product to sync attributes for.
     * @param  array  $row  The array representing the product data.
     */
    private function syncAttributes(Product $product, array $row): void
    {
        for ($i = 15; $i <= count($row) - 1; $i += 3) {
            if ($row[$i] != null) {
                $attribute = Attribute::where('name', trim($row[$i]))->first();

                if ($attribute) {
                    $product->attributes()->syncWithoutDetaching([$attribute->id => [
                        'value' => $row[$i + 1],
                        'display' => $row[$i + 2],
                    ]]);
                } else {
                    $attribute = Attribute::create([
                        'name' => trim($row[$i]),
                        'slug' => str_replace(' ', '-', $row[$i]),
                    ]);

                    $product->attributes()->attach($attribute->id, [
                        'value' => $row[$i + 1],
                        'display' => $row[$i + 2],
                    ]);
                }
            }
        }
    }

    /**
     * Create images for a product.
     *
     * @param  Product  $product  The product to create images for.
     * @param  array  $images  The array of image paths.
     */
    private function createImages(Product $product, array $images): void
    {
        $product->images()->delete();

        $imageData = [];

        foreach ($images as $image) {
            $image = trim($image);

            array_push($imageData, new Image(['path' => $image]));
        }

        $product->images()->saveMany($imageData);
    }

    /**
     * Create a file for a product.
     *
     * @param  Product  $product  The product to create a file for.
     * @param  string  $filePath  The file path.
     */
    private function createFile(Product $product, string $filePath): void
    {
        // Security check: prevent path traversal
        if (strpos($filePath, '..') !== false) {
            Log::warning("Skipping file creation for product {$product->id} due to invalid path: {$filePath}");

            return;
        }

        $product->files()->create([
            'path' => $filePath,
        ]);
    }

    /**
     * Get the chunk size for importing products.
     *
     * @return int The chunk size.
     */
    public function chunkSize(): int
    {
        return 100;
    }
}
