<?php

namespace Tests\Feature\Api;

use App\Models\Attribute;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAttributeTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_attributes(): void
    {
        Attribute::factory()->create(['name' => 'Height', 'slug' => 'height']);

        $this->actingAsAdminApiUser()
            ->getJson('/api/v1/admin/attributes')
            ->assertOk()
            ->assertJsonPath('data.data.0.name', 'Height')
            ->assertJsonPath('data.data.0.can_delete', true);
    }

    public function test_admin_can_create_attribute(): void
    {
        $this->actingAsAdminApiUser()
            ->postJson('/api/v1/admin/attributes', [
                'name' => 'Width',
                'slug' => 'width',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'ویژگی جدید با موفقیت ایجاد شد.');

        $this->assertDatabaseHas('attributes', ['slug' => 'width']);
    }

    public function test_admin_can_delete_attribute(): void
    {
        $attribute = Attribute::factory()->create();

        $this->actingAsAdminApiUser()
            ->deleteJson("/api/v1/admin/attributes/{$attribute->id}")
            ->assertOk();

        $this->assertDatabaseMissing('attributes', ['id' => $attribute->id]);
    }

    public function test_admin_cannot_delete_attribute_attached_to_product(): void
    {
        $attribute = Attribute::factory()->create();
        $product = Product::factory()->create([
            'category_id' => Category::factory()->create()->id,
        ]);
        $product->attributes()->attach($attribute->id, ['value' => 'Large']);

        $this->actingAsAdminApiUser()
            ->deleteJson("/api/v1/admin/attributes/{$attribute->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('attributes', ['id' => $attribute->id]);
    }
}
