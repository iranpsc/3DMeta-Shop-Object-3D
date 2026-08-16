<?php

namespace App\Jobs;

use App\Models\Category;
use App\Models\Product;
use App\Models\Tag;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Spatie\Sitemap\Sitemap;

class SitemapGenerator implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(Sitemap $sitemap): void
    {
        $sitemap->create();

        Product::published()->chunk(200, function ($products) use ($sitemap) {
            $sitemap->add($products);
        });

        $sitemap->writeToFile(public_path('sitemap/products.xml'));

        $sitemap->create()->add(Tag::all())
            ->writeToFile(public_path('sitemap/tags.xml'));

        $sitemap->create()->add(Category::all())
            ->writeToFile(public_path('sitemap/categories.xml'));
    }
}
