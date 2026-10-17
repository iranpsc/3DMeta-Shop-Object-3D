<?php

namespace Tests\Feature\Api;

use App\Models\Attribute;
use App\Models\User;
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

    public function test_admin_can_update_attribute(): void
    {
        $attribute = Attribute::factory()->create([
            'name' => 'Height',
            'slug' => 'height',
        ]);

        $this->actingAsAdminApiUser()
            ->putJson("/api/v1/admin/attributes/{$attribute->id}", [
                'name' => 'Width',
                'slug' => 'width',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'ویژگی با موفقیت ویرایش شد.')
            ->assertJsonPath('data.name', 'Width')
            ->assertJsonPath('data.slug', 'width');

        $this->assertDatabaseHas('attributes', [
            'id' => $attribute->id,
            'name' => 'Width',
            'slug' => 'width',
        ]);
    }

    public function test_admin_can_update_attribute_keeping_the_same_name_and_slug(): void
    {
        $attribute = Attribute::factory()->create([
            'name' => 'Height',
            'slug' => 'height',
        ]);

        $this->actingAsAdminApiUser()
            ->putJson("/api/v1/admin/attributes/{$attribute->id}", [
                'name' => 'Height',
                'slug' => 'height',
            ])
            ->assertOk()
            ->assertJsonPath('data.id', $attribute->id);

        $this->assertDatabaseHas('attributes', [
            'id' => $attribute->id,
            'name' => 'Height',
            'slug' => 'height',
        ]);
    }

    public function test_admin_cannot_update_attribute_to_a_duplicate_name_or_slug(): void
    {
        Attribute::factory()->create([
            'name' => 'Width',
            'slug' => 'width',
        ]);
        $attribute = Attribute::factory()->create([
            'name' => 'Height',
            'slug' => 'height',
        ]);

        $this->actingAsAdminApiUser()
            ->putJson("/api/v1/admin/attributes/{$attribute->id}", [
                'name' => 'Width',
                'slug' => 'height',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);

        $this->actingAsAdminApiUser()
            ->putJson("/api/v1/admin/attributes/{$attribute->id}", [
                'name' => 'Height',
                'slug' => 'width',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['slug']);

        $this->assertDatabaseHas('attributes', [
            'id' => $attribute->id,
            'name' => 'Height',
            'slug' => 'height',
        ]);
    }

    public function test_update_attribute_requires_name_and_slug(): void
    {
        $attribute = Attribute::factory()->create();

        $this->actingAsAdminApiUser()
            ->putJson("/api/v1/admin/attributes/{$attribute->id}", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'slug']);
    }

    public function test_guest_cannot_update_attribute(): void
    {
        $attribute = Attribute::factory()->create();

        $this->putJson("/api/v1/admin/attributes/{$attribute->id}", [
            'name' => 'Width',
            'slug' => 'width',
        ])->assertUnauthorized();
    }

    public function test_non_admin_cannot_update_attribute(): void
    {
        $attribute = Attribute::factory()->create();
        $user = User::factory()->create();

        $this->actingAsVerifiedApiUser($user)
            ->putJson("/api/v1/admin/attributes/{$attribute->id}", [
                'name' => 'Width',
                'slug' => 'width',
            ])
            ->assertForbidden();
    }

    public function test_updating_missing_attribute_returns_not_found(): void
    {
        $this->actingAsAdminApiUser()
            ->putJson('/api/v1/admin/attributes/9999', [
                'name' => 'Width',
                'slug' => 'width',
            ])
            ->assertNotFound();
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
