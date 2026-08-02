<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreContactUsRequest;
use App\Http\Resources\ApiResource;
use App\Services\ContactService;
use Illuminate\Http\JsonResponse;

class ContactController extends Controller
{
    public function __construct(
        private ContactService $contacts,
    ) {}

    public function store(StoreContactUsRequest $request): JsonResponse
    {
        $message = $this->contacts->store($request->validated());

        return ApiResource::success(
            [
                'id' => $message->id,
            ],
            'پیام شما با موفقیت ارسال شد.',
            201
        );
    }
}
