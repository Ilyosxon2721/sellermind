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
        
        // Add locale middleware globally
        $middleware->append(\App\Http\Middleware\SetLocale::class);

        // Configure API rate limiting
        $middleware->throttleApi('api');

        // Route middleware aliases
        $middleware->alias([
            'auth.any' => \App\Http\Middleware\AuthenticateAnyGuard::class,
            'subscription' => \App\Http\Middleware\CheckSubscription::class,
            'plan.limits' => \App\Http\Middleware\CheckPlanLimits::class,
            'setlocale' => \App\Http\Middleware\SetLocale::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Handle authentication exceptions for API routes
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'Сессия истекла. Пожалуйста, войдите в систему снова.',
                    'error' => 'unauthenticated',
                ], 401);
            }
        });

        // Handle validation exceptions for API routes
        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'Ошибка валидации данных',
                    'errors' => $e->errors(),
                    'error' => implode(', ', array_map(fn($errors) => implode(', ', $errors), $e->errors()))
                ], 422);
            }
        });

        // Handle model not found exceptions for API routes
        $exceptions->render(function (\Illuminate\Database\Eloquent\ModelNotFoundException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'Ресурс не найден',
                    'error' => 'not_found',
                ], 404);
            }
        });

        // Handle authorization exceptions for API routes
        $exceptions->render(function (\Illuminate\Auth\Access\AuthorizationException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'Доступ запрещён',
                    'error' => 'forbidden',
                ], 403);
            }
        });

        // Handle general exceptions for API routes (prevent 500 errors with stack traces)
        $exceptions->render(function (\Throwable $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                // Log the error for debugging
                \Log::error('API Exception', [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'url' => $request->fullUrl(),
                ]);

                // Don't expose internal errors in production
                $message = config('app.debug')
                    ? $e->getMessage()
                    : 'Произошла внутренняя ошибка. Пожалуйста, попробуйте позже.';

                return response()->json([
                    'message' => $message,
                    'error' => 'server_error',
                ], 500);
            }
        });
    })->create();
