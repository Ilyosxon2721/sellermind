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
    // Use 'default' queue so existing queue workers process this
    // Previously was 'marketplace-sync' which wasn't being processed
    public $queue = 'default';

    /**
     * Handle the stock updated event.
     * Note: Dependencies must be resolved in handle() for queued listeners,
     * not in constructor (constructor injection doesn't work with serialized queue jobs)
     */
    public function handle(StockUpdated $event): void
    {
        $stockSyncService = app(StockSyncService::class);
        Log::info('SyncStockToMarketplaces::handle() STARTED - Stock updated, checking marketplace accounts for sync', [
            'variant_id' => $event->variant->id,
            'sku' => $event->variant->sku,
            'old_stock' => $event->oldStock,
            'new_stock' => $event->newStock,
            'source_link_id' => $event->sourceLinkId,
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

                Log::debug('Processing marketplace link for stock sync', [
                    'link_id' => $link->id,
                    'marketplace' => $account?->marketplace,
                    'account_id' => $account?->id,
                    'account_name' => $account?->name,
                    'sync_stock_enabled' => $link->sync_stock_enabled,
                    'external_sku' => $link->external_sku,
                    'external_sku_id' => $link->external_sku_id,
                ]);

                // Проверяем настройки аккаунта маркетплейса
                if (! $account || ! $account->isAutoSyncOnChangeEnabled()) {
                    $skippedAccounts[] = [
                        'account_id' => $account?->id,
                        'account_name' => $account?->name,
                        'marketplace' => $account?->marketplace,
                        'reason' => 'auto_sync_on_change disabled',
                    ];
                    Log::info('Skipping link - auto_sync_on_change disabled', [
                        'link_id' => $link->id,
                        'account_id' => $account?->id,
                        'marketplace' => $account?->marketplace,
                    ]);

                    continue;
                }

                // Skip the specific link that triggered the stock change (no need to sync back)
                // This is important for Uzum where one account can have multiple shops
                if ($event->sourceLinkId && $link->id === $event->sourceLinkId) {
                    $skippedAccounts[] = [
                        'account_id' => $account->id,
                        'account_name' => $account->name,
                        'link_id' => $link->id,
                        'reason' => 'source link (already has correct stock)',
                    ];

                    continue;
                }

                try {
                    Log::info('Attempting to sync stock to marketplace', [
                        'link_id' => $link->id,
                        'marketplace' => $account->marketplace,
                        'account_name' => $account->name,
                        'stock' => $event->newStock,
                    ]);

                    $result = $stockSyncService->syncLinkStock($link);
                    $syncedAccounts[] = [
                        'account_id' => $account->id,
                        'marketplace' => $account->marketplace,
                        'result' => $result,
                    ];

                    Log::info('Successfully synced stock to marketplace', [
                        'link_id' => $link->id,
                        'marketplace' => $account->marketplace,
                        'result' => $result,
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to sync stock to marketplace account', [
                        'link_id' => $link->id,
                        'account_id' => $account->id,
                        'marketplace' => $account->marketplace,
                        'account_name' => $account->name,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);

                    $skippedAccounts[] = [
                        'account_id' => $account->id,
                        'account_name' => $account->name,
                        'marketplace' => $account->marketplace,
                        'reason' => 'sync error: '.$e->getMessage(),
                    ];
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
