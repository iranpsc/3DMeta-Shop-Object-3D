<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(Registered::class, SendEmailVerificationNotification::class);

        // Nginx terminates TLS and proxies to http://127.0.0.1:8000. Without this,
        // absolute URLs (storage, signed links, redirects) are generated as http://
        // and browsers on the HTTPS frontend block them as Mixed Content.
        if (! $this->app->environment(['local', 'testing'])) {
            URL::forceScheme('https');
        }

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        Blade::directive('hasRole', fn ($role) => "<?php if(auth()->check() && auth()->user()->hasRole({$role})): ?>");
        Blade::directive('endHasRole', fn () => '<?php endif; ?>');

        Livewire::addPersistentMiddleware([
            \App\Http\Middleware\Admin::class,
        ]);
    }
}
