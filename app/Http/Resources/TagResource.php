<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class TagResource extends ApiResource
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
            'url' => '/tags/'.$this->slug,
        ];
    }
}
