<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreSubmitOrderRequest;
use App\Http\Resources\ApiResource;
use App\Services\SubmitOrderService;
use Illuminate\Http\JsonResponse;

class SubmitOrderController extends Controller
{
    public function __construct(
        private SubmitOrderService $submitOrders,
    ) {}

    public function store(StoreSubmitOrderRequest $request): JsonResponse
    {
        $order = $this->submitOrders->store(
            $request->validated(),
            $request->user(),
            $request->file('attachment'),
        );

        return ApiResource::success(
            [
                'id' => $order->id,
            ],
            'سفارش شما با موفقیت ثبت شد.',
            201
        );
    }
}
