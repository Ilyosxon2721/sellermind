<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AuthenticateAnyGuard
{
    public function handle(Request $request, Closure $next, ...$guards)
    {
        $guards = count($guards) ? $guards : ['web', 'sanctum'];

        // Debug logging for auth issues
        Log::debug('AuthenticateAnyGuard: checking auth', [
            'url' => $request->url(),
            'method' => $request->method(),
            'has_session' => $request->hasSession(),
            'session_id' => $request->hasSession() ? $request->session()->getId() : null,
            'session_user_id' => $request->hasSession() ? $request->session()->get('_auth.web.id') : null,
            'has_bearer' => $request->bearerToken() ? true : false,
            'guards_to_check' => $guards,
            'cookies' => array_keys($request->cookies->all()),
        ]);

        foreach ($guards as $guard) {
            $isAuthenticated = Auth::guard($guard)->check();
            Log::debug("AuthenticateAnyGuard: guard '{$guard}' check", [
                'authenticated' => $isAuthenticated,
                'user_id' => $isAuthenticated ? Auth::guard($guard)->id() : null,
            ]);

            if ($isAuthenticated) {
                Auth::setDefaultDriver($guard);
                return $next($request);
            }
        }

        Log::warning('AuthenticateAnyGuard: no valid auth found', [
            'url' => $request->url(),
            'ip' => $request->ip(),
        ]);

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
