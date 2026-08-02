<?php

namespace Tests\Feature\Api;

use App\Models\Attribute;
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
            ->assertJsonPath('data.data.0.name', 'Height');
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
}
