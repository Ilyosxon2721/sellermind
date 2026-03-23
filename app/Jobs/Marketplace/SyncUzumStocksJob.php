<?php

namespace App\Jobs\Marketplace;

use App\Models\MarketplaceAccount;
use App\Models\MarketplaceSyncLog;
use App\Models\VariantMarketplaceLink;
use App\Services\Stock\StockSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Синхронизация остатков Uzum через StockSyncService.
 * Отдельный job чтобы не зависеть от перезапуска воркеров при изменении MarketplaceSyncService.
 */
class SyncUzumStocksJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 600;

    public function __construct(
        public MarketplaceAccount $account,
    ) {}

    public function handle(StockSyncService $syncService): void
    {
        $log = MarketplaceSyncLog::start($this->account->id, MarketplaceSyncLog::TYPE_STOCKS);

        try {
            $links = VariantMarketplaceLink::where('marketplace_account_id', $this->account->id)
                ->where('marketplace_code', 'uzum')
                ->where('is_active', true)
                ->where('sync_stock_enabled', true)
                ->whereNotNull('external_sku_id')
                ->with(['variant', 'marketplaceProduct', 'account'])
                ->get();

            if ($links->isEmpty()) {
                $log->markAsSuccess('Нет активных связей с external_sku_id для синхронизации');
                return;
            }

            $synced = 0;
            $skipped = 0;
            $errors = 0;

            foreach ($links as $link) {
                try {
                    $syncService->syncLinkStock($link);
                    $synced++;
                } catch (\RuntimeException $e) {
                    if (str_contains($e->getMessage(), 'не подключён к FBS/DBS')) {
                        $skipped++;
                    } else {
                        $errors++;
                        Log::warning('SyncUzumStocksJob: ошибка SKU', [
                            'account_id' => $this->account->id,
                            'sku_id' => $link->external_sku_id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                } catch (\Throwable $e) {
                    $errors++;
                    Log::error('SyncUzumStocksJob: критическая ошибка', [
                        'account_id' => $this->account->id,
                        'sku_id' => $link->external_sku_id,
                        'error' => $e->getMessage(),
                    ]);
                }

                usleep(300000); // 300ms между запросами
            }

            $log->markAsSuccess(
                "Uzum остатки: синхронизировано {$synced}, пропущено {$skipped}, ошибок {$errors}"
            );
        } catch (\Throwable $e) {
            $log->markAsError($e->getMessage());
            throw $e;
        }
    }

    public function tags(): array
    {
        return ['marketplace-sync', 'stocks', 'uzum', 'account:' . $this->account->id];
    }
}
