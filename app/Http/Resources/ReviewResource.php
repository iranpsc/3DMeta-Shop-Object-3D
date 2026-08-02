<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class ReviewResource extends ApiResource
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
            'created_at' => $this->created_at,
            'user' => $this->when(
                $this->relationLoaded('user') && $this->user,
                fn () => [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'avatar' => $this->user->avatar,
                ]
            ),
            'replies' => $this->when(
                $this->relationLoaded('replies'),
                fn () => $this->replies->map(fn ($reply) => [
                    'id' => $reply->id,
                    'comment' => $reply->comment,
                    'created_at' => $reply->created_at,
                    'user' => $reply->user ? [
                        'id' => $reply->user->id,
                        'name' => $reply->user->name,
                        'avatar' => $reply->user->avatar,
                    ] : null,
                ])->values()
            ),
        ];
    }
}
