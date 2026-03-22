<?php

// file: app/Modules/UzumAnalytics/Jobs/SyncCategoriesJob.php

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
 * Job для синхронизации дерева категорий Uzum.
 *
 * Запускается 1 раз в сутки (Laravel Scheduler).
 * GET /main/root-categories → рекурсивный обход дочерних категорий.
 */
final class SyncCategoriesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function backoff(): array
    {
        return [300, 900, 3600];
    }

    public function handle(
        UzumAnalyticsApiClient $apiClient,
        AnalyticsRepository $repository,
    ): void {
        Log::channel('uzum-analytics')->info('SyncCategoriesJob: начало синхронизации категорий');

        $response   = $apiClient->getRootCategories();
        $categories = $response['data']
            ?? $response['payload']
            ?? $response['categories']
            ?? $response;

        if (empty($categories) || ! is_array($categories)) {
            Log::channel('uzum-analytics')->warning('SyncCategoriesJob: пустой ответ API', [
                'response' => $response,
            ]);

            return;
        }

        $repository->saveCategories($categories);

        Log::channel('uzum-analytics')->info('SyncCategoriesJob: завершено', [
            'root_categories' => count($categories),
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::channel('uzum-analytics')->error('SyncCategoriesJob: провал', [
            'error' => $exception->getMessage(),
        ]);
    }
}
