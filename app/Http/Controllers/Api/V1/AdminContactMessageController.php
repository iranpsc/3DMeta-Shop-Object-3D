<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdminContactMessageResource;
use App\Http\Resources\ApiResource;
use App\Services\AdminContactMessageService;
use Illuminate\Http\JsonResponse;

class AdminContactMessageController extends Controller
{
    public function __construct(
        private AdminContactMessageService $messages,
    ) {}

    public function index(): JsonResponse
    {
        $paginator = $this->messages->paginate();

        return ApiResource::success([
            'data' => AdminContactMessageResource::collection($paginator->getCollection())->resolve(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }
}
