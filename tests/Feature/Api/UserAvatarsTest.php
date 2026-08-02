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
        \App\Models\Tag::factory()->create();
        \App\Models\Attribute::factory()->create();
        Product::factory()->create([
            'category_id' => Category::factory()->create()->id,
            'sku' => '3D-rgb-10010',
        ]);

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
        $avatar = Product::where('name', 'My Avatar')->first();
        $this->assertSame('3D-rgb-10011', $avatar->sku);
        $this->assertTrue($avatar->tags()->exists());
        $this->assertTrue($avatar->attributes()->exists());
    }

    public function test_verified_user_can_search_avatars(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['slug' => 'avatar', 'name' => 'Avatars']);
        $match = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Searchable Avatar',
            'created_by' => 'user',
        ]);
        $match->images()->create(['path' => 'avatars/s.png']);
        $match->files()->create([
            'name' => 'a.glb',
            'path' => 'products/a.glb',
            'type' => 'model/gltf-binary',
            'size' => '1 MB',
        ]);
        $user->products()->attach($match->id);

        $other = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Other Avatar',
            'created_by' => 'user',
        ]);
        $other->images()->create(['path' => 'avatars/o.png']);
        $other->files()->create([
            'name' => 'b.glb',
            'path' => 'products/b.glb',
            'type' => 'model/gltf-binary',
            'size' => '1 MB',
        ]);
        $user->products()->attach($other->id);

        $this->actingAsVerifiedApiUser($user)
            ->getJson('/api/v1/user/avatars?search=Searchable')
            ->assertOk()
            ->assertJsonPath('data.data.0.name', 'Searchable Avatar')
            ->assertJsonPath('data.meta.total', 1);
    }
}
