<?php

namespace Tests\Feature\Api;

use App\Models\Attribute;
use App\Models\Category;
use App\Models\Product;
use App\Models\Tag;
use App\Models\User;
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
            ->assertJsonPath('data.data.0.name', 'Admin Product')
            ->assertJsonPath('data.data.0.can_delete', true);
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

    public function test_admin_cannot_delete_purchased_product(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);
        $buyer = User::factory()->create();
        $buyer->products()->attach($product->id, ['quantity' => 1]);

        $this->actingAsAdminApiUser()
            ->deleteJson("/api/v1/admin/products/{$product->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    public function test_admin_product_validates_stock_quantity_rules_and_file_extension(): void
    {
        Storage::fake('public');
        $category = Category::factory()->create();
        $tag = Tag::factory()->create();
        $attribute = Attribute::factory()->create();

        $this->actingAsAdminApiUser()
            ->post('/api/v1/admin/products', [
                'category_id' => $category->id,
                'sku' => '3D-rgb-40001',
                'name' => 'Invalid Stock',
                'slug' => 'invalid-stock',
                'short_description' => 'Short',
                'long_description' => 'Long description',
                'stock_status' => 0,
                'quantity' => 5,
                'delivery_time' => 0,
                'customer_can_add_review' => 1,
                'price' => 100000,
                'sale_price' => 90000,
                'published' => 1,
                'meta_description' => 'Meta desc',
                'meta_keywords' => 'meta, keywords',
                'images' => [UploadedFile::fake()->image('product.jpg')],
                'files' => json_encode([[
                    'path' => 'upload/x/',
                    'name' => 'bad.exe',
                    'mime_type' => 'application/octet-stream',
                    'size' => '10 KB',
                ]]),
                'tags' => json_encode([$tag->id]),
                'attributes' => json_encode([['id' => $attribute->id, 'value' => 'Large']]),
            ], ['Accept' => 'application/json'])
            ->assertStatus(422);

        $this->actingAsAdminApiUser()
            ->post('/api/v1/admin/products', [
                'category_id' => $category->id,
                'sku' => '3D-rgb-40002',
                'name' => 'Invalid Stock True',
                'slug' => 'invalid-stock-true',
                'short_description' => 'Short',
                'long_description' => 'Long description',
                'stock_status' => 1,
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
                    'path' => 'upload/x/',
                    'name' => 'model.glb',
                    'mime_type' => 'model-gltf',
                    'size' => '10 KB',
                ]]),
                'tags' => json_encode([$tag->id]),
                'attributes' => json_encode([['id' => $attribute->id, 'value' => 'Large']]),
            ], ['Accept' => 'application/json'])
            ->assertStatus(422);
    }

    public function test_admin_can_search_products(): void
    {
        $category = Category::factory()->create();
        Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Searchable Admin Product',
        ]);
        Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Other Product',
        ]);

        $this->actingAsAdminApiUser()
            ->getJson('/api/v1/admin/products?search=Searchable')
            ->assertOk()
            ->assertJsonPath('data.data.0.name', 'Searchable Admin Product')
            ->assertJsonPath('data.meta.total', 1);
    }

    public function test_admin_can_show_product(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Detail Product',
        ]);

        $this->actingAsAdminApiUser()
            ->getJson("/api/v1/admin/products/{$product->id}")
            ->assertOk()
            ->assertJsonPath('data.name', 'Detail Product');
    }

    public function test_form_data_increments_next_sku_from_existing(): void
    {
        $category = Category::factory()->create();
        Product::factory()->create([
            'category_id' => $category->id,
            'sku' => '3D-rgb-10050',
        ]);

        $this->actingAsAdminApiUser()
            ->getJson('/api/v1/admin/products/form-data')
            ->assertOk()
            ->assertJsonPath('data.next_sku', '3D-rgb-10051');
    }

    public function test_admin_can_update_product_with_parent_category_files(): void
    {
        Storage::fake('public');
        $parent = Category::factory()->create(['slug' => 'parent-cat']);
        $category = Category::factory()->create([
            'slug' => 'child-cat',
            'parent_id' => $parent->id,
        ]);
        $tag = Tag::factory()->create();
        $attribute = Attribute::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'sku' => '3D-rgb-20001',
            'slug' => 'updatable-product',
        ]);

        $uploadPath = 'upload/model-gltf/2026-07-28/';
        $this->seedUploadFile($uploadPath, 'updated.glb');

        $this->actingAsAdminApiUser()
            ->put('/api/v1/admin/products/'.$product->id, [
                'category_id' => $category->id,
                'sku' => '3D-rgb-20001',
                'name' => 'Updated Product',
                'slug' => 'updatable-product',
                'short_description' => 'Short',
                'long_description' => 'Long description',
                'stock_status' => 0,
                'quantity' => 0,
                'delivery_time' => 0,
                'customer_can_add_review' => 1,
                'price' => 120000,
                'sale_price' => 100000,
                'published' => 1,
                'meta_description' => 'Meta desc',
                'meta_keywords' => 'meta, keywords',
                'images' => [UploadedFile::fake()->image('updated.jpg')],
                'files' => json_encode([[
                    'path' => $uploadPath,
                    'name' => 'updated.glb',
                    'mime_type' => 'model-gltf',
                    'size' => '10 KB',
                ]]),
                'tags' => json_encode([$tag->id]),
                'attributes' => json_encode([['id' => $attribute->id, 'value' => 'Medium']]),
            ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated Product');
    }

    public function test_admin_can_update_product_without_new_files(): void
    {
        Storage::fake('public');
        $category = Category::factory()->create();
        $tag = Tag::factory()->create();
        $attribute = Attribute::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'sku' => '3D-rgb-20002',
            'slug' => 'no-files-update',
        ]);

        $this->actingAsAdminApiUser()
            ->putJson('/api/v1/admin/products/'.$product->id, [
                'category_id' => $category->id,
                'sku' => '3D-rgb-20002',
                'name' => 'No Files Update',
                'slug' => 'no-files-update',
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
                'tags' => [$tag->id],
                'attributes' => [['id' => $attribute->id, 'value' => 'Small']],
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'No Files Update');
    }

    public function test_admin_can_import_products(): void
    {
        Category::factory()->count(3)->create();

        $csv = implode("\n", [
            'sku,name,published,short_description,long_description,stock_status,quantity,delivery_time,customer_can_add_review,sale_price,price,categories,tags,images,file',
            'IMP-1,Imported Product,1,Short,Long,1,10,2,1,100,150,CatA,TagA,img.jpg,file.pdf',
        ]);

        $file = UploadedFile::fake()->createWithContent('products.csv', $csv);

        $this->actingAsAdminApiUser()
            ->post('/api/v1/admin/products/import', [
                'file' => $file,
            ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('message', 'محصولات با موفقیت درون ریزی شدند.');

        $this->assertDatabaseHas('products', ['sku' => 'IMP-1']);
    }

    public function test_admin_can_destroy_product_image_and_file(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);
        $image = $product->images()->create(['path' => 'products/remove-me.jpg']);

        $fileDir = storage_path('app/download/test');
        if (! is_dir($fileDir)) {
            mkdir($fileDir, 0777, true);
        }
        file_put_contents($fileDir.'/remove-me.glb', 'content');
        $file = $product->files()->create([
            'name' => 'remove-me.glb',
            'path' => 'download/test/remove-me.glb',
            'type' => 'model/gltf-binary',
            'size' => '1 KB',
        ]);

        $this->actingAsAdminApiUser()
            ->deleteJson("/api/v1/admin/products/{$product->id}/images/{$image->id}")
            ->assertOk();

        $this->assertDatabaseMissing('images', ['id' => $image->id]);

        $this->actingAsAdminApiUser()
            ->deleteJson("/api/v1/admin/products/{$product->id}/files/{$file->id}")
            ->assertOk();

        $this->assertDatabaseMissing('files', ['id' => $file->id]);
    }

    public function test_admin_cannot_destroy_image_or_file_from_another_product(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);
        $other = Product::factory()->create(['category_id' => $category->id]);
        $image = $other->images()->create(['path' => 'products/other.jpg']);
        $file = $other->files()->create([
            'name' => 'other.glb',
            'path' => 'download/other.glb',
            'type' => 'model/gltf-binary',
            'size' => '1 KB',
        ]);

        $this->actingAsAdminApiUser()
            ->deleteJson("/api/v1/admin/products/{$product->id}/images/{$image->id}")
            ->assertForbidden();

        $this->actingAsAdminApiUser()
            ->deleteJson("/api/v1/admin/products/{$product->id}/files/{$file->id}")
            ->assertForbidden();
    }

    public function test_discard_temp_upload_ignores_path_traversal(): void
    {
        $this->actingAsAdminApiUser()
            ->postJson('/api/v1/admin/products/temp-uploads/discard', [
                'path' => '../secrets/',
                'name' => 'evil.txt',
            ])
            ->assertOk();
    }

    public function test_create_product_rejects_invalid_temp_file_path(): void
    {
        Storage::fake('public');
        $category = Category::factory()->create();
        $tag = Tag::factory()->create();
        $attribute = Attribute::factory()->create();

        $this->actingAsAdminApiUser()
            ->post('/api/v1/admin/products', [
                'category_id' => $category->id,
                'sku' => '3D-rgb-30001',
                'name' => 'Bad File Path',
                'slug' => 'bad-file-path',
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
                    'path' => 'upload/missing/',
                    'name' => 'missing.glb',
                    'mime_type' => 'model-gltf',
                    'size' => '10 KB',
                ]]),
                'tags' => json_encode([$tag->id]),
                'attributes' => json_encode([['id' => $attribute->id, 'value' => 'Large']]),
            ], ['Accept' => 'application/json'])
            ->assertStatus(422);
    }

    public function test_create_product_rejects_path_traversal_in_files(): void
    {
        Storage::fake('public');
        $category = Category::factory()->create();
        $tag = Tag::factory()->create();
        $attribute = Attribute::factory()->create();

        $this->actingAsAdminApiUser()
            ->post('/api/v1/admin/products', [
                'category_id' => $category->id,
                'sku' => '3D-rgb-30002',
                'name' => 'Traversal File',
                'slug' => 'traversal-file',
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
                    'path' => 'upload/../',
                    'name' => 'evil.glb',
                    'mime_type' => 'model-gltf',
                    'size' => '10 KB',
                ]]),
                'tags' => json_encode([$tag->id]),
                'attributes' => json_encode([['id' => $attribute->id, 'value' => 'Large']]),
            ], ['Accept' => 'application/json'])
            ->assertStatus(422);
    }

    public function test_admin_can_upload_product_file_chunk(): void
    {
        $file = UploadedFile::fake()->create('chunk-model.glb', 50, 'model/gltf-binary');

        $this->actingAsAdminApiUser()
            ->post('/api/v1/admin/products/upload', [
                'file' => $file,
            ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonStructure(['path', 'name', 'mime_type', 'size']);
    }
}
