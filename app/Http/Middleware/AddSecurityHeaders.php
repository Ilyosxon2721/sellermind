<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

class AddSecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Генерируем CSP nonce до обработки запроса (для Blade шаблонов)
        $nonce = $this->generateNonce();
        app()->instance('csp-nonce', $nonce);
        Vite::useCspNonce($nonce);
        view()->share('cspNonce', $nonce);

        $response = $next($request);

        // Prevent clickjacking attacks
        $response->headers->set('X-Frame-Options', 'DENY');

        // Prevent MIME type sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // XSS Protection (legacy browsers)
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Strict Transport Security (HSTS) - Force HTTPS for 1 year
        if ($request->secure() || config('app.env') === 'production') {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // Referrer Policy
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Permissions Policy (formerly Feature-Policy)
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        // Content Security Policy (CSP)
        if (config('app.env') === 'production') {
            $csp = implode('; ', [
                "default-src 'self'",
                // Alpine.js requires unsafe-eval for x-data expressions
                "script-src 'self' 'nonce-{$nonce}' 'unsafe-eval' https://cdn.jsdelivr.net",
                "style-src 'self' 'unsafe-inline' https://fonts.bunny.net",
                "img-src 'self' data: https:",
                "font-src 'self' data: https://fonts.bunny.net",
                "connect-src 'self' wss: ws: https:",
                "object-src 'none'",
                "frame-ancestors 'none'",
                "base-uri 'self'",
                "form-action 'self'",
                "upgrade-insecure-requests",
            ]);
            $response->headers->set('Content-Security-Policy', $csp);
        }

        return $response;
    }

    /**
     * Генерация криптографически безопасного nonce
     */
    private function generateNonce(): string
    {
        return base64_encode(random_bytes(16));
    }
}
