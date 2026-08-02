<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Product;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagTest extends TestCase
{
    use RefreshDatabase;

    public function test_tag_products_are_paginated(): void
    {
        $category = Category::factory()->create();
        $tag = Tag::factory()->create(['slug' => 'low-poly', 'name' => 'Low Poly']);

        $product = Product::factory()->create([
            'category_id' => $category->id,
            'published' => true,
            'created_by' => 'admin',
            'name' => 'Tagged Product',
        ]);
        $product->tags()->attach($tag);

        Product::factory()->create([
            'category_id' => $category->id,
            'published' => true,
            'created_by' => 'admin',
            'name' => 'Untagged',
        ]);

        $this->getJson('/api/v1/tags/low-poly/products')
            ->assertOk()
            ->assertJsonPath('data.tag.slug', 'low-poly')
            ->assertJsonPath('data.products.data.0.id', $product->id)
            ->assertJsonStructure([
                'data' => [
                    'tag' => ['id', 'name', 'slug'],
                    'products' => [
                        'data',
                        'meta' => ['current_page', 'last_page', 'per_page', 'total'],
                    ],
                ],
            ]);
    }

    public function test_unknown_tag_returns_404(): void
    {
        $this->getJson('/api/v1/tags/missing/products')
            ->assertNotFound();
    }
}
