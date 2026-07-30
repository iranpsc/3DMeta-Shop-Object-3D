<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResource;
use App\Http\Resources\UserResource;
use App\Services\UserDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserDashboardController extends Controller
{
    public function __construct(
        private UserDashboardService $dashboard,
    ) {}

    public function show(Request $request): JsonResponse
    {
        return ApiResource::success(
            $this->dashboard->summary($request->user())
        );
    }
}
