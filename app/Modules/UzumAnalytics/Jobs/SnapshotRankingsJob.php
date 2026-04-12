<?php

declare(strict_types=1);

namespace App\Modules\UzumAnalytics\Jobs;

use App\Modules\UzumAnalytics\Models\UzumRankingHistory;
use App\Modules\UzumAnalytics\Repositories\AnalyticsRepository;
use App\Modules\UzumAnalytics\Services\UzumAnalyticsApiClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Ежедневный снепшот рангов компании в категории Uzum.
 * Запускается через Scheduler в 04:00 для каждой категории компании.
 */
final class SnapshotRankingsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public function __construct(
        public readonly int $companyId,
        public readonly int $categoryId,
    ) {}

    public function handle(
        UzumAnalyticsApiClient $apiClient,
        AnalyticsRepository $repository,
    ): void {
        $ourSlugs = $repository->getCompanyShopSlugs($this->companyId);
        if (empty($ourSlugs)) {
            return;
        }

        try {
            $apiData = $apiClient->getCategory($this->categoryId, 0, 48);
            $items = $apiData['data']['makeSearch']['items'] ?? [];
        } catch (\Throwable $e) {
            Log::warning('[UzumAnalytics] SnapshotRankingsJob: ошибка API', [
                'category_id' => $this->categoryId,
                'error' => $e->getMessage(),
            ]);

            return;
        }

        $products = collect($items)
            ->map(fn ($item) => $item['catalogCard'] ?? null)
            ->filter()
            ->values()
            ->map(fn ($card) => [
                'shop_slug' => $card['shop']['slug'] ?? '',
                'price' => (int) ($card['minSellPrice'] ?? 0) / 100,
                'orders_count' => (int) ($card['ordersCount'] ?? 0),
                'reviews_count' => (int) ($card['reviewsCount'] ?? 0),
                'rating' => (float) ($card['rating'] ?? 0),
            ]);

        if ($products->isEmpty()) {
            return;
        }

        $shops = $products->groupBy('shop_slug')->map(fn ($prods, $slug) => [
            'shop_slug' => $slug,
            'total_orders' => $prods->sum('orders_count'),
            'total_revenue' => (int) $prods->sum(fn ($p) => $p['orders_count'] * $p['price']),
            'total_reviews' => $prods->sum('reviews_count'),
            'avg_rating' => round($prods->avg('rating'), 2),
            'is_our' => in_array($slug, $ourSlugs, true),
        ]);

        $ourShop = $shops->first(fn ($s) => $s['is_our']);
        if (! $ourShop) {
            return;
        }

        $byOrders = $shops->sortByDesc('total_orders')->values();
        $byRevenue = $shops->sortByDesc('total_revenue')->values();
        $byReviews = $shops->sortByDesc('total_reviews')->values();
        $byRating = $shops->sortByDesc('avg_rating')->values();

        $categoryTotalOrders = $shops->sum('total_orders');
        $marketShare = $categoryTotalOrders > 0
            ? round($ourShop['total_orders'] / $categoryTotalOrders * 100, 2)
            : 0;

        UzumRankingHistory::create([
            'company_id' => $this->companyId,
            'category_id' => $this->categoryId,
            'shop_slug' => $ourShop['shop_slug'],
            'rank_by_orders' => $byOrders->search(fn ($s) => $s['is_our']) + 1,
            'rank_by_revenue' => $byRevenue->search(fn ($s) => $s['is_our']) + 1,
            'rank_by_reviews' => $byReviews->search(fn ($s) => $s['is_our']) + 1,
            'rank_by_rating' => $byRating->search(fn ($s) => $s['is_our']) + 1,
            'total_shops' => $shops->count(),
            'our_orders' => $ourShop['total_orders'],
            'our_revenue' => $ourShop['total_revenue'],
            'our_reviews' => $ourShop['total_reviews'],
            'our_rating' => $ourShop['avg_rating'],
            'category_total_orders' => $categoryTotalOrders,
            'market_share_pct' => $marketShare,
            'recorded_at' => now(),
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('[UzumAnalytics] SnapshotRankingsJob failed', [
            'company_id' => $this->companyId,
            'category_id' => $this->categoryId,
            'error' => $exception->getMessage(),
        ]);
    }
}
