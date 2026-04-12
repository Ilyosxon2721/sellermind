<?php

// file: app/Modules/UzumAnalytics/Repositories/AnalyticsRepository.php

declare(strict_types=1);

namespace App\Modules\UzumAnalytics\Repositories;

use App\Models\MarketplaceAccount;
use App\Models\MarketplaceProduct;
use App\Modules\UzumAnalytics\Models\UzumCategory;
use App\Modules\UzumAnalytics\Models\UzumProductSnapshot;
use App\Modules\UzumAnalytics\Models\UzumRankingHistory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Репозиторий для хранения и получения аналитических данных Uzum.
 *
 * Кэшируем агрегированные данные в Redis на 30 минут,
 * так как снепшоты собираются 4 раза в сутки.
 */
final class AnalyticsRepository
{
    private readonly int $cacheTtl;

    private readonly string $cachePrefix;

    public function __construct()
    {
        $this->cacheTtl = (int) config('uzum-crawler.cache_ttl_minutes', 30) * 60;
        $this->cachePrefix = config('uzum-crawler.redis_prefix', 'uzum_crawler:').'analytics:';
    }

    // -------------------------------------------------------------------------
    // Снепшоты товаров
    // -------------------------------------------------------------------------

    /**
     * Сохранить снепшот товара из ответа Uzum API.
     * Цены приходят в тийинах — делим на 100.
     */
    public function saveProductSnapshot(array $apiProduct): UzumProductSnapshot
    {
        $product = $apiProduct['product'] ?? $apiProduct;

        $snapshot = UzumProductSnapshot::create([
            'product_id' => $product['id'],
            'category_id' => $product['category']['id'] ?? $product['categoryId'] ?? 0,
            'shop_slug' => $product['shop']['slug'] ?? $product['shopSlug'] ?? '',
            'title' => $product['title'] ?? '',
            'price' => (int) ($product['minSellPrice'] ?? 0) / 100,
            'original_price' => isset($product['maxFullPrice']) ? (int) $product['maxFullPrice'] / 100 : null,
            'rating' => (float) ($product['rating'] ?? 0),
            'reviews_count' => (int) ($product['reviewsAmount'] ?? $product['reviewsCount'] ?? 0),
            'orders_count' => (int) ($product['ordersAmount'] ?? $product['ordersCount'] ?? 0),
            'scraped_at' => now(),
        ]);

        // Инвалидировать кэш истории цен для этого товара
        Cache::forget($this->cachePrefix."price_history:{$snapshot->product_id}");

        return $snapshot;
    }

