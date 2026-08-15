<?php

namespace Tests\Feature;

use App\Imports\ProductImport;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // ایجاد دسته‌ها قبل از اجرای تست‌ها برای جلوگیری از خطای کلید خارجی
        Category::factory()->count(10)->create();
    }

    public function test_import_product_array()
    {
        $data = [
            ['sku', 'name', 'published', 'short_description', 'long_description', 'stock_status', 'quantity', 'delivery_time', 'customer_can_add_review', 'sale_price', 'price', 'categories', 'tags', 'images', 'file'],
            ['SKU123', 'Test Product', true, 'Short desc', 'Long desc', 1, 10, 2, true, 100, 150, 'Category1,Category2', 'Tag1,Tag2', 'image1.jpg,image2.jpg', 'file1.pdf'],
        ];

        $import = new ProductImport;
        $import->array($data);

        // تأیید ثبت محصول
        $this->assertDatabaseHas('products', ['sku' => 'SKU123', 'name' => 'Test Product']);
        $this->assertDatabaseHas('categories', ['name' => 'Category1']);
        $this->assertDatabaseHas('categories', ['name' => 'Category2']);
        $this->assertDatabaseHas('tags', ['name' => 'Tag1']);
        $this->assertDatabaseHas('tags', ['name' => 'Tag2']);
        $this->assertDatabaseHas('images', ['path' => 'image1.jpg']);
        $this->assertDatabaseHas('images', ['path' => 'image2.jpg']);
    }

    public function test_sync_tags()
    {
        $product = Product::factory()->create(['category_id' => Category::inRandomOrder()->first()->id]);
        $tags = ['Tag1', 'Tag2'];

        $import = new ProductImport;
        $tagIds = $this->invokeMethod($import, 'createTags', [$tags]);
        $this->invokeMethod($import, 'syncTags', [$product, $tagIds]);

        // تأیید ثبت تگ‌ها برای محصول
        $this->assertCount(2, $product->tags);
    }

    public function test_sync_attributes()
    {
        $product = Product::factory()->create(['category_id' => Category::inRandomOrder()->first()->id]);
        $data = [null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, 'Color', 'Red', 1]; // تغییر 'Visible' به 1

        $import = new ProductImport;
        $this->invokeMethod($import, 'syncAttributes', [$product, $data]);

        // تأیید ثبت خصوصیات
        $this->assertDatabaseHas('attributes', ['name' => 'Color']);
        $this->assertDatabaseHas('attribute_product', [
            'product_id' => $product->id,
            'value' => 'Red',
            'display' => 1, // تغییر به 1
        ]);
    }

    public function test_chunk_size()
    {
        $import = new ProductImport;
        $this->assertEquals(100, $import->chunkSize());
    }

    /**
     * Invoke a private or protected method of a class.
     *
     * @param  object  &$object  Instance of the class.
     * @param  string  $methodName  Method name to call.
     * @param  array  $parameters  Parameters to pass into the method.
     * @return mixed Method return.
     */
    protected function invokeMethod(&$object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}
