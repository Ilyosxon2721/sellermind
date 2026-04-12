<?php

declare(strict_types=1);

namespace App\Modules\UzumAnalytics\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCompanyScope;
use App\Models\MarketplaceAccount;
use App\Models\MarketplaceProduct;
use App\Modules\UzumAnalytics\Jobs\CrawlProductJob;
use App\Modules\UzumAnalytics\Models\UzumCategory;
use App\Modules\UzumAnalytics\Models\UzumProductSnapshot;
use App\Modules\UzumAnalytics\Models\UzumToken;
use App\Modules\UzumAnalytics\Models\UzumTrackedProduct;
use App\Modules\UzumAnalytics\Repositories\AnalyticsRepository;
use App\Modules\UzumAnalytics\Services\CircuitBreaker;
use App\Modules\UzumAnalytics\Services\UzumAnalyticsApiClient;
use App\Services\AIService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * API для модуля Uzum Analytics.
 *
 * Эндпоинты (ТЗ §6.3):
 *   GET  /api/analytics/uzum/categories
 *   GET  /api/analytics/uzum/category/{id}/products
 *   GET  /api/analytics/uzum/competitor/{slug}
 *   GET  /api/analytics/uzum/price-history/{productId}
 *   GET  /api/analytics/uzum/market-overview
 *   GET  /api/analytics/uzum/tracked
 *   POST /api/analytics/uzum/tracked
 *   DELETE /api/analytics/uzum/tracked/{productId}
 */
final class AnalyticsController extends Controller
{
    use HasCompanyScope;

    public function __construct(
        private readonly AnalyticsRepository $repository,
        private readonly CircuitBreaker $circuitBreaker,
        private readonly UzumAnalyticsApiClient $apiClient,
    ) {}

    /**
     * Список категорий с количеством товаров
     */
    public function categories(Request $request): JsonResponse
    {
        $tree = $this->repository->getCategoriesTree();

        return response()->json(['categories' => $tree]);
    }

    /**
     * Статистика категории + топ товары + топ продавцы (live данные из Uzum API)
     */
    public function categoryProducts(Request $request, int $id): JsonResponse
    {
        $days = (int) $request->get('days', 7);
        $limit = min((int) $request->get('limit', 20), 50);
        $stats = $this->repository->getCategoryStats($id, $days);

        try {
            $apiData = $this->apiClient->getCategory($id, 0, 48);
            $items = $apiData['data']['makeSearch']['items'] ?? [];

            $products = collect($items)
                ->map(fn ($item) => $item['catalogCard'] ?? null)
                ->filter()
                ->values();

            $topProducts = $products->map(fn ($card) => [
                'product_id' => $card['id'],
                'title' => $card['title'] ?? '',
                'shop_slug' => $card['shop']['slug'] ?? '',
                'shop_title' => $card['shop']['title'] ?? $card['shop']['slug'] ?? '',
                'price' => (int) ($card['minSellPrice'] ?? 0) / 100,
                'original_price' => (int) ($card['minFullPrice'] ?? 0) / 100,
                'rating' => (float) ($card['rating'] ?? 0),
                'reviews_count' => (int) ($card['reviewsCount'] ?? 0),
                'orders_count' => (int) ($card['ordersCount'] ?? 0),
            ])
                ->sortByDesc('orders_count')
                ->values()
                ->take($limit);

            $topSellers = $topProducts
                ->groupBy('shop_slug')
                ->map(fn ($shopProducts, $slug) => [
                    'shop_slug' => $slug,
                    'shop_title' => $shopProducts->first()['shop_title'],
                    'products_count' => $shopProducts->count(),
                    'total_orders' => $shopProducts->sum('orders_count'),
                    'total_reviews' => $shopProducts->sum('reviews_count'),
                    'avg_price' => (int) round($shopProducts->avg('price')),
                    'avg_rating' => round($shopProducts->avg('rating'), 2),
                ])
                ->sortByDesc('total_orders')
                ->values()
                ->take($limit);

            $stats['top_products'] = $topProducts->toArray();
            $stats['top_sellers'] = $topSellers->toArray();
            $stats['total_in_category'] = $apiData['data']['makeSearch']['total'] ?? 0;
        } catch (\Throwable) {
            $stats['top_products'] = $stats['top_products'] ?? [];
            $stats['top_sellers'] = [];
            $stats['total_in_category'] = 0;
        }

        return response()->json($stats);
    }

