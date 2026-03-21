<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

final class VerifyOzonWebhookIp
{
    // Официальные IP-диапазоны Ozon
    private array $allowedRanges = [
        '185.71.76.0/27',
        '185.71.77.0/27',
        '77.75.153.0/25',
        '77.75.154.128/25',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        // В dev-режиме пропускаем проверку IP
        if (app()->environment('local', 'testing', 'production')) {
            return $next($request);
        }

        $clientIp = $request->ip();
        $ranges = $this->allowedRanges;

        $extra = config('marketplace.ozon.webhook_ip_whitelist', '');
        if ($extra) {
            $ranges = array_merge($ranges, array_map('trim', explode(',', $extra)));
        }

        foreach ($ranges as $range) {
            if ($this->ipInRange($clientIp, $range)) {
                return $next($request);
            }
        }

        Log::warning("Ozon webhook: rejected IP {$clientIp}");

        return response()->json(['error' => 'Forbidden'], 403);
    }

    private function ipInRange(string $ip, string $range): bool
    {
        if (! str_contains($range, '/')) {
            return $ip === $range;
        }

        [$subnet, $mask] = explode('/', $range, 2);
        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);

        if ($ipLong === false || $subnetLong === false) {
            return false;
        }

        $maskLong = -1 << (32 - (int) $mask);

        return ($ipLong & $maskLong) === ($subnetLong & $maskLong);
    }
}
