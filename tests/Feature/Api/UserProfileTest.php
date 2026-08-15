<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_verified_user_can_fetch_profile(): void
    {
        $user = User::factory()->create(['phone' => '09123456789']);

        $this->actingAsVerifiedApiUser($user)
            ->getJson('/api/v1/user/profile')
            ->assertOk()
            ->assertJsonPath('data.email', $user->email);
    }

    public function test_verified_user_can_update_profile(): void
    {
        Notification::fake();
        Storage::fake('public');
        $user = User::factory()->create(['phone' => '09123456789']);

        $this->actingAsVerifiedApiUser($user)
            ->put('/api/v1/user/profile', [
                'name' => 'Updated Name',
                'email' => 'updated@example.com',
                'phone' => '09121111111',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated Name')
            ->assertJsonPath('message', 'اطلاعات کاربری شما با موفقیت بروزرسانی شدند.')
            ->assertJsonPath('info', 'ایمیل تایید حساب کاربری برای شما ارسال شد.');

        $user->refresh();
        $this->assertNull($user->email_verified_at);
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_profile_update_validates_phone(): void
    {
        $user = User::factory()->create(['phone' => '09123456789']);

        $this->actingAsVerifiedApiUser($user)
            ->putJson('/api/v1/user/profile', [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => 'invalid',
            ])
            ->assertUnprocessable();
    }

    public function test_profile_update_accepts_avatar_upload(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['phone' => '09123456789']);

        $this->actingAsVerifiedApiUser($user)
            ->put('/api/v1/user/profile', [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => '09123456789',
                'avatar' => UploadedFile::fake()->image('avatar.jpg'),
            ])
            ->assertOk();

        $user->refresh();
        $this->assertNotNull($user->avatar);
    }
}