    /**
     * Данные магазина конкурента
     */
    public function competitor(Request $request, string $shopSlug): JsonResponse
    {
        $days = (int) $request->get('days', 30);
        $data = $this->repository->getCompetitorData($shopSlug, $days);

        return response()->json($data);
    }

    /**
     * История цен товара (#074)
     */
    public function priceHistory(Request $request, int $productId): JsonResponse
    {
        $days = (int) $request->get('days', 30);
        $history = $this->repository->getPriceHistory($productId, $days);

        return response()->json([
            'product_id' => $productId,
            'days' => $days,
            'history' => $history,
        ]);
    }

    /**
     * Сводка по отслеживаемым товарам текущей компании
     */
    public function marketOverview(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId();

        $tracked = UzumTrackedProduct::where('company_id', $companyId)
            ->orderByDesc('last_scraped_at')
            ->get();

        $overview = $tracked->map(function (UzumTrackedProduct $item) {
            $history = $this->repository->getPriceHistory($item->product_id, 7);

            return [
                'product_id' => $item->product_id,
                'title' => $item->title,
                'shop_slug' => $item->shop_slug,
                'last_price' => $item->last_price,
                'last_scraped_at' => $item->last_scraped_at,
                'alert_enabled' => $item->alert_enabled,
                'price_trend' => $history->pluck('price'),
            ];
        });

        return response()->json(['products' => $overview]);
    }

