<?php

namespace Tests\Feature\Api;

use App\Models\Attribute;
use App\Models\Category;
use App\Models\Product;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminProductTest extends TestCase
{
    use RefreshDatabase;

    private function seedUploadFile(string $relativePath, string $filename): void
    {
        $dir = storage_path('app/'.$relativePath);
        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        file_put_contents($dir.$filename, 'test-file-content');
    }

    public function test_admin_can_list_products(): void
    {
        $category = Category::factory()->create();
        Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Admin Product',
        ]);

        $this->actingAsAdminApiUser()
            ->getJson('/api/v1/admin/products')
            ->assertOk()
            ->assertJsonPath('data.data.0.name', 'Admin Product');
    }

    public function test_admin_can_fetch_product_form_data(): void
    {
        Category::factory()->create();
        Tag::factory()->create();
        Attribute::factory()->create();

        $this->actingAsAdminApiUser()
            ->getJson('/api/v1/admin/products/form-data')
            ->assertOk()
            ->assertJsonStructure([
                'data' => ['categories', 'tags', 'attributes', 'next_sku'],
            ]);
    }

    public function test_admin_can_create_product(): void
    {
        Storage::fake('public');
        $category = Category::factory()->create();
        $tag = Tag::factory()->create();
        $attribute = Attribute::factory()->create();
        $uploadPath = 'upload/image-jpeg/2026-07-28/';
        $this->seedUploadFile($uploadPath, 'model.glb');

        $this->actingAsAdminApiUser()
            ->post('/api/v1/admin/products', [
                'category_id' => $category->id,
                'sku' => '3D-rgb-10001',
                'name' => 'New Product',
                'slug' => 'new-product',
                'short_description' => 'Short',
                'long_description' => 'Long description',
                'stock_status' => 0,
                'quantity' => 0,
                'delivery_time' => 0,
                'customer_can_add_review' => 1,
                'price' => 100000,
                'sale_price' => 90000,
                'published' => 1,
                'meta_description' => 'Meta desc',
                'meta_keywords' => 'meta, keywords',
                'images' => [UploadedFile::fake()->image('product.jpg')],
                'files' => json_encode([[
                    'path' => $uploadPath,
                    'name' => 'model.glb',
                    'mime_type' => 'image-jpeg',
                    'size' => '10 KB',
                ]]),
                'tags' => json_encode([$tag->id]),
                'attributes' => json_encode([['id' => $attribute->id, 'value' => 'Large']]),
            ], ['Accept' => 'application/json'])
            ->assertOk();

        $this->assertDatabaseHas('products', ['sku' => '3D-rgb-10001']);
    }

    public function test_admin_can_discard_temp_upload(): void
    {
        $uploadPath = 'upload/model-gltf/2026-07-28/';
        $filename = 'temp-model.glb';
        $this->seedUploadFile($uploadPath, $filename);

        $this->assertFileExists(storage_path('app/'.$uploadPath.$filename));

        $this->actingAsAdminApiUser()
            ->postJson('/api/v1/admin/products/temp-uploads/discard', [
                'path' => $uploadPath,
                'name' => $filename,
            ])
            ->assertOk();

        $this->assertFileDoesNotExist(storage_path('app/'.$uploadPath.$filename));
    }

    public function test_admin_can_delete_product(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);

        $this->actingAsAdminApiUser()
            ->deleteJson("/api/v1/admin/products/{$product->id}")
            ->assertOk();

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }
}
