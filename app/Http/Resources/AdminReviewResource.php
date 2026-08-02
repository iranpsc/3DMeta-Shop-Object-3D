<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class AdminReviewResource extends ApiResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'comment' => $this->comment,
            'rating' => $this->rating,
            'approved' => (bool) $this->approved,
            'approved_at' => $this->approved_at,
            'approved_by' => $this->approved_by,
            'created_at' => $this->created_at,
            'user' => $this->when(
                $this->relationLoaded('user') && $this->user,
                fn () => ['id' => $this->user->id, 'name' => $this->user->name]
            ),
            'product' => $this->when(
                $this->relationLoaded('product') && $this->product,
                fn () => [
                    'id' => $this->product->id,
                    'name' => $this->product->name,
                    'sku' => $this->product->sku,
                ]
            ),
            'replies_count' => $this->when(
                $this->relationLoaded('replies'),
                fn () => $this->replies->count()
            ),
            'replies' => $this->when(
                $this->relationLoaded('replies'),
                fn () => AdminReviewReplyResource::collection($this->replies)->resolve()
            ),
        ];
    }
}
