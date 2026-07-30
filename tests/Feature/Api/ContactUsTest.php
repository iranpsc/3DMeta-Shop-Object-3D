<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactUsTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_form_persists_message(): void
    {
        $this->postJson('/api/v1/contact-us', [
            'name' => 'علی رضایی',
            'email' => 'ali@example.com',
            'phone' => '09121234567',
            'subject' => 'سوال درباره محصول',
            'message' => 'سلام، میخواستم درباره محصولات بپرسم.',
        ])
            ->assertCreated()
            ->assertJsonPath('message', 'پیام شما با موفقیت ارسال شد.');

        $this->assertDatabaseHas('contact_us_messages', [
            'name' => 'علی رضایی',
            'email' => 'ali@example.com',
            'phone' => '09121234567',
            'subject' => 'سوال درباره محصول',
        ]);
    }

    public function test_contact_form_validates_required_fields(): void
    {
        $this->postJson('/api/v1/contact-us', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'phone', 'subject', 'message']);
    }

    public function test_contact_form_validates_iranian_mobile(): void
    {
        $this->postJson('/api/v1/contact-us', [
            'name' => 'Ali',
            'email' => 'ali@example.com',
            'phone' => '12345',
            'subject' => 'Subject',
            'message' => 'Message body here',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['phone']);
    }
}
