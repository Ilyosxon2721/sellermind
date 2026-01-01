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

        return redirect()->guest(route('login'));
    }
}
