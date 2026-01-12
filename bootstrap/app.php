<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
        then: function () {
            // Configure API rate limiting
            RateLimiter::for('api', function (Request $request) {
                return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
            });
        }
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
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
