<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreAvatarRequest;
use App\Http\Resources\ApiResource;
use App\Http\Resources\AvatarResource;
use App\Services\AvatarService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserAvatarController extends Controller
{
    public function __construct(
        private AvatarService $avatars,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $paginator = $this->avatars->paginateForUser(
            $request->user(),
            $request->string('search')->toString() ?: null
        );

        return ApiResource::success([
            'data' => AvatarResource::collection($paginator->getCollection())->resolve(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function store(StoreAvatarRequest $request): JsonResponse
    {
        $result = $this->avatars->store(
            $request->user(),
            $request->string('name')->toString(),
            $request->string('avatar_url')->toString(),
            $request->string('avatar_image_url')->toString(),
        );

        return ApiResource::success(
            (new AvatarResource($result['product']->load(['latestImage', 'files'])))->resolve(),
            $result['message']
        );
    }
}
