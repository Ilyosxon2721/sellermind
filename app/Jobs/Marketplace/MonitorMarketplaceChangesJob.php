<?php

namespace App\Jobs\Marketplace;

use App\Models\MarketplaceAccount;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Главная джоба для запуска всех типов мониторинга маркетплейса
 * Запускает отдельные джобы с разными интервалами:
 * - MonitorOrdersJob: каждую минуту
 * - MonitorProductsJob: каждый час
 * - MonitorPricesJob: каждые 2 часа
 */
class MonitorMarketplaceChangesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 60; // 1 минута на выполнение
    public int $tries = 1; // Не повторять при ошибке

    protected MarketplaceAccount $account;

    /**
     * Уникальный ID джобы на основе аккаунта
     */
    public function uniqueId(): string
    {
        return "monitor_marketplace_{$this->account->id}";
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
     * Запускает три отдельные джобы мониторинга
     */
    public function handle(): void
    {
        if (!$this->account->is_active) {
            Log::info("Monitoring skipped for inactive account {$this->account->id}");
            return;
        }

        Log::info("Starting monitoring for marketplace account {$this->account->id} ({$this->account->marketplace})");

        try {
            // Запускаем мониторинг заказов (каждую минуту)
            MonitorOrdersJob::dispatch($this->account);
            Log::info("MonitorOrdersJob dispatched for account {$this->account->id}");

            // Запускаем мониторинг товаров (каждый час)
            MonitorProductsJob::dispatch($this->account);
            Log::info("MonitorProductsJob dispatched for account {$this->account->id}");

            // Запускаем мониторинг цен (каждые 2 часа)
            MonitorPricesJob::dispatch($this->account);
            Log::info("MonitorPricesJob dispatched for account {$this->account->id}");

            Log::info("All monitoring jobs started for account {$this->account->id}");
        } catch (\Throwable $e) {
            Log::error("Failed to start monitoring for account {$this->account->id}: " . $e->getMessage());
        }
    }
}
