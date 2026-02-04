<?php

namespace App\Jobs;

use App\Jobs\Concerns\HandlesMarketplaceRateLimiting;
use App\Models\MarketplaceAccount;
use App\Services\Marketplaces\MarketplaceSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncUzumOrders implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, HandlesMarketplaceRateLimiting, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Таймаут выполнения job (5 минут)
     */
    public int $timeout = 300;

    /**
     * Количество попыток
     */
    public int $tries = 3;

    /**
     * Время уникальности job
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
        return 'sync-uzum-orders-'.$this->accountId;
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
    public function handle(MarketplaceSyncService $syncService): void
    {
        $account = $this->getAccount();

        if (! $account || ! $account->isUzum()) {
            Log::warning('SyncUzumOrders skipped: account not found or not Uzum', [
                'account_id' => $this->accountId,
            ]);

            return;
        }

        if (! $account->is_active) {
            Log::info('SyncUzumOrders skipped for inactive account', ['account_id' => $account->id]);

            return;
        }

        Log::info('SyncUzumOrders started', [
            'account_id' => $account->id,
            'attempt' => $this->attempts(),
        ]);

        try {
            // Тянем последние 30 дней (по умолчанию внутри syncOrders)
            $syncService->syncOrders($account);
            Log::info('SyncUzumOrders completed', ['account_id' => $account->id]);
        } catch (\Throwable $e) {
            Log::error('SyncUzumOrders failed', [
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
        Log::error('SyncUzumOrders: Job failed permanently', [
            'account_id' => $this->accountId,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);
    }
}
