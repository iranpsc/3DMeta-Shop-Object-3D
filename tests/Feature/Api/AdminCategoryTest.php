<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminCategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_categories(): void
    {
        Category::factory()->create(['name' => 'Electronics']);

        $this->actingAsAdminApiUser()
            ->getJson('/api/v1/admin/categories')
            ->assertOk()
            ->assertJsonPath('data.data.0.name', 'Electronics');
    }

    public function test_admin_can_create_category(): void
    {
        Storage::fake('public');

        $this->actingAsAdminApiUser()
            ->post('/api/v1/admin/categories', [
                'name' => 'Furniture',
                'slug' => 'furniture',
                'description' => 'Furniture category',
                'image' => UploadedFile::fake()->image('cat.jpg'),
            ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('message', 'دسته بندی با موفقیت ایجاد شد.');

        $this->assertDatabaseHas('categories', ['slug' => 'furniture']);
    }

    public function test_admin_can_update_category(): void
    {
        $category = Category::factory()->create(['name' => 'Old Name']);

        $this->actingAsAdminApiUser()
            ->putJson("/api/v1/admin/categories/{$category->id}", [
                'name' => 'Updated Name',
                'slug' => $category->slug,
                'description' => 'Updated description',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated Name');
    }

    public function test_admin_can_delete_empty_category(): void
    {
        $category = Category::factory()->create();

        $this->actingAsAdminApiUser()
            ->deleteJson("/api/v1/admin/categories/{$category->id}")
            ->assertOk();

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }
}
