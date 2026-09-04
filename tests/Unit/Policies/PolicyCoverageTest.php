<?php

namespace Tests\Unit\Policies;

use App\Models\Attribute;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\ReviewReply;
use App\Models\Tag;
use App\Models\Ticket;
use App\Models\User;
use App\Policies\AttributePolicy;
use App\Policies\CategoryPolicy;
use App\Policies\OrderPolicy;
use App\Policies\ProductPolicy;
use App\Policies\ReviewPolicy;
use App\Policies\ReviewReplyPolicy;
use App\Policies\TagPolicy;
use App\Policies\TicketPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PolicyCoverageTest extends TestCase
{
    use RefreshDatabase;

    public function test_tag_policy_branches(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        $tag = Tag::factory()->create();
        $policy = new TagPolicy;

        $this->assertTrue($policy->create($admin));
        $this->assertFalse($policy->create($user));
        $this->assertTrue($policy->update($admin, $tag));
        $this->assertTrue($policy->delete($admin, $tag)->allowed());

        $product = Product::factory()->create(['category_id' => Category::factory()->create()->id]);
        $product->tags()->attach($tag);
        $this->assertTrue($policy->delete($admin, $tag->fresh())->denied());
    }

    public function test_attribute_policy_branches(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        $attribute = Attribute::factory()->create();
        $policy = new AttributePolicy;

        $this->assertTrue($policy->create($admin));
        $this->assertFalse($policy->create($user));
        $this->assertTrue($policy->update($admin, $attribute));
        $this->assertTrue($policy->delete($admin, $attribute));
        $this->assertFalse($policy->delete($user, $attribute));

        $product = Product::factory()->create(['category_id' => Category::factory()->create()->id]);
        $product->attributes()->attach($attribute->id, ['value' => 'Large']);
        $this->assertFalse($policy->delete($admin, $attribute->fresh()));
    }

    public function test_product_policy_review_admin_abilities(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        $policy = new ProductPolicy;

        $this->assertTrue($policy->approveReview($admin));
        $this->assertFalse($policy->approveReview($user));
        $this->assertTrue($policy->deleteReview($admin));
        $this->assertFalse($policy->deleteReview($user));
        $this->assertTrue($policy->import($admin));
        $this->assertFalse($policy->import($user));
    }

    public function test_ticket_policy_respond_for_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->create();
        $ticket = Ticket::create([
            'user_id' => $owner->id,
            'title' => 'T',
            'message' => 'M',
            'priority' => 'low',
        ]);

        $policy = new TicketPolicy;
        $this->assertTrue($policy->view($admin, $ticket));
        $this->assertTrue($policy->respond($admin, $ticket));
        $this->assertFalse($policy->update($admin, $ticket));
    }

    public function test_category_policy_branches(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $policy = new CategoryPolicy;

        $this->assertTrue($policy->create($admin)->allowed());
        $this->assertTrue($policy->create($user)->denied());
        $this->assertTrue($policy->update($admin, $category)->allowed());
        $this->assertTrue($policy->update($user, $category)->denied());
        $this->assertTrue($policy->delete($admin, $category)->allowed());

        $childParent = Category::factory()->create();
        Category::factory()->create(['parent_id' => $childParent->id]);
        $this->assertTrue($policy->delete($admin, $childParent->fresh())->denied());

        Product::factory()->create(['category_id' => $category->id]);
        $this->assertTrue($policy->delete($admin, $category->fresh())->denied());
        $this->assertTrue($policy->delete($user, $category)->denied());
    }

    public function test_order_policy_branches(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $paidOrder = Order::create([
            'user_id' => $owner->id,
            'amount' => 1000,
            'tracking_id' => 11111111111,
            'status' => 0,
        ]);
        $unpaidOrder = Order::create([
            'user_id' => $owner->id,
            'amount' => 1000,
            'tracking_id' => 22222222222,
            'status' => -1,
        ]);
        $policy = new OrderPolicy;

        $this->assertTrue($policy->view($owner, $paidOrder));
        $this->assertFalse($policy->view($owner, $unpaidOrder));
        $this->assertFalse($policy->view($other, $paidOrder));
        $this->assertTrue($policy->pay($owner, $unpaidOrder));
        $this->assertFalse($policy->pay($owner, $paidOrder));
        $this->assertFalse($policy->pay($other, $unpaidOrder));
    }

    public function test_product_policy_remaining_branches(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'price' => 1000,
            'customer_can_add_review' => true,
        ]);
        $policy = new ProductPolicy;

        $this->assertTrue($policy->create($admin));
        $this->assertFalse($policy->create($user));
        $this->assertTrue($policy->update($admin, $product));
        $this->assertFalse($policy->update($user, $product));
        $this->assertTrue($policy->delete($admin, $product));
        $this->assertFalse($policy->delete($user, $product));
        $this->assertFalse($policy->download($user, $product));

        $user->products()->attach($product->id, ['quantity' => 1]);
        $this->assertFalse($policy->delete($admin, $product->fresh()));

        $freeProduct = Product::factory()->create([
            'category_id' => $category->id,
            'price' => 0,
            'customer_can_add_review' => true,
        ]);
        $this->assertTrue($policy->download($user, $freeProduct));
        $this->assertTrue($policy->addReview($user, $freeProduct)->allowed());

        $this->assertTrue($policy->addReview($user, $product)->allowed());

        Review::create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'comment' => 'Already reviewed',
            'rating' => 4,
        ]);
        $this->assertTrue($policy->addReview($user, $product->fresh())->denied());

        $closedReviews = Product::factory()->create([
            'category_id' => $category->id,
            'price' => 0,
            'customer_can_add_review' => false,
        ]);
        $this->assertTrue($policy->addReview($user, $closedReviews)->denied());
    }

    public function test_ticket_policy_remaining_branches(): void
    {
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $ticket = Ticket::create([
            'user_id' => $owner->id,
            'title' => 'T',
            'message' => 'M',
            'priority' => 'low',
        ]);
        $policy = new TicketPolicy;

        $this->actingAs($owner);
        $this->assertTrue($policy->create($owner));
        $this->assertTrue($policy->view($owner, $ticket));
        $this->assertFalse($policy->view($other, $ticket));
        $this->assertTrue($policy->update($owner, $ticket));
        $this->assertFalse($policy->update($other, $ticket));
        $this->assertTrue($policy->delete($admin, $ticket));
        $this->assertFalse($policy->delete($owner, $ticket));
    }

    public function test_review_policy_delete_branches(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        $product = Product::factory()->create(['category_id' => Category::factory()->create()->id]);
        $review = Review::create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'comment' => 'Pending',
            'rating' => 3,
        ]);
        $policy = new ReviewPolicy;

        $this->assertTrue($policy->delete($admin, $review));
        $this->assertFalse($policy->delete($user, $review));

        $review->approve($admin->name);
        $this->assertFalse($policy->delete($admin, $review->fresh()));
    }

    public function test_review_reply_policy_delete_branches(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        $product = Product::factory()->create(['category_id' => Category::factory()->create()->id]);
        $review = Review::create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'comment' => 'Pending',
            'rating' => 3,
        ]);
        $reply = ReviewReply::create([
            'review_id' => $review->id,
            'user_id' => $admin->id,
            'comment' => 'Reply',
        ]);
        $policy = new ReviewReplyPolicy;

        $this->assertTrue($policy->delete($admin, $reply));
        $this->assertFalse($policy->delete($user, $reply));

        $reply->approve($admin->name);
        $this->assertFalse($policy->delete($admin, $reply->fresh()));
    }
}
