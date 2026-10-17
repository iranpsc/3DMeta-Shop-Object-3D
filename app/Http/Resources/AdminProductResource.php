<?php

namespace App\Http\Resources;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

class AdminProductResource extends ApiResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $data = array_merge((new ProductResource($this->resource))->resolve(), [
            'published' => (bool) $this->published,
            'meta_description' => $this->meta_description,
            'meta_keywords' => $this->meta_keywords,
            'delivery_time' => $this->delivery_time,
            'created_at' => $this->created_at,
            'category_id' => $this->category_id,
            'sold' => array_key_exists('users_count', $this->resource->getAttributes())
                ? (int) $this->users_count > 0
                : $this->resource->users()->exists(),
        ]);

        if ($this->relationLoaded('images')) {
            $data['images'] = $this->images->map(fn ($image) => [
                'id' => $image->id,
                'path' => $image->path,
                'url' => $image->url,
            ])->values()->all();
        }

        if ($this->relationLoaded('files')) {
            $data['files'] = $this->files->map(fn ($file) => [
                'id' => $file->id,
                'name' => $file->name ?? null,
                'extension' => $file->extension ?? null,
                'size' => $file->size ?? null,
                'url' => $file->url,
            ])->values()->all();
        }

        return $data;
    }

    /**
     * @param  Collection<int, mixed>|array<int, mixed>  $items
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
