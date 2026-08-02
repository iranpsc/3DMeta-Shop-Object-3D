<?php

namespace App\Services;

use App\Models\ContactUsMessage;

class ContactService
{
    /**
     * Store a contact-us message (parity with ContactUs Livewire).
     *
     * @param  array{name: string, email: string, phone: string, subject: string, message: string}  $data
     */
    public function store(array $data): ContactUsMessage
    {
        return ContactUsMessage::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'subject' => $data['subject'],
            'message' => $data['message'],
        ]);
    }
}
