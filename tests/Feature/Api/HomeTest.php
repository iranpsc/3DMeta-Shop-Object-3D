<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_products_default_to_newest_sort_with_take(): void
    {
        $category = Category::factory()->create();

        $older = Product::factory()->create([
            'category_id' => $category->id,
            'published' => true,
            'created_by' => 'admin',
            'name' => 'Older Product',
            'created_at' => now()->subDay(),
        ]);

        $newer = Product::factory()->create([
            'category_id' => $category->id,
            'published' => true,
            'created_by' => 'admin',
            'name' => 'Newer Product',
            'created_at' => now(),
        ]);

        Product::factory()->create([
            'category_id' => $category->id,
            'published' => false,
            'created_by' => 'admin',
            'name' => 'Draft Product',
        ]);

        $response = $this->getJson('/api/v1/products?sort=newest&take=15')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'sku',
                        'name',
                        'price',
                        'final_price',
                        'is_free',
                        'url',
                    ],
                ],
            ]);

        $ids = collect($response->json('data'))->pluck('id')->all();

        $this->assertSame([$newer->id, $older->id], $ids);
        $this->assertNotContains(
            Product::where('name', 'Draft Product')->value('id'),
            $ids
        );
    }

    public function test_products_can_sort_by_score(): void
    {
        $category = Category::factory()->create();

        $low = Product::factory()->create([
            'category_id' => $category->id,
            'published' => true,
            'created_by' => 'admin',
            'name' => 'Low Score',
        ]);

        $high = Product::factory()->create([
            'category_id' => $category->id,
            'published' => true,
            'created_by' => 'admin',
            'name' => 'High Score',
        ]);

        $low->reviews()->create([
            'user_id' => User::factory()->create()->id,
            'comment' => 'ok product review',
            'rating' => 2,
            'approved' => true,
        ]);

        $high->reviews()->create([
            'user_id' => User::factory()->create()->id,
            'comment' => 'great product review',
            'rating' => 5,
            'approved' => true,
        ]);

        $response = $this->getJson('/api/v1/products?sort=score&take=15')
            ->assertOk();

        $ids = collect($response->json('data'))->pluck('id')->all();

        $this->assertSame($high->id, $ids[0]);
        $this->assertSame($low->id, $ids[1]);
    }

    public function test_products_can_sort_by_sales(): void
    {
        $category = Category::factory()->create();

        $noSales = Product::factory()->create([
            'category_id' => $category->id,
            'published' => true,
            'created_by' => 'admin',
            'name' => 'No Sales',
        ]);

        $withSales = Product::factory()->create([
            'category_id' => $category->id,
            'published' => true,
            'created_by' => 'admin',
            'name' => 'Has Sales',
        ]);

        $user = User::factory()->create();
        $order = Order::query()->create([
            'user_id' => $user->id,
            'amount' => 100000,
            'status' => 0,
        ]);

        OrderItem::query()->create([
            'order_id' => $order->id,
            'product_id' => $withSales->id,
        ]);

        $response = $this->getJson('/api/v1/products?sort=sales&take=15')
            ->assertOk();

        $ids = collect($response->json('data'))->pluck('id')->all();

        $this->assertSame($withSales->id, $ids[0]);
        $this->assertContains($noSales->id, $ids);
    }

    public function test_popular_categories_ordered_by_product_count(): void
    {
        $popular = Category::factory()->create(['name' => 'Popular']);
        $less = Category::factory()->create(['name' => 'Less']);

        Product::factory()->count(3)->create([
            'category_id' => $popular->id,
            'published' => true,
            'created_by' => 'admin',
        ]);

        Product::factory()->create([
            'category_id' => $less->id,
            'published' => true,
            'created_by' => 'admin',
        ]);

        Category::factory()->create(['name' => 'Empty']);

        $response = $this->getJson('/api/v1/categories/popular?take=12')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'slug',
                        'url',
                        'products_count',
                    ],
                ],
            ]);

        $names = collect($response->json('data'))->pluck('name')->all();

        $this->assertSame('Popular', $names[0]);
        $this->assertSame('Less', $names[1]);
        $this->assertNotContains('Empty', $names);
    }
}
