<?php

namespace App\Jobs\Marketplace;

use App\Events\MarketplaceDataChanged;
use App\Models\MarketplaceAccount;
use App\Services\Marketplaces\MarketplaceSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Джоба для мониторинга цен маркетплейса
 * Запускается каждые 2 часа для обновления цен товаров
 */
class MonitorPricesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300; // 5 минут на выполнение
    public int $tries = 1; // Не повторять при ошибке

    protected MarketplaceAccount $account;
    protected int $checkInterval = 7200; // Проверять каждые 2 часа

    /**
     * Уникальный ID джобы на основе аккаунта
     */
    public function uniqueId(): string
    {
        return "monitor_prices_{$this->account->id}";
    }

    /**
     * Create a new job instance.
     */
    public function __construct(MarketplaceAccount $account)
    {
        $this->account = $account;
    }

    /**
     * Execute the job.
     */
    public function handle(MarketplaceSyncService $syncService): void
    {
        if (!$this->account->is_active) {
            Log::info("Prices monitoring skipped for inactive account {$this->account->id}");
            return;
        }

        Log::info("Starting prices monitoring for account {$this->account->id} ({$this->account->marketplace})");

        try {
            $this->syncPrices($syncService);

            // Перезапускаем мониторинг через 2 часа
            $this->rescheduleMonitoring();
        } catch (\Throwable $e) {
            Log::error("Prices monitoring error for account {$this->account->id}: " . $e->getMessage());

            // Даже при ошибке перезапускаем мониторинг
            $this->rescheduleMonitoring();
        }
    }

    /**
     * Синхронизация цен
     */
    protected function syncPrices(MarketplaceSyncService $syncService): void
    {
        $cacheKey = "prices_last_check_{$this->account->id}";

        try {
            // Синхронизируем цены товаров
            $result = $syncService->syncPrices($this->account);

            // Отправляем событие об обновлении цен
            broadcast(new MarketplaceDataChanged(
                $this->account->company_id,
                $this->account->id,
                'prices',
                'synced',
                0, // affectedCount
                null, // changes
                ['synced_at' => now()->toIso8601String()] // metadata
            ))->toOthers();

            Log::info("Prices synced for account {$this->account->id}");

            Cache::put($cacheKey, now(), now()->addHours(3));
        } catch (\Throwable $e) {
            Log::error("Failed to sync prices for account {$this->account->id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Перезапустить мониторинг через интервал
     */
    protected function rescheduleMonitoring(): void
    {
        // Перезапускаем job через указанный интервал (2 часа)
        static::dispatch($this->account)
            ->delay(now()->addSeconds($this->checkInterval));
    }
}
