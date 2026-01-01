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
 * Джоба для мониторинга товаров маркетплейса
 * Запускается каждый час для синхронизации товаров
 */
class MonitorProductsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300; // 5 минут на выполнение
    public int $tries = 1; // Не повторять при ошибке

    protected MarketplaceAccount $account;
    protected int $checkInterval = 3600; // Проверять каждый час

    /**
     * Уникальный ID джобы на основе аккаунта
     */
    public function uniqueId(): string
    {
        return "monitor_products_{$this->account->id}";
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
            Log::info("Products monitoring skipped for inactive account {$this->account->id}");
            return;
        }

        Log::info("Starting products monitoring for account {$this->account->id} ({$this->account->marketplace})");

        try {
            $this->syncProducts($syncService);

            // Перезапускаем мониторинг через 1 час
            $this->rescheduleMonitoring();
        } catch (\Throwable $e) {
            Log::error("Products monitoring error for account {$this->account->id}: " . $e->getMessage());

            // Даже при ошибке перезапускаем мониторинг
            $this->rescheduleMonitoring();
        }
    }

    /**
     * Синхронизация товаров
     */
    protected function syncProducts(MarketplaceSyncService $syncService): void
    {
        $cacheKey = "products_last_check_{$this->account->id}";

        try {
            // Синхронизируем товары
            $result = $syncService->syncProducts($this->account);

            // Отправляем событие об обновлении товаров
            broadcast(new MarketplaceDataChanged(
                $this->account->company_id,
                $this->account->id,
                'products',
                'synced',
                [
                    'synced_at' => now()->toIso8601String(),
                ]
            ))->toOthers();

            Log::info("Products synced for account {$this->account->id}");

            Cache::put($cacheKey, now(), now()->addHours(2));
        } catch (\Throwable $e) {
            Log::error("Failed to sync products for account {$this->account->id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Перезапустить мониторинг через интервал
     */
    protected function rescheduleMonitoring(): void
    {
        // Перезапускаем job через указанный интервал (1 час)
        static::dispatch($this->account)
            ->delay(now()->addSeconds($this->checkInterval));
    }
}
