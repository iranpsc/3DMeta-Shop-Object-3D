<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreAdminTagRequest;
use App\Http\Resources\ApiResource;
use App\Http\Resources\TagResource;
use App\Models\Tag;
use App\Services\AdminTagService;
use Illuminate\Http\JsonResponse;

class AdminTagController extends Controller
{
    public function __construct(
        private AdminTagService $tags,
    ) {}

    public function index(): JsonResponse
    {
        $paginator = $this->tags->paginate();

        return ApiResource::success([
            'data' => TagResource::collection($paginator->getCollection())->resolve(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function store(StoreAdminTagRequest $request): JsonResponse
    {
        $tag = $this->tags->store($request->validated());

        return ApiResource::success(
            (new TagResource($tag))->resolve(),
            'برچسب جدید با موفقیت ایجاد شد.'
        );
    }

    public function destroy(Tag $tag): JsonResponse
    {
        $this->tags->delete($tag);

        return ApiResource::success(null, 'برچسب با موفقیت حذف شد.');
    }
}
