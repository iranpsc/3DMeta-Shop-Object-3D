<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_fetch_user(): void
    {
        $this->getJson('/api/v1/user')
            ->assertUnauthorized();
    }

    public function test_authenticated_user_includes_role(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->actingAs($user)
            ->getJson('/api/v1/user')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.email', $user->email)
            ->assertJsonPath('data.role', 'admin')
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'email',
                    'role',
                    'avatar',
                    'phone',
                    'email_verified_at',
                ],
            ]);
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withHeaders([
                'Origin' => 'http://localhost:3000',
                'Referer' => 'http://localhost:3000/',
            ])
            ->postJson('/api/v1/logout')
            ->assertOk()
            ->assertJsonPath('data', null)
            ->assertJsonPath('message', 'با موفقیت خارج شدید.');
    }
}
