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

    public function test_admin_can_show_category(): void
    {
        $category = Category::factory()->create(['name' => 'Show Me']);

        $this->actingAsAdminApiUser()
            ->getJson("/api/v1/admin/categories/{$category->id}")
            ->assertOk()
            ->assertJsonPath('data.name', 'Show Me');
    }

    public function test_admin_can_fetch_category_form_data(): void
    {
        Category::factory()->create(['name' => 'Root']);

        $this->actingAsAdminApiUser()
            ->getJson('/api/v1/admin/categories/form-data')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Root');
    }

    public function test_admin_can_update_category_with_image(): void
    {
        Storage::fake('public');
        $category = Category::factory()->create(['name' => 'Old Name']);
        $category->image()->create(['path' => 'categories/old.jpg']);

        $this->actingAsAdminApiUser()
            ->post("/api/v1/admin/categories/{$category->id}", [
                '_method' => 'PUT',
                'name' => 'Updated With Image',
                'slug' => $category->slug,
                'description' => 'Updated description',
                'image' => UploadedFile::fake()->image('new-cat.jpg'),
            ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated With Image');
    }

    public function test_admin_can_delete_category_with_children(): void
    {
        $parent = Category::factory()->create();
        Category::factory()->create(['parent_id' => $parent->id]);

        $this->actingAsAdminApiUser()
            ->deleteJson("/api/v1/admin/categories/{$parent->id}")
            ->assertOk();

        $this->assertDatabaseMissing('categories', ['id' => $parent->id]);
        $this->assertDatabaseCount('categories', 0);
    }

    public function test_admin_cannot_delete_category_with_products(): void
    {
        $category = Category::factory()->create();
        \App\Models\Product::factory()->create(['category_id' => $category->id]);

        $this->actingAsAdminApiUser()
            ->deleteJson("/api/v1/admin/categories/{$category->id}")
            ->assertForbidden();
    }
}
