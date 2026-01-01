<?php

namespace App\Listeners;

use App\Events\StockUpdated;
use App\Services\Stock\StockSyncService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Синхронизировать остатки с маркетплейсами при изменении
 */
class SyncStockToMarketplaces implements ShouldQueue
{
    public $queue = 'stock-sync';
    
    public function __construct(
        protected StockSyncService $stockSyncService
    ) {}

    public function handle(StockUpdated $event): void
    {
        Log::info('Stock updated, syncing to marketplaces', [
            'variant_id' => $event->variant->id,
            'sku' => $event->variant->sku,
            'old_stock' => $event->oldStock,
            'new_stock' => $event->newStock,
        ]);

        try {
            $results = $this->stockSyncService->syncVariantStock($event->variant);
            
            Log::info('Stock sync completed', [
                'variant_id' => $event->variant->id,
                'results' => $results,
            ]);
        } catch (\Exception $e) {
            Log::error('Stock sync failed', [
                'variant_id' => $event->variant->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Обработка неудачной задачи
     */
    public function failed(StockUpdated $event, \Throwable $exception): void
    {
        Log::error('Stock sync job failed permanently', [
            'variant_id' => $event->variant->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
