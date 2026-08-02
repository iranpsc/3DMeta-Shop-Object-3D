<?php

namespace Tests\Feature\Api;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTagTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_tags(): void
    {
        Tag::factory()->create(['name' => 'Sample Tag', 'slug' => 'sample-tag']);

        $this->actingAsAdminApiUser()
            ->getJson('/api/v1/admin/tags')
            ->assertOk()
            ->assertJsonPath('data.data.0.name', 'Sample Tag');
    }

    public function test_admin_can_create_tag(): void
    {
        $this->actingAsAdminApiUser()
            ->postJson('/api/v1/admin/tags', [
                'name' => 'New Tag',
                'slug' => 'new-tag',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'برچسب جدید با موفقیت ایجاد شد.');

        $this->assertDatabaseHas('tags', ['slug' => 'new-tag']);
    }

    public function test_admin_can_delete_tag(): void
    {
        $tag = Tag::factory()->create();

        $this->actingAsAdminApiUser()
            ->deleteJson("/api/v1/admin/tags/{$tag->id}")
            ->assertOk();

        $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
    }

    public function test_non_admin_cannot_manage_tags(): void
    {
        $user = User::factory()->create();

        $this->actingAsVerifiedApiUser($user)
            ->getJson('/api/v1/admin/tags')
            ->assertForbidden();
    }
}
