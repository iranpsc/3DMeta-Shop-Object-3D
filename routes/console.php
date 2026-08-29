<?php

use App\Jobs\SitemapGenerator;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('sitemap:generate', function () {
    dispatch(new SitemapGenerator);
    $this->info('Sitemap generation job dispatched.');
})->purpose('Dispatch the SitemapGenerator job to generate the sitemap.');

Schedule::command('sitemap:generate')
    ->everyThreeHours()
    ->withoutOverlapping();

Artisan::command('sentry:test-exception', function () {
    return $this->call('sentry:test');
})->purpose('Generate and send a test exception to the Sentry server.');
