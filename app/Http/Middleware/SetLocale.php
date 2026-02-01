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
        $supportedLocales = ['uz', 'ru', 'en'];
        $locale = null;

        // 1. Priority: URL segment (explicitly requested)
        $urlLocale = $request->segment(1);
        if (in_array($urlLocale, $supportedLocales)) {
            $locale = $urlLocale;
            // \Log::debug('Locale chosen from URL: ' . $locale);
        }

        // 2. Priority: Authenticated User preference
        if (!$locale && auth()->check() && auth()->user()->locale) {
            $locale = auth()->user()->locale;
            // \Log::debug('Locale chosen from User pref: ' . $locale);
        }

        // 3. Priority: Session
        if (!$locale && Session::has('locale')) {
            $locale = Session::get('locale');
            // \Log::debug('Locale chosen from Session: ' . $locale);
        }

        // 4. Default
        if (!$locale || !in_array($locale, $supportedLocales)) {
            $locale = config('app.locale', 'ru');
            // \Log::debug('Locale chosen from Default: ' . $locale);
        }

        // Set the locale
        App::setLocale($locale);

        // Persistent in session if possible
        if (Session::isStarted()) {
            Session::put('locale', $locale);
        }

        return $next($request);
    }
}
