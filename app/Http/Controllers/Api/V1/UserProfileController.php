<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UpdateProfileRequest;
use App\Http\Resources\ApiResource;
use App\Http\Resources\UserResource;
use App\Services\UserProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserProfileController extends Controller
{
    public function __construct(
        private UserProfileService $profile,
    ) {}

    public function show(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $result = $this->profile->update(
            $request->user(),
            $request->safe()->only(['name', 'email', 'phone']),
            $request->file('avatar')
        );

        $response = ApiResource::success(
            (new UserResource($result['user']))->resolve(),
            $result['message']
        );

        if ($result['info']) {
            $response->setData(array_merge($response->getData(true), [
                'info' => $result['info'],
            ]));
        }

        return $response;
    }
}
