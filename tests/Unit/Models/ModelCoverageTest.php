<?php

namespace Tests\Unit\Models;

use App\Models\Category;
use App\Models\Interaction;
use App\Models\Product;
use App\Models\Tag;
use App\Models\User;
use App\Notifications\TicketResponse as TicketResponseNotification;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Sitemap\Tags\Url;
use Tests\TestCase;

class ModelCoverageTest extends TestCase
{
    use RefreshDatabase;

    public function test_sitemap_tags_for_product_category_and_tag(): void
    {
        $category = Category::factory()->create(['slug' => 'furniture']);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'sku' => 'SM-1',
            'published' => true,
        ]);
        $tag = Tag::factory()->create(['slug' => 'wood']);

        $this->assertInstanceOf(Url::class, $product->toSitemapTag());
        $this->assertInstanceOf(Url::class, $category->toSitemapTag());
        $this->assertInstanceOf(Url::class, $tag->toSitemapTag());
    }

    public function test_interaction_scopes(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'category_id' => Category::factory()->create()->id,
        ]);

        $interaction = Interaction::create([
            'user_id' => $user->id,
            'interactable_id' => $product->id,
            'interactable_type' => Product::class,
            'type' => 'like',
            'ip' => '127.0.0.1',
        ]);

        $this->assertTrue(
            Interaction::query()->type('like')->user($user)->ip('127.0.0.1')->interactable($product)->whereKey($interaction->id)->exists()
        );
        $this->assertInstanceOf(Product::class, $interaction->interactable);
        $this->assertTrue($interaction->user->is($user));
    }

    public function test_ticket_response_notification_payload(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::create([
            'user_id' => $user->id,
            'title' => 'Help',
            'message' => 'Body',
            'priority' => 'low',
        ]);

        $notification = new TicketResponseNotification($ticket);

        $this->assertSame(['database'], $notification->via($user));
        $payload = $notification->toArray($user);
        $this->assertSame('پاسخ جدید', $payload['title']);
        $this->assertStringContainsString((string) $ticket->id, $payload['url']);
    }
}