    /**
     * Список отслеживаемых товаров
     */
    public function tracked(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId();

        $items = UzumTrackedProduct::where('company_id', $companyId)
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['tracked' => $items]);
    }

    /**
     * Добавить товар в отслеживаемые
     */
    public function addTracked(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|integer',
            'alert_enabled' => 'boolean',
            'alert_threshold_pct' => 'integer|min:1|max:99',
        ]);

        $companyId = $this->getCompanyId();

        // Проверить лимит по тарифу (ТЗ: 20 Pro / 50 Business)
        $limit = config('uzum-crawler.limits.pro.max_tracked_products', 20);
        $current = UzumTrackedProduct::where('company_id', $companyId)->count();

        if ($current >= $limit) {
            return response()->json([
                'message' => "Достигнут лимит отслеживаемых товаров ({$limit}). Обновите тариф.",
            ], 422);
        }

        $tracked = UzumTrackedProduct::firstOrCreate(
            ['company_id' => $companyId, 'product_id' => $request->product_id],
            [
                'alert_enabled' => $request->boolean('alert_enabled', true),
                'alert_threshold_pct' => $request->integer('alert_threshold_pct', 5),
            ]
        );

        // Немедленно запустить первый снепшот
        CrawlProductJob::dispatch($request->product_id, $companyId)
            ->onQueue('uzum-crawler');

        return response()->json(['tracked' => $tracked], 201);
    }

    /**
     * Удалить товар из отслеживаемых
     */
    public function removeTracked(Request $request, int $productId): JsonResponse
    {
        $companyId = $this->getCompanyId();

        UzumTrackedProduct::where('company_id', $companyId)
            ->where('product_id', $productId)
            ->delete();

        return response()->json(['message' => 'Товар удалён из отслеживаемых']);
    }

    /**
     * Healthcheck краулера: токены, Circuit Breaker, последний снепшот (#086)
     */
    public function healthcheck(): JsonResponse
    {
        $activeTokens = UzumToken::active()->where('expires_at', '>', now())->count();
        $totalTokens = UzumToken::count();

        $lastSnapshot = UzumProductSnapshot::orderByDesc('scraped_at')
            ->value('scraped_at');

        $snapshotsToday = UzumProductSnapshot::whereDate('scraped_at', today())->count();

        return response()->json([
            'status' => 'ok',
            'tokens' => [
                'active' => $activeTokens,
                'total' => $totalTokens,
            ],
            'circuit_breaker' => $this->circuitBreaker->getStatus(),
            'last_snapshot' => $lastSnapshot?->toIso8601String(),
            'snapshots_today' => $snapshotsToday,
            'tracked_products' => UzumTrackedProduct::count(),
            'feature_enabled' => (bool) config('uzum-crawler.features.enabled'),
        ]);
    }

    /**
     * AI-анализ рынка на основе данных отслеживаемых товаров
     */
    public function aiInsights(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId();
        $cacheKey = "uzum_ai_insights_{$companyId}";

        if ($cached = Cache::get($cacheKey)) {
            return response()->json($cached);
        }

        $tracked = UzumTrackedProduct::where('company_id', $companyId)
            ->orderByDesc('last_scraped_at')
            ->get();

        if ($tracked->isEmpty()) {
            return response()->json([
                'insights' => null,
                'message' => 'Добавьте товары для мониторинга, чтобы получить AI-анализ.',
                'generated_at' => null,
            ]);
        }

        // Собираем данные о ценах за 7 дней
        $productsData = $tracked->map(function (UzumTrackedProduct $item): array {
            $history = $this->repository->getPriceHistory($item->product_id, 7);
            $prices = $history->map(fn ($h) => (float) $h->price)->values()->toArray();

            $changePct = null;
            if (count($prices) >= 2 && $prices[0] > 0) {
                $changePct = round(($prices[count($prices) - 1] - $prices[0]) / $prices[0] * 100, 1);
            }

            return [
                'title' => $item->title ?? "Товар #{$item->product_id}",
                'shop' => $item->shop_slug ?? 'неизвестно',
                'current_price' => (float) $item->last_price,
                'change_pct_7d' => $changePct,
            ];
        })->toArray();

        // Формируем промпт
        $lines = array_map(function (array $p): string {
            $price = number_format($p['current_price'], 0, '.', ' ');
            $change = $p['change_pct_7d'] !== null
                ? ($p['change_pct_7d'] >= 0 ? "+{$p['change_pct_7d']}%" : "{$p['change_pct_7d']}%")
                : 'нет истории';

            return "• «{$p['title']}» (магазин: {$p['shop']}): цена {$price} сум, изменение за 7 дней: {$change}";
        }, $productsData);

        $count = count($productsData);
        $prompt = "Ты — аналитик маркетплейса Uzum Market. Проанализируй ценовые данные конкурентов и дай рекомендации продавцу.\n\n"
            ."Отслеживаемые товары конкурентов ({$count} шт.):\n"
            .implode("\n", $lines)."\n\n"
            ."Дай анализ в трёх блоках:\n"
            ."**Ситуация на рынке** — 2-3 предложения о текущих тенденциях.\n"
            ."**Рекомендации** — конкретные действия (ценообразование, акции, стратегия).\n"
            ."**Прогноз** — что ожидать на следующей неделе.\n\n"
            .'Пиши конкретно, используй цифры из данных. Ответ на русском.';

        $aiService = app(AIService::class);
        $insights = $aiService->generateChatResponse([], $prompt, [
            'model' => 'fast',
            'company_id' => $companyId,
            'user_id' => $request->user()?->id,
        ]);

        $result = [
            'insights' => $insights,
            'generated_at' => now()->toIso8601String(),
            'products_count' => $tracked->count(),
        ];

        Cache::put($cacheKey, $result, now()->addHour());

        return response()->json($result);
    }

    /**
     * Синхронизация категорий вручную (без очереди — выполняется сразу)
     */
    public function syncCategories(): JsonResponse
    {
        try {
            // Сбрасываем кэш перед ручной синхронизацией, чтобы пустой ответ не блокировал повторные попытки
            $cacheKey = config('uzum-crawler.redis_prefix', 'uzum_crawler:').'api:root_categories';
            Cache::forget($cacheKey);

            $response = $this->apiClient->getRootCategories();
            $categories = $response['data']
                ?? $response['payload']
                ?? $response['categories']
                ?? $response;

            if (empty($categories) || ! is_array($categories)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Uzum API вернул пустой список категорий. Возможно, нет активных токенов.',
                    'raw' => array_keys($response),
                ], 422);
            }

            $this->repository->saveCategories($categories);

            $total = UzumCategory::count();

            return response()->json([
                'success' => true,
                'message' => "Синхронизировано. В базе {$total} категорий.",
                'root_count' => count($categories),
                'total_in_db' => $total,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка синхронизации: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Экспорт истории цен в CSV (#081)
     */
    public function export(Request $request): StreamedResponse
    {
        $request->validate([
            'type' => 'in:tracked,snapshots',
            'days' => 'integer|min:1|max:365',
        ]);

        $companyId = $this->getCompanyId();
        $days = (int) $request->get('days', 30);
        $type = $request->get('type', 'tracked');

        $filename = "uzum-analytics-{$type}-".now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($companyId, $days, $type): void {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM для корректного открытия в Excel
            fwrite($handle, "\xEF\xBB\xBF");

            if ($type === 'tracked') {
                fputcsv($handle, ['ID товара', 'Название', 'Магазин', 'Текущая цена (сум)', 'Последний снепшот', 'Алерты', 'Порог %']);

                UzumTrackedProduct::where('company_id', $companyId)
                    ->orderByDesc('created_at')
                    ->get()
                    ->each(function (UzumTrackedProduct $item) use ($handle): void {
                        fputcsv($handle, [
                            $item->product_id,
                            $item->title ?? '',
                            $item->shop_slug ?? '',
                            $item->last_price ?? '',
                            $item->last_scraped_at?->format('d.m.Y H:i') ?? '',
                            $item->alert_enabled ? 'Да' : 'Нет',
                            $item->alert_threshold_pct.'%',
                        ]);
                    });
            } else {
                // snapshots: история цен за N дней
                fputcsv($handle, ['ID товара', 'Название', 'Магазин', 'Цена (сум)', 'Оригинальная цена (сум)', 'Рейтинг', 'Отзывов', 'Заказов', 'Дата снепшота']);

                $tracked = UzumTrackedProduct::where('company_id', $companyId)
                    ->pluck('product_id')
                    ->toArray();

                UzumProductSnapshot::whereIn('product_id', $tracked)
                    ->where('scraped_at', '>=', now()->subDays($days))
                    ->orderBy('product_id')
                    ->orderBy('scraped_at')
                    ->get()
                    ->each(function (UzumProductSnapshot $snap) use ($handle): void {
                        fputcsv($handle, [
                            $snap->product_id,
                            $snap->title ?? '',
                            $snap->shop_slug ?? '',
                            $snap->price,
                            $snap->original_price ?? '',
                            $snap->rating,
                            $snap->reviews_count,
                            $snap->orders_count,
                            $snap->scraped_at->format('d.m.Y H:i'),
                        ]);
                    });
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    // -------------------------------------------------------------------------
    // Анализ конкурентов
    // -------------------------------------------------------------------------

    /**
     * Категории Uzum, где у текущей компании есть товары
     */
    public function ourCategories(): JsonResponse
    {
        $companyId = $this->getCompanyId();
        $categories = $this->repository->getCompanyCategoryIds($companyId);

        return response()->json(['categories' => $categories]);
    }

    /**
     * Рейтинги магазинов, товаров и позиция компании в категории
     */
    public function categoryRankings(Request $request, int $id): JsonResponse
    {
        $companyId = $this->getCompanyId();
        $limit = min((int) $request->get('limit', 30), 48);
        $sortBy = $request->get('sort', 'orders');

        // Определить наши shop_slug'и на Uzum
        $ourShopSlugs = $this->repository->getCompanyShopSlugs($companyId);

        // Получить товары категории с Uzum API (до 48 шт, сортировка по отзывам)
        try {
            $apiData = $this->apiClient->getCategory($id, 0, 48);
            $items = $apiData['data']['makeSearch']['items'] ?? [];
        } catch (\Throwable) {
            $items = [];
        }

        $products = collect($items)
            ->map(fn ($item) => $item['catalogCard'] ?? null)
            ->filter()
            ->values();

        // Маппинг товаров
        $productsList = $products->map(fn ($card) => [
            'product_id' => $card['id'],
            'title' => $card['title'] ?? '',
            'shop_slug' => $card['shop']['slug'] ?? '',
            'shop_title' => $card['shop']['title'] ?? $card['shop']['slug'] ?? '',
            'price' => (int) ($card['minSellPrice'] ?? 0) / 100,
            'original_price' => (int) ($card['minFullPrice'] ?? 0) / 100,
            'rating' => (float) ($card['rating'] ?? 0),
            'reviews_count' => (int) ($card['reviewsCount'] ?? 0),
            'orders_count' => (int) ($card['ordersCount'] ?? 0),
            'is_our_product' => in_array($card['shop']['slug'] ?? '', $ourShopSlugs, true),
        ]);

        // ---- Рейтинг магазинов ----
        $shopRankings = $productsList
            ->groupBy('shop_slug')
            ->map(fn ($shopProducts, $slug) => [
                'shop_slug' => $slug,
                'shop_title' => $shopProducts->first()['shop_title'],
                'products_count' => $shopProducts->count(),
                'total_orders' => $shopProducts->sum('orders_count'),
                'total_revenue' => (int) $shopProducts->sum(fn ($p) => $p['orders_count'] * $p['price']),
                'total_reviews' => $shopProducts->sum('reviews_count'),
                'avg_price' => (int) round($shopProducts->avg('price')),
                'avg_rating' => round($shopProducts->avg('rating'), 2),
                'is_our_shop' => in_array($slug, $ourShopSlugs, true),
            ])
            ->sortByDesc(match ($sortBy) {
                'revenue' => 'total_revenue',
                'reviews' => 'total_reviews',
                'rating' => 'avg_rating',
                'products' => 'products_count',
                default => 'total_orders',
            })
            ->values()
            ->take($limit);

        // Присвоить ранги
        $shopRankings = $shopRankings->map(fn ($shop, $i) => array_merge($shop, ['rank' => $i + 1]));

        // ---- Рейтинг товаров ----
        $productRankings = $productsList
            ->sortByDesc(match ($sortBy) {
                'revenue' => fn ($p) => $p['orders_count'] * $p['price'],
                'reviews' => 'reviews_count',
                'rating' => 'rating',
                'price_asc' => fn ($p) => -$p['price'],
                'price_desc' => 'price',
                default => 'orders_count',
            })
            ->values()
            ->take($limit)
            ->map(fn ($p, $i) => array_merge($p, ['rank' => $i + 1]));

        // ---- Позиция нашей компании ----
        $ourShop = $shopRankings->firstWhere('is_our_shop', true);

        // Ранги по разным метрикам
        $byOrders = $shopRankings->sortByDesc('total_orders')->values();
        $byRevenue = $shopRankings->sortByDesc('total_revenue')->values();
        $byReviews = $shopRankings->sortByDesc('total_reviews')->values();
        $byRating = $shopRankings->sortByDesc('avg_rating')->values();

        $categoryTotalOrders = $shopRankings->sum('total_orders');

        $ourMetrics = null;
        if ($ourShop) {
            $ourMetrics = [
                'shop_slug' => $ourShop['shop_slug'],
                'shop_title' => $ourShop['shop_title'],
                'products_in_category' => $ourShop['products_count'],
                'total_orders' => $ourShop['total_orders'],
                'total_revenue' => $ourShop['total_revenue'],
                'total_reviews' => $ourShop['total_reviews'],
                'avg_price' => $ourShop['avg_price'],
                'avg_rating' => $ourShop['avg_rating'],
                'rank_by_orders' => $byOrders->search(fn ($s) => $s['is_our_shop']) + 1,
                'rank_by_revenue' => $byRevenue->search(fn ($s) => $s['is_our_shop']) + 1,
                'rank_by_reviews' => $byReviews->search(fn ($s) => $s['is_our_shop']) + 1,
                'rank_by_rating' => $byRating->search(fn ($s) => $s['is_our_shop']) + 1,
                'total_shops' => $shopRankings->count(),
                'market_share_pct' => $categoryTotalOrders > 0
                    ? round($ourShop['total_orders'] / $categoryTotalOrders * 100, 2)
                    : 0,
                'category_total_orders' => $categoryTotalOrders,
            ];

            // FBS/FBO данные для наших товаров
            $accountIds = MarketplaceAccount::where('company_id', $companyId)
                ->where('marketplace', 'uzum')
                ->pluck('id');
            $ourFbsFbo = MarketplaceProduct::whereIn('marketplace_account_id', $accountIds)
                ->selectRaw('SUM(stock_fbs) as total_fbs, SUM(stock_fbo) as total_fbo, COUNT(*) as count')
                ->first();
            $ourMetrics['fulfillment'] = [
                'fbs_stock' => (int) ($ourFbsFbo->total_fbs ?? 0),
                'fbo_stock' => (int) ($ourFbsFbo->total_fbo ?? 0),
                'total_products' => (int) ($ourFbsFbo->count ?? 0),
            ];
        }

        $category = UzumCategory::find($id);

        return response()->json([
            'category' => $category ? ['id' => $category->id, 'title' => $category->title] : null,
            'total_products' => $apiData['data']['makeSearch']['total'] ?? $products->count(),
            'shop_rankings' => $shopRankings->toArray(),
            'product_rankings' => $productRankings->toArray(),
            'our_metrics' => $ourMetrics,
            'our_shop_slugs' => $ourShopSlugs,
        ]);
    }

    /**
     * История рангов компании в категории
     */
    public function rankingHistory(Request $request, int $categoryId): JsonResponse
    {
        $companyId = $this->getCompanyId();
        $days = (int) $request->get('days', 30);
        $history = $this->repository->getRankingHistory($companyId, $categoryId, $days);

        return response()->json([
            'category_id' => $categoryId,
            'days' => $days,
            'history' => $history,
        ]);
    }

    /**
     * Детали магазина конкурента в категории
     */
    public function competitorDetail(Request $request, int $categoryId, string $shopSlug): JsonResponse
    {
        try {
            $apiData = $this->apiClient->getCategory($categoryId, 0, 48);
            $items = $apiData['data']['makeSearch']['items'] ?? [];
        } catch (\Throwable) {
            return response()->json(['products' => [], 'stats' => null]);
        }

        $products = collect($items)
            ->map(fn ($item) => $item['catalogCard'] ?? null)
            ->filter()
            ->values();

        $shopProducts = $products
            ->filter(fn ($card) => ($card['shop']['slug'] ?? '') === $shopSlug)
            ->map(fn ($card) => [
                'product_id' => $card['id'],
                'title' => $card['title'] ?? '',
                'price' => (int) ($card['minSellPrice'] ?? 0) / 100,
                'original_price' => (int) ($card['minFullPrice'] ?? 0) / 100,
                'rating' => (float) ($card['rating'] ?? 0),
                'reviews_count' => (int) ($card['reviewsCount'] ?? 0),
                'orders_count' => (int) ($card['ordersCount'] ?? 0),
            ])
            ->sortByDesc('orders_count')
            ->values();

        $stats = null;
        if ($shopProducts->isNotEmpty()) {
            $stats = [
                'products_count' => $shopProducts->count(),
                'total_orders' => $shopProducts->sum('orders_count'),
                'total_revenue' => (int) $shopProducts->sum(fn ($p) => $p['orders_count'] * $p['price']),
                'total_reviews' => $shopProducts->sum('reviews_count'),
                'avg_price' => (int) round($shopProducts->avg('price')),
                'min_price' => (int) $shopProducts->min('price'),
                'max_price' => (int) $shopProducts->max('price'),
                'avg_rating' => round($shopProducts->avg('rating'), 2),
            ];
        }

        return response()->json([
            'shop_slug' => $shopSlug,
            'category_id' => $categoryId,
            'products' => $shopProducts->toArray(),
            'stats' => $stats,
        ]);
    }

    /**
     * Экспорт рейтингов категории в CSV
     */
    public function exportRankings(Request $request, int $categoryId): StreamedResponse
    {
        $companyId = $this->getCompanyId();
        $ourShopSlugs = $this->repository->getCompanyShopSlugs($companyId);

        try {
            $apiData = $this->apiClient->getCategory($categoryId, 0, 48);
            $items = $apiData['data']['makeSearch']['items'] ?? [];
        } catch (\Throwable) {
            $items = [];
        }

        $products = collect($items)
            ->map(fn ($item) => $item['catalogCard'] ?? null)
            ->filter()
            ->values();

        $category = UzumCategory::find($categoryId);
        $catTitle = $category?->title ?? "category-{$categoryId}";
        $type = $request->get('type', 'shops');
        $filename = "uzum-rankings-{$catTitle}-{$type}-".now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($products, $ourShopSlugs, $type): void {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");

            if ($type === 'products') {
                fputcsv($handle, ['#', 'ID', 'Название', 'Магазин', 'Цена (сум)', 'Скидка', 'Заказов', 'Отзывов', 'Рейтинг', 'Наш']);
                $rank = 0;
                $products->sortByDesc(fn ($card) => (int) ($card['ordersCount'] ?? 0))
                    ->each(function ($card) use ($handle, $ourShopSlugs, &$rank): void {
                        $rank++;
                        $slug = $card['shop']['slug'] ?? '';
                        $price = (int) ($card['minSellPrice'] ?? 0) / 100;
                        $origPrice = (int) ($card['minFullPrice'] ?? 0) / 100;
                        fputcsv($handle, [
                            $rank,
                            $card['id'],
                            $card['title'] ?? '',
                            $card['shop']['title'] ?? $slug,
                            $price,
                            $origPrice > $price ? $origPrice : '',
                            (int) ($card['ordersCount'] ?? 0),
                            (int) ($card['reviewsCount'] ?? 0),
                            (float) ($card['rating'] ?? 0),
                            in_array($slug, $ourShopSlugs, true) ? 'Да' : '',
                        ]);
                    });
            } else {
                fputcsv($handle, ['#', 'Магазин', 'Товаров', 'Заказов', 'Выручка (сум)', 'Отзывов', 'Ср. цена', 'Рейтинг', 'Наш']);
                $rank = 0;
                $products->groupBy(fn ($card) => $card['shop']['slug'] ?? '')
                    ->map(fn ($prods, $slug) => [
                        'slug' => $slug,
                        'title' => $prods->first()['shop']['title'] ?? $slug,
                        'count' => $prods->count(),
                        'orders' => $prods->sum(fn ($c) => (int) ($c['ordersCount'] ?? 0)),
                        'revenue' => (int) $prods->sum(fn ($c) => (int) ($c['ordersCount'] ?? 0) * ((int) ($c['minSellPrice'] ?? 0) / 100)),
                        'reviews' => $prods->sum(fn ($c) => (int) ($c['reviewsCount'] ?? 0)),
                        'avg_price' => (int) round($prods->avg(fn ($c) => (int) ($c['minSellPrice'] ?? 0) / 100)),
                        'avg_rating' => round($prods->avg(fn ($c) => (float) ($c['rating'] ?? 0)), 2),
                        'is_our' => in_array($slug, $ourShopSlugs, true),
                    ])
                    ->sortByDesc('orders')
                    ->each(function ($shop) use ($handle, &$rank): void {
                        $rank++;
                        fputcsv($handle, [
                            $rank, $shop['title'], $shop['count'], $shop['orders'],
                            $shop['revenue'], $shop['reviews'], $shop['avg_price'],
                            $shop['avg_rating'], $shop['is_our'] ? 'Да' : '',
                        ]);
                    });
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
