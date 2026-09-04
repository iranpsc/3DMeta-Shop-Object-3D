<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class CategoryResource extends ApiResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->when(isset($this->description), $this->description),
            'url' => '/categories/'.$this->url,
            'products_count' => $this->when(isset($this->products_count), (int) $this->products_count),
            'image' => $this->when(
                $this->relationLoaded('image') && $this->image,
                fn () => new ImageResource($this->image)
            ),
            'parent' => $this->when(
                $this->relationLoaded('parent') && $this->parent,
                fn () => [
                    'id' => $this->parent->id,
                    'name' => $this->parent->name,
                    'slug' => $this->parent->slug,
                    'url' => '/categories/'.$this->parent->url,
                ]
            ),
            'children' => $this->when(
                $this->relationLoaded('children'),
                fn () => $this->children->map(fn ($child) => [
                    'id' => $child->id,
                    'name' => $child->name,
                    'slug' => $child->slug,
                    'url' => '/categories/'.$child->url,
                    'image' => $child->relationLoaded('image') && $child->image
                        ? (new ImageResource($child->image))->resolve()
                        : null,
                ])->values()
            ),
            'can_delete' => $this->canDelete($request),
        ];
    }
}
