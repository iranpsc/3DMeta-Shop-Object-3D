<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\FileUploadController;
use App\Http\Requests\Api\ImportAdminProductsRequest;
use App\Http\Requests\Api\StoreAdminProductRequest;
use App\Http\Requests\Api\UpdateAdminProductRequest;
use App\Http\Resources\AdminProductResource;
use App\Http\Resources\ApiResource;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\TagResource;
use App\Models\File;
use App\Models\Image;
use App\Models\Product;
use App\Services\AdminProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminProductController extends Controller
{
    public function __construct(
        private AdminProductService $products,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $paginator = $this->products->paginate(
            $request->string('search')->toString() ?: null
        );

        return AdminProductResource::paginated($paginator);
    }

    public function formData(): JsonResponse
    {
        $data = $this->products->formData();

        return ApiResource::success([
            'categories' => CategoryResource::collection($data['categories'])->resolve(),
            'tags' => TagResource::collection($data['tags'])->resolve(),
            'attributes' => $data['attributes'],
            'next_sku' => $data['next_sku'],
        ]);
    }

    public function store(StoreAdminProductRequest $request): JsonResponse
    {
        $product = $this->products->store(
            $request->safe()->only([
                'category_id', 'sku', 'name', 'slug', 'short_description', 'long_description',
                'stock_status', 'quantity', 'delivery_time', 'customer_can_add_review',
                'price', 'sale_price', 'published', 'meta_description', 'meta_keywords',
            ]),
            $request->file('images', []),
            $request->input('files', []),
            $request->input('tags', []),
            $request->input('attributes', [])
        );

        return ApiResource::success(
            (new AdminProductResource($product))->resolve(),
            __('Product created successfully.')
        );
    }

    public function show(Product $product): JsonResponse
    {
        return ApiResource::success(
            (new AdminProductResource($this->products->find($product->id)))->resolve()
        );
    }

    public function update(UpdateAdminProductRequest $request, Product $product): JsonResponse
    {
        $product = $this->products->update(
            $product,
            $request->safe()->only([
                'category_id', 'sku', 'name', 'slug', 'short_description', 'long_description',
                'stock_status', 'quantity', 'delivery_time', 'customer_can_add_review',
                'price', 'sale_price', 'published', 'meta_description', 'meta_keywords',
            ]),
            $request->file('images', []) ?? [],
            $request->input('files', []) ?? [],
            $request->input('tags', []),
            $request->input('attributes', [])
        );

        return ApiResource::success(
            (new AdminProductResource($product))->resolve(),
            __('Product updated successfully.')
        );
    }

    public function destroy(Product $product): JsonResponse
    {
        $this->products->delete($product);

        return ApiResource::success(null, __('Product deleted successfully.'));
    }

    public function import(ImportAdminProductsRequest $request): JsonResponse
    {
        $this->products->import($request->file('file'));

        return ApiResource::success(null, 'محصولات با موفقیت درون ریزی شدند.');
    }

    public function destroyImage(Product $product, Image $image): JsonResponse
    {
        $product = $this->products->removeImage($product, $image);

        return ApiResource::success(
            (new AdminProductResource($product))->resolve()
        );
    }

    public function destroyFile(Product $product, File $file): JsonResponse
    {
        $product = $this->products->removeFile($product, $file);

        return ApiResource::success(
            (new AdminProductResource($product))->resolve()
        );
    }

    public function upload(Request $request, FileUploadController $uploader): JsonResponse
    {
        return $uploader->upload($request);
    }

    public function discardTempUpload(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'path' => ['required', 'string'],
            'name' => ['required', 'string'],
        ]);

        $this->products->discardTempUpload($validated['path'], $validated['name']);

        return ApiResource::success(null);
    }
}
