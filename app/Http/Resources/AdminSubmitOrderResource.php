<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class AdminSubmitOrderResource extends ApiResource
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
            'subject' => $this->subject,
            'message' => $this->message,
            'attachment' => $this->attachment,
            'attachment_url' => $this->attachment ? asset('storage/'.$this->attachment) : null,
            'created_at' => $this->created_at,
        ];
    }
}
