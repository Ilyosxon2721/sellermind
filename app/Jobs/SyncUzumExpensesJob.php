<?php

namespace App\Jobs;

use App\Models\MarketplaceAccount;
use App\Models\UzumExpense;
use App\Services\Marketplaces\IssueDetectorService;
use App\Services\Marketplaces\MarketplaceHttpClient;
use App\Services\Marketplaces\UzumClient;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SyncUzumExpensesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Таймаут выполнения job (15 минут)
     */
    public int $timeout = 900;

    /**
     * Количество попыток
     */
    public int $tries = 3;

    protected int $accountId;

    protected int $days;

    /**
     * Ключ кэша для прогресса синхронизации
     */
    public static function getCacheKey(int $accountId): string
    {
        return "uzum_expenses_sync_progress:{$accountId}";
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
     * @param  int  $days  Количество дней для синхронизации (0 = все данные)
     */
    public function __construct(MarketplaceAccount $account, int $days = 365)
    {
        $this->accountId = $account->id;
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

        Cache::put($cacheKey, $progress, now()->addHour());
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $account = MarketplaceAccount::find($this->accountId);

        if (! $account || $account->marketplace !== 'uzum') {
            Log::warning('SyncUzumExpensesJob skipped: account not found or not Uzum', [
                'account_id' => $this->accountId,
            ]);

            return;
        }

        if (! $account->is_active) {
            Log::info('SyncUzumExpensesJob skipped for inactive account', ['account_id' => $account->id]);

            return;
        }

        Log::info('SyncUzumExpensesJob started', [
            'account_id' => $account->id,
            'days' => $this->days,
            'attempt' => $this->attempts(),
        ]);

        $this->updateProgress([
            'status' => 'running',
            'started_at' => now()->toIso8601String(),
            'days' => $this->days,
            'processed' => 0,
            'created' => 0,
            'updated' => 0,
            'errors' => 0,
            'message' => 'Запуск синхронизации расходов...',
        ]);

        try {
            $httpClient = new MarketplaceHttpClient($account, 'uzum');
            $client = new UzumClient($httpClient, app(IssueDetectorService::class));

            $result = $this->syncExpenses($client, $account);

            $this->updateProgress([
                'status' => 'completed',
                'finished_at' => now()->toIso8601String(),
                'message' => "Синхронизация завершена. Создано: {$result['created']}, обновлено: {$result['updated']}",
            ]);

            Log::info('SyncUzumExpensesJob completed', [
                'account_id' => $account->id,
                'created' => $result['created'],
                'updated' => $result['updated'],
                'errors' => $result['errors'],
            ]);

        } catch (\Throwable $e) {
            $this->updateProgress([
                'status' => 'failed',
                'error' => $e->getMessage(),
                'finished_at' => now()->toIso8601String(),
                'message' => 'Ошибка: '.$e->getMessage(),
            ]);

            Log::error('SyncUzumExpensesJob failed', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            if ($this->isRateLimitError($e)) {
                $delay = $this->attempts() * 60;
                $this->release($delay);

                return;
            }

            throw $e;
        }
    }

    /**
     * Синхронизация расходов для аккаунта
     */
    protected function syncExpenses(UzumClient $client, MarketplaceAccount $account): array
    {
        $created = 0;
        $updated = 0;
        $errors = 0;
        $totalProcessed = 0;

        $this->updateProgress(['message' => 'Получение списка магазинов...']);

        $shops = $client->fetchShops($account);
        $shopIds = array_column($shops, 'id');

        if (empty($shopIds)) {
            Log::warning('SyncUzumExpensesJob: no shops found', ['account_id' => $account->id]);

            return ['created' => 0, 'updated' => 0, 'errors' => 0];
        }

        $this->updateProgress([
            'message' => 'Получение расходов...',
            'shops_count' => count($shopIds),
        ]);

        // Определяем даты фильтрации (локально, т.к. API фильтры ненадёжны)
        $dateFromMs = $this->days > 0 ? now()->subDays($this->days)->startOfDay()->getTimestampMs() : null;
        $dateToMs = now()->endOfDay()->getTimestampMs();

        // Получаем все расходы без фильтра дат (API не поддерживает)
        $this->updateProgress(['message' => 'Загрузка всех расходов...']);

        $allExpenses = $client->fetchAllFinanceExpenses($account, $shopIds, null, null);

        $this->updateProgress([
            'total' => count($allExpenses),
            'message' => 'Найдено '.count($allExpenses).' записей расходов',
        ]);

        foreach ($allExpenses as $expense) {
            try {
                // Фильтруем по дате локально
                $dateService = $expense['dateService'] ?? $expense['dateCreated'] ?? 0;
                if ($dateFromMs && $dateService < $dateFromMs) {
                    continue;
                }
                if ($dateToMs && $dateService > $dateToMs) {
                    continue;
                }

                $uzumId = $expense['id'] ?? null;
                if (! $uzumId) {
                    $errors++;

                    continue;
                }

                $source = $expense['source'] ?? 'Unknown';
                $name = $expense['name'] ?? '';
                $sourceNormalized = UzumExpense::normalizeSource($source, $name);

                $data = [
                    'marketplace_account_id' => $account->id,
                    'uzum_id' => $uzumId,
                    'shop_id' => $expense['shopId'] ?? 0,
                    'name' => $name,
                    'source' => $source,
                    'source_normalized' => $sourceNormalized,
                    'payment_price' => abs((int) ($expense['paymentPrice'] ?? 0)),
                    'amount' => abs((int) ($expense['amount'] ?? 0)),
                    'date_created' => $this->parseTimestamp($expense['dateCreated'] ?? null),
                    'date_service' => $this->parseTimestamp($expense['dateService'] ?? null),
                    'status' => $expense['status'] ?? null,
                    'raw_data' => $expense,
                ];

                $record = UzumExpense::updateOrCreate(
                    [
                        'marketplace_account_id' => $account->id,
                        'uzum_id' => $uzumId,
                    ],
                    $data
                );

                if ($record->wasRecentlyCreated) {
                    $created++;
                } else {
                    $updated++;
                }
                $totalProcessed++;

                if ($totalProcessed % 50 === 0) {
                    $this->updateProgress([
                        'processed' => $totalProcessed,
                        'created' => $created,
                        'updated' => $updated,
                        'errors' => $errors,
                        'percent' => count($allExpenses) > 0 ? round(($totalProcessed / count($allExpenses)) * 100, 1) : 0,
                    ]);
                }

            } catch (\Throwable $e) {
                $errors++;
                Log::warning('SyncUzumExpensesJob item failed', [
                    'account_id' => $account->id,
                    'expense_id' => $expense['id'] ?? null,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->updateProgress([
            'processed' => $totalProcessed,
            'created' => $created,
            'updated' => $updated,
            'errors' => $errors,
            'percent' => 100,
        ]);

        return [
            'created' => $created,
            'updated' => $updated,
            'errors' => $errors,
        ];
    }

    /**
     * Parse timestamp from milliseconds
     */
    protected function parseTimestamp($timestamp): ?Carbon
    {
        if (! $timestamp) {
            return null;
        }

        // API returns milliseconds
        $appTz = config('app.timezone');
        if (is_numeric($timestamp) && $timestamp > 1000000000000) {
            return Carbon::createFromTimestampMs($timestamp, $appTz);
        }

        if (is_numeric($timestamp)) {
            return Carbon::createFromTimestamp($timestamp, $appTz);
        }

        try {
            return Carbon::parse($timestamp);
        } catch (\Exception $e) {
            return null;
        }
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
        Log::error('SyncUzumExpensesJob: Job failed permanently', [
            'account_id' => $this->accountId,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);
    }
}
