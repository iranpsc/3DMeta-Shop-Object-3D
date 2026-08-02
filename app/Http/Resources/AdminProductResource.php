<?php

namespace App\Http\Resources;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminProductResource extends ApiResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return array_merge((new ProductResource($this->resource))->resolve(), [
            'published' => (bool) $this->published,
            'meta_description' => $this->meta_description,
            'meta_keywords' => $this->meta_keywords,
            'delivery_time' => $this->delivery_time,
            'created_at' => $this->created_at,
            'category_id' => $this->category_id,
        ]);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, mixed>|array<int, mixed>  $items
     */
    public static function paginated(LengthAwarePaginator $paginator): JsonResponse
    {
        return ApiResource::success([
            'data' => static::collection($paginator->getCollection())->resolve(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }
}
