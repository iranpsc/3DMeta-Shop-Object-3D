<?php

namespace Tests\Feature\Api;

use App\Jobs\DownloadFileJob;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class UserAvatarsTest extends TestCase
{
    use RefreshDatabase;

    public function test_verified_user_can_list_avatars(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['slug' => 'avatar', 'name' => 'Avatars']);

        $this->actingAsVerifiedApiUser($user)
            ->getJson('/api/v1/user/avatars')
            ->assertOk()
            ->assertJsonStructure(['data' => ['data', 'meta']]);
    }

    public function test_verified_user_can_create_avatar(): void
    {
        Bus::fake();
        $user = User::factory()->create();

        $this->actingAsVerifiedApiUser($user)
            ->postJson('/api/v1/user/avatars', [
                'name' => 'My Avatar',
                'avatar_url' => 'https://example.com/avatar.glb',
                'avatar_image_url' => 'https://example.com/avatar.png',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'My Avatar')
            ->assertJsonPath('message', 'Avatar created successfully.');

        Bus::assertDispatched(DownloadFileJob::class, 2);
        $this->assertDatabaseHas('products', ['name' => 'My Avatar', 'created_by' => 'user']);
    }
}
