<?php

namespace App\Jobs;

use App\Models\MarketplaceAccount;
use App\Models\UzumFinanceOrder;
use App\Services\Marketplaces\IssueDetectorService;
use App\Services\Marketplaces\MarketplaceHttpClient;
use App\Services\Marketplaces\UzumClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SyncUzumFinanceOrdersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Таймаут выполнения job (30 минут для полной синхронизации)
     */
    public int $timeout = 1800;

    /**
     * Количество попыток
     */
    public int $tries = 3;

    protected int $accountId;

    protected bool $fullSync;

    protected int $days;

    /**
     * Ключ кэша для прогресса синхронизации
     */
    public static function getCacheKey(int $accountId): string
    {
        return "uzum_finance_sync_progress:{$accountId}";
    }

    /**
     * Получить статус синхронизации из кэша
     */
    public static function getSyncStatus(int $accountId): ?array
    {
        return Cache::get(self::getCacheKey($accountId));
    }

    /**
     * Create a new job instance.
     *
     * @param  bool  $fullSync  Полная синхронизация (все данные) или инкрементальная
     * @param  int  $days  Количество дней для инкрементальной синхронизации
     */
    public function __construct(MarketplaceAccount $account, bool $fullSync = false, int $days = 90)
    {
        $this->accountId = $account->id;
        $this->fullSync = $fullSync;
        $this->days = $days;
        $this->onQueue('marketplace-sync');
    }

    /**
     * Обновить прогресс в кэше
     */
    protected function updateProgress(array $data): void
    {
        $cacheKey = self::getCacheKey($this->accountId);
        $current = Cache::get($cacheKey, []);

        $progress = array_merge($current, $data, [
            'account_id' => $this->accountId,
            'updated_at' => now()->toIso8601String(),
        ]);

        // Рассчитываем оставшееся время
        if (isset($progress['started_at']) && isset($progress['processed']) && isset($progress['total']) && $progress['processed'] > 0) {
            $startedAt = \Carbon\Carbon::parse($progress['started_at']);
            $elapsed = now()->diffInSeconds($startedAt);
            $rate = $progress['processed'] / max($elapsed, 1); // items per second
            $remaining = $progress['total'] - $progress['processed'];
            $progress['estimated_seconds_remaining'] = $rate > 0 ? (int) ceil($remaining / $rate) : null;
            $progress['items_per_second'] = round($rate, 2);
        }

        // Кэш на 1 час
        Cache::put($cacheKey, $progress, now()->addHour());
    }

    /**
     * Очистить прогресс
     */
    protected function clearProgress(): void
    {
        Cache::forget(self::getCacheKey($this->accountId));
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $account = MarketplaceAccount::find($this->accountId);

        if (! $account || $account->marketplace !== 'uzum') {
            Log::warning('SyncUzumFinanceOrdersJob skipped: account not found or not Uzum', [
                'account_id' => $this->accountId,
            ]);
            $this->clearProgress();

            return;
        }

        if (! $account->is_active) {
            Log::info('SyncUzumFinanceOrdersJob skipped for inactive account', ['account_id' => $account->id]);
            $this->clearProgress();

            return;
        }

        Log::info('SyncUzumFinanceOrdersJob started', [
            'account_id' => $account->id,
            'full_sync' => $this->fullSync,
            'days' => $this->days,
            'attempt' => $this->attempts(),
        ]);

        // Инициализируем прогресс
        $this->updateProgress([
            'status' => 'running',
            'started_at' => now()->toIso8601String(),
            'full_sync' => $this->fullSync,
            'days' => $this->days,
            'processed' => 0,
            'total' => 0,
            'created' => 0,
            'updated' => 0,
            'errors' => 0,
            'current_shop' => null,
            'message' => 'Запуск синхронизации...',
        ]);

        try {
            $httpClient = new MarketplaceHttpClient($account, 'uzum');
            $client = new UzumClient($httpClient, app(IssueDetectorService::class));

            $result = $this->syncAccountFinanceOrders($client, $account);

            // Финальный статус
            $this->updateProgress([
                'status' => 'completed',
                'finished_at' => now()->toIso8601String(),
                'message' => "Синхронизация завершена. Создано: {$result['created']}, обновлено: {$result['updated']}",
            ]);

            Log::info('SyncUzumFinanceOrdersJob completed', [
                'account_id' => $account->id,
                'created' => $result['created'],
                'updated' => $result['updated'],
                'errors' => $result['errors'],
            ]);

            // Очищаем прогресс через 5 минут после завершения
            Cache::put(self::getCacheKey($this->accountId), Cache::get(self::getCacheKey($this->accountId)), now()->addMinutes(5));

        } catch (\Throwable $e) {
            $this->updateProgress([
                'status' => 'failed',
                'error' => $e->getMessage(),
                'finished_at' => now()->toIso8601String(),
                'message' => 'Ошибка: '.$e->getMessage(),
            ]);

            Log::error('SyncUzumFinanceOrdersJob failed', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            // Проверяем rate limit
            if ($this->isRateLimitError($e)) {
                $delay = $this->attempts() * 60; // 1 мин, 2 мин, 3 мин
                $this->updateProgress([
                    'status' => 'rate_limited',
                    'message' => "Превышен лимит запросов. Повтор через {$delay} сек...",
                    'retry_after' => $delay,
                ]);
                $this->release($delay);

                return;
            }

            throw $e;
        }
    }

    /**
     * Синхронизация финансовых заказов для аккаунта
     */
    protected function syncAccountFinanceOrders(UzumClient $client, MarketplaceAccount $account): array
    {
        $created = 0;
        $updated = 0;
        $errors = 0;
        $totalProcessed = 0;

        // Получаем список магазинов
        $this->updateProgress(['message' => 'Получение списка магазинов...']);

        $shops = $client->fetchShops($account);
        $shopIds = array_column($shops, 'id');

        if (empty($shopIds)) {
            Log::warning('SyncUzumFinanceOrdersJob: no shops found', ['account_id' => $account->id]);

            return ['created' => 0, 'updated' => 0, 'errors' => 0];
        }

        $this->updateProgress([
            'message' => 'Подсчёт общего количества заказов...',
            'shops_count' => count($shopIds),
        ]);

        // Определяем дату начала
        $dateFromMs = null;
        if (! $this->fullSync && $this->days > 0) {
            $dateFromMs = now()->subDays($this->days)->startOfDay()->getTimestampMs();
        }

        // Сначала получаем общее количество заказов для расчёта прогресса
        $totalOrders = 0;
        $shopTotals = [];
        foreach ($shopIds as $shopId) {
            try {
                $response = $client->fetchFinanceOrders($account, [$shopId], 0, 1, false, $dateFromMs);
                $shopTotal = $response['totalElements'] ?? 0;
                $totalOrders += $shopTotal;
                $shopTotals[$shopId] = $shopTotal;
            } catch (\Throwable $e) {
                // Игнорируем ошибки при подсчёте
            }
        }

        $this->updateProgress([
            'total' => $totalOrders,
            'message' => "Найдено {$totalOrders} заказов в ".count($shopIds).' магазинах',
        ]);

        $currentShopIndex = 0;
        foreach ($shopIds as $shopId) {
            $currentShopIndex++;
            $shopName = collect($shops)->firstWhere('id', $shopId)['name'] ?? "Магазин {$shopId}";
            $shopTotal = $shopTotals[$shopId] ?? 0;

            $this->updateProgress([
                'current_shop' => $shopName,
                'current_shop_index' => $currentShopIndex,
                'shops_count' => count($shopIds),
                'message' => "Магазин {$currentShopIndex}/".count($shopIds).": {$shopName} ({$shopTotal} заказов)",
            ]);

            $page = 0;
            $pagesLoaded = 0;
            $shopCreated = 0;
            $shopUpdated = 0;
            $itemsCount = 0;

            do {
                try {
                    $response = $client->fetchFinanceOrders($account, [$shopId], $page, 100, false, $dateFromMs);
                    $items = $response['orderItems'] ?? [];

                    // Обрабатываем пачку сразу
                    foreach ($items as $item) {
                        try {
                            $data = $client->mapFinanceOrderData($item);

                            if (! $data['uzum_id']) {
                                $errors++;

                                continue;
                            }

                            $order = UzumFinanceOrder::updateOrCreate(
                                [
                                    'marketplace_account_id' => $account->id,
                                    'uzum_id' => $data['uzum_id'],
                                ],
                                array_merge($data, [
                                    'marketplace_account_id' => $account->id,
                                ])
                            );

                            if ($order->wasRecentlyCreated) {
                                $shopCreated++;
                            } else {
                                $shopUpdated++;
                            }
                            $totalProcessed++;

                            // Обновляем прогресс каждые 50 записей
                            if ($totalProcessed % 50 === 0) {
                                $this->updateProgress([
                                    'processed' => $totalProcessed,
                                    'created' => $created + $shopCreated,
                                    'updated' => $updated + $shopUpdated,
                                    'errors' => $errors,
                                    'percent' => $totalOrders > 0 ? round(($totalProcessed / $totalOrders) * 100, 1) : 0,
                                ]);
                            }
                        } catch (\Throwable $e) {
                            $errors++;
                            Log::warning('SyncUzumFinanceOrdersJob item failed', [
                                'account_id' => $account->id,
                                'shop_id' => $shopId,
                                'item_id' => $item['id'] ?? null,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }

                    $pagesLoaded++;
                    $page++;

                    // Задержка чтобы не превысить rate limit
                    if (! empty($items)) {
                        usleep(200000); // 200ms
                    }

                    // Сохраняем количество для проверки продолжения
                    $itemsCount = count($items);

                    // Освобождаем память
                    unset($items, $response);
                    gc_collect_cycles();

                } catch (\Throwable $e) {
                    Log::warning('SyncUzumFinanceOrdersJob page failed', [
                        'account_id' => $account->id,
                        'shop_id' => $shopId,
                        'page' => $page,
                        'error' => $e->getMessage(),
                    ]);

                    // Если rate limit - пробрасываем наверх
                    if ($this->isRateLimitError($e)) {
                        throw $e;
                    }

                    break;
                }
            } while ($itemsCount === 100);

            $created += $shopCreated;
            $updated += $shopUpdated;

            // Обновляем прогресс после каждого магазина
            $this->updateProgress([
                'processed' => $totalProcessed,
                'created' => $created,
                'updated' => $updated,
                'errors' => $errors,
                'percent' => $totalOrders > 0 ? round(($totalProcessed / $totalOrders) * 100, 1) : 0,
            ]);
        }

        return [
            'created' => $created,
            'updated' => $updated,
            'errors' => $errors,
        ];
    }

    /**
     * Проверка на rate limit ошибку
     */
    protected function isRateLimitError(\Throwable $e): bool
    {
        $message = $e->getMessage();

        return str_contains($message, '429') ||
               str_contains($message, 'Too Many Requests') ||
               str_contains($message, 'rate limit');
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SyncUzumFinanceOrdersJob: Job failed permanently', [
            'account_id' => $this->accountId,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);
    }
}
