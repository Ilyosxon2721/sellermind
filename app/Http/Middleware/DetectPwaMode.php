<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Определение режима PWA и автоматический редирект на PWA-версии страниц
 */
final class DetectPwaMode
{
    /**
     * Маппинг браузерных маршрутов на их PWA-версии
     *
     * @var array<string, string>
     */
    private const PWA_ROUTES = [
        'dashboard' => 'dashboard-flutter',
        'chat' => 'chat-pwa',
        'products' => 'products-pwa',
        'analytics' => 'analytics/pwa',
        'profile' => 'profile-pwa',
    ];

    /**
     * Обработка входящего запроса
     *
     * Определяет режим PWA по cookie или заголовку и расшаривает переменную во все views.
     * Если пользователь в PWA-режиме и текущий путь имеет PWA-версию — выполняет редирект.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $isPWA = $request->cookie('pwa_installed') === 'true'
            || $request->header('X-Requested-With') === 'com.sellermind.pwa';

        view()->share('isPWA', $isPWA);

        if ($isPWA) {
            $currentPath = trim($request->path(), '/');
            $pwaRoute = self::PWA_ROUTES[$currentPath] ?? null;

            if ($pwaRoute !== null) {
                return redirect('/'.$pwaRoute);
            }
        }

        return $next($request);
    }
}
