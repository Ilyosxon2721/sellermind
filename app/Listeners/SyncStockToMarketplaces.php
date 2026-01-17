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
        Log::info('Stock updated, checking marketplace accounts for sync', [
            'variant_id' => $event->variant->id,
            'sku' => $event->variant->sku,
            'old_stock' => $event->oldStock,
            'new_stock' => $event->newStock,
        ]);

        try {
            // Получаем активные связи варианта с маркетплейсами
            $links = $event->variant->marketplaceLinks()
                ->where('is_active', true)
                ->where('sync_stock_enabled', true)
                ->with('account')
                ->get();

            if ($links->isEmpty()) {
                Log::info('No active marketplace links for variant', [
                    'variant_id' => $event->variant->id,
                ]);
                return;
            }

            $syncedAccounts = [];
            $skippedAccounts = [];

            foreach ($links as $link) {
                $account = $link->account;

                // Проверяем настройки аккаунта маркетплейса
                if (!$account || !$account->isAutoSyncOnChangeEnabled()) {
                    $skippedAccounts[] = [
                        'account_id' => $account?->id,
                        'account_name' => $account?->name,
                        'reason' => 'auto_sync_on_change disabled',
                    ];
                    continue;
                }

                try {
                    $result = $this->stockSyncService->syncLinkStock($link);
                    $syncedAccounts[] = [
                        'account_id' => $account->id,
                        'marketplace' => $account->marketplace,
                        'result' => $result,
                    ];
                } catch (\Exception $e) {
                    Log::warning('Failed to sync stock to account', [
                        'link_id' => $link->id,
                        'account_id' => $account->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            Log::info('Stock sync completed', [
                'variant_id' => $event->variant->id,
                'synced_count' => count($syncedAccounts),
                'skipped_count' => count($skippedAccounts),
                'synced_accounts' => $syncedAccounts,
                'skipped_accounts' => $skippedAccounts,
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
