<?php

use App\Http\Middleware\Admin;
use App\Http\Middleware\AuthenticateWithOnceBasic;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo('/dashboard');

        // Sanctum SPA cookie auth for Next.js (cross-subdomain / localhost)
        $middleware->statefulApi();

        $middleware->preventRequestForgery(except: [
            '/callback',
        ]);

        $middleware->alias([
            'admin' => Admin::class,
            'auth.oncebasic' => AuthenticateWithOnceBasic::class,
        ]);
    })

    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
