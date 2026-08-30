<?php

namespace Tests\Unit;

use App\Http\Resources\ProductResource;
use App\Http\Resources\TicketResource;
use App\Imports\ProductImport;
use App\Models\Attribute;
use App\Models\Category;
use App\Models\ContactUsMessage;
use App\Models\Order;
use App\Models\Product;
use App\Models\Tag;
use App\Models\Ticket;
use App\Models\Transaction;
use App\Models\User;
use App\Rules\SecureFile;
use App\Services\AdminProductService;
use App\Services\CheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use ReflectionMethod;
use Tests\TestCase;

class FinalCoverageGapsTest extends TestCase
{
    use RefreshDatabase;

    public function test_update_admin_product_validation_closures(): void
    {
        Storage::fake('public');
        $category = Category::factory()->create();
        $tag = Tag::factory()->create();
        $attribute = Attribute::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'sku' => '3D-rgb-50001',
            'slug' => 'update-validation',
        ]);

        $this->actingAsAdminApiUser()
            ->putJson('/api/v1/admin/products/'.$product->id, [
                'category_id' => $category->id,
                'sku' => '3D-rgb-50001',
                'name' => 'Bad Qty',
                'slug' => 'update-validation',
                'short_description' => 'Short',
                'long_description' => 'Long description',
                'stock_status' => false,
                'quantity' => 3,
                'delivery_time' => 0,
                'customer_can_add_review' => true,
                'price' => 100000,
                'sale_price' => 90000,
                'published' => true,
                'meta_description' => 'Meta',
                'meta_keywords' => 'meta',
                'tags' => [$tag->id],
                'attributes' => [['id' => $attribute->id, 'value' => 'X']],
                'files' => [[
                    'path' => 'upload/x/',
                    'name' => 'bad.exe',
                    'mime_type' => 'application/octet-stream',
                    'size' => '1 KB',
                ]],
            ])
            ->assertStatus(422);

        $this->actingAsAdminApiUser()
            ->putJson('/api/v1/admin/products/'.$product->id, [
                'category_id' => $category->id,
                'sku' => '3D-rgb-50001',
                'name' => 'Bad Qty True',
                'slug' => 'update-validation',
                'short_description' => 'Short',
                'long_description' => 'Long description',
                'stock_status' => true,
                'quantity' => 0,
                'delivery_time' => 0,
                'customer_can_add_review' => true,
                'price' => 100000,
                'sale_price' => 90000,
                'published' => true,
                'meta_description' => 'Meta',
                'meta_keywords' => 'meta',
                'tags' => [$tag->id],
                'attributes' => [['id' => $attribute->id, 'value' => 'X']],
            ])
            ->assertStatus(422);
    }

    public function test_product_resource_oldest_image_branch(): void
    {
        $product = Product::factory()->create([
            'category_id' => Category::factory()->create()->id,
        ]);
        $image = $product->images()->create(['path' => 'products/oldest.jpg']);
        $product->setRelation('oldestImage', $image);
        $product->unsetRelation('latestImage');

        $resolved = (new ProductResource($product))->toArray(Request::create('/'));
        $this->assertNotNull($resolved['image']);
    }

    public function test_ticket_resource_closed_label(): void
    {
        $ticket = Ticket::create([
            'user_id' => User::factory()->create()->id,
            'title' => 'T',
            'message' => 'M',
            'priority' => 'low',
            'status' => 'closed',
            'response_status' => 'pending',
        ]);

        $payload = (new TicketResource($ticket))->resolve();
        $this->assertSame('بسته', $payload['status_label']);
    }

    public function test_ticket_resource_attachment_name(): void
    {
        $ticket = Ticket::create([
            'user_id' => User::factory()->create()->id,
            'title' => 'T',
            'message' => 'M',
            'priority' => 'low',
            'status' => 'open',
            'response_status' => 'pending',
            'attachment' => 'tickets/uploads/report.pdf',
        ]);

        $payload = (new TicketResource($ticket))->resolve();
        $this->assertSame('report.pdf', $payload['attachment_name']);
    }

    public function test_product_import_empty_sku_existing_attribute_and_path_traversal_file(): void
    {
        Category::factory()->create();
        Attribute::factory()->create(['name' => 'Color', 'slug' => 'color']);
        $import = new ProductImport;

        $data = [
            ['sku', 'name', 'published', 'short_description', 'long_description', 'stock_status', 'quantity', 'delivery_time', 'customer_can_add_review', 'sale_price', 'price', 'categories', 'tags', 'images', 'file', 'attr', 'val', 'display'],
            ['', 'Skip', true, 'S', 'L', 1, 1, 1, true, 1, 2, 'Cat', 'Tag', 'img.jpg', 'file.pdf', '', '', ''],
            ['SKU-ATTR', 'WithAttr', true, 'S', 'L', 1, 1, 1, true, 1, 2, 'Cat2', 'Tag2', 'img2.jpg', '../evil.pdf', 'Color', 'Red', 1],
        ];
        $import->array($data);

        $this->assertDatabaseHas('products', ['sku' => 'SKU-ATTR']);
        $this->assertDatabaseMissing('files', ['path' => '../evil.pdf']);
    }

    public function test_category_cycle_break_in_url_and_breadcrumb(): void
    {
        $a = Category::factory()->create(['slug' => 'a']);
        $b = Category::factory()->create(['slug' => 'b', 'parent_id' => $a->id]);
        // Create a cycle a -> b -> a
        $a->forceFill(['parent_id' => $b->id])->save();

        $url = $b->fresh()->url;
        $breadcrumb = $b->fresh()->breadcrumb;
        $this->assertIsString($url);
        $this->assertIsString($breadcrumb);
    }

    public function test_contact_message_mark_as_read(): void
    {
        $message = ContactUsMessage::create([
            'name' => 'A',
            'email' => 'a@b.com',
            'phone' => '09121111111',
            'subject' => 'S',
            'message' => 'M',
            'is_read' => false,
        ]);
        $message->markAsRead();
        $this->assertTrue($message->fresh()->is_read);
    }

    public function test_ticket_priority_title_low_break(): void
    {
        $ticket = Ticket::create([
            'user_id' => User::factory()->create()->id,
            'title' => 'T',
            'message' => 'M',
            'priority' => 'low',
            'status' => 'open',
            'response_status' => 'pending',
        ]);
        $this->assertSame('پایین', $ticket->priority_title);
    }

    public function test_transaction_pending_scope_and_user_transactions(): void
    {
        $user = User::factory()->create();
        $order = Order::create([
            'user_id' => $user->id,
            'amount' => 1,
            'tracking_id' => 22222222222,
            'status' => -1,
        ]);
        Transaction::create([
            'order_id' => $order->id,
            'token' => 'p1',
            'amount' => 1,
            'status' => 'pending',
        ]);

        $this->assertTrue(Transaction::pending()->exists());
        $this->assertTrue($user->transactions()->exists());
    }

    public function test_secure_file_scan_exception_branch(): void
    {
        $rule = new SecureFile(['txt']);
        $file = new class extends UploadedFile
        {
            public function __construct() {}

            public function getError(): int
            {
                return UPLOAD_ERR_OK;
            }

            public function getSize(): int
            {
                return 10;
            }

            public function getClientOriginalExtension(): string
            {
                return 'txt';
            }

            public function getClientOriginalName(): string
            {
                return 'note.txt';
            }

            public function getRealPath(): string|false
            {
                return '/tmp/missing-secure-file-scan.txt';
            }

            public function getMimeType(): ?string
            {
                return 'text/plain';
            }
        };

        $failed = null;
        $rule->validate('f', $file, function ($message) use (&$failed) {
            $failed = $message;
        });

        $this->assertNotNull($failed);
    }

    public function test_admin_product_service_is_inside_upload_directory_returns_false_for_missing_path(): void
    {
        $service = $this->app->make(AdminProductService::class);
        $method = new ReflectionMethod(AdminProductService::class, 'isInsideUploadDirectory');
        $method->setAccessible(true);

        $result = $method->invoke(
            $service,
            storage_path('app/upload/does-not-exist/'.uniqid('missing-', true))
        );

        $this->assertFalse($result);
    }

    public function test_checkout_prepare_order_items_skips_missing_cart_products(): void
    {
        $product = Product::factory()->create([
            'category_id' => Category::factory()->create()->id,
            'published' => true,
            'created_by' => 'admin',
            'price' => 1000,
            'sale_price' => 1000,
        ]);
        $extra = Product::factory()->create([
            'category_id' => $product->category_id,
            'published' => true,
            'created_by' => 'admin',
            'price' => 1000,
            'sale_price' => 1000,
        ]);

        $service = $this->app->make(CheckoutService::class);
        $method = new ReflectionMethod(CheckoutService::class, 'prepareOrderItems');
        $method->setAccessible(true);

        $items = $method->invoke(
            $service,
            'order-id',
            [['product_id' => $product->id, 'quantity' => 1]],
            collect([$product, $extra])
        );

        $this->assertCount(1, $items);
        $this->assertSame($product->id, $items[0]['product_id']);
    }
}
