<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();

        // Exclude specific routes from CSRF verification
        $middleware->validateCsrfTokens(except: [
            'products',
            'products/*',
        ]);

        // Add performance headers globally
        $middleware->append(\App\Http\Middleware\AddPerformanceHeaders::class);

        // Route middleware aliases
        $middleware->alias([
            'auth.any' => \App\Http\Middleware\AuthenticateAnyGuard::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
