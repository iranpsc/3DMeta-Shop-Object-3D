<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_users(): void
    {
        $user = User::factory()->create(['name' => 'Ali Test']);
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);
        $user->products()->attach($product->id, ['quantity' => 1]);

        $this->actingAsAdminApiUser()
            ->getJson('/api/v1/admin/users?search=Ali')
            ->assertOk()
            ->assertJsonPath('data.data.0.name', 'Ali Test')
            ->assertJsonPath('data.data.0.products_count', 1);
    }
}
