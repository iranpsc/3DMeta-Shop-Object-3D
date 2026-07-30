<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class AdminUserResource extends ApiResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'role' => $this->role,
            'products_count' => $this->when(isset($this->products_count), (int) $this->products_count),
            'created_at' => $this->created_at,
        ];
    }
}
