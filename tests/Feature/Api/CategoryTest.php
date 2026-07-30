<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_categories_index_paginates_categories_with_products(): void
    {
        $withProducts = Category::factory()->create(['name' => 'With Products']);
        Category::factory()->create(['name' => 'Empty']);

        Product::factory()->create([
            'category_id' => $withProducts->id,
            'published' => true,
            'created_by' => 'admin',
        ]);

        $response = $this->getJson('/api/v1/categories')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'slug', 'url', 'products_count'],
                ],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);

        $names = collect($response->json('data'))->pluck('name')->all();

        $this->assertContains('With Products', $names);
        $this->assertNotContains('Empty', $names);
    }

    public function test_category_show_returns_children_when_not_leaf(): void
    {
        $parent = Category::factory()->create(['slug' => 'parent-cat', 'name' => 'Parent']);
        $child = Category::factory()->create([
            'slug' => 'child-cat',
            'name' => 'Child',
            'parent_id' => $parent->id,
        ]);

        Product::factory()->create([
            'category_id' => $child->id,
            'published' => true,
            'created_by' => 'admin',
        ]);

        $this->getJson('/api/v1/categories/parent-cat')
            ->assertOk()
            ->assertJsonPath('data.slug', 'parent-cat')
            ->assertJsonPath('data.children.0.slug', 'child-cat')
            ->assertJsonPath('data.products', []);
    }

    public function test_category_show_returns_products_when_leaf(): void
    {
        $parent = Category::factory()->create(['slug' => 'furniture']);
        $leaf = Category::factory()->create([
            'slug' => 'chairs',
            'parent_id' => $parent->id,
        ]);

        $product = Product::factory()->create([
            'category_id' => $leaf->id,
            'published' => true,
            'created_by' => 'admin',
            'name' => 'Leaf Chair',
        ]);

        $this->getJson('/api/v1/categories/furniture/chairs')
            ->assertOk()
            ->assertJsonPath('data.slug', 'chairs')
            ->assertJsonPath('data.products.data.0.id', $product->id);
    }

    public function test_category_show_returns_404_for_unknown_slug(): void
    {
        $this->getJson('/api/v1/categories/does-not-exist')
            ->assertNotFound();
    }

    public function test_top_level_categories(): void
    {
        $root = Category::factory()->create(['name' => 'Root', 'parent_id' => null]);
        Category::factory()->create(['name' => 'Child', 'parent_id' => $root->id]);

        $response = $this->getJson('/api/v1/categories/top-level')
            ->assertOk();

        $names = collect($response->json('data'))->pluck('name')->all();

        $this->assertContains('Root', $names);
        $this->assertNotContains('Child', $names);
    }
}
