<?php

namespace Tests\Unit\Jobs;

use App\Jobs\DownloadFileJob;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DownloadFileJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_downloads_image_type(): void
    {
        Storage::fake('local');
        $product = Product::factory()->create([
            'category_id' => Category::factory()->create()->id,
        ]);

        $temp = tempnam(sys_get_temp_dir(), 'img');
        file_put_contents($temp, 'fake-image-bytes');

        $job = new DownloadFileJob('file://'.$temp, $product, 'image');
        $job->handle();

        $this->assertDatabaseHas('images', [
            'imageable_id' => $product->id,
            'imageable_type' => Product::class,
        ]);

        @unlink($temp);
    }

    public function test_handle_downloads_file_type(): void
    {
        Storage::fake('local');
        $product = Product::factory()->create([
            'category_id' => Category::factory()->create()->id,
        ]);

        $temp = tempnam(sys_get_temp_dir(), 'glb');
        file_put_contents($temp, 'fake-glb-bytes');

        $job = new DownloadFileJob('file://'.$temp, $product, 'file');
        $job->handle();

        $this->assertDatabaseHas('files', [
            'product_id' => $product->id,
        ]);

        @unlink($temp);
    }
}
