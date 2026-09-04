<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreAdminAttributeRequest;
use App\Http\Resources\ApiResource;
use App\Http\Resources\AttributeResource;
use App\Models\Attribute;
use App\Services\AdminAttributeService;
use Illuminate\Http\JsonResponse;

class AdminAttributeController extends Controller
{
    public function __construct(
        private AdminAttributeService $attributes,
    ) {}

    public function index(): JsonResponse
    {
        $paginator = $this->attributes->paginate();

        return ApiResource::success([
            'data' => AttributeResource::collection($paginator->getCollection())->resolve(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function store(StoreAdminAttributeRequest $request): JsonResponse
    {
        $attribute = $this->attributes->store($request->validated());

        return ApiResource::success(
            (new AttributeResource($attribute))->resolve(),
            'ویژگی جدید با موفقیت ایجاد شد.'
        );
    }

    public function destroy(Attribute $attribute): JsonResponse
    {
        $this->attributes->delete($attribute);

        return ApiResource::success(null, 'ویژگی با موفقیت حذف شد.');
    }
}
