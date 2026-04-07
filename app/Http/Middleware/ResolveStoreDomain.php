<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Store\Store;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Резолвинг магазина по поддомену (*.sellermind.uz) или кастомному домену
 *
 * Поддерживает:
 * 1. Бесплатный поддомен: forris-store.sellermind.uz → Store(slug: forris-store)
 * 2. Кастомный домен: forris.uz → Store(custom_domain: forris.uz)
 * 3. Fallback путь: sellermind.uz/store/forris-store (без middleware)
 */
final class ResolveStoreDomain
{
    /**
     * Основной домен платформы (без поддоменов)
     */
    private const PLATFORM_DOMAINS = [
        'sellermind.uz',
        'www.sellermind.uz',
    ];

    /**
     * Системные поддомены — НЕ магазины
     */
    private const RESERVED_SUBDOMAINS = [
        'www', 'api', 'admin', 'app', 'mail', 'ftp', 'cdn', 'static',
        'staging', 'dev', 'test', 'panel', 'dashboard', 'billing',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();

        // 1. Основной домен платформы — пропускаем (обычные маршруты)
        if (in_array($host, self::PLATFORM_DOMAINS, true)) {
            return $next($request);
        }

        // 2. Поддомен *.sellermind.uz → slug магазина
        $baseDomain = config('app.store_base_domain', 'sellermind.uz');
        if (str_ends_with($host, '.' . $baseDomain)) {
            $subdomain = str_replace('.' . $baseDomain, '', $host);

            if (in_array($subdomain, self::RESERVED_SUBDOMAINS, true)) {
                return $next($request);
            }

            $store = Store::where('slug', $subdomain)
                ->where('is_active', true)
                ->where('is_published', true)
                ->first();

            if ($store) {
                return $this->serveStore($request, $next, $store);
            }

            abort(404, 'Магазин не найден');
        }

        // 3. Кастомный домен клиента (forris.uz)
        $store = Store::where('custom_domain', $host)
            ->where('domain_verified', true)
            ->where('is_active', true)
            ->where('is_published', true)
            ->first();

        if ($store) {
            return $this->serveStore($request, $next, $store);
        }

        // 4. Неизвестный домен → 404
        abort(404, 'Магазин не найден');
    }

    /**
     * Подставить store в маршрут и перенаправить на storefront
     */
    private function serveStore(Request $request, Closure $next, Store $store): Response
    {
        // Сохраняем магазин в request для контроллеров
        $request->attributes->set('resolved_store', $store);
        $request->attributes->set('resolved_store_slug', $store->slug);

        // Перезаписываем путь: / → /store/{slug}, /catalog → /store/{slug}/catalog
        $path = $request->getPathInfo();

        // Корень домена → главная магазина
        if ($path === '/' || $path === '') {
            $path = '/store/' . $store->slug;
        } else {
            // /catalog → /store/slug/catalog, /product/5 → /store/slug/product/5
            $path = '/store/' . $store->slug . $path;
        }

        // Переопределяем путь запроса для маршрутизатора
        $request->server->set('REQUEST_URI', $path . ($request->getQueryString() ? '?' . $request->getQueryString() : ''));
        $request->initialize(
            $request->query->all(),
            $request->request->all(),
            $request->attributes->all(),
            $request->cookies->all(),
            $request->files->all(),
            $request->server->all(),
            $request->getContent()
        );

        return $next($request);
    }
}
