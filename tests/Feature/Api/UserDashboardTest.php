<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_fetch_dashboard(): void
    {
        $this->getJson('/api/v1/user/dashboard')
            ->assertUnauthorized();
    }

    public function test_unverified_user_cannot_fetch_dashboard(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAsVerifiedApiUser($user)
            ->getJson('/api/v1/user/dashboard')
            ->assertForbidden();
    }

    public function test_verified_user_can_fetch_dashboard_summary(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id, 'published' => true]);

        Order::create([
            'user_id' => $user->id,
            'amount' => 800000,
            'tracking_id' => 12345678901,
            'status' => 0,
        ]);

        Order::create([
            'user_id' => $user->id,
            'amount' => 500000,
            'tracking_id' => 12345678902,
            'status' => -1,
        ]);

        $user->products()->attach($product->id, ['quantity' => 1]);

        $this->actingAsVerifiedApiUser($user)
            ->getJson('/api/v1/user/dashboard')
            ->assertOk()
            ->assertJsonPath('data.stats.orders_total', 2)
            ->assertJsonPath('data.stats.orders_paid', 1)
            ->assertJsonPath('data.stats.orders_unpaid', 1)
            ->assertJsonPath('data.stats.products_owned', 1);
    }
}
