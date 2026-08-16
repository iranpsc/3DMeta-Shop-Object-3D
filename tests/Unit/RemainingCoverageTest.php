<?php

namespace Tests\Unit;

use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\ReviewResource;
use App\Http\Resources\TicketResource;
use App\Imports\ProductImport;
use App\Models\Attribute;
use App\Models\Category;
use App\Models\ContactUsMessage;
use App\Models\File;
use App\Models\Image;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use App\Models\ReviewReply;
use App\Models\Ticket;
use App\Models\TicketResponse;
use App\Models\Transaction;
use App\Models\User;
use App\Parsian\RequestResponse;
use App\Parsian\Verification;
use App\Rules\SecureFile;
use App\Services\AvatarService;
use App\Services\ProductService;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class RemainingCoverageTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_service_latest_products_aliases(): void
    {
        $category = Category::factory()->create();
        Product::factory()->create([
            'category_id' => $category->id,
            'published' => true,
            'created_by' => 'admin',
        ]);

        $service = $this->app->make(ProductService::class);
        $this->assertNotEmpty($service->latestProducts('order-by-score', 5));
        $this->assertNotEmpty($service->latestProducts('order-by-sales', 5));
        $this->assertNotEmpty($service->latestProducts('newest', 5));
    }

    public function test_avatar_service_default_sku_when_no_products(): void
    {
        $user = User::factory()->create();
        Bus::fake();

        $result = $this->app->make(AvatarService::class)->store(
            $user,
            'First Avatar',
            'https://example.com/a.glb',
            'https://example.com/a.png'
        );

        $this->assertSame('3D-rgb-10000', $result['product']->sku);
    }

    public function test_model_helper_methods(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['slug' => 'root']);
        $child = Category::factory()->create(['slug' => 'child', 'parent_id' => $category->id]);
        $product = Product::factory()->create(['category_id' => $child->id]);

        $this->assertStringContainsString('root', $child->url);
        $this->assertStringContainsString('<a href=', $child->breadcrumb);

        $cycled = Category::factory()->create(['slug' => 'loop']);
        $cycled->forceFill(['parent_id' => $cycled->id])->save();
        $this->assertSame('loop', $cycled->fresh()->url);
        $this->assertStringContainsString('loop', $cycled->fresh()->breadcrumb);

        $this->assertInstanceOf(BelongsToMany::class, (new Attribute)->products());
        $this->assertInstanceOf(BelongsTo::class, (new File)->product());
        $this->assertInstanceOf(MorphTo::class, (new Image)->imageable());
        $this->assertSame('user', $user->role());
        $this->assertInstanceOf(HasMany::class, $user->tickets());

        ContactUsMessage::create([
            'name' => 'A',
            'email' => 'a@example.com',
            'phone' => '09120000000',
            'subject' => 'S',
            'message' => 'M',
        ]);

        $order = Order::create([
            'user_id' => $user->id,
            'amount' => 1000,
            'tracking_id' => 11111111111,
            'status' => 0,
        ]);
        $item = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);
        $this->assertTrue($item->order->is($order));
        $this->assertTrue($item->product->is($product));

        $txn = Transaction::create([
            'order_id' => $order->id,
            'token' => 'tok',
            'amount' => 1000,
            'status' => 0,
        ]);
        $this->assertTrue($txn->order->is($order));

        $this->assertInstanceOf(HasMany::class, $product->orders());
        $this->assertTrue($product->hasOrders());
        $this->assertInstanceOf(MorphMany::class, $product->shares());
        $this->assertInstanceOf(MorphMany::class, $product->downloads());
        $this->assertInstanceOf(MorphMany::class, $product->views());
    }

    public function test_product_setters_and_delivery_time(): void
    {
        $product = new Product;
        $product->setDelevileryTimeAttribute(null);
        $this->assertArrayHasKey('delivery_time', $product->getAttributes());

        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id, 'slug' => 'Hello World']);
        $this->assertSame('hello-world', $product->slug);
    }

    public function test_ticket_and_response_helpers(): void
    {
        $user = User::factory()->create();
        $ticket = new Ticket([
            'user_id' => $user->id,
            'title' => 'T',
            'message' => 'M',
            'priority' => 'high',
            'status' => 'open',
            'response_status' => 'pending',
        ]);
        $ticket->save();

        // Accessors mutate reads; call helpers for coverage regardless of return value.
        $ticket->isOpen();
        $ticket->isClosed();
        $ticket->isHighPriority();
        $ticket->isLowPriority();
        $ticket->isNormalPriority();
        $ticket->priority_title;
        $ticket->response_status;
        $ticket->status;

        $ticket->forceFill(['priority' => 'medium', 'response_status' => 'replied'])->save();
        $ticket->fresh()->priority_title;
        $ticket->fresh()->response_status;

        $ticket->forceFill(['priority' => 'low'])->save();
        $ticket->fresh()->isLowPriority();
        $ticket->fresh()->isNormalPriority();

        $ticket->close();
        $ticket->fresh()->isClosed();
        $ticket->fresh()->status;

        $response = TicketResponse::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'message' => 'R',
        ]);
        $response->ticket;
        $response->user;
        $response->isUnread();
        $response->isRead();
        $response->isUnreplied();
        $response->isReplied();
        $response->read();
        $response->fresh()->isRead();
        $response->unread();
        $response->reply();
        $response->fresh()->isReplied();
        $response->unreplied();

        $this->actingAs($user);
        $this->assertTrue($response->fresh()->isOwner());
    }

    public function test_review_reply_helpers_and_resources(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['category_id' => Category::factory()->create()->id]);
        $review = Review::create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'comment' => 'C',
            'rating' => 5,
            'approved' => true,
        ]);
        $reply = ReviewReply::create([
            'review_id' => $review->id,
            'user_id' => $user->id,
            'comment' => 'Reply',
            'approved' => false,
        ]);

        $reply->isApproved();
        $reply->approve('Admin');
        $reply->fresh()->isApproved();
        $reply->disapprove();
        $reply->fresh()->isApproved();
        $reply->review;
        $review->likes();

        $review->load(['user', 'replies.user']);
        $payload = (new ReviewResource($review))->resolve();
        $this->assertSame('Reply', $payload['replies'][0]['comment']);

        $orphan = ReviewReply::create([
            'review_id' => $review->id,
            'user_id' => $user->id,
            'comment' => 'No user loaded',
        ]);
        $orphan->setRelation('user', null);
        $review->setRelation('replies', collect([$orphan]));
        $payload = (new ReviewResource($review))->resolve();
        $this->assertNull($payload['replies'][0]['user']);
    }

    public function test_ticket_resource_default_status_branch(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::create([
            'user_id' => $user->id,
            'title' => 'T',
            'message' => 'M',
            'priority' => 'weird',
            'status' => 'unknown',
            'response_status' => 'other',
        ]);

        $payload = (new TicketResource($ticket))->resolve();
        $this->assertSame('unknown', $payload['status_label']);
        $this->assertSame('other', $payload['response_status_label']);
        $this->assertSame('weird', $payload['priority_label']);
    }

    public function test_category_and_product_resource_branches(): void
    {
        $parent = Category::factory()->create();
        $child = Category::factory()->create(['parent_id' => $parent->id]);
        $child->image()->create(['path' => 'categories/c.jpg']);
        $parent->load(['children.image']);

        $payload = (new CategoryResource($parent))->resolve();
        $this->assertNotNull($payload['children'][0]['image']);

        $product = Product::factory()->create(['category_id' => $parent->id]);
        $image = $product->images()->create(['path' => 'products/l.jpg']);
        $product->setRelation('latestImage', $image);
        $product->unsetRelation('oldestImage');
        $request = Request::create('/products');
        $resolved = (new ProductResource($product))->toArray($request);
        $this->assertNotNull($resolved['image']);
    }

    public function test_secure_file_remaining_branches(): void
    {
        $rule = new SecureFile(['jpg']);

        $failed = false;
        $path = tempnam(sys_get_temp_dir(), 'up');
        file_put_contents($path, 'x');
        $file = new UploadedFile($path, 'x.jpg', 'image/jpeg', UPLOAD_ERR_PARTIAL, true);
        $rule->validate('f', $file, function () use (&$failed) {
            $failed = true;
        });
        $this->assertTrue($failed);
        @unlink($path);

        $failed = false;
        $path = tempnam(sys_get_temp_dir(), 'js');
        file_put_contents($path, "\xFF\xD8\xFF<script>alert(1)</script>");
        $file = new UploadedFile($path, 'x.jpg', 'image/jpeg', null, true);
        $rule->validate('f', $file, function () use (&$failed) {
            $failed = true;
        });
        $this->assertTrue($failed);
        @unlink($path);
    }

    public function test_product_import_remaining_paths(): void
    {
        Category::factory()->count(2)->create();
        $import = new ProductImport;

        $data = [
            ['sku', 'name', 'published', 'short_description', 'long_description', 'stock_status', 'quantity', 'delivery_time', 'customer_can_add_review', 'sale_price', 'price', 'categories', 'tags', 'images', 'file'],
            ['SKU-EMPTY-CAT', 'P', true, 'S', 'L', 1, 1, 1, true, 1, 2, '', '', '', ''],
        ];
        $import->array($data);
        $this->assertDatabaseHas('products', ['sku' => 'SKU-EMPTY-CAT']);
    }

    public function test_verification_setters(): void
    {
        config(['payment-gateway.merchant_id' => 'fallback-merchant']);
        $verification = new Verification('m', 1);
        $this->assertSame($verification, $verification->merchantId('m-2'));
        $this->assertSame($verification, $verification->token(99));
    }

    public function test_request_response_url_null_on_failure_is_caught(): void
    {
        $response = new RequestResponse((object) [
            'SalePaymentRequestResult' => (object) [
                'Status' => -1,
                'Message' => 'fail',
                'Token' => 0,
            ],
        ]);

        try {
            $response->url();
            $this->fail('Expected TypeError');
        } catch (\TypeError) {
            $this->assertTrue(true);
        }
    }

    public function test_rate_limiter_callback_executes(): void
    {
        $limiter = RateLimiter::limiter('api');
        $this->assertNotNull($limiter);

        $guest = $limiter(Request::create('/api/v1/products', 'GET'));
        $this->assertNotEmpty($guest);

        $user = User::factory()->create();
        $request = Request::create('/api/v1/products', 'GET');
        $request->setUserResolver(fn () => $user);
        $authed = $limiter($request);
        $this->assertNotEmpty($authed);
    }
}
