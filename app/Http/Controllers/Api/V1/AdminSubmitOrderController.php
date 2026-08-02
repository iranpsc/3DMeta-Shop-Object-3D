<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdminSubmitOrderResource;
use App\Http\Resources\ApiResource;
use App\Models\SubmitOrder;
use App\Services\AdminSubmitOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminSubmitOrderController extends Controller
{
    public function __construct(
        private AdminSubmitOrderService $orders,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $paginator = $this->orders->paginate();

        return ApiResource::success([
            'data' => AdminSubmitOrderResource::collection($paginator->getCollection())->resolve(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function show(SubmitOrder $order): JsonResponse
    {
        return ApiResource::success(
            (new AdminSubmitOrderResource($this->orders->find($order->id)))->resolve()
        );
    }
}
