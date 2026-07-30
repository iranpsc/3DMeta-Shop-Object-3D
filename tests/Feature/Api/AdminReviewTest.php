<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Product;
use App\Models\Review;
use App\Models\ReviewReply;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_reviews(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['category_id' => Category::factory()->create()->id]);
        Review::create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'comment' => 'Great product',
            'rating' => 5,
        ]);

        $this->actingAsAdminApiUser()
            ->getJson('/api/v1/admin/reviews')
            ->assertOk()
            ->assertJsonPath('data.data.0.comment', 'Great product');
    }

    public function test_admin_can_approve_review(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        $product = Product::factory()->create(['category_id' => Category::factory()->create()->id]);
        $review = Review::create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'comment' => 'Needs approval',
            'rating' => 4,
        ]);

        $this->actingAsAdminApiUser($admin)
            ->postJson("/api/v1/admin/reviews/{$review->id}/approve")
            ->assertOk()
            ->assertJsonPath('data.approved', true);

        $this->assertTrue($review->fresh()->approved);
    }

    public function test_admin_can_delete_review(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['category_id' => Category::factory()->create()->id]);
        $review = Review::create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'comment' => 'Delete me',
            'rating' => 2,
        ]);

        $this->actingAsAdminApiUser()
            ->deleteJson("/api/v1/admin/reviews/{$review->id}")
            ->assertOk();

        $this->assertDatabaseMissing('reviews', ['id' => $review->id]);
    }

    public function test_admin_can_manage_review_replies(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        $product = Product::factory()->create(['category_id' => Category::factory()->create()->id]);
        $review = Review::create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'comment' => 'Question',
            'rating' => 5,
        ]);

        $this->actingAsAdminApiUser($admin)
            ->postJson("/api/v1/admin/reviews/{$review->id}/replies", [
                'comment' => 'Admin reply',
            ])
            ->assertOk();

        $reply = ReviewReply::first();

        $this->actingAsAdminApiUser($admin)
            ->postJson("/api/v1/admin/review-replies/{$reply->id}/approve")
            ->assertOk()
            ->assertJsonPath('message', 'پاسخ با موفقیت تایید شد.');
    }
}
