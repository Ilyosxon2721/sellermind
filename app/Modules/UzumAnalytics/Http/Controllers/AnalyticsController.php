<?php

declare(strict_types=1);

namespace App\Modules\UzumAnalytics\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\UzumAnalytics\Jobs\CrawlProductJob;
use App\Modules\UzumAnalytics\Models\UzumCategory;
use App\Modules\UzumAnalytics\Models\UzumTrackedProduct;
use App\Modules\UzumAnalytics\Repositories\AnalyticsRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
     * Статистика категории + топ товары
     */
    public function categoryProducts(Request $request, int $categoryId): JsonResponse
    {
        $days  = (int) $request->get('days', 7);
        $stats = $this->repository->getCategoryStats($categoryId, $days);

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
}
