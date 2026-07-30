<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class AvatarResource extends ApiResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'sku' => $this->sku,
            'slug' => $this->slug,
            'image' => $this->whenLoaded('latestImage', fn () => $this->latestImage ? [
                'id' => $this->latestImage->id,
                'url' => $this->latestImage->url,
            ] : null),
            'files' => $this->whenLoaded('files', fn () => $this->files->map(fn ($file) => [
                'id' => $file->id,
                'name' => $file->name,
                'url' => $file->url,
            ])->values()),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
