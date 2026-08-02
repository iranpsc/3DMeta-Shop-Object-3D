<?php

namespace Tests\Feature\Api;

use App\Models\Attribute;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class UserAssetTest extends TestCase
{
    use RefreshDatabase;

    private function oauthUser(User $user): void
    {
        config(['app.oauth_server_url' => 'https://accounts.example.com']);

        Http::fake([
            'https://accounts.example.com/api/user' => Http::response([
                'email' => $user->email,
                'name' => $user->name,
            ], 200),
        ]);
    }

    private function authHeaders(): array
    {
        return ['Authorization' => 'Bearer test-token'];
    }

    private function createOwnedProduct(User $user, array $overrides = []): Product
    {
        $category = Category::factory()->create([
            'slug' => $overrides['category_slug'] ?? 'furniture',
            'name' => 'Furniture',
            'parent_id' => null,
        ]);

        $product = Product::factory()->create(array_merge([
            'category_id' => $category->id,
            'name' => 'Owned Model',
            'published' => true,
        ], $overrides));

        $product->images()->create(['path' => 'products/owned.jpg']);
        $product->files()->create([
            'name' => 'model.glb',
            'path' => 'products/model.glb',
            'type' => 'model/gltf-binary',
            'size' => '1 MB',
        ]);

        $user->products()->attach($product->id, ['quantity' => 2]);

        return $product->fresh();
    }

    public function test_unauthorized_without_valid_oauth_token(): void
    {
        config(['app.oauth_server_url' => 'https://accounts.example.com']);

        Http::fake([
            'https://accounts.example.com/api/user' => Http::response(['message' => 'Unauthorized'], 401),
        ]);

        $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/user/assets/categories')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Unauthorized');
    }

    public function test_get_categories_for_user_products(): void
    {
        $user = User::factory()->create();
        $this->oauthUser($user);
        $this->createOwnedProduct($user);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/user/assets/categories')
            ->assertOk();

        $this->assertNotEmpty($response->json('data'));
        $this->assertEquals('Furniture', $response->json('data.0.name'));
    }

    public function test_get_categories_with_defaults(): void
    {
        $user = User::factory()->create();
        $this->oauthUser($user);
        $category = Category::factory()->create(['slug' => 'leaf', 'parent_id' => null]);
        Product::factory()->create(['category_id' => $category->id]);

        $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/user/assets/categories?defaults=1')
            ->assertOk()
            ->assertJsonPath('data.0.slug', 'leaf');
    }

    public function test_get_category_products_for_user(): void
    {
        $user = User::factory()->create();
        $this->oauthUser($user);
        $product = $this->createOwnedProduct($user);

        $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/user/assets/categories/'.$product->category_id)
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Owned Model')
            ->assertJsonPath('data.0.quantity', 2);
    }

    public function test_get_category_products_with_defaults(): void
    {
        $user = User::factory()->create();
        $this->oauthUser($user);
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Default Product',
        ]);
        $product->images()->create(['path' => 'products/default.jpg']);

        $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/user/assets/categories/'.$category->id.'?defaults=1')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Default Product');
    }

    public function test_get_product_details(): void
    {
        $user = User::factory()->create();
        $this->oauthUser($user);
        $product = $this->createOwnedProduct($user);
        $attribute = Attribute::factory()->create(['name' => 'Size', 'slug' => 'size']);
        $product->attributes()->attach($attribute->id, ['value' => 'Large']);

        $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/user/assets/products/'.$product->id)
            ->assertOk()
            ->assertJsonPath('data.name', 'Owned Model')
            ->assertJsonPath('data.attributes.0.value', 'Large');
    }

    public function test_search_user_products(): void
    {
        $user = User::factory()->create();
        $this->oauthUser($user);
        $this->createOwnedProduct($user, ['name' => 'Wood Chair']);

        $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/user/assets/search?q=Wood')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Wood Chair');
    }

    public function test_search_defaults_products(): void
    {
        $user = User::factory()->create();
        $this->oauthUser($user);
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Searchable Default',
        ]);
        $product->images()->create(['path' => 'products/search.jpg']);

        $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/user/assets/search?q=Searchable&defaults=1')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Searchable Default');
    }

    public function test_get_avatars_for_user(): void
    {
        $user = User::factory()->create();
        $this->oauthUser($user);
        $avatarCategory = Category::factory()->create(['slug' => 'avatar', 'name' => 'Avatars']);
        $product = Product::factory()->create([
            'category_id' => $avatarCategory->id,
            'name' => 'My Avatar',
        ]);
        $product->images()->create(['path' => 'avatars/a.png']);
        $user->products()->attach($product->id, ['quantity' => 1]);

        $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/user/assets/avatars')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'My Avatar');
    }

    public function test_get_avatars_with_defaults(): void
    {
        $user = User::factory()->create();
        $this->oauthUser($user);
        $avatarCategory = Category::factory()->create(['slug' => 'avatar', 'name' => 'Avatars']);
        $product = Product::factory()->create([
            'category_id' => $avatarCategory->id,
            'name' => 'Default Avatar',
        ]);
        $product->images()->create(['path' => 'avatars/d.png']);

        $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/user/assets/avatars?defaults=1')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Default Avatar');
    }

    public function test_get_avatar_by_id(): void
    {
        $user = User::factory()->create();
        $this->oauthUser($user);
        $avatarCategory = Category::factory()->create(['slug' => 'avatar', 'name' => 'Avatars']);
        $product = Product::factory()->create([
            'category_id' => $avatarCategory->id,
            'name' => 'Detail Avatar',
        ]);
        $product->images()->create(['path' => 'avatars/detail.png']);
        $product->files()->create([
            'name' => 'avatar.glb',
            'path' => 'products/avatar.glb',
            'type' => 'model/gltf-binary',
            'size' => '2 MB',
        ]);
        $attribute = Attribute::factory()->create(['name' => 'Style', 'slug' => 'style']);
        $product->attributes()->attach($attribute->id, ['value' => 'Custom']);
        $user->products()->attach($product->id, ['quantity' => 3]);

        $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/user/assets/avatars/'.$product->id)
            ->assertOk()
            ->assertJsonPath('data.name', 'Detail Avatar')
            ->assertJsonPath('data.quantity', 3)
            ->assertJsonPath('data.attributes.0.value', 'Custom');
    }

    public function test_get_avatar_returns_404_when_missing(): void
    {
        $user = User::factory()->create();
        $this->oauthUser($user);
        Category::factory()->create(['slug' => 'avatar']);

        $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/user/assets/avatars/99999')
            ->assertNotFound();
    }
}
