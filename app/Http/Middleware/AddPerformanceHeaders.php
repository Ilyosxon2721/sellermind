<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AddPerformanceHeaders
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Add security headers
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Add performance hints
        if ($request->secure()) {
            // Preconnect to external domains
            $response->headers->set('Link', '<https://fonts.bunny.net>; rel=preconnect; crossorigin', false);
        }

        // Enable gzip compression hint
        if (!$response->headers->has('Content-Encoding')) {
            $response->headers->set('Vary', 'Accept-Encoding');
        }

        return $response;
    }
}
