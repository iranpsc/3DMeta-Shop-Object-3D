<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('sitemap:generate', function () {
    dispatch(new \App\Jobs\SitemapGenerator());
    $this->info('Sitemap generation job dispatched.');
})->purpose('Dispatch the SitemapGenerator job to generate the sitemap.');
