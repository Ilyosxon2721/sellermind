<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class SetLocale
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Supported locales
        $supportedLocales = ['uz', 'ru', 'en'];
        
        // 1. Check authenticated user's locale preference first
        if (auth()->check() && auth()->user()->locale) {
            $locale = auth()->user()->locale;
            if (in_array($locale, $supportedLocales)) {
                App::setLocale($locale);
                Session::put('locale', $locale);
                return $next($request);
            }
        }
        
        // 2. Get locale from URL segment
        $locale = $request->segment(1);
        
        // If locale is valid, set it
        if (in_array($locale, $supportedLocales)) {
            App::setLocale($locale);
            Session::put('locale', $locale);
        } else {
            // Fallback to session or default (uz)
            $locale = Session::get('locale', 'uz');
            App::setLocale($locale);
        }
        
        return $next($request);
    }
}

