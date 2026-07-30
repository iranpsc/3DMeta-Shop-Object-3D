<?php

namespace App\Services;

use App\Support\IntendedUrl;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    private const PRICE_MULTIPLIER = 1;

    public function __construct(
        private CartService $cart,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function state(Request $request): array
    {
        $snapshot = $this->cart->snapshot($request);

        if ($snapshot['count'] === 0) {
            throw ValidationException::withMessages([
                'cart' => 'سبد خرید شما خالی است.',
            ]);
        }

        return [
            'step' => $request->user() ? 'payment' : 'create-account',
            'items' => $snapshot['items'],
            'products' => $snapshot['products'],
            'count' => $snapshot['count'],
            'total_price' => $snapshot['total_price'],
        ];
    }

    /**
     * @return array{action: string, redirect_url: string}
     */
    public function accountRedirect(string $action, ?string $intended = null): array
    {
        $params = [];

        if ($intended = IntendedUrl::resolve($intended)) {
            $params['intended'] = $intended;
        }

        $redirectUrl = $action === 'register'
            ? route('register', $params)
            : route('login', $params);

        return [
            'action' => $action,
            'redirect_url' => $redirectUrl,
        ];
    }

    /**
     * @return array{redirect_url: string}
     */
    public function initiatePayment(Request $request): array
    {
        $user = $request->user();
        if (! $user) {
            abort(401);
        }

        $snapshot = $this->cart->snapshot($request);
        if ($snapshot['count'] === 0) {
            throw ValidationException::withMessages([
                'cart' => 'سبد خرید شما خالی است.',
            ]);
        }

        try {
            $amount = (int) ($snapshot['total_price'] * self::PRICE_MULTIPLIER);

            $order = Order::create([
                'user_id' => $user->id,
                'amount' => $amount,
                'tracking_id' => random_int(10000000000, 99999999999),
            ]);

            OrderItem::insert($this->prepareOrderItems($order->id, $snapshot['items'], $snapshot['products']));

            $response = parsian()
                ->amount($amount)
                ->orderId((string) $order->tracking_id)
                ->request()
                ->callbackUrl(route('callback'))
                ->send();

            if (! $response->success()) {
                throw ValidationException::withMessages([
                    'payment' => $response->error()->message(),
                ]);
            }

            $order->transaction()->create([
                'token' => $response->token(),
                'amount' => $amount,
            ]);

            $this->cart->clear($request);

            return [
                'redirect_url' => $response->url(),
            ];
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            Log::error('Payment failed: '.$exception->getMessage());

            throw ValidationException::withMessages([
                'payment' => 'پرداخت با مشکل مواجه شد. لطفا مجددا تلاش کنید.',
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function verify(Request $request, array $params): array
    {
        $token = $params['Token'];
        $status = (int) ($params['status'] ?? -1);

        $transaction = Transaction::query()
            ->where('token', $token)
            ->with(['order.orderItems', 'order.user'])
            ->first();

        if (! $transaction || $transaction->status != -1) {
            throw ValidationException::withMessages([
                'Token' => 'تراکنش مورد نظر یافت نشد.',
            ]);
        }

        if ($request->user() && ! $transaction->order->user->is($request->user())) {
            abort(403);
        }

        $response = parsian()
            ->token((int) $token)
            ->verification()
            ->send();

        if (! $response->success()) {
            $transaction->update(['status' => $status]);
            $transaction->order->update(['status' => $status]);

            throw ValidationException::withMessages([
                'payment' => $response->error()->message(),
            ]);
        }

        $transaction->update([
            'reference_id' => $response->referenceId(),
            'card_hash' => $response->cardHash(),
            'status' => $status,
        ]);

        $order = $transaction->order;
        $order->load('products.files');
        $order->update(['status' => $status]);

        $this->fulfillOrderProducts($order->user, $order);

        return [
            'success' => $status === 0,
            'status' => $status,
            'reference_id' => $transaction->reference_id,
            'tracking_id' => $order->tracking_id,
            'order_id' => $order->id,
            'products' => $order->products->map(fn (Product $product) => [
                'id' => $product->id,
                'sku' => $product->sku,
                'name' => $product->name,
                'url' => '/products/'.$product->sku,
                'files' => $product->files->map(fn ($file) => [
                    'id' => $file->id,
                    'name' => $file->name,
                    'url' => $file->url,
                ])->values(),
            ])->values(),
        ];
    }

    /**
     * @return array{redirect_url: string}
     */
    public function initiateOrderRepayment(Request $request, Order $order): array
    {
        $this->authorizeRepayment($request->user(), $order);

        $order->update(['status' => -1]);

        $response = parsian()
            ->amount((int) $order->amount)
            ->orderId((string) $order->tracking_id)
            ->request()
            ->callbackUrl(route('callback'))
            ->send();

        if (! $response->success()) {
            throw ValidationException::withMessages([
                'payment' => $response->error()->message(),
            ]);
        }

        $order->transaction()->updateOrCreate(
            ['order_id' => $order->id],
            [
                'token' => $response->token(),
                'amount' => $order->amount,
                'status' => -1,
            ]
        );

        return [
            'redirect_url' => $response->url(),
        ];
    }

    private function authorizeRepayment(?User $user, Order $order): void
    {
        if (! $user || ! $user->can('pay', $order)) {
            abort(403);
        }
    }

    /**
     * @param  array<int, array{product_id: int, quantity: int}>  $items
     * @param  \Illuminate\Support\Collection<int, Product>  $products
     * @return array<int, array{order_id: string, product_id: int, quantity: int}>
     */
    private function prepareOrderItems(string $orderId, array $items, $products): array
    {
        $orderItems = [];

        foreach ($products as $product) {
            $cartItem = collect($items)->firstWhere('product_id', $product->id);
            if (! $cartItem) {
                continue;
            }

            $orderItems[] = [
                'order_id' => $orderId,
                'product_id' => $product->id,
                'quantity' => $cartItem['quantity'],
            ];
        }

        return $orderItems;
    }

    private function fulfillOrderProducts(User $user, Order $order): void
    {
        foreach ($order->orderItems as $item) {
            $existing = $user->products()->where('product_id', $item->product_id)->first();

            if ($existing) {
                $existing->pivot->increment('quantity', $item->quantity);
            } else {
                $user->products()->attach($item->product_id, ['quantity' => $item->quantity]);
            }
        }
    }
}
