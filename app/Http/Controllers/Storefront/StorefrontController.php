<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Store\Store;
use App\Models\Store\StoreAnalytics;
use Illuminate\Contracts\View\View;

/**
 * Публичная витрина магазина — главная страница, статические страницы
 */
final class StorefrontController extends Controller
{
    /**
     * Главная страница магазина
     *
     * GET /store/{slug}
     */
    public function home(string $slug): View
    {
        $store = Store::where('slug', $slug)
            ->where('is_active', true)
            ->where('is_published', true)
            ->with([
                'theme',
                'activeBanners',
                'visibleCategories.category',
                'featuredProducts.product.mainImage',
            ])
            ->firstOrFail();

        if ($store->maintenance_mode) {
            return view('storefront.maintenance', compact('store'));
        }

        $this->trackVisit($store);

        $template = $store->theme->template ?? 'default';

        return view("storefront.themes.{$template}.home", compact('store'));
    }

    /**
     * Статическая страница магазина (о нас, доставка, контакты и т.д.)
     *
     * GET /store/{slug}/page/{pageSlug}
     */
    public function page(string $slug, string $pageSlug): View
    {
        $store = $this->getPublishedStore($slug);

        $page = $store->activePages()
            ->where('slug', $pageSlug)
            ->firstOrFail();

        $this->trackPageView($store);

        $template = $store->theme->template ?? 'default';

        return view("storefront.themes.{$template}.page", compact('store', 'page'));
    }

    /**
     * Получить опубликованный магазин по slug
     */
    protected function getPublishedStore(string $slug): Store
    {
        return Store::where('slug', $slug)
            ->where('is_active', true)
            ->where('is_published', true)
            ->with('theme')
            ->firstOrFail();
    }

    /**
     * Трекинг визита (visits + page_views) — fire-and-forget
     */
    protected function trackVisit(Store $store): void
    {
        try {
            $today = now()->toDateString();

            StoreAnalytics::updateOrCreate(
                ['store_id' => $store->id, 'date' => $today],
                []
            );

            StoreAnalytics::where('store_id', $store->id)
                ->where('date', $today)
                ->increment('visits');

            StoreAnalytics::where('store_id', $store->id)
                ->where('date', $today)
                ->increment('page_views');
        } catch (\Throwable) {
            // Не прерываем пользовательский флоу при ошибке аналитики
        }
    }

    /**
     * Трекинг просмотра страницы (только page_views) — fire-and-forget
     */
    protected function trackPageView(Store $store): void
    {
        try {
            $today = now()->toDateString();

            StoreAnalytics::updateOrCreate(
                ['store_id' => $store->id, 'date' => $today],
                []
            );

            StoreAnalytics::where('store_id', $store->id)
                ->where('date', $today)
                ->increment('page_views');
        } catch (\Throwable) {
            // Не прерываем пользовательский флоу при ошибке аналитики
        }
    }
}
