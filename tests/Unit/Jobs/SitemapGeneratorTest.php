<?php

namespace Tests\Unit\Jobs;

use App\Jobs\SitemapGenerator;
use App\Models\Category;
use App\Models\Product;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Spatie\Sitemap\Sitemap;
use Tests\TestCase;

class SitemapGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_writes_sitemaps(): void
    {
        Category::factory()->create();
        Tag::factory()->create();
        Product::factory()->create([
            'category_id' => Category::factory()->create()->id,
            'published' => true,
        ]);

        if (! is_dir(public_path('sitemap'))) {
            mkdir(public_path('sitemap'), 0777, true);
        }

        $sitemap = Mockery::mock(Sitemap::class);
        $sitemap->shouldReceive('create')->andReturnSelf();
        $sitemap->shouldReceive('add')->andReturnSelf();
        $sitemap->shouldReceive('writeToFile')->times(3)->andReturnSelf();

        (new SitemapGenerator)->handle($sitemap);

        $this->assertTrue(true);
    }
}
