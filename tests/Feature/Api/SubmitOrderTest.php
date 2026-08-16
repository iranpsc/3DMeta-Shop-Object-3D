<?php

namespace Tests\Feature\Api;

use App\Models\SubmitOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SubmitOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_submit_order(): void
    {
        Storage::fake('public');

        $this->postJson('/api/v1/submit-order', [
            'name' => 'علی رضایی',
            'email' => 'ali@example.com',
            'phone' => '09121234567',
            'subject' => 'طراحی مدل سه بعدی',
            'message' => 'می‌خواهم یک مدل سه بعدی سفارش دهم.',
        ], $this->statefulApiHeaders())
            ->assertCreated()
            ->assertJsonPath('message', 'سفارش شما با موفقیت ثبت شد.');

        $this->assertDatabaseHas('submit_orders', [
            'name' => 'علی رضایی',
            'email' => 'ali@example.com',
            'phone' => '09121234567',
            'subject' => 'طراحی مدل سه بعدی',
        ]);
    }

    public function test_guest_can_submit_order_with_attachment(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->create('design.pdf', 100);

        $this->postJson('/api/v1/submit-order', [
            'name' => 'علی رضایی',
            'email' => 'ali@example.com',
            'phone' => '09121234567',
            'subject' => 'طراحی مدل سه بعدی',
            'message' => 'فایل ضمیمه ارسال شد.',
            'attachment' => $file,
        ], $this->statefulApiHeaders())
            ->assertCreated();

        $this->assertDatabaseCount('submit_orders', 1);
        $this->assertNotNull(SubmitOrder::first()->attachment);
    }

    public function test_authenticated_user_uses_profile_fields(): void
    {
        $user = User::factory()->create([
            'name' => 'کاربر ثبت‌نام‌شده',
            'email' => 'user@example.com',
            'phone' => '09111111111',
        ]);

        $this->actingAsVerifiedApiUser($user)
            ->postJson('/api/v1/submit-order', [
                'subject' => 'سفارش طراحی',
                'message' => 'توضیحات سفارش',
            ])
            ->assertCreated()
            ->assertJsonPath('message', 'سفارش شما با موفقیت ثبت شد.');

        $this->assertDatabaseHas('submit_orders', [
            'name' => 'کاربر ثبت‌نام‌شده',
            'email' => 'user@example.com',
            'phone' => '09111111111',
            'subject' => 'سفارش طراحی',
        ]);
    }

    public function test_guest_validates_required_fields(): void
    {
        $this->postJson('/api/v1/submit-order', [], $this->statefulApiHeaders())
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'phone', 'subject', 'message']);
    }

    public function test_guest_validates_attachment_size(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->create('large.pdf', 2048);

        $this->postJson('/api/v1/submit-order', [
            'name' => 'Ali',
            'email' => 'ali@example.com',
            'phone' => '09121234567',
            'subject' => 'Subject',
            'message' => 'Message',
            'attachment' => $file,
        ], $this->statefulApiHeaders())
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['attachment']);
    }
}
