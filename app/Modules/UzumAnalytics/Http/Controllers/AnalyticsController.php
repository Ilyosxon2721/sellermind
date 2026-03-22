<?php

declare(strict_types=1);

namespace App\Modules\UzumAnalytics\Http\Controllers;

use App\Http\Controllers\Controller;
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
use Illuminate\Http\Response;
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
        $days  = (int) $request->get('days', 7);
        $limit = min((int) $request->get('limit', 20), 50);
        $stats = $this->repository->getCategoryStats($id, $days);

        try {
            $apiData = $this->apiClient->getCategory($id, 0, 48);
            $items   = $apiData['data']['makeSearch']['items'] ?? [];

            $products = collect($items)
                ->map(fn ($item) => $item['catalogCard'] ?? null)
                ->filter()
                ->values();

            $topProducts = $products->map(fn ($card) => [
                'product_id'     => $card['id'],
                'title'          => $card['title'] ?? '',
                'shop_slug'      => $card['shop']['slug'] ?? '',
                'shop_title'     => $card['shop']['title'] ?? $card['shop']['slug'] ?? '',
                'price'          => (int) ($card['minSellPrice'] ?? 0) / 100,
                'original_price' => (int) ($card['minFullPrice'] ?? 0) / 100,
                'rating'         => (float) ($card['rating'] ?? 0),
                'reviews_count'  => (int) ($card['reviewsCount'] ?? 0),
                'orders_count'   => (int) ($card['ordersCount'] ?? 0),
            ])
                ->sortByDesc('orders_count')
                ->values()
                ->take($limit);

            $topSellers = $topProducts
                ->groupBy('shop_slug')
                ->map(fn ($shopProducts, $slug) => [
                    'shop_slug'      => $slug,
                    'shop_title'     => $shopProducts->first()['shop_title'],
                    'products_count' => $shopProducts->count(),
                    'total_orders'   => $shopProducts->sum('orders_count'),
                    'total_reviews'  => $shopProducts->sum('reviews_count'),
                    'avg_price'      => (int) round($shopProducts->avg('price')),
                    'avg_rating'     => round($shopProducts->avg('rating'), 2),
                ])
                ->sortByDesc('total_orders')
                ->values()
                ->take($limit);

            $stats['top_products']      = $topProducts->toArray();
            $stats['top_sellers']       = $topSellers->toArray();
            $stats['total_in_category'] = $apiData['data']['makeSearch']['total'] ?? 0;
        } catch (\Throwable) {
            $stats['top_products']      = $stats['top_products'] ?? [];
            $stats['top_sellers']       = [];
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
        $days    = (int) $request->get('days', 30);
        $history = $this->repository->getPriceHistory($productId, $days);

        return response()->json([
            'product_id' => $productId,
            'days'       => $days,
            'history'    => $history,
        ]);
    }

    /**
     * Сводка по отслеживаемым товарам текущей компании
     */
    public function marketOverview(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id
            ?? $request->get('company_id');

        $tracked = UzumTrackedProduct::where('company_id', $companyId)
            ->orderByDesc('last_scraped_at')
            ->get();

        $overview = $tracked->map(function (UzumTrackedProduct $item) {
            $history = $this->repository->getPriceHistory($item->product_id, 7);

            return [
                'product_id'      => $item->product_id,
                'title'           => $item->title,
                'shop_slug'       => $item->shop_slug,
                'last_price'      => $item->last_price,
                'last_scraped_at' => $item->last_scraped_at,
                'alert_enabled'   => $item->alert_enabled,
                'price_trend'     => $history->pluck('price'),
            ];
        });

        return response()->json(['products' => $overview]);
    }

    /**
     * Список отслеживаемых товаров
     */
    public function tracked(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id ?? $request->get('company_id');

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
            'product_id'          => 'required|integer',
            'alert_enabled'       => 'boolean',
            'alert_threshold_pct' => 'integer|min:1|max:99',
        ]);

        $companyId = $request->user()->company_id ?? $request->get('company_id');

        // Проверить лимит по тарифу (ТЗ: 20 Pro / 50 Business)
        $limit   = config('uzum-crawler.limits.pro.max_tracked_products', 20);
        $current = UzumTrackedProduct::where('company_id', $companyId)->count();

        if ($current >= $limit) {
            return response()->json([
                'message' => "Достигнут лимит отслеживаемых товаров ({$limit}). Обновите тариф.",
            ], 422);
        }

        $tracked = UzumTrackedProduct::firstOrCreate(
            ['company_id' => $companyId, 'product_id' => $request->product_id],
            [
                'alert_enabled'       => $request->boolean('alert_enabled', true),
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
        $companyId = $request->user()->company_id ?? $request->get('company_id');

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
        $totalTokens  = UzumToken::count();

        $lastSnapshot = UzumProductSnapshot::orderByDesc('scraped_at')
            ->value('scraped_at');

        $snapshotsToday = UzumProductSnapshot::whereDate('scraped_at', today())->count();

        return response()->json([
            'status'         => 'ok',
            'tokens'         => [
                'active' => $activeTokens,
                'total'  => $totalTokens,
            ],
            'circuit_breaker'   => $this->circuitBreaker->getStatus(),
            'last_snapshot'     => $lastSnapshot?->toIso8601String(),
            'snapshots_today'   => $snapshotsToday,
            'tracked_products'  => UzumTrackedProduct::count(),
            'feature_enabled'   => (bool) config('uzum-crawler.features.enabled'),
        ]);
    }

    /**
     * AI-анализ рынка на основе данных отслеживаемых товаров
     */
    public function aiInsights(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id ?? $request->get('company_id');
        $cacheKey  = "uzum_ai_insights_{$companyId}";

        if ($cached = Cache::get($cacheKey)) {
            return response()->json($cached);
        }

        $tracked = UzumTrackedProduct::where('company_id', $companyId)
            ->orderByDesc('last_scraped_at')
            ->get();

        if ($tracked->isEmpty()) {
            return response()->json([
                'insights'     => null,
                'message'      => 'Добавьте товары для мониторинга, чтобы получить AI-анализ.',
                'generated_at' => null,
            ]);
        }

        // Собираем данные о ценах за 7 дней
        $productsData = $tracked->map(function (UzumTrackedProduct $item): array {
            $history = $this->repository->getPriceHistory($item->product_id, 7);
            $prices  = $history->map(fn ($h) => (float) $h->price)->values()->toArray();

            $changePct = null;
            if (count($prices) >= 2 && $prices[0] > 0) {
                $changePct = round(($prices[count($prices) - 1] - $prices[0]) / $prices[0] * 100, 1);
            }

            return [
                'title'         => $item->title ?? "Товар #{$item->product_id}",
                'shop'          => $item->shop_slug ?? 'неизвестно',
                'current_price' => (float) $item->last_price,
                'change_pct_7d' => $changePct,
            ];
        })->toArray();

        // Формируем промпт
        $lines = array_map(function (array $p): string {
            $price  = number_format($p['current_price'], 0, '.', ' ');
            $change = $p['change_pct_7d'] !== null
                ? ($p['change_pct_7d'] >= 0 ? "+{$p['change_pct_7d']}%" : "{$p['change_pct_7d']}%")
                : 'нет истории';

            return "• «{$p['title']}» (магазин: {$p['shop']}): цена {$price} сум, изменение за 7 дней: {$change}";
        }, $productsData);

        $count  = count($productsData);
        $prompt = "Ты — аналитик маркетплейса Uzum Market. Проанализируй ценовые данные конкурентов и дай рекомендации продавцу.\n\n"
            . "Отслеживаемые товары конкурентов ({$count} шт.):\n"
            . implode("\n", $lines) . "\n\n"
            . "Дай анализ в трёх блоках:\n"
            . "**Ситуация на рынке** — 2-3 предложения о текущих тенденциях.\n"
            . "**Рекомендации** — конкретные действия (ценообразование, акции, стратегия).\n"
            . "**Прогноз** — что ожидать на следующей неделе.\n\n"
            . "Пиши конкретно, используй цифры из данных. Ответ на русском.";

        $aiService = app(AIService::class);
        $insights  = $aiService->generateChatResponse([], $prompt, [
            'model'      => 'fast',
            'company_id' => $companyId,
            'user_id'    => $request->user()?->id,
        ]);

        $result = [
            'insights'       => $insights,
            'generated_at'   => now()->toIso8601String(),
            'products_count' => $tracked->count(),
        ];

        Cache::put($cacheKey, $result, now()->addHour());

        return response()->json($result);
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

        $companyId = $request->user()->company_id ?? $request->get('company_id');
        $days      = (int) $request->get('days', 30);
        $type      = $request->get('type', 'tracked');

        $filename = "uzum-analytics-{$type}-" . now()->format('Y-m-d') . '.csv';

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
                            $item->alert_threshold_pct . '%',
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
}
