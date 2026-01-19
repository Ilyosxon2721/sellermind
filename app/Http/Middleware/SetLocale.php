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
        // Get locale from URL segment or session
        $locale = $request->segment(1);
        
        // Supported locales
        $supportedLocales = ['uz', 'ru', 'en'];
        
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
