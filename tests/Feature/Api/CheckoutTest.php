<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use App\Services\CheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    private function createProduct(array $overrides = []): Product
    {
        $category = Category::factory()->create();

        return Product::factory()->create(array_merge([
            'category_id' => $category->id,
            'published' => true,
            'created_by' => 'admin',
            'price' => 100000,
            'sale_price' => 80000,
        ], $overrides));
    }

    public function test_guest_checkout_returns_create_account_step(): void
    {
        $product = $this->createProduct();

        $this->withHeaders($this->statefulApiHeaders())
            ->withSession([
                'cart' => [
                    ['product_id' => $product->id, 'quantity' => 1],
                ],
            ])
            ->getJson('/api/v1/checkout')
            ->assertOk()
            ->assertJsonPath('data.step', 'create-account')
            ->assertJsonPath('data.count', 1);
    }

    public function test_authenticated_checkout_returns_payment_step(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct();

        $this->actingAs($user)
            ->withHeaders($this->statefulApiHeaders())
            ->withSession([
                'cart' => [
                    ['product_id' => $product->id, 'quantity' => 1],
                ],
            ])
            ->getJson('/api/v1/checkout')
            ->assertOk()
            ->assertJsonPath('data.step', 'payment');
    }

    public function test_empty_cart_checkout_returns_422(): void
    {
        $this->withHeaders($this->statefulApiHeaders())
            ->getJson('/api/v1/checkout')
            ->assertStatus(422)
            ->assertJsonPath('message', 'سبد خرید شما خالی است.');
    }

    public function test_checkout_account_returns_login_redirect_url(): void
    {
        config(['app.frontend_url' => 'http://localhost:3000']);

        $this->postJson('/api/v1/checkout/account', [
            'action' => 'login',
            'intended' => 'http://localhost:3000/checkout',
        ])
            ->assertOk()
            ->assertJsonPath('data.action', 'login')
            ->assertJsonPath(
                'data.redirect_url',
                route('login', ['intended' => 'http://localhost:3000/checkout'])
            );
    }

    public function test_checkout_account_returns_register_redirect_url(): void
    {
        config(['app.frontend_url' => 'http://localhost:3000']);

        $this->postJson('/api/v1/checkout/account', [
            'action' => 'register',
            'intended' => 'http://localhost:3000/checkout',
        ])
            ->assertOk()
            ->assertJsonPath('data.action', 'register')
            ->assertJsonPath(
                'data.redirect_url',
                route('register', ['intended' => 'http://localhost:3000/checkout'])
            );
    }

    public function test_authenticated_user_can_initiate_payment(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct();

        $this->mock(CheckoutService::class, function (MockInterface $mock) {
            $mock->shouldReceive('initiatePayment')
                ->once()
                ->andReturn([
                    'redirect_url' => 'https://pec.shaparak.ir/NewIPG/?Token=12345',
                ]);
        });

        $this->actingAs($user)
            ->withHeaders($this->statefulApiHeaders())
            ->withSession([
                'cart' => [
                    ['product_id' => $product->id, 'quantity' => 1],
                ],
            ])
            ->postJson('/api/v1/checkout/payment')
            ->assertOk()
            ->assertJsonPath('data.redirect_url', 'https://pec.shaparak.ir/NewIPG/?Token=12345');
    }

    public function test_guest_cannot_initiate_payment(): void
    {
        $product = $this->createProduct();

        $this->withHeaders($this->statefulApiHeaders())
            ->withSession([
                'cart' => [
                    ['product_id' => $product->id, 'quantity' => 1],
                ],
            ])
            ->postJson('/api/v1/checkout/payment')
            ->assertUnauthorized();
    }

    public function test_verify_endpoint_requires_token(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withHeaders($this->statefulApiHeaders())
            ->getJson('/api/v1/checkout/verify')
            ->assertStatus(422);
    }

    public function test_authenticated_user_can_initiate_order_repay(): void
    {
        $user = User::factory()->create();
        $order = Order::create([
            'user_id' => $user->id,
            'amount' => 800000,
            'tracking_id' => 12345678901,
            'status' => -1,
        ]);
        Transaction::create([
            'order_id' => $order->id,
            'token' => '99999',
            'amount' => 800000,
            'status' => -1,
        ]);

        $this->mock(CheckoutService::class, function (MockInterface $mock) {
            $mock->shouldReceive('initiateOrderRepayment')
                ->once()
                ->andReturn([
                    'redirect_url' => 'https://pec.shaparak.ir/NewIPG/?Token=54321',
                ]);
        });

        $this->actingAs($user)
            ->withHeaders($this->statefulApiHeaders())
            ->postJson("/api/v1/orders/{$order->id}/pay")
            ->assertOk()
            ->assertJsonPath('data.redirect_url', 'https://pec.shaparak.ir/NewIPG/?Token=54321');
    }
}
