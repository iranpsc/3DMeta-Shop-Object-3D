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

    public function test_admin_cannot_delete_approved_review(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        $product = Product::factory()->create(['category_id' => Category::factory()->create()->id]);
        $review = Review::create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'comment' => 'Approved comment',
            'rating' => 5,
            'approved' => true,
        ]);

        $this->actingAsAdminApiUser($admin)
            ->deleteJson("/api/v1/admin/reviews/{$review->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('reviews', ['id' => $review->id]);
    }

    public function test_admin_update_returns_review_to_pending(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        $product = Product::factory()->create(['category_id' => Category::factory()->create()->id]);
        $review = Review::create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'comment' => 'Approved comment',
            'rating' => 5,
            'approved' => true,
            'approved_by' => $admin->name,
            'approved_at' => now(),
        ]);

        $this->actingAsAdminApiUser($admin)
            ->putJson("/api/v1/admin/reviews/{$review->id}", [
                'comment' => 'Edited comment',
                'rating' => 4,
            ])
            ->assertOk()
            ->assertJsonPath('data.approved', false)
            ->assertJsonPath('data.comment', 'Edited comment');

        $review->refresh();
        $this->assertFalse((bool) $review->approved);
        $this->assertNull($review->approved_by);
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
            ->getJson("/api/v1/admin/reviews/{$review->id}/replies")
            ->assertOk()
            ->assertJsonPath('data.replies.0.comment', 'Admin reply');

        $this->actingAsAdminApiUser($admin)
            ->postJson("/api/v1/admin/review-replies/{$reply->id}/approve")
            ->assertOk()
            ->assertJsonPath('message', 'پاسخ با موفقیت تایید شد.');

        $pending = $review->replies()->create([
            'user_id' => $admin->id,
            'comment' => 'Pending reply',
        ]);

        $this->actingAsAdminApiUser($admin)
            ->deleteJson("/api/v1/admin/review-replies/{$pending->id}")
            ->assertOk();

        $this->assertDatabaseMissing('review_replies', ['id' => $pending->id]);
        $this->assertDatabaseHas('review_replies', ['id' => $reply->id]);
    }

    public function test_admin_cannot_delete_approved_reply_and_update_returns_it_to_pending(): void
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
        $reply = $review->replies()->create([
            'user_id' => $user->id,
            'comment' => 'Approved reply',
            'approved' => true,
            'approved_by' => $admin->name,
            'approved_at' => now(),
        ]);

        $this->actingAsAdminApiUser($admin)
            ->deleteJson("/api/v1/admin/review-replies/{$reply->id}")
            ->assertForbidden();

        $this->actingAsAdminApiUser($admin)
            ->putJson("/api/v1/admin/review-replies/{$reply->id}", [
                'comment' => 'Edited reply',
            ])
            ->assertOk()
            ->assertJsonPath('data.approved', false)
            ->assertJsonPath('data.comment', 'Edited reply');

        $this->assertFalse((bool) $reply->fresh()->approved);
        $this->assertDatabaseHas('review_replies', ['id' => $reply->id]);
    }
}
