<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class TicketResource extends ApiResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $status = $this->getRawOriginal('status');
        $responseStatus = $this->getRawOriginal('response_status');

        return [
            'id' => $this->id,
            'title' => $this->title,
            'message' => $this->message,
            'priority' => $this->priority,
            'priority_label' => match ($this->priority) {
                'high' => 'بالا',
                'medium' => 'متوسط',
                'low' => 'پایین',
                default => $this->priority,
            },
            'status' => $status,
            'status_label' => match ($status) {
                'open' => 'باز',
                'closed' => 'بسته',
                default => $status,
            },
            'response_status' => $responseStatus,
            'response_status_label' => match ($responseStatus) {
                'pending' => 'درحال بررسی',
                'replied' => 'پاسخ داده شده',
                default => $responseStatus,
            },
            'attachment' => $this->attachment,
            'attachment_name' => $this->attachment ? basename($this->attachment) : null,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
                'avatar' => $this->user->avatar,
            ]),
            'responses' => TicketResponseResource::collection($this->whenLoaded('responses')),
        ];
    }
}
