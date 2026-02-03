<?php

namespace App\Console\Commands;

use App\Models\MarketplaceAccount;
use App\Models\VariantMarketplaceLink;
use App\Services\Marketplaces\MarketplaceHttpClient;
use App\Services\Marketplaces\OzonClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class OzonFullStockSync extends Command
{
    protected $signature = 'ozon:full-stock-sync
                            {--account= : ID аккаунта OZON}';

    protected $description = 'Синхронизация остатков OZON: push локальных остатков в OZON';

    public function handle(): int
    {
        $accountId = $this->option('account');

        $accounts = $accountId
            ? MarketplaceAccount::where('id', $accountId)->get()
            : MarketplaceAccount::where('marketplace', 'ozon')->where('is_active', true)->get();

        if ($accounts->isEmpty()) {
            $this->warn('Нет активных OZON аккаунтов');

            return self::SUCCESS;
        }

        foreach ($accounts as $account) {
            $this->info("Аккаунт #{$account->id} ({$account->name})");

            try {
                $this->syncAccountStocks($account);
            } catch (\Exception $e) {
                $this->error("Ошибка синхронизации: {$e->getMessage()}");
                Log::error('OZON stock sync failed', [
                    'account_id' => $account->id,
                    'error' => $e->getMessage(),
                ]);
            }

            $this->newLine();
        }

        return self::SUCCESS;
    }

    protected function syncAccountStocks(MarketplaceAccount $account): void
    {
        $httpClient = app(MarketplaceHttpClient::class);
        $client = new OzonClient($httpClient);

        // Get warehouse_id from account settings
        $credentials = $account->credentials_json ?? [];
        $warehouseId = $credentials['warehouse_id'] ?? null;

        if (! $warehouseId) {
            $this->warn('  Не указан warehouse_id в настройках аккаунта');

            return;
        }

        // Get all active links with stock sync enabled
        $links = VariantMarketplaceLink::where('marketplace_account_id', $account->id)
            ->where('is_active', true)
            ->where('sync_stock_enabled', true)
            ->with(['variant', 'marketplaceProduct'])
            ->get();

        if ($links->isEmpty()) {
            $this->info('  Нет товаров для синхронизации');

            return;
        }

        $this->line('  Найдено товаров для синхронизации: '.$links->count());

        // Prepare stock data for batch API
        $stocksData = [];
        $linksMap = []; // Map offer_id => link для обновления статусов

        foreach ($links as $link) {
            try {
                $offerId = $link->external_offer_id ?? $link->marketplaceProduct->external_offer_id ?? null;
                $productId = $link->marketplaceProduct->external_product_id ?? null;

                if (! $offerId || ! $productId) {
                    $this->warn("  Пропущен товар link_id={$link->id}: нет offer_id или product_id");

                    continue;
                }

                // Get current stock using model method (handles aggregated/basic modes)
                $stock = $link->getCurrentStock();

                $stocksData[] = [
                    'offer_id' => $offerId,
                    'product_id' => (int) $productId,
                    'stock' => max(0, $stock),
                    'warehouse_id' => (int) $warehouseId,
                ];

                $linksMap[$offerId] = [
                    'link' => $link,
                    'stock' => $stock,
                ];
            } catch (\Exception $e) {
                $this->warn("  Ошибка подготовки товара link_id={$link->id}: {$e->getMessage()}");
                Log::warning('OZON stock preparation failed', [
                    'link_id' => $link->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if (empty($stocksData)) {
            $this->warn('  Нет данных для отправки');

            return;
        }

        $this->line('  Подготовлено для отправки: '.count($stocksData));

        // Send in batches of 100 (OZON API limit)
        $batches = array_chunk($stocksData, 100);
        $totalSynced = 0;
        $totalErrors = 0;

        foreach ($batches as $batchIndex => $batch) {
            try {
                $this->line('  Отправка batch '.($batchIndex + 1).'/'.count($batches).' ('.count($batch).' товаров)...');

                $client->syncStocks($account, $batch);

                // Update link statuses
                foreach ($batch as $item) {
                    if (isset($linksMap[$item['offer_id']])) {
                        $linkData = $linksMap[$item['offer_id']];
                        $linkData['link']->markSynced($linkData['stock']);
                    }
                }

                $totalSynced += count($batch);
                $this->info('    ✓ Синхронизировано: '.count($batch));
            } catch (\Exception $e) {
                $totalErrors += count($batch);
                $this->error("    ✗ Ошибка batch: {$e->getMessage()}");
                Log::error('OZON batch sync failed', [
                    'account_id' => $account->id,
                    'batch_index' => $batchIndex,
                    'error' => $e->getMessage(),
                ]);

                // Mark failed links
                foreach ($batch as $item) {
                    if (isset($linksMap[$item['offer_id']])) {
                        $linksMap[$item['offer_id']]['link']->markFailed($e->getMessage());
                    }
                }
            }

            // Small delay between batches to avoid rate limiting
            if ($batchIndex < count($batches) - 1) {
                usleep(100000); // 100ms
            }
        }

        $this->info("  Итого: синхронизировано {$totalSynced}, ошибок {$totalErrors}");
    }
}
