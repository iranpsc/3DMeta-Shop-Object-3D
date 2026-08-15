<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateProductTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test product creation.
     *
     * @return void
     */
    public function test_create_product()
    {
        // ایجاد یک دسته‌بندی برای استفاده در تست
        $category = Category::factory()->create();

        // ایجاد محصول با دسته‌بندی ایجاد شده
        $product = Product::factory()->create([
            'category_id' => $category->id,
        ]);

        // بررسی اینکه محصول به درستی ایجاد شده است
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
        ]);

        // بررسی مقادیر پیش‌فرض محصول
        $this->assertTrue($product->stock_status);
        $this->assertNotEmpty($product->price);
        $this->assertNotEmpty($product->quantity);
        $this->assertIsBool($product->published);
    }
}
