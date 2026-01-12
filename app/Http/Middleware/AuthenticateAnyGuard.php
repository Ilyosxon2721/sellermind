<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthenticateAnyGuard
{
    public function handle(Request $request, Closure $next, ...$guards)
    {
        $guards = count($guards) ? $guards : ['web', 'sanctum'];

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                Auth::setDefaultDriver($guard);
                return $next($request);
            }
        }

        // For API requests, return JSON
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'message' => 'Unauthenticated.'
            ], 401);
        }

        // For web requests, redirect to login
        return redirect()->guest(route('login'));
    }
}
