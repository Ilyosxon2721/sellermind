<?php

declare(strict_types=1);

namespace App\Modules\UzumAnalytics\Console;

use App\Modules\UzumAnalytics\Jobs\SyncCategoriesJob;
use App\Modules\UzumAnalytics\Models\UzumCategory;
use App\Modules\UzumAnalytics\Repositories\AnalyticsRepository;
use App\Modules\UzumAnalytics\Services\UzumAnalyticsApiClient;
use Illuminate\Console\Command;

/**
 * Синхронизация дерева категорий Uzum Market.
 *
 * Использование:
 *   php artisan uzum-analytics:sync-categories
 */
final class SyncCategoriesCommand extends Command
{
    protected $signature = 'uzum-analytics:sync-categories';

    protected $description = 'Синхронизировать дерево категорий Uzum Market (выполняется синхронно)';

    public function handle(
        UzumAnalyticsApiClient $apiClient,
        AnalyticsRepository $repository,
    ): int {
        $this->info('Получаем корневые категории из Uzum API...');

        try {
            $response   = $apiClient->getRootCategories();
            $categories = $response['data']
                ?? $response['payload']
                ?? $response['categories']
                ?? $response;

            if (empty($categories) || ! is_array($categories)) {
                $this->error('Пустой ответ от API. Ключи: ' . implode(', ', array_keys($response)));
                $this->warn('Добавьте токен командой: php artisan uzum-analytics:add-token');
                return self::FAILURE;
            }

            $this->info('Получено корневых категорий: ' . count($categories));
            $this->info('Сохраняем в базу...');

            $repository->saveCategories($categories);

            $total = UzumCategory::count();
            $this->info("✓ Готово! В базе теперь {$total} категорий.");
        } catch (\Throwable $e) {
            $this->error('Ошибка: ' . $e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
