<?php

namespace Tests\Unit\Services;

use App\Models\Category;
use App\Models\Product;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class CartServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_add_clamps_quantity_below_one_to_one(): void
    {
        $product = Product::factory()->create([
            'category_id' => Category::factory()->create()->id,
            'name' => 'Clamp Me',
        ]);

        $request = Request::create('/cart', 'POST');
        $request->setLaravelSession($this->app['session']->driver());

        $result = $this->app->make(CartService::class)->add($request, $product, 0);

        $this->assertSame(1, $result['snapshot']['items'][0]['quantity']);
    }
}
