<?php

namespace App\Jobs;

use App\Jobs\Concerns\HandlesMarketplaceRateLimiting;
use App\Models\MarketplaceAccount;
use App\Services\Marketplaces\Wildberries\WildberriesOrderService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncNewWildberriesOrdersJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, HandlesMarketplaceRateLimiting;

    /**
     * Таймаут выполнения job (5 минут)
     */
    public int $timeout = 300;

    /**
     * Количество попыток (с учётом retry при 429)
     */
    public int $tries = 3;

    /**
     * Время уникальности job (предотвращает дублирование)
     */
    public int $uniqueFor = 300; // 5 минут

    protected int $accountId;

    /**
     * Create a new job instance.
     */
    public function __construct(MarketplaceAccount $account)
    {
        $this->accountId = $account->id;
        $this->onQueue('marketplace-sync');
    }

    /**
     * Уникальный ID для предотвращения дублирования
     */
    public function uniqueId(): string
    {
        return 'sync-wb-new-orders-' . $this->accountId;
    }

    /**
     * Получить аккаунт
     */
    protected function getAccount(): ?MarketplaceAccount
    {
        return MarketplaceAccount::find($this->accountId);
    }

    /**
     * Execute the job.
     */
    public function handle(WildberriesOrderService $orderService): void
    {
        $account = $this->getAccount();

        if (!$account) {
            Log::warning('SyncNewWildberriesOrdersJob: Account not found', [
                'account_id' => $this->accountId,
            ]);
            return;
        }

        // Проверяем, что это Wildberries аккаунт
        if ($account->marketplace !== 'wb') {
            Log::warning('SyncNewWildberriesOrdersJob: Not a WB account', [
                'account_id' => $account->id,
                'marketplace' => $account->marketplace,
            ]);
            return;
        }

        if (!$account->is_active) {
            Log::info('SyncNewWildberriesOrdersJob: Skipped inactive account', [
                'account_id' => $account->id,
            ]);
            return;
        }

        Log::info('SyncNewWildberriesOrdersJob: Starting', [
            'account_id' => $account->id,
            'account_name' => $account->name,
            'attempt' => $this->attempts(),
        ]);

        try {
            $result = $orderService->fetchNewOrders($account);

            Log::info('SyncNewWildberriesOrdersJob: Completed', [
                'account_id' => $account->id,
                'synced' => $result['synced'],
                'created' => $result['created'],
                'errors_count' => count($result['errors']),
            ]);

            // Если есть ошибки, логируем их
            if (!empty($result['errors'])) {
                Log::warning('SyncNewWildberriesOrdersJob: Errors occurred', [
                    'account_id' => $account->id,
                    'errors' => $result['errors'],
                ]);
            }

        } catch (\Exception $e) {
            Log::error('SyncNewWildberriesOrdersJob: Failed', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            // Проверяем, нужен ли retry
            if ($this->shouldRetry($e)) {
                $delay = $this->getRetryAfterSeconds($e) ?? $this->backoff()[$this->attempts() - 1] ?? 60;
                $this->release($delay);
                return;
            }

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SyncNewWildberriesOrdersJob: Job failed permanently', [
            'account_id' => $this->accountId,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);
    }
}
