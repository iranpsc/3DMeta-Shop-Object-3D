<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    private function createProduct(array $overrides = []): Product
    {
        $category = Category::factory()->create();

        return Product::factory()->create(array_merge([
            'category_id' => $category->id,
            'published' => true,
            'created_by' => 'admin',
            'price' => 100000,
            'sale_price' => 80000,
        ], $overrides));
    }

    public function test_guest_can_fetch_empty_cart(): void
    {
        $this->withHeaders($this->statefulApiHeaders())
            ->getJson('/api/v1/cart')
            ->assertOk()
            ->assertJsonPath('data.items', [])
            ->assertJsonPath('data.products', [])
            ->assertJsonPath('data.count', 0)
            ->assertJsonPath('data.total_price', 0);
    }

    public function test_guest_can_add_product_to_cart(): void
    {
        $product = $this->createProduct(['name' => 'Test Chair']);

        $this->withHeaders($this->statefulApiHeaders())
            ->postJson("/api/v1/cart/{$product->id}", ['quantity' => 2])
            ->assertOk()
            ->assertJsonPath('data.count', 1)
            ->assertJsonPath('data.items.0.product_id', $product->id)
            ->assertJsonPath('data.items.0.quantity', 2)
            ->assertJsonPath('message', 'Test Chair به سبد خرید اضافه شد.');
    }

    public function test_cannot_add_same_product_twice(): void
    {
        $product = $this->createProduct(['name' => 'Duplicate Item']);

        $this->withHeaders($this->statefulApiHeaders())
            ->withSession([
            'cart' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ])
            ->postJson("/api/v1/cart/{$product->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Duplicate Item قبلا به سبد خرید اضافه شده است.');
    }

    public function test_guest_can_update_cart_quantity(): void
    {
        $product = $this->createProduct();

        $this->withHeaders($this->statefulApiHeaders())
            ->withSession([
            'cart' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ])
            ->putJson("/api/v1/cart/{$product->id}", ['quantity' => 3])
            ->assertOk()
            ->assertJsonPath('data.items.0.quantity', 3);
    }

    public function test_guest_can_remove_product_from_cart(): void
    {
        $product = $this->createProduct();

        $this->withHeaders($this->statefulApiHeaders())
            ->withSession([
            'cart' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ])
            ->deleteJson("/api/v1/cart/{$product->id}")
            ->assertOk()
            ->assertJsonPath('data.count', 0)
            ->assertJsonPath('message', 'محصول از سبد خرید حذف شد.');
    }

    public function test_cart_total_price_uses_final_price(): void
    {
        $product = $this->createProduct([
            'price' => 100000,
            'sale_price' => 50000,
        ]);

        $this->withHeaders($this->statefulApiHeaders())
            ->withSession([
            'cart' => [
                ['product_id' => $product->id, 'quantity' => 2],
            ],
        ])
            ->getJson('/api/v1/cart')
            ->assertOk()
            ->assertJsonPath('data.total_price', $product->final_price * 2);
    }
}
