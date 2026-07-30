<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class ProductResource extends ApiResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $canDownload = $user ? $user->can('download', $this->resource) : false;

        $image = null;
        if ($this->relationLoaded('oldestImage') && $this->oldestImage) {
            $image = (new ImageResource($this->oldestImage))->resolve();
        } elseif ($this->relationLoaded('latestImage') && $this->latestImage) {
            $image = (new ImageResource($this->latestImage))->resolve();
        }

        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'name' => $this->name,
            'slug' => $this->slug,
            'url' => '/products/'.$this->sku,
            'short_description' => $this->short_description,
            'long_description' => $this->long_description,
            'price' => $this->price,
            'sale_price' => $this->sale_price,
            'final_price' => $this->final_price,
            'discount' => $this->discount,
            'is_free' => (bool) $this->is_free,
            'stock_status' => $this->when(isset($this->stock_status), (bool) $this->stock_status),
            'quantity' => $this->when(isset($this->quantity), $this->quantity),
            'customer_can_add_review' => $this->when(
                isset($this->customer_can_add_review),
                (bool) $this->customer_can_add_review
            ),
            'rating_avg' => $this->rating_avg !== null ? (float) $this->rating_avg : null,
            'reviews_count' => $this->when(isset($this->reviews_count), (int) $this->reviews_count),
            'approved_reviews_count' => $this->when(
                array_key_exists('approved_reviews_count', $this->resource->getAttributes())
                    || isset($this->approved_reviews_count),
                (int) ($this->approved_reviews_count ?? 0)
            ),
            'likes_count' => $this->when(isset($this->likes_count), (int) $this->likes_count),
            'user_liked' => $this->when(isset($this->user_liked), (bool) $this->user_liked),
            'user_bookmarked' => $this->when(isset($this->user_bookmarked), (bool) $this->user_bookmarked),
            'user_can_download' => $this->when($user !== null, $canDownload),
            'image' => $image,
            'images' => $this->when(
                $this->relationLoaded('images'),
                fn () => ImageResource::collection($this->images)
            ),
            'files' => $this->when(
                $this->relationLoaded('files'),
                fn () => $this->files->map(fn ($file) => [
                    'id' => $file->id,
                    'name' => $file->name ?? null,
                    'extension' => $file->extension ?? null,
                    'size' => $file->size ?? null,
                    'url' => $canDownload ? ($file->url ?? null) : null,
                ])->values()
            ),
            'tags' => $this->when(
                $this->relationLoaded('tags'),
                fn () => TagResource::collection($this->tags)
            ),
            'attributes' => $this->when(
                $this->relationLoaded('attributes'),
                fn () => $this->attributes->map(fn ($attr) => [
                    'id' => $attr->id,
                    'name' => $attr->name,
                    'slug' => $attr->slug,
                    'value' => $attr->pivot->value ?? null,
                ])->values()
            ),
            'category' => $this->when(
                $this->relationLoaded('category') && $this->category,
                fn () => new CategoryResource($this->category)
            ),
            'similar_products' => $this->when(
                $this->relationLoaded('similar_products'),
                fn () => ProductResource::collection($this->similar_products)
            ),
        ];
    }
}
