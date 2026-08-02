<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CheckoutAccountRequest;
use App\Http\Requests\Api\VerifyCheckoutRequest;
use App\Http\Resources\ApiResource;
use App\Http\Resources\ProductResource;
use App\Models\Order;
use App\Services\CheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CheckoutController extends Controller
{
    public function __construct(
        private CheckoutService $checkout,
    ) {}

    public function show(Request $request): JsonResponse
    {
        try {
            $state = $this->checkout->state($request);
        } catch (ValidationException $exception) {
            return ApiResource::error(
                $exception->validator->errors()->first() ?: 'سبد خرید شما خالی است.',
                422,
                $exception->errors()
            );
        }

        return ApiResource::success([
            'step' => $state['step'],
            'items' => $state['items'],
            'products' => ProductResource::collection($state['products'])->resolve(),
            'count' => $state['count'],
            'total_price' => $state['total_price'],
        ]);
    }

    public function account(CheckoutAccountRequest $request): JsonResponse
    {
        return ApiResource::success(
            $this->checkout->accountRedirect(
                $request->string('action')->toString(),
                $request->input('intended'),
            )
        );
    }

    public function payment(Request $request): JsonResponse
    {
        try {
            $result = $this->checkout->initiatePayment($request);
        } catch (ValidationException $exception) {
            return ApiResource::error(
                $exception->validator->errors()->first() ?: 'پرداخت با مشکل مواجه شد.',
                422,
                $exception->errors()
            );
        }

        return ApiResource::success($result);
    }

    public function verify(VerifyCheckoutRequest $request): JsonResponse
    {
        try {
            $result = $this->checkout->verify($request, $request->validated());
        } catch (ValidationException $exception) {
            return ApiResource::error(
                $exception->validator->errors()->first() ?: 'تراکنش مورد نظر یافت نشد.',
                422,
                $exception->errors()
            );
        }

        $message = $result['success']
            ? 'پرداخت شما با موفقیت انجام شد.'
            : null;

        return ApiResource::success($result, $message);
    }

    public function repay(Request $request, Order $order): JsonResponse
    {
        try {
            $result = $this->checkout->initiateOrderRepayment($request, $order);
        } catch (ValidationException $exception) {
            return ApiResource::error(
                $exception->validator->errors()->first() ?: 'پرداخت با مشکل مواجه شد.',
                422,
                $exception->errors()
            );
        }

        return ApiResource::success($result);
    }
}
