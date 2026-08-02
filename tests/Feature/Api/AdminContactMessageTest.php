<?php

namespace Tests\Feature\Api;

use App\Models\ContactUsMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminContactMessageTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_contact_messages(): void
    {
        ContactUsMessage::create([
            'name' => 'Reza',
            'email' => 'reza@example.com',
            'phone' => '09120000000',
            'subject' => 'Question',
            'message' => 'Hello there',
        ]);

        $this->actingAsAdminApiUser()
            ->getJson('/api/v1/admin/contact-messages')
            ->assertOk()
            ->assertJsonPath('data.data.0.name', 'Reza');
    }
}
