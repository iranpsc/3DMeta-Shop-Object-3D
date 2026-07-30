<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResource;
use App\Http\Resources\OrderSummaryResource;
use App\Models\Order;
use App\Services\UserOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserOrderController extends Controller
{
    public function __construct(
        private UserOrderService $orders,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $paginator = $this->orders->paginateForUser($request->user());

        return ApiResource::success([
            'data' => OrderSummaryResource::collection($paginator->getCollection())->resolve(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        $this->authorize('view', $order);

        $payload = $this->orders->showForUser($request->user(), $order);

        return ApiResource::success([
            'id' => $order->id,
            'tracking_id' => $order->tracking_id,
            'amount' => $order->amount,
            'status' => $order->status,
            'is_paid' => $order->isPaid(),
            'status_label' => $order->isPaid() ? 'پرداخت شده' : 'پرداخت نشده',
            'created_at' => $order->created_at?->toIso8601String(),
            'products' => $payload['products'],
        ]);
    }
}
