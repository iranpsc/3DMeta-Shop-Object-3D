<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreAdminCategoryRequest;
use App\Http\Requests\Api\UpdateAdminCategoryRequest;
use App\Http\Resources\ApiResource;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Services\AdminCategoryService;
use Illuminate\Http\JsonResponse;

class AdminCategoryController extends Controller
{
    public function __construct(
        private AdminCategoryService $categories,
    ) {}

    public function index(): JsonResponse
    {
        $paginator = $this->categories->paginate();

        return ApiResource::success([
            'data' => CategoryResource::collection($paginator->getCollection())->resolve(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function store(StoreAdminCategoryRequest $request): JsonResponse
    {
        $category = $this->categories->store(
            $request->safe()->only(['name', 'slug', 'parent_id', 'description']),
            $request->file('image')
        );

        return ApiResource::success(
            (new CategoryResource($category))->resolve(),
            'دسته بندی با موفقیت ایجاد شد.'
        );
    }

    public function show(Category $category): JsonResponse
    {
        return ApiResource::success(
            (new CategoryResource($this->categories->find($category->id)))->resolve()
        );
    }

    public function update(UpdateAdminCategoryRequest $request, Category $category): JsonResponse
    {
        $category = $this->categories->update(
            $category,
            $request->safe()->only(['name', 'slug', 'parent_id', 'description']),
            $request->file('image')
        );

        return ApiResource::success(
            (new CategoryResource($category))->resolve(),
            'دسته بندی با موفقیت ویرایش شد.'
        );
    }

    public function destroy(Category $category): JsonResponse
    {
        $this->categories->delete($category);

        return ApiResource::success(null, 'دسته بندی با موفقیت حذف شد');
    }

    public function formData(): JsonResponse
    {
        return ApiResource::success(
            CategoryResource::collection($this->categories->all())->resolve()
        );
    }
}
