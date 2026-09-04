<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class AttributeResource extends ApiResource
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
            'created_at' => $this->when(isset($this->created_at), $this->created_at),
            'can_delete' => $this->canDelete($request),
        ];
    }
}
