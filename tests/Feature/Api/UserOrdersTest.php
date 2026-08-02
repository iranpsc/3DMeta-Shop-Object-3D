<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserOrdersTest extends TestCase
{
    use RefreshDatabase;

    private function createOrder(User $user, int $status = 0): Order
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'published' => true,
            'price' => 100000,
        ]);

        $order = Order::create([
            'user_id' => $user->id,
            'amount' => 800000,
            'tracking_id' => random_int(10000000000, 99999999999),
            'status' => $status,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $user->products()->attach($product->id, [
            'quantity' => 1,
            'download_count' => 0,
        ]);

        return $order->fresh(['products']);
    }

    public function test_verified_user_can_list_orders(): void
    {
        $user = User::factory()->create();
        $this->createOrder($user);

        $this->actingAsVerifiedApiUser($user)
            ->getJson('/api/v1/user/orders')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'data' => [['id', 'tracking_id', 'is_paid', 'status_label']],
                    'meta' => ['current_page', 'last_page', 'total'],
                ],
            ]);
    }

    public function test_user_can_view_paid_order_details(): void
    {
        $user = User::factory()->create();
        $order = $this->createOrder($user, status: 0);

        $this->actingAsVerifiedApiUser($user)
            ->getJson("/api/v1/user/orders/{$order->id}")
            ->assertOk()
            ->assertJsonPath('data.is_paid', true)
            ->assertJsonPath('data.products.0.name', $order->products->first()->name);
    }

    public function test_user_cannot_view_unpaid_order_details(): void
    {
        $user = User::factory()->create();
        $order = $this->createOrder($user, status: -1);

        $this->actingAsVerifiedApiUser($user)
            ->getJson("/api/v1/user/orders/{$order->id}")
            ->assertForbidden();
    }
}
