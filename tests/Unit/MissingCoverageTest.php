<?php

namespace Tests\Unit;

use App\Http\Resources\TicketResponseResource;
use App\Models\Category;
use App\Models\Product;
use App\Models\Review;
use App\Models\ReviewReply;
use App\Models\Tag;
use App\Models\Ticket;
use App\Models\TicketResponse;
use App\Models\User;
use App\Parsian\RequestResponse;
use App\Policies\TagPolicy;
use App\Services\ReviewService;
use App\Services\TicketService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MissingCoverageTest extends TestCase
{
    use RefreshDatabase;

    public function test_ticket_response_resource_includes_loaded_user(): void
    {
        $user = User::factory()->create(['name' => 'Response User']);
        $ticket = Ticket::create([
            'user_id' => $user->id,
            'title' => 'Help',
            'message' => 'Body',
            'priority' => 'low',
        ]);
        $response = TicketResponse::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'message' => 'Reply',
            'attachment' => 'attachments/reply.pdf',
        ])->load('user');

        $data = (new TicketResponseResource($response))->toArray(Request::create('/'));

        $this->assertSame($user->id, $data['user']['id']);
        $this->assertSame('Response User', $data['user']['name']);
    }

    public function test_category_and_tag_urls_fall_back_to_application_url(): void
    {
        config(['app.frontend_url' => '']);
        $category = Category::factory()->create(['slug' => 'furniture']);
        $tag = Tag::factory()->create(['slug' => 'wood']);

        $this->assertStringContainsString('/categories/', (string) $category->toSitemapTag()->url);
        $this->assertSame(url('/tags/wood'), $tag->url);
    }

    public function test_failed_payment_response_returns_null_url(): void
    {
        $response = new RequestResponse((object) [
            'SalePaymentRequestResult' => (object) [
                'Status' => -1,
                'Message' => 'failed',
                'Token' => 0,
            ],
        ]);

        $this->assertNull($response->url());
    }

    public function test_non_admin_cannot_delete_tag(): void
    {
        $user = User::factory()->create();
        $tag = Tag::factory()->create();

        $this->assertTrue((new TagPolicy)->delete($user, $tag)->denied());
    }

    public function test_user_cannot_update_another_users_review(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $product = Product::factory()->create(['category_id' => Category::factory()->create()->id]);
        $review = Review::create([
            'product_id' => $product->id,
            'user_id' => $owner->id,
            'comment' => 'Original',
            'rating' => 5,
        ]);

        $this->expectException(HttpException::class);
        (new ReviewService)->update($other, $review, ['comment' => 'Changed', 'rating' => 4]);
    }

    public function test_user_cannot_update_another_users_review_reply(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $product = Product::factory()->create(['category_id' => Category::factory()->create()->id]);
        $review = Review::create([
            'product_id' => $product->id,
            'user_id' => $owner->id,
            'comment' => 'Review',
            'rating' => 5,
        ]);
        $reply = ReviewReply::create([
            'review_id' => $review->id,
            'user_id' => $owner->id,
            'comment' => 'Reply',
        ]);

        $this->expectException(HttpException::class);
        (new ReviewService)->updateReply($other, $reply, ['comment' => 'Changed']);
    }

    public function test_response_attachment_must_belong_to_ticket(): void
    {
        Storage::fake('local');
        $admin = User::factory()->admin()->create();
        $ticket = Ticket::create([
            'user_id' => $admin->id,
            'title' => 'Ticket',
            'message' => 'Body',
            'priority' => 'low',
        ]);
        $otherTicket = Ticket::create([
            'user_id' => $admin->id,
            'title' => 'Other',
            'message' => 'Body',
            'priority' => 'low',
        ]);
        $response = TicketResponse::create([
            'ticket_id' => $otherTicket->id,
            'user_id' => $admin->id,
            'message' => 'Reply',
            'attachment' => 'attachments/reply.pdf',
        ]);

        $this->actingAs($admin);
        $this->expectException(HttpException::class);
        (new TicketService)->downloadResponseAttachment($admin, $ticket, $response);
    }
}
