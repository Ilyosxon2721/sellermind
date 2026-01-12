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
            'api/*', // Exclude all API routes from CSRF (using Bearer token auth)
        ]);

        // Add performance headers globally
        $middleware->append(\App\Http\Middleware\AddPerformanceHeaders::class);

        // Add security headers globally
        $middleware->append(\App\Http\Middleware\AddSecurityHeaders::class);

        // Route middleware aliases
        $middleware->alias([
            'auth.any' => \App\Http\Middleware\AuthenticateAnyGuard::class,
            'subscription' => \App\Http\Middleware\CheckSubscription::class,
            'plan.limits' => \App\Http\Middleware\CheckPlanLimits::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
