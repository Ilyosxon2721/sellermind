<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Storefront\Traits\StorefrontHelpers;
use App\Models\Store\Store;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;

/**
 * Публичная витрина магазина — главная страница, статические страницы
 */
final class StorefrontController extends Controller
{
    use StorefrontHelpers;

    /**
     * Главная страница магазина
     *
     * GET /store/{slug}
     */
    public function home(string $slug): View
    {
        $store = Cache::remember("storefront:home:{$slug}", 300, function () use ($slug) {
            return Store::where('slug', $slug)
                ->where('is_active', true)
                ->where('is_published', true)
                ->with([
                    'theme',
                    'activeBanners',
                    'visibleCategories.category',
                    'featuredProducts.product.mainImage',
                    'featuredProducts.product.variants:id,product_id,price_default,old_price_default',
                    'visibleProducts.product.mainImage',
                    'visibleProducts.product.variants:id,product_id,price_default,old_price_default',
                ])
                ->firstOrFail();
        });

        if ($store->maintenance_mode) {
            return view('storefront.maintenance', compact('store'));
        }

        $this->trackVisit($store);

        $template = $store->theme?->resolvedTemplate() ?? 'default';

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

        $page = Cache::remember("storefront:page:{$slug}:{$pageSlug}", 300, function () use ($store, $pageSlug) {
            return $store->activePages()
                ->where('slug', $pageSlug)
                ->firstOrFail();
        });

        $this->trackPageView($store);

        $template = $store->theme?->resolvedTemplate() ?? 'default';

        return view("storefront.themes.{$template}.page", compact('store', 'page'));
    }

    /**
     * Страница входа/регистрации покупателя
     *
     * GET /store/{slug}/login
     */
    public function login(string $slug): View
    {
        $store = $this->getPublishedStore($slug);
        $template = $store->theme?->resolvedTemplate() ?? 'default';

        // Если тема marketplace — используем её, иначе fallback
        $view = "storefront.themes.{$template}.account-login";

        if (! view()->exists($view)) {
            $view = 'storefront.themes.marketplace.account-login';
        }

        return view($view, compact('store'));
    }

    /**
     * Личный кабинет покупателя
     *
     * GET /store/{slug}/account
     */
    public function account(string $slug): View
    {
        $store = $this->getPublishedStore($slug);
        $template = $store->theme?->resolvedTemplate() ?? 'default';

        $view = "storefront.themes.{$template}.account";

        if (! view()->exists($view)) {
            $view = 'storefront.themes.marketplace.account';
        }

        return view($view, compact('store'));
    }

    /**
     * Страница избранного (Wishlist)
     *
     * GET /store/{slug}/wishlist
     */
    public function wishlist(string $slug): View
    {
        $store = $this->getPublishedStore($slug);
        $template = $store->theme?->resolvedTemplate() ?? 'default';

        $this->trackPageView($store);

        return view('storefront.wishlist', compact('store'));
    }
}
