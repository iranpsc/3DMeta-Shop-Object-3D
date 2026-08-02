<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BuildPackageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'sku' => $this->sku,
            'images' => $this->images->map(function ($image) {
                return [
                    'id' => $image->id,
                    'url' => $image->url,
                ];
            }),
            'files' => $this->files->map(function ($file) {
                return [
                    'id' => $file->id,
                    'url' => $file->url,
                ];
            }),
            'attributes' => $this->attributes->map(function ($attribute) {
                return [
                    'slug' => $attribute->slug,
                    'name' => $attribute->name,
                    'value' => $attribute->pivot->value,
                ];
            }),
        ];
    }
}
