<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResource;
use App\Services\AdminDashboardService;
use Illuminate\Http\JsonResponse;

class AdminDashboardController extends Controller
{
    public function __construct(
        private AdminDashboardService $dashboard,
    ) {}

    public function show(): JsonResponse
    {
        return ApiResource::success($this->dashboard->summary());
    }
}
