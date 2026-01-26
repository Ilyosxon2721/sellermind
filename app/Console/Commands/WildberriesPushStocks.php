<?php

namespace App\Console\Commands;

use App\Models\MarketplaceAccount;
use App\Models\VariantMarketplaceLink;
use App\Services\Marketplaces\Wildberries\WildberriesStockService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class WildberriesPushStocks extends Command
{
    protected $signature = 'wb:push-stocks
                            {--account= : ID аккаунта WB}
                            {--dry-run : Показать что будет отправлено, без реальной синхронизации}';

    protected $description = 'Push локальных остатков в Wildberries (отправка наших остатков в WB)';

    public function handle(): int
    {
        $accountId = $this->option('account');
        $dryRun = $this->option('dry-run');

        $accounts = $accountId
            ? MarketplaceAccount::where('id', $accountId)->where('marketplace', 'wildberries')->get()
            : MarketplaceAccount::where('marketplace', 'wildberries')->where('is_active', true)->get();

        if ($accounts->isEmpty()) {
            $this->warn('Нет активных WB аккаунтов');
            return self::SUCCESS;
        }

        foreach ($accounts as $account) {
            $this->info("Аккаунт #{$account->id} ({$account->name})");

            try {
                $this->pushStocks($account, $dryRun);
            } catch (\Exception $e) {
                $this->error("Ошибка синхронизации: {$e->getMessage()}");
                Log::error('WB stocks push failed', [
                    'account_id' => $account->id,
                    'error' => $e->getMessage(),
                ]);
            }

            $this->newLine();
        }

        return self::SUCCESS;
    }

    protected function pushStocks(MarketplaceAccount $account, bool $dryRun): void
    {
        $wbStockService = new WildberriesStockService();

        $this->line('  Начинаем отправку остатков в WB...');
        $startTime = microtime(true);

        // Получить все активные связи для этого аккаунта
        $links = VariantMarketplaceLink::where('marketplace_account_id', $account->id)
            ->where('marketplace_code', 'wildberries')
            ->where('is_active', true)
            ->where('sync_stock_enabled', true)
            ->with(['variant', 'marketplaceProduct'])
            ->get();

        if ($links->isEmpty()) {
            $this->warn('  Нет активных связей для синхронизации');
            return;
        }

        $this->line("  Найдено связей: " . $links->count());

        // Получить warehouse_id
        $warehouseId = $wbStockService->getDefaultWarehouseId($account);
        if (!$warehouseId) {
            $this->error('  Не найден склад WB для отправки остатков');
            return;
        }
        $this->line("  Склад WB ID: {$warehouseId}");

        // Подготовить данные для синхронизации
        $stocksData = [];
        foreach ($links as $link) {
            $stock = $link->getCurrentStock();

            // Для WB нужен barcode (external_sku)
            $barcode = $link->external_sku;

            if (!$barcode) {
                $this->warn("  Пропуск связи #{$link->id}: отсутствует barcode (external_sku)");
                continue;
            }

            $this->line("    Barcode {$barcode}: остаток {$stock} (variant_id: {$link->product_variant_id})");

            $stocksData[] = [
                'link_id' => $link->id,
                'barcode' => $barcode,
                'stock' => max(0, $stock),
            ];
        }

        if (empty($stocksData)) {
            $this->warn('  Нет данных для синхронизации');
            return;
        }

        if ($dryRun) {
            $this->info('  [DRY-RUN] Данные для отправки:');
            foreach ($stocksData as $item) {
                $this->line("    SKU: {$item['barcode']}, Amount: {$item['stock']}");
            }
            return;
        }

        $synced = 0;
        $errors = 0;

        // Синхронизируем каждый barcode отдельно
        foreach ($stocksData as $index => $stockItem) {
            try {
                $wbStockService->updateStock(
                    $account,
                    $stockItem['barcode'],
                    $stockItem['stock'],
                    $warehouseId
                );
                $synced++;

                // Задержка между запросами
                if ($index < count($stocksData) - 1) {
                    usleep(300000); // 300ms
                }
            } catch (\Exception $e) {
                $errors++;
                Log::error('Failed to push WB stock for SKU', [
                    'account_id' => $account->id,
                    'barcode' => $stockItem['barcode'],
                    'error' => $e->getMessage(),
                ]);
                $this->error("  Ошибка для barcode {$stockItem['barcode']}: " . $e->getMessage());
            }
        }

        $duration = round(microtime(true) - $startTime, 2);

        $this->info("  ✓ Синхронизация завершена за {$duration} сек");
        $this->line("    Синхронизировано: {$synced}");

        if ($errors > 0) {
            $this->warn("    Ошибок: {$errors}");
        }

        Log::info('WB stocks pushed', [
            'account_id' => $account->id,
            'synced' => $synced,
            'errors' => $errors,
            'duration' => $duration,
        ]);
    }
}
