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
        
        // Priority 1: URL segment (for landing pages)
        $urlLocale = $request->segment(1);
        
        if (in_array($urlLocale, $supportedLocales)) {
            App::setLocale($urlLocale);
            Session::put('locale', $urlLocale);
        } else {
            // Priority 2: Authenticated user's preference
            $userLocale = null;
            if (auth()->check() && auth()->user()->locale) {
                $userLocale = auth()->user()->locale;
            }
            
            // Priority 3: Session locale
            $sessionLocale = Session::get('locale');
            
            // Priority 4: Default (uz)
            $locale = $userLocale ?? $sessionLocale ?? 'uz';
            
            // Validate and set
            if (in_array($locale, $supportedLocales)) {
                App::setLocale($locale);
            } else {
                App::setLocale('uz');
            }
        }
        
        return $next($request);
    }
}
