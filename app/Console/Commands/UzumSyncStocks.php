<?php

namespace App\Console\Commands;

use App\Models\MarketplaceAccount;
use App\Models\VariantMarketplaceLink;
use App\Services\Stock\StockSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UzumSyncStocks extends Command
{
    protected $signature = 'uzum:sync-stocks
                            {--account= : ID аккаунта Uzum}';

    protected $description = 'Синхронизация остатков Uzum Market (push локальных остатков в Uzum)';

    public function handle(StockSyncService $syncService): int
    {
        $accountId = $this->option('account');

        $accounts = $accountId
            ? MarketplaceAccount::where('id', $accountId)->where('marketplace', 'uzum')->get()
            : MarketplaceAccount::where('marketplace', 'uzum')->where('is_active', true)->get();

        if ($accounts->isEmpty()) {
            $this->warn('Нет активных Uzum Market аккаунтов');

            return self::SUCCESS;
        }

        foreach ($accounts as $account) {
            $this->info("Аккаунт #{$account->id} ({$account->name})");

            try {
                $this->syncStocks($account, $syncService);
            } catch (\Exception $e) {
                $this->error("Ошибка синхронизации: {$e->getMessage()}");
                Log::error('Uzum stocks sync failed', [
                    'account_id' => $account->id,
                    'error' => $e->getMessage(),
                ]);
            }

            $this->newLine();
        }

        return self::SUCCESS;
    }

    protected function syncStocks(MarketplaceAccount $account, StockSyncService $syncService): void
    {
        $this->line('  Начинаем синхронизацию остатков...');
        $startTime = microtime(true);

        $links = VariantMarketplaceLink::where('marketplace_account_id', $account->id)
            ->where('marketplace_code', 'uzum')
            ->where('is_active', true)
            ->where('sync_stock_enabled', true)
            ->whereNotNull('external_sku_id')
            ->with(['variant', 'marketplaceProduct', 'account'])
            ->get();

        if ($links->isEmpty()) {
            $this->warn('  Нет активных связей для синхронизации');

            return;
        }

        $this->line('  Найдено связей: '.$links->count());

        $synced = 0;
        $errors = 0;
        $skipped = 0;

        foreach ($links as $index => $link) {
            $skuId = $link->external_sku_id;
            $stock = max(0, $link->getCurrentStock());

            $this->line("    SKU {$skuId}: остаток {$stock}");

            try {
                $syncService->syncLinkStock($link, $stock);
                $synced++;
            } catch (\RuntimeException $e) {
                // SKU не подключён к FBS/DBS — пропускаем без ошибки
                if (str_contains($e->getMessage(), 'не подключён к FBS/DBS')) {
                    $skipped++;
                    $this->warn("    Пропущен SKU {$skuId}: ".$e->getMessage());
                } else {
                    $errors++;
                    $this->error("    Ошибка SKU {$skuId}: ".$e->getMessage());
                    Log::error('Failed to sync Uzum stock for SKU', [
                        'account_id' => $account->id,
                        'sku_id' => $skuId,
                        'error' => $e->getMessage(),
                    ]);
                }
            } catch (\Exception $e) {
                $errors++;
                $this->error("    Ошибка SKU {$skuId}: ".$e->getMessage());
                Log::error('Failed to sync Uzum stock for SKU', [
                    'account_id' => $account->id,
                    'sku_id' => $skuId,
                    'error' => $e->getMessage(),
                ]);

                // Rate limit — ждём дольше
                if (str_contains($e->getMessage(), 'много запросов') || str_contains($e->getMessage(), 'rate') || str_contains($e->getMessage(), 'Подождите')) {
                    $this->warn('    Rate limit, ждём 3 секунды...');
                    sleep(3);
                }
            }

            // Задержка 500ms между запросами для избежания rate limiting
            if ($index < $links->count() - 1) {
                usleep(500000);
            }
        }

        $duration = round(microtime(true) - $startTime, 2);

        $this->info("  ✓ Синхронизация завершена за {$duration} сек");
        $this->line("    Синхронизировано: {$synced}");

        if ($skipped > 0) {
            $this->line("    Пропущено (не в FBS/DBS): {$skipped}");
        }

        if ($errors > 0) {
            $this->warn("    Ошибок: {$errors}");
        }

        Log::info('Uzum stocks synced', [
            'account_id' => $account->id,
            'synced' => $synced,
            'skipped' => $skipped,
            'errors' => $errors,
            'duration' => $duration,
        ]);
    }
}
