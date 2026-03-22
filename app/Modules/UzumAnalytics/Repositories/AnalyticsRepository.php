<?php

// file: app/Modules/UzumAnalytics/Repositories/AnalyticsRepository.php

declare(strict_types=1);

namespace App\Modules\UzumAnalytics\Repositories;

use App\Modules\UzumAnalytics\Models\UzumCategory;
use App\Modules\UzumAnalytics\Models\UzumProductSnapshot;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

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
        $this->cacheTtl    = (int) config('uzum-crawler.cache_ttl_minutes', 30) * 60;
        $this->cachePrefix = config('uzum-crawler.redis_prefix', 'uzum_crawler:') . 'analytics:';
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
            'product_id'     => $product['id'],
            'category_id'    => $product['category']['id'] ?? $product['categoryId'] ?? 0,
            'shop_slug'      => $product['shop']['slug'] ?? $product['shopSlug'] ?? '',
            'title'          => $product['title'] ?? '',
            'price'          => (int) ($product['minSellPrice'] ?? 0) / 100,
            'original_price' => isset($product['maxFullPrice']) ? (int) $product['maxFullPrice'] / 100 : null,
            'rating'         => (float) ($product['rating'] ?? 0),
            'reviews_count'  => (int) ($product['reviewsAmount'] ?? $product['reviewsCount'] ?? 0),
            'orders_count'   => (int) ($product['ordersAmount'] ?? $product['ordersCount'] ?? 0),
            'scraped_at'     => now(),
        ]);

        // Инвалидировать кэш истории цен для этого товара
        Cache::forget($this->cachePrefix . "price_history:{$snapshot->product_id}");

        return $snapshot;
    }

    /**
     * История цен товара за период (из снепшотов).
     */
    public function getPriceHistory(int $productId, int $days = 30): Collection
    {
        $cacheKey = $this->cachePrefix . "price_history:{$productId}:{$days}";

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
        $cacheKey = $this->cachePrefix . "category_stats:{$categoryId}:{$days}";

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
                'category_id'    => $categoryId,
                'period_days'    => $days,
                'products_count' => (int) ($stats->products_count ?? 0),
                'min_price'      => (float) ($stats->min_price ?? 0),
                'max_price'      => (float) ($stats->max_price ?? 0),
                'avg_price'      => (float) ($stats->avg_price ?? 0),
                'avg_rating'     => (float) ($stats->avg_rating ?? 0),
                'total_reviews'  => (int) ($stats->total_reviews ?? 0),
                'top_products'   => $topProducts->toArray(),
            ];
        });
    }

    /**
     * Данные конкурирующего магазина: товары, средняя цена, рейтинг.
     */
    public function getCompetitorData(string $shopSlug, int $days = 30): array
    {
        $cacheKey = $this->cachePrefix . "competitor:{$shopSlug}:{$days}";

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
                'shop_slug'      => $shopSlug,
                'period_days'    => $days,
                'products_count' => (int) ($stats->products_count ?? 0),
                'avg_price'      => (float) ($stats->avg_price ?? 0),
                'avg_rating'     => (float) ($stats->avg_rating ?? 0),
                'total_reviews'  => (int) ($stats->total_reviews ?? 0),
                'total_orders'   => (int) ($stats->total_orders ?? 0),
                'products'       => $products->toArray(),
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
                    'parent_id'      => $parentId,
                    'title'          => $cat['title'] ?? $cat['name'] ?? '',
                    'products_count' => $cat['productCount'] ?? $cat['products_count'] ?? 0,
                    'last_synced_at' => now(),
                ]
            );

            // Рекурсивно сохранить дочерние категории
            if (! empty($cat['children'])) {
                $this->saveCategories($cat['children'], $cat['id']);
            }
        }

        Cache::forget($this->cachePrefix . 'categories_tree');
    }

    /**
     * Получить дерево категорий (из кэша).
     */
    public function getCategoriesTree(): Collection
    {
        $cacheKey = $this->cachePrefix . 'categories_tree';

        return Cache::remember($cacheKey, $this->cacheTtl, function () {
            return UzumCategory::whereNull('parent_id')
                ->with('children')
                ->orderBy('title')
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
