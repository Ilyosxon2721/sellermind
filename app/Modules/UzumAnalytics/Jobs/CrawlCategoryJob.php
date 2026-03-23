<?php

// file: app/Modules/UzumAnalytics/Jobs/CrawlCategoryJob.php

declare(strict_types=1);

namespace App\Modules\UzumAnalytics\Jobs;

use App\Modules\UzumAnalytics\Repositories\AnalyticsRepository;
use App\Modules\UzumAnalytics\Services\UzumAnalyticsApiClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job для сбора снепшотов товаров одной категории.
 *
 * Получает список товаров из Uzum GraphQL API постранично,
 * сохраняет снепшоты в uzum_products_snapshots.
 * Retry: 3 попытки с backoff 5/15/60 минут.
 */
final class CrawlCategoryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $maxExceptions = 3;

    /**
     * @param  int  $categoryId  ID категории в Uzum
     * @param  int  $offset  Смещение (для пагинации, 0-based)
     * @param  int  $limit   Товаров на страницу (max 48 по API)
     */
    public function __construct(
        public readonly int $categoryId,
        public readonly int $offset = 0,
        public readonly int $limit = 48,
    ) {}

    /**
     * Интервалы retry: 5 мин → 15 мин → 60 мин
     */
    public function backoff(): array
    {
        return [300, 900, 3600];
    }

    public function handle(
        UzumAnalyticsApiClient $apiClient,
        AnalyticsRepository $repository,
    ): void {
        Log::channel('uzum-analytics')->info('CrawlCategoryJob: старт', [
            'category_id' => $this->categoryId,
            'offset'      => $this->offset,
            'limit'       => $this->limit,
        ]);

        $page = (int) floor($this->offset / $this->limit);

        $response = $apiClient->getCategory($this->categoryId, $page, $this->limit);

        // GraphQL ответ: data.makeSearch.items[]
        $items = $response['data']['makeSearch']['items']
            ?? $response['makeSearch']['items']
            ?? $response['items']
            ?? [];

        $total = $response['data']['makeSearch']['total']
            ?? $response['makeSearch']['total']
            ?? $response['total']
            ?? 0;

        $saved = 0;
        foreach ($items as $item) {
            $product = $item['catalogCard'] ?? $item['product'] ?? $item;
            if (empty($product['id'])) {
                continue;
            }

            try {
                $repository->saveProductSnapshot($product);
                $saved++;
            } catch (\Throwable $e) {
                Log::channel('uzum-analytics')->warning('CrawlCategoryJob: ошибка сохранения товара', [
                    'product_id' => $product['id'] ?? null,
                    'error'      => $e->getMessage(),
                ]);
            }
        }

        Log::channel('uzum-analytics')->info('CrawlCategoryJob: завершён', [
            'category_id' => $this->categoryId,
            'offset'      => $this->offset,
            'saved'       => $saved,
            'total'       => $total,
        ]);

        // Если есть следующая страница — ставим в очередь
        $nextOffset = $this->offset + $this->limit;
        if ($nextOffset < $total) {
            self::dispatch($this->categoryId, $nextOffset, $this->limit)
                ->onQueue('uzum-crawler')
                ->delay(now()->addSeconds(13)); // rate limit: 5 запросов/мин = 12 сек + jitter
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::channel('uzum-analytics')->error('CrawlCategoryJob: все попытки исчерпаны', [
            'category_id' => $this->categoryId,
            'offset'      => $this->offset,
            'error'       => $exception->getMessage(),
        ]);
    }
}
