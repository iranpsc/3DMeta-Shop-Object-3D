<?php

namespace Tests\Feature\Api;

use App\Models\Attribute;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BuildPackageTest extends TestCase
{
    use RefreshDatabase;

    private function attachBuildAttributes(Product $product, array $values): void
    {
        foreach ($values as $slug => $value) {
            $attribute = Attribute::query()->firstOrCreate(
                ['slug' => $slug],
                ['name' => ucfirst($slug)]
            );
            $product->attributes()->attach($attribute->id, ['value' => (string) $value]);
        }
    }

    public function test_build_package_returns_matching_metargb_products(): void
    {
        $category = Category::factory()->create();
        $match = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Matching Package',
            'sku' => 'PKG-1',
        ]);
        $match->images()->create(['path' => 'products/pkg.jpg']);
        $match->files()->create([
            'name' => 'pkg.glb',
            'path' => 'products/pkg.glb',
            'type' => 'model/gltf-binary',
            'size' => '1 MB',
        ]);
        $this->attachBuildAttributes($match, [
            'area' => '50.5',
            'density' => '10',
            'karbari' => 'residential',
            'owner' => 'METARGB',
        ]);

        $nonMatch = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Other Owner',
            'sku' => 'PKG-2',
        ]);
        $this->attachBuildAttributes($nonMatch, [
            'area' => '20',
            'density' => '5',
            'karbari' => 'residential',
            'owner' => 'OTHER',
        ]);

        $this->getJson('/api/v1/build-package?area=100&density=20&karbari=residential')
            ->assertOk()
            ->assertJsonPath('data.0.sku', 'PKG-1')
            ->assertJsonStructure([
                'data' => [[
                    'id',
                    'name',
                    'sku',
                    'images',
                    'files',
                    'attributes',
                ]],
            ]);

        $skus = collect($this->getJson('/api/v1/build-package?area=100&density=20&karbari=residential')->json('data'))
            ->pluck('sku')
            ->all();

        $this->assertContains('PKG-1', $skus);
        $this->assertNotContains('PKG-2', $skus);
    }
}
