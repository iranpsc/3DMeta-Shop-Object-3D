<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Attribute;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicAvatarTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_list_only_unowned_avatars(): void
    {
        $category = Category::factory()->create(['slug' => 'avatar', 'name' => 'Avatars']);
        $default = Product::factory()->create(['category_id' => $category->id, 'name' => 'Default Avatar']);
        $owned = Product::factory()->create(['category_id' => $category->id, 'name' => 'Owned Avatar']);
        $owned->users()->attach(User::factory()->create());

        $this->getJson('/api/v1/avatars')
            ->assertOk()
            ->assertJsonPath('data.data.0.id', (string) $default->id)
            ->assertJsonMissing(['id' => $owned->id]);
    }

    public function test_list_supports_category_filter_and_pagination(): void
    {
        $avatar = Category::factory()->create(['slug' => 'avatar', 'name' => 'Avatars']);
        $female = Category::factory()->create(['slug' => 'female', 'name' => 'Female', 'parent_id' => $avatar->id]);
        Product::factory()->create(['category_id' => $female->id, 'name' => 'Female Avatar']);
        Product::factory()->create(['category_id' => $avatar->id, 'name' => 'Standard Avatar']);

        $this->getJson('/api/v1/avatars?category=female&limit=1&page=1')
            ->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.category', 'female')
            ->assertJsonPath('data.per_page', 1);
    }

    public function test_guest_can_view_avatar_details_and_download_model(): void
    {
        Storage::fake('local');
        $category = Category::factory()->create(['slug' => 'avatar', 'name' => 'Avatars']);
        $avatar = Product::factory()->create(['category_id' => $category->id, 'name' => 'Detailed Avatar']);
        $avatar->files()->create(['name' => 'avatar.glb', 'path' => 'products/avatar.glb', 'type' => 'model/gltf-binary', 'size' => 12]);
        $attribute = Attribute::factory()->create(['name' => 'Gender', 'slug' => 'gender']);
        $avatar->attributes()->attach($attribute->id, ['value' => 'Female']);
        Storage::disk('local')->put('products/avatar.glb', 'glb-content');

        $this->getJson('/api/v1/avatars/'.$avatar->id)
            ->assertOk()
            ->assertJsonPath('data.name', 'Detailed Avatar')
            ->assertJsonPath('data.format', 'glb')
            ->assertJsonPath('data.file_size', '12')
            ->assertJsonPath('data.attributes.0.name', 'Gender')
            ->assertJsonPath('data.attributes.0.slug', 'gender')
            ->assertJsonPath('data.attributes.0.value', 'Female');

        $this->get('/api/v1/avatars/'.$avatar->id.'/download')
            ->assertOk()
            ->assertHeader('content-disposition', 'attachment; filename=avatar.glb');
    }

    public function test_owned_or_missing_avatar_is_not_publicly_available(): void
    {
        $category = Category::factory()->create(['slug' => 'avatar']);
        $avatar = Product::factory()->create(['category_id' => $category->id]);
        $avatar->users()->attach(User::factory()->create());

        $this->getJson('/api/v1/avatars/'.$avatar->id)->assertNotFound();
        $this->getJson('/api/v1/avatars/99999')->assertNotFound();
    }
}
