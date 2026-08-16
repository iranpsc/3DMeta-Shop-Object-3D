<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_products_index_supports_search_category_tag_and_pagination(): void
    {
        $category = Category::factory()->create(['slug' => 'furniture']);
        $otherCategory = Category::factory()->create(['slug' => 'other']);
        $tag = Tag::factory()->create(['slug' => 'wood']);

        $match = Product::factory()->create([
            'category_id' => $category->id,
            'published' => true,
            'created_by' => 'admin',
            'name' => 'Wood Chair',
            'sku' => 'SKU-WOOD-1',
        ]);
        $match->tags()->attach($tag);

        Product::factory()->create([
            'category_id' => $otherCategory->id,
            'published' => true,
            'created_by' => 'admin',
            'name' => 'Metal Table',
            'sku' => 'SKU-METAL-1',
        ]);

        $this->getJson('/api/v1/products?search=Wood&category=furniture&tag=wood&page=1')
            ->assertOk()
            ->assertJsonPath('data.0.id', $match->id)
            ->assertJsonStructure([
                'data',
                'meta' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ],
            ]);
    }

    public function test_product_show_by_sku_includes_relations(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'published' => true,
            'created_by' => 'admin',
            'sku' => 'SHOW-123',
            'name' => 'Detail Product',
            'short_description' => 'Short text',
            'long_description' => 'Long text',
        ]);

        $this->getJson('/api/v1/products/SHOW-123')
            ->assertOk()
            ->assertJsonPath('data.sku', 'SHOW-123')
            ->assertJsonPath('data.name', 'Detail Product')
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'sku',
                    'name',
                    'short_description',
                    'long_description',
                    'price',
                    'final_price',
                    'is_free',
                    'category',
                    'tags',
                    'images',
                    'files',
                    'attributes',
                    'rating_avg',
                    'approved_reviews_count',
                    'similar_products',
                ],
            ]);
    }

    public function test_product_show_returns_404_for_unknown_sku(): void
    {
        $this->getJson('/api/v1/products/missing-sku')
            ->assertNotFound();
    }

    public function test_guest_cannot_create_review(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'published' => true,
            'created_by' => 'admin',
            'sku' => 'REV-1',
            'price' => 0,
            'customer_can_add_review' => true,
        ]);

        $this->postJson('/api/v1/products/REV-1/reviews', [
            'comment' => 'This is a review',
            'rating' => 5,
        ])->assertUnauthorized();
    }

    public function test_authenticated_user_can_review_free_product(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'published' => true,
            'created_by' => 'admin',
            'sku' => 'REV-FREE',
            'price' => 0,
            'customer_can_add_review' => true,
        ]);

        $this->actingAs($user)
            ->postJson('/api/v1/products/REV-FREE/reviews', [
                'comment' => 'This is a review',
                'rating' => 4,
            ])
            ->assertCreated()
            ->assertJsonPath('message', 'نظر شما با موفقیت ثبت شد و پس از تایید نمایش داده خواهد شد.');

        $this->assertDatabaseHas('reviews', [
            'product_id' => $product->id,
            'user_id' => $user->id,
            'comment' => 'This is a review',
            'rating' => 4,
        ]);
    }

    public function test_product_reviews_list_returns_approved_only(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'published' => true,
            'created_by' => 'admin',
            'sku' => 'REV-LIST',
        ]);

        $product->reviews()->create([
            'user_id' => User::factory()->create()->id,
            'comment' => 'Approved review text',
            'rating' => 5,
            'approved' => true,
        ]);

        // Bypass approved scope on relation for pending review
        Review::query()->create([
            'product_id' => $product->id,
            'user_id' => User::factory()->create()->id,
            'comment' => 'Pending review text',
            'rating' => 3,
            'approved' => false,
        ]);

        $response = $this->getJson('/api/v1/products/REV-LIST/reviews')
            ->assertOk();

        $comments = collect($response->json('data.reviews'))->pluck('comment')->all();

        $this->assertContains('Approved review text', $comments);
        $this->assertNotContains('Pending review text', $comments);
        $this->assertArrayHasKey('rating_breakdown', $response->json('data'));
    }

    public function test_authenticated_user_can_reply_to_review(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'published' => true,
            'created_by' => 'admin',
            'sku' => 'REV-REPLY',
        ]);

        $review = Review::query()->create([
            'product_id' => $product->id,
            'user_id' => User::factory()->create()->id,
            'comment' => 'Parent review text',
            'rating' => 5,
            'approved' => true,
        ]);

        $this->actingAs($user)
            ->postJson("/api/v1/reviews/{$review->id}/replies", [
                'comment' => 'This is a reply comment',
            ])
            ->assertCreated()
            ->assertJsonPath('message', 'پاسخ شما با موفقیت ثبت شد و پس از تایید نمایش داده خواهد شد.');

        $this->assertDatabaseHas('review_replies', [
            'review_id' => $review->id,
            'user_id' => $user->id,
            'comment' => 'This is a reply comment',
        ]);
    }

    public function test_guest_product_show_hides_download_urls(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'published' => true,
            'created_by' => 'admin',
            'sku' => 'DL-GUEST',
            'price' => 0,
            'sale_price' => 0,
        ]);

        $product->files()->create([
            'name' => 'model.zip',
            'path' => 'products/model.zip',
            'type' => 'application/zip',
            'size' => '1 MB',
        ]);

        $this->getJson('/api/v1/products/DL-GUEST')
            ->assertOk()
            ->assertJsonMissingPath('data.user_can_download')
            ->assertJsonPath('data.files.0.url', null);
    }

    public function test_authenticated_free_product_user_can_download_with_signed_url(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'published' => true,
            'created_by' => 'admin',
            'sku' => 'DL-FREE',
            'price' => 0,
            'sale_price' => 0,
        ]);

        $product->files()->create([
            'name' => 'free-model.zip',
            'path' => 'products/free-model.zip',
            'type' => 'application/zip',
            'size' => '2 MB',
        ]);

        $response = $this->actingAsVerifiedApiUser($user)
            ->getJson('/api/v1/products/DL-FREE')
            ->assertOk()
            ->assertJsonPath('data.user_can_download', true);

        $url = $response->json('data.files.0.url');
        $this->assertIsString($url);
        $this->assertStringContainsString('/download/', $url);
        $this->assertStringContainsString('signature=', $url);
    }

    public function test_authenticated_purchased_user_can_download_paid_product(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'published' => true,
            'created_by' => 'admin',
            'sku' => 'DL-PAID',
            'price' => 100000,
        ]);

        $product->files()->create([
            'name' => 'paid-model.zip',
            'path' => 'products/paid-model.zip',
            'type' => 'application/zip',
            'size' => '3 MB',
        ]);

        $order = Order::create([
            'user_id' => $user->id,
            'amount' => 100000,
            'tracking_id' => random_int(10000000000, 99999999999),
            'status' => 0,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->actingAsVerifiedApiUser($user)
            ->getJson('/api/v1/products/DL-PAID')
            ->assertOk()
            ->assertJsonPath('data.user_can_download', true)
            ->assertJsonStructure([
                'data' => [
                    'files' => [['id', 'name', 'url']],
                ],
            ]);
    }

    public function test_authenticated_user_without_purchase_cannot_download_paid_product(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'published' => true,
            'created_by' => 'admin',
            'sku' => 'DL-NOPAY',
            'price' => 100000,
        ]);

        $product->files()->create([
            'name' => 'locked-model.zip',
            'path' => 'products/locked-model.zip',
            'type' => 'application/zip',
            'size' => '3 MB',
        ]);

        $this->actingAsVerifiedApiUser($user)
            ->getJson('/api/v1/products/DL-NOPAY')
            ->assertOk()
            ->assertJsonPath('data.user_can_download', false)
            ->assertJsonPath('data.files.0.url', null);
    }

    public function test_store_filters_returns_categories(): void
    {
        $category = Category::factory()->create(['slug' => 'store-filter']);
        Product::factory()->create([
            'category_id' => $category->id,
            'published' => true,
            'created_by' => 'admin',
        ]);

        $this->getJson('/api/v1/products/store-filters')
            ->assertOk()
            ->assertJsonPath('data.categories.0.slug', 'store-filter');
    }

    public function test_products_index_supports_price_range_and_sorts(): void
    {
        $category = Category::factory()->create();

        $cheap = Product::factory()->create([
            'category_id' => $category->id,
            'published' => true,
            'created_by' => 'admin',
            'name' => 'Cheap',
            'sku' => 'CHEAP-1',
            'price' => 10000,
        ]);
        $expensive = Product::factory()->create([
            'category_id' => $category->id,
            'published' => true,
            'created_by' => 'admin',
            'name' => 'Expensive',
            'sku' => 'EXP-1',
            'price' => 500000,
        ]);

        $this->getJson('/api/v1/products?price_min=5000&price_max=20000&sort=cheapest')
            ->assertOk()
            ->assertJsonPath('data.0.sku', 'CHEAP-1');

        $this->getJson('/api/v1/products?sort=most-expensive')
            ->assertOk()
            ->assertJsonPath('data.0.sku', 'EXP-1');

        $this->getJson('/api/v1/products?sort=most-sales')
            ->assertOk();

        $this->assertNotNull($cheap->id);
        $this->assertNotNull($expensive->id);
    }

    public function test_products_index_supports_tags_array_filter(): void
    {
        $category = Category::factory()->create();
        $tag = Tag::factory()->create(['slug' => 'metal']);
        $match = Product::factory()->create([
            'category_id' => $category->id,
            'published' => true,
            'created_by' => 'admin',
            'sku' => 'TAG-ARR-1',
        ]);
        $match->tags()->attach($tag);

        $this->getJson('/api/v1/products?tags[]=metal')
            ->assertOk()
            ->assertJsonPath('data.0.sku', 'TAG-ARR-1');
    }

    public function test_product_show_returns_empty_similar_when_no_category(): void
    {
        $product = Product::factory()->create([
            'category_id' => null,
            'published' => true,
            'created_by' => 'admin',
            'sku' => 'NO-CAT',
        ]);

        // Force null category if factory always sets one
        $product->forceFill(['category_id' => null])->save();

        $this->getJson('/api/v1/products/NO-CAT')
            ->assertOk()
            ->assertJsonPath('data.similar_products', []);
    }

    public function test_purchased_user_can_review_paid_product(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'published' => true,
            'created_by' => 'admin',
            'sku' => 'REV-PAID',
            'price' => 100000,
            'customer_can_add_review' => true,
        ]);
        $user->products()->attach($product->id, ['quantity' => 1]);

        $this->actingAs($user)
            ->postJson('/api/v1/products/REV-PAID/reviews', [
                'comment' => 'Bought and reviewed',
                'rating' => 5,
            ])
            ->assertCreated();

        $this->assertDatabaseHas('reviews', [
            'product_id' => $product->id,
            'user_id' => $user->id,
            'comment' => 'Bought and reviewed',
        ]);
    }
}
