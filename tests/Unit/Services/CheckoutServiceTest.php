<?php

namespace Tests\Unit\Services;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use App\Parsian\Parsian;
use App\Parsian\Request as ParsianRequest;
use App\Parsian\Verification;
use App\Services\CheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Mockery;
use Mockery\MockInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class CheckoutServiceTest extends TestCase
{
    use RefreshDatabase;

    private function createProduct(array $overrides = []): Product
    {
        return Product::factory()->create(array_merge([
            'category_id' => Category::factory()->create()->id,
            'published' => true,
            'created_by' => 'admin',
            'price' => 100000,
            'sale_price' => 80000,
        ], $overrides));
    }

    private function requestWithCart(?User $user, array $cart): Request
    {
        $request = Request::create('/checkout', 'POST');
        $request->setLaravelSession($this->app['session']->driver());
        $request->session()->put('cart', $cart);

        if ($user) {
            $request->setUserResolver(fn () => $user);
        }

        return $request;
    }

    /**
     * @return MockInterface&Parsian
     */
    private function mockSuccessfulPaymentRequest(string $token = '12345'): MockInterface
    {
        $response = Mockery::mock();
        $response->shouldReceive('success')->andReturn(true);
        $response->shouldReceive('token')->andReturn($token);
        $response->shouldReceive('url')->andReturn('https://pec.shaparak.ir/NewIPG/?Token='.$token);

        $gatewayRequest = Mockery::mock(ParsianRequest::class);
        $gatewayRequest->shouldReceive('callbackUrl')->andReturnSelf();
        $gatewayRequest->shouldReceive('send')->andReturn($response);

        $parsian = Mockery::mock(Parsian::class);
        $parsian->shouldReceive('amount')->andReturnSelf();
        $parsian->shouldReceive('orderId')->andReturnSelf();
        $parsian->shouldReceive('request')->andReturn($gatewayRequest);
        $this->app->instance(Parsian::class, $parsian);

        return $parsian;
    }

    private function mockVerification(bool $success, ?string $errorMessage = null): void
    {
        $verificationResponse = Mockery::mock();
        $verificationResponse->shouldReceive('success')->andReturn($success);

        if ($success) {
            $verificationResponse->shouldReceive('referenceId')->andReturn('ref-1');
            $verificationResponse->shouldReceive('cardHash')->andReturn('card-hash');
        } else {
            $error = Mockery::mock();
            $error->shouldReceive('message')->andReturn($errorMessage ?? 'تراکنش ناموفق می باشد');
            $verificationResponse->shouldReceive('error')->andReturn($error);
        }

        $verification = Mockery::mock(Verification::class);
        $verification->shouldReceive('send')->andReturn($verificationResponse);

        $parsian = Mockery::mock(Parsian::class);
        $parsian->shouldReceive('token')->andReturnSelf();
        $parsian->shouldReceive('verification')->andReturn($verification);
        $this->app->instance(Parsian::class, $parsian);
    }

    public function test_initiate_payment_aborts_for_guest(): void
    {
        $product = $this->createProduct();
        $request = $this->requestWithCart(null, [
            ['product_id' => $product->id, 'quantity' => 1],
        ]);

        $this->expectException(HttpException::class);

        $this->app->make(CheckoutService::class)->initiatePayment($request);
    }

    public function test_initiate_payment_rejects_empty_cart(): void
    {
        $user = User::factory()->create();
        $request = $this->requestWithCart($user, []);

        $this->expectException(ValidationException::class);

        $this->app->make(CheckoutService::class)->initiatePayment($request);
    }

    public function test_initiate_payment_success_creates_order_and_clears_cart(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct();
        $this->mockSuccessfulPaymentRequest('99999');

        $request = $this->requestWithCart($user, [
            ['product_id' => $product->id, 'quantity' => 2],
        ]);

        $result = $this->app->make(CheckoutService::class)->initiatePayment($request);

        $this->assertStringContainsString('Token=99999', $result['redirect_url']);
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('order_items', 1);
        $this->assertDatabaseHas('transactions', ['token' => '99999']);
        $this->assertSame([], $request->session()->get('cart', []));
    }

    public function test_initiate_payment_fails_when_gateway_rejects(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct();

        $error = Mockery::mock();
        $error->shouldReceive('message')->andReturn('تراکنش ناموفق می باشد');

        $response = Mockery::mock();
        $response->shouldReceive('success')->andReturn(false);
        $response->shouldReceive('error')->andReturn($error);

        $gatewayRequest = Mockery::mock(ParsianRequest::class);
        $gatewayRequest->shouldReceive('callbackUrl')->andReturnSelf();
        $gatewayRequest->shouldReceive('send')->andReturn($response);

        $parsian = Mockery::mock(Parsian::class);
        $parsian->shouldReceive('amount')->andReturnSelf();
        $parsian->shouldReceive('orderId')->andReturnSelf();
        $parsian->shouldReceive('request')->andReturn($gatewayRequest);
        $this->app->instance(Parsian::class, $parsian);

        $request = $this->requestWithCart($user, [
            ['product_id' => $product->id, 'quantity' => 1],
        ]);

        try {
            $this->app->make(CheckoutService::class)->initiatePayment($request);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'تراکنش ناموفق می باشد',
                $exception->errors()['payment'][0]
            );
        }
    }

    public function test_initiate_payment_wraps_unexpected_errors(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct();

        $gatewayRequest = Mockery::mock(ParsianRequest::class);
        $gatewayRequest->shouldReceive('callbackUrl')->andReturnSelf();
        $gatewayRequest->shouldReceive('send')->andThrow(new \RuntimeException('SOAP down'));

        $parsian = Mockery::mock(Parsian::class);
        $parsian->shouldReceive('amount')->andReturnSelf();
        $parsian->shouldReceive('orderId')->andReturnSelf();
        $parsian->shouldReceive('request')->andReturn($gatewayRequest);
        $this->app->instance(Parsian::class, $parsian);

        Log::shouldReceive('error')->once();

        $request = $this->requestWithCart($user, [
            ['product_id' => $product->id, 'quantity' => 1],
        ]);

        $this->expectException(ValidationException::class);

        $this->app->make(CheckoutService::class)->initiatePayment($request);
    }

    public function test_verify_rejects_missing_transaction(): void
    {
        $request = Request::create('/checkout/verify', 'GET');

        $this->expectException(ValidationException::class);

        $this->app->make(CheckoutService::class)->verify($request, [
            'Token' => 'missing',
            'status' => 0,
        ]);
    }

    public function test_verify_rejects_non_pending_transaction(): void
    {
        $user = User::factory()->create();
        $order = Order::create([
            'user_id' => $user->id,
            'amount' => 80000,
            'tracking_id' => 12345678901,
            'status' => 0,
        ]);
        Transaction::create([
            'order_id' => $order->id,
            'token' => '11111',
            'amount' => 80000,
            'status' => 0,
        ]);

        $request = Request::create('/checkout/verify', 'GET');

        $this->expectException(ValidationException::class);

        $this->app->make(CheckoutService::class)->verify($request, [
            'Token' => '11111',
            'status' => 0,
        ]);
    }

    public function test_verify_forbids_other_users(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $order = Order::create([
            'user_id' => $owner->id,
            'amount' => 80000,
            'tracking_id' => 12345678902,
            'status' => -1,
        ]);
        Transaction::create([
            'order_id' => $order->id,
            'token' => '22222',
            'amount' => 80000,
            'status' => -1,
        ]);

        $request = Request::create('/checkout/verify', 'GET');
        $request->setUserResolver(fn () => $other);

        $this->expectException(HttpException::class);

        $this->app->make(CheckoutService::class)->verify($request, [
            'Token' => '22222',
            'status' => 0,
        ]);
    }

    public function test_verify_success_fulfills_products(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct();
        $product->files()->create([
            'name' => 'model.glb',
            'path' => 'products/model.glb',
            'type' => 'model/gltf-binary',
            'size' => '1 MB',
        ]);

        $order = Order::create([
            'user_id' => $user->id,
            'amount' => 80000,
            'tracking_id' => 12345678903,
            'status' => -1,
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);
        Transaction::create([
            'order_id' => $order->id,
            'token' => '33333',
            'amount' => 80000,
            'status' => -1,
        ]);

        $this->mockVerification(true);

        $request = Request::create('/checkout/verify', 'GET');
        $request->setUserResolver(fn () => $user);

        $result = $this->app->make(CheckoutService::class)->verify($request, [
            'Token' => '33333',
            'status' => 0,
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame('ref-1', $result['reference_id']);
        $this->assertTrue($user->products()->where('product_id', $product->id)->exists());
    }

    public function test_verify_increments_existing_product_quantity(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct();
        $user->products()->attach($product->id, ['quantity' => 1]);

        $order = Order::create([
            'user_id' => $user->id,
            'amount' => 80000,
            'tracking_id' => 12345678904,
            'status' => -1,
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);
        Transaction::create([
            'order_id' => $order->id,
            'token' => '44444',
            'amount' => 80000,
            'status' => -1,
        ]);

        $this->mockVerification(true);

        $request = Request::create('/checkout/verify', 'GET');

        $this->app->make(CheckoutService::class)->verify($request, [
            'Token' => '44444',
            'status' => 0,
        ]);

        $quantity = (int) DB::table('product_user')
            ->where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->value('quantity');

        $this->assertSame(3, $quantity);
    }

    public function test_verify_fails_when_gateway_rejects(): void
    {
        $user = User::factory()->create();
        $order = Order::create([
            'user_id' => $user->id,
            'amount' => 80000,
            'tracking_id' => 12345678905,
            'status' => -1,
        ]);
        Transaction::create([
            'order_id' => $order->id,
            'token' => '55555',
            'amount' => 80000,
            'status' => -1,
        ]);

        $this->mockVerification(false, 'تراکنش ناموفق می باشد');

        $request = Request::create('/checkout/verify', 'GET');

        try {
            $this->app->make(CheckoutService::class)->verify($request, [
                'Token' => '55555',
                'status' => -1,
            ]);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            $this->assertSame(-1, $order->fresh()->status);
            $this->assertSame(-1, $order->transaction->fresh()->status);
            $this->assertSame(
                'تراکنش ناموفق می باشد',
                $exception->errors()['payment'][0]
            );
        }
    }

    public function test_repay_forbids_unauthorized_user(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $order = Order::create([
            'user_id' => $owner->id,
            'amount' => 80000,
            'tracking_id' => 12345678906,
            'status' => -1,
        ]);

        $request = Request::create('/orders/'.$order->id.'/pay', 'POST');
        $request->setUserResolver(fn () => $other);

        $this->expectException(HttpException::class);

        $this->app->make(CheckoutService::class)->initiateOrderRepayment($request, $order);
    }

    public function test_repay_success_updates_transaction(): void
    {
        $user = User::factory()->create();
        $order = Order::create([
            'user_id' => $user->id,
            'amount' => 80000,
            'tracking_id' => 12345678907,
            'status' => -1,
        ]);
        Transaction::create([
            'order_id' => $order->id,
            'token' => 'old-token',
            'amount' => 80000,
            'status' => -1,
        ]);

        $this->mockSuccessfulPaymentRequest('66666');

        $request = Request::create('/orders/'.$order->id.'/pay', 'POST');
        $request->setUserResolver(fn () => $user);

        $result = $this->app->make(CheckoutService::class)->initiateOrderRepayment($request, $order);

        $this->assertStringContainsString('Token=66666', $result['redirect_url']);
        $this->assertDatabaseHas('transactions', [
            'order_id' => $order->id,
            'token' => '66666',
            'status' => -1,
        ]);
    }

    public function test_repay_fails_when_gateway_rejects(): void
    {
        $user = User::factory()->create();
        $order = Order::create([
            'user_id' => $user->id,
            'amount' => 80000,
            'tracking_id' => 12345678908,
            'status' => -1,
        ]);

        $error = Mockery::mock();
        $error->shouldReceive('message')->andReturn('شناسه سفارش تکراری است.');

        $response = Mockery::mock();
        $response->shouldReceive('success')->andReturn(false);
        $response->shouldReceive('error')->andReturn($error);

        $gatewayRequest = Mockery::mock(ParsianRequest::class);
        $gatewayRequest->shouldReceive('callbackUrl')->andReturnSelf();
        $gatewayRequest->shouldReceive('send')->andReturn($response);

        $parsian = Mockery::mock(Parsian::class);
        $parsian->shouldReceive('amount')->andReturnSelf();
        $parsian->shouldReceive('orderId')->andReturnSelf();
        $parsian->shouldReceive('request')->andReturn($gatewayRequest);
        $this->app->instance(Parsian::class, $parsian);

        $request = Request::create('/orders/'.$order->id.'/pay', 'POST');
        $request->setUserResolver(fn () => $user);

        $this->expectException(ValidationException::class);

        $this->app->make(CheckoutService::class)->initiateOrderRepayment($request, $order);
    }

    public function test_account_redirect_ignores_untrusted_intended_url(): void
    {
        config(['app.frontend_url' => 'http://localhost:3000']);

        $result = $this->app->make(CheckoutService::class)->accountRedirect(
            'login',
            'https://evil.example/phish'
        );

        $this->assertSame('login', $result['action']);
        $this->assertSame(route('auth.redirect'), $result['redirect_url']);
    }
}
