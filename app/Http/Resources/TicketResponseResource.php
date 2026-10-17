<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class TicketResponseResource extends ApiResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'message' => $this->message,
            'attachment' => $this->attachment,
            'attachment_name' => $this->attachment ? basename($this->attachment) : null,
            'attachment_url' => $this->attachment
                ? route('api.v1.tickets.responses.attachment', [
                    'ticket' => $this->ticket_id,
                    'response' => $this->id,
                ])
                : null,
            'created_at' => $this->created_at?->toIso8601String(),
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'avatar' => $this->user->avatar,
            ]),
        ];
    }
}