    /**
     * История цен товара за период (из снепшотов).
     */
    public function getPriceHistory(int $productId, int $days = 30): Collection
    {
        $cacheKey = $this->cachePrefix."price_history:{$productId}:{$days}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($productId, $days) {
            return UzumProductSnapshot::forProduct($productId)
                ->recent($days)
                ->orderBy('scraped_at')
                ->get(['scraped_at', 'price', 'original_price', 'rating', 'reviews_count', 'orders_count']);
        });
    }

    /**
     * Статистика по категории: мин/макс/средняя цена, топ товары.
     */
    public function getCategoryStats(int $categoryId, int $days = 7): array
    {
        $cacheKey = $this->cachePrefix."category_stats:{$categoryId}:{$days}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($categoryId, $days) {
            $since = now()->subDays($days);

            $stats = UzumProductSnapshot::forCategory($categoryId)
                ->where('scraped_at', '>=', $since)
                ->selectRaw('
                    COUNT(DISTINCT product_id) as products_count,
                    MIN(price) as min_price,
                    MAX(price) as max_price,
                    ROUND(AVG(price), 2) as avg_price,
                    ROUND(AVG(rating), 2) as avg_rating,
                    SUM(reviews_count) as total_reviews
                ')
                ->first();

            $topProducts = UzumProductSnapshot::forCategory($categoryId)
                ->where('scraped_at', '>=', $since)
                ->orderByDesc('orders_count')
                ->limit(10)
                ->get(['product_id', 'title', 'price', 'rating', 'reviews_count', 'orders_count', 'shop_slug']);

            return [
                'category_id' => $categoryId,
                'period_days' => $days,
                'products_count' => (int) ($stats->products_count ?? 0),
                'min_price' => (float) ($stats->min_price ?? 0),
                'max_price' => (float) ($stats->max_price ?? 0),
                'avg_price' => (float) ($stats->avg_price ?? 0),
                'avg_rating' => (float) ($stats->avg_rating ?? 0),
                'total_reviews' => (int) ($stats->total_reviews ?? 0),
                'top_products' => $topProducts->toArray(),
            ];
        });
    }

    /**
     * Данные конкурирующего магазина: товары, средняя цена, рейтинг.
     */
    public function getCompetitorData(string $shopSlug, int $days = 30): array
    {
        $cacheKey = $this->cachePrefix."competitor:{$shopSlug}:{$days}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($shopSlug, $days) {
            $since = now()->subDays($days);

            $stats = UzumProductSnapshot::where('shop_slug', $shopSlug)
                ->where('scraped_at', '>=', $since)
                ->selectRaw('
                    COUNT(DISTINCT product_id) as products_count,
                    ROUND(AVG(price), 2) as avg_price,
                    ROUND(AVG(rating), 2) as avg_rating,
                    SUM(reviews_count) as total_reviews,
                    SUM(orders_count) as total_orders
                ')
                ->first();

            $products = UzumProductSnapshot::where('shop_slug', $shopSlug)
                ->where('scraped_at', '>=', $since)
                ->orderByDesc('orders_count')
                ->limit(50)
                ->get(['product_id', 'title', 'price', 'original_price', 'rating', 'reviews_count', 'orders_count']);

            return [
                'shop_slug' => $shopSlug,
                'period_days' => $days,
                'products_count' => (int) ($stats->products_count ?? 0),
                'avg_price' => (float) ($stats->avg_price ?? 0),
                'avg_rating' => (float) ($stats->avg_rating ?? 0),
                'total_reviews' => (int) ($stats->total_reviews ?? 0),
                'total_orders' => (int) ($stats->total_orders ?? 0),
                'products' => $products->toArray(),
            ];
        });
    }

    // -------------------------------------------------------------------------
    // Категории
    // -------------------------------------------------------------------------

    /**
     * Сохранить дерево категорий из API.
     * Использует upsert для идемпотентности.
     */
    public function saveCategories(array $categories, ?int $parentId = null): void
    {
        foreach ($categories as $cat) {
            UzumCategory::updateOrCreate(
                ['id' => $cat['id']],
                [
                    'parent_id' => $parentId,
                    'title' => $cat['title'] ?? $cat['name'] ?? '',
                    'products_count' => $cat['productCount'] ?? $cat['products_count'] ?? 0,
                    'last_synced_at' => now(),
                ]
            );

            // Рекурсивно сохранить дочерние категории
            if (! empty($cat['children'])) {
                $this->saveCategories($cat['children'], $cat['id']);
            }
        }

        Cache::forget($this->cachePrefix.'categories_tree');
    }

    /**
     * Получить дерево категорий (из кэша).
     */
    public function getCategoriesTree(): Collection
    {
        $cacheKey = $this->cachePrefix.'categories_tree';

        return Cache::remember($cacheKey, $this->cacheTtl, function () {
            return UzumCategory::whereNull('parent_id')
                ->with('children')
                ->orderBy('title')
                ->get();
        });
    }

    // -------------------------------------------------------------------------
    // Анализ конкурентов
    // -------------------------------------------------------------------------

    /**
     * Получить shop_slug'и компании на Uzum (из снепшотов или MarketplaceProduct)
     */
    public function getCompanyShopSlugs(int $companyId): array
    {
        $cacheKey = $this->cachePrefix."company_shop_slugs:{$companyId}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($companyId): array {
            // Получить Uzum аккаунты компании
            $accountIds = MarketplaceAccount::where('company_id', $companyId)
                ->where('marketplace', 'uzum')
                ->pluck('id');

            if ($accountIds->isEmpty()) {
                return [];
            }

            // Получить external_product_id наших товаров на Uzum
            $externalIds = MarketplaceProduct::whereIn('marketplace_account_id', $accountIds)
                ->whereNotNull('external_product_id')
                ->pluck('external_product_id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->toArray();

            if (empty($externalIds)) {
                return [];
            }

            // Найти shop_slug из снепшотов наших товаров
            return UzumProductSnapshot::whereIn('product_id', $externalIds)
                ->whereNotNull('shop_slug')
                ->where('shop_slug', '!=', '')
                ->distinct()
                ->pluck('shop_slug')
                ->toArray();
        });
    }

    /**
     * Получить категории Uzum, где у компании есть товары
     */
    public function getCompanyCategoryIds(int $companyId): Collection
    {
        $cacheKey = $this->cachePrefix."company_categories:{$companyId}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($companyId): Collection {
            $accountIds = MarketplaceAccount::where('company_id', $companyId)
                ->where('marketplace', 'uzum')
                ->pluck('id');

            if ($accountIds->isEmpty()) {
                return collect();
            }

            $externalIds = MarketplaceProduct::whereIn('marketplace_account_id', $accountIds)
                ->whereNotNull('external_product_id')
                ->pluck('external_product_id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->toArray();

            if (empty($externalIds)) {
                return collect();
            }

            // Находим категории из снепшотов наших товаров
            $categoryIds = UzumProductSnapshot::whereIn('product_id', $externalIds)
                ->where('category_id', '>', 0)
                ->distinct()
                ->pluck('category_id')
                ->toArray();

            // Получить информацию о категориях
            return UzumCategory::whereIn('id', $categoryIds)
                ->get(['id', 'title', 'products_count'])
                ->map(function (UzumCategory $cat) use ($externalIds) {
                    $ourCount = UzumProductSnapshot::where('category_id', $cat->id)
                        ->whereIn('product_id', $externalIds)
                        ->distinct('product_id')
                        ->count('product_id');

                    return [
                        'id' => $cat->id,
                        'title' => $cat->title,
                        'products_count' => $cat->products_count,
                        'our_products_count' => $ourCount,
                    ];
                });
        });
    }

    /**
     * История рангов компании в категории
     */
    public function getRankingHistory(int $companyId, int $categoryId, int $days = 30): Collection
    {
        $cacheKey = $this->cachePrefix."ranking_history:{$companyId}:{$categoryId}:{$days}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($companyId, $categoryId, $days) {
            return UzumRankingHistory::where('company_id', $companyId)
                ->where('category_id', $categoryId)
                ->where('recorded_at', '>=', now()->subDays($days))
                ->orderBy('recorded_at')
                ->get();
        });
    }

    /**
     * Инвалидировать весь кэш аналитики (вызывать после пакетного обновления).
     */
    public function flushCache(): void
    {
        Cache::flush(); // TODO: заменить на selective flush по тегам если настроить Redis tags
    }
}
