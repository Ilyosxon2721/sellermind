<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Store\Store;
use App\Models\Store\StoreAnalytics;
use App\Models\Store\StoreProduct;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Публичный каталог товаров магазина
 */
final class CatalogController extends Controller
{
    /**
     * Каталог товаров с фильтрацией и сортировкой
     *
     * GET /store/{slug}/catalog
     *
     * Параметры: category_id, search, sort (price_asc, price_desc, newest), page, per_page
     */
    public function index(string $slug, Request $request): View
    {
        $store = $this->getPublishedStore($slug);

        $perPage = min((int) $request->input('per_page', $store->theme->products_per_page ?? 12), 48);

        $query = StoreProduct::where('store_id', $store->id)
            ->where('is_visible', true)
            ->with(['product.mainImage', 'product.variants']);

        // Фильтр по категории
        if ($categoryId = $request->input('category')) {
            $query->whereHas('product', function ($q) use ($categoryId) {
                $q->where('category_id', (int) $categoryId);
            });
        }

        // Поиск по названию товара
        if ($search = $request->input('search')) {
            $escapedSearch = $this->escapeLike($search);
            $query->where(function ($q) use ($escapedSearch) {
                $q->where('custom_name', 'like', "%{$escapedSearch}%")
                    ->orWhereHas('product', function ($productQuery) use ($escapedSearch) {
                        $productQuery->where('name', 'like', "%{$escapedSearch}%")
                            ->orWhere('article', 'like', "%{$escapedSearch}%");
                    });
            });
        }

        // Фильтр по цене
        if ($priceMin = $request->input('price_min')) {
            $query->where(function ($q) use ($priceMin) {
                $q->where('custom_price', '>=', (float) $priceMin)
                    ->orWhereHas('product', function ($pq) use ($priceMin) {
                        $pq->where('price', '>=', (float) $priceMin);
                    });
            });
        }

        if ($priceMax = $request->input('price_max')) {
            $query->where(function ($q) use ($priceMax) {
                $q->where('custom_price', '<=', (float) $priceMax)
                    ->orWhere(function ($q2) use ($priceMax) {
                        $q2->whereNull('custom_price')
                            ->whereHas('product', function ($pq) use ($priceMax) {
                                $pq->where('price', '<=', (float) $priceMax);
                            });
                    });
            });
        }

        // Сортировка
        $sort = $request->input('sort', 'position');
        $query = match ($sort) {
            'price_asc' => $query->orderByRaw('COALESCE(custom_price, 0) ASC')->orderBy('position'),
            'price_desc' => $query->orderByRaw('COALESCE(custom_price, 0) DESC')->orderBy('position'),
            'newest' => $query->orderByDesc('created_at'),
            'popular' => $query->orderByDesc('is_featured')->orderBy('position'),
            default => $query->orderBy('position'),
        };

        $products = $query->paginate($perPage)->withQueryString();

        $categories = $store->visibleCategories()->with('category')->get();

        $this->trackPageView($store);

        $template = $store->theme?->resolvedTemplate() ?? 'default';

        return view("storefront.themes.{$template}.catalog", compact(
            'store',
            'products',
            'categories',
        ));
    }

    /**
     * Страница отдельного товара
     *
     * GET /store/{slug}/product/{productId}
     */
    public function show(string $slug, int $productId): View
    {
        $store = $this->getPublishedStore($slug);

        $storeProduct = StoreProduct::where('store_id', $store->id)
            ->where('is_visible', true)
            ->where('id', $productId)
            ->with([
                'product.mainImage',
                'product.images',
                'product.variants',
                'product.options',
            ])
            ->firstOrFail();

        $this->trackPageView($store);

        $template = $store->theme?->resolvedTemplate() ?? 'default';

        return view("storefront.themes.{$template}.product", compact('store', 'storeProduct'));
    }

    /**
     * Получить опубликованный магазин по slug с загрузкой темы
     */
    private function getPublishedStore(string $slug): Store
    {
        return Store::where('slug', $slug)
            ->where('is_active', true)
            ->where('is_published', true)
            ->with('theme')
            ->firstOrFail();
    }

    /**
     * Трекинг просмотра страницы — fire-and-forget
     */
    private function trackPageView(Store $store): void
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
