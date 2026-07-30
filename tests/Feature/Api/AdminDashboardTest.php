<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_fetch_admin_dashboard(): void
    {
        $this->getJson('/api/v1/admin/dashboard')
            ->assertUnauthorized();
    }

    public function test_non_admin_cannot_fetch_admin_dashboard(): void
    {
        $user = User::factory()->create();

        $this->actingAsVerifiedApiUser($user)
            ->getJson('/api/v1/admin/dashboard')
            ->assertForbidden();
    }

    public function test_admin_can_fetch_dashboard_summary(): void
    {
        $category = Category::factory()->create();
        Product::factory()->create(['category_id' => $category->id]);
        $admin = User::factory()->admin()->create();
        $customer = User::factory()->create();

        Order::create([
            'user_id' => $customer->id,
            'amount' => 500000,
            'tracking_id' => 12345678901,
            'status' => 0,
        ]);

        $this->actingAsAdminApiUser($admin)
            ->getJson('/api/v1/admin/dashboard')
            ->assertOk()
            ->assertJsonPath('data.products_count', 1)
            ->assertJsonPath('data.orders_total', 1)
            ->assertJsonPath('data.orders_paid', 1)
            ->assertJsonPath('data.total_sales', 500000)
            ->assertJsonPath('data.users_count', 2);
    }
}
