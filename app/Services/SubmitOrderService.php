<?php

namespace App\Services;

use App\Models\SubmitOrder;
use App\Models\User;
use Illuminate\Http\UploadedFile;

class SubmitOrderService
{
    /**
     * Store a design order submission (parity with SubmitOrder Livewire).
     *
     * @param  array{name?: string|null, email?: string|null, phone?: string|null, subject: string, message: string}  $data
     */
    public function store(array $data, ?User $user = null, ?UploadedFile $attachment = null): SubmitOrder
    {
        return SubmitOrder::create([
            'name' => $user ? $user->name : $data['name'],
            'email' => $user ? $user->email : $data['email'],
            'phone' => $user ? $user->phone : $data['phone'],
            'subject' => $data['subject'],
            'message' => $data['message'],
            'attachment' => $attachment?->store('attachments', 'public'),
        ]);
    }
}
