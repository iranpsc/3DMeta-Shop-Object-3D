<?php

namespace Tests\Unit\Policies;

use App\Models\Attribute;
use App\Models\Category;
use App\Models\Product;
use App\Models\Tag;
use App\Models\Ticket;
use App\Models\User;
use App\Policies\AttributePolicy;
use App\Policies\ProductPolicy;
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
}
