<?php

namespace App\Console\Commands;

use App\Models\Warehouse\Sku;
use App\Models\Warehouse\StockLedger;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CleanupDuplicateLedgerEntries extends Command
{
    protected $signature = 'stock:cleanup-duplicate-ledger
                            {--dry-run : Показать что будет удалено без реального удаления}
                            {--threshold=5 : Допуск по времени в секундах для определения дубликатов}';

    protected $description = 'Удалить дублирующие записи stock_ledger (stock_adjustment от Observer) и пересчитать остатки';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $threshold = (int) $this->option('threshold');

        if ($dryRun) {
            $this->warn('=== DRY RUN — изменения НЕ будут применены ===');
        }

        $this->info('Поиск дублирующих записей в stock_ledger...');
        $this->line("Допуск по времени: {$threshold} секунд");

        // 1. Получить все записи marketplace_order_reserve и marketplace_order_cancel
        $marketplaceEntries = StockLedger::whereIn('source_type', [
            'marketplace_order_reserve',
            'marketplace_order_cancel',
        ])->orderBy('created_at')->get();

        $this->line("Найдено marketplace записей: {$marketplaceEntries->count()}");

        $duplicateIds = [];
        $affectedSkuIds = [];

        foreach ($marketplaceEntries as $entry) {
            // Ищем stock_adjustment запись с такими же параметрами, созданную в пределах threshold секунд
            $duplicate = StockLedger::where('source_type', 'stock_adjustment')
                ->where('sku_id', $entry->sku_id)
                ->where('warehouse_id', $entry->warehouse_id)
                ->where('qty_delta', $entry->qty_delta)
                ->where('id', '!=', $entry->id)
                ->whereBetween('created_at', [
                    Carbon::parse($entry->created_at)->subSeconds($threshold),
                    Carbon::parse($entry->created_at)->addSeconds($threshold),
                ])
                // Не удалять записи, которые уже были помечены как дубликат
                ->whereNotIn('id', $duplicateIds)
                ->first();

            if ($duplicate) {
                $duplicateIds[] = $duplicate->id;
                $affectedSkuIds[$entry->sku_id] = true;

                $this->line(sprintf(
                    '  Дубликат #%d (stock_adjustment, qty=%s, sku=%d, %s) ← оригинал #%d (%s, %s)',
                    $duplicate->id,
                    $duplicate->qty_delta,
                    $duplicate->sku_id,
                    $duplicate->created_at,
                    $entry->id,
                    $entry->source_type,
                    $entry->created_at,
                ));
            }
        }

        $this->newLine();
        $this->info('Найдено дубликатов: '.count($duplicateIds));
        $this->info('Затронуто SKU: '.count($affectedSkuIds));

        if (empty($duplicateIds)) {
            $this->info('Дубликатов не найдено. Завершение.');

            return self::SUCCESS;
        }

        // 2. Удалить дубликаты
        if (! $dryRun) {
            $this->info('Удаление дубликатов...');
            DB::beginTransaction();
            try {
                $deleted = StockLedger::whereIn('id', $duplicateIds)->delete();
                $this->info("Удалено записей: {$deleted}");

                // 3. Пересчитать остатки для затронутых вариантов
                $this->info('Пересчёт остатков для затронутых товаров...');
                $recalculated = 0;

                foreach (array_keys($affectedSkuIds) as $skuId) {
                    $sku = Sku::with('productVariant')->find($skuId);
                    if (! $sku || ! $sku->productVariant) {
                        $this->warn("  SKU #{$skuId}: вариант товара не найден, пропуск");

                        continue;
                    }

                    $variant = $sku->productVariant;
                    $oldStock = $variant->stock_default;

                    // Пересчитываем баланс из ledger
                    $ledgerBalance = (int) StockLedger::where('sku_id', $skuId)->sum('qty_delta');
                    $newStock = max(0, $ledgerBalance);

                    if ($oldStock !== $newStock) {
                        // Обновляем quietly, чтобы не создать ещё одну запись в ledger
                        $variant->stock_default = $newStock;
                        $variant->updateQuietly(['stock_default' => $newStock]);

                        $this->line(sprintf(
                            '  %s (variant #%d): %d → %d (разница: %+d)',
                            $variant->sku,
                            $variant->id,
                            $oldStock,
                            $newStock,
                            $newStock - $oldStock,
                        ));
                        $recalculated++;
                    } else {
                        $this->line(sprintf(
                            '  %s (variant #%d): остаток корректен (%d)',
                            $variant->sku,
                            $variant->id,
                            $oldStock,
                        ));
                    }
                }

                DB::commit();

                $this->newLine();
                $this->info("Пересчитано вариантов: {$recalculated}");

                Log::info('CleanupDuplicateLedgerEntries completed', [
                    'duplicates_deleted' => $deleted,
                    'variants_recalculated' => $recalculated,
                    'affected_sku_ids' => array_keys($affectedSkuIds),
                ]);

            } catch (\Throwable $e) {
                DB::rollBack();
                $this->error("Ошибка: {$e->getMessage()}");
                Log::error('CleanupDuplicateLedgerEntries failed', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                return self::FAILURE;
            }
        } else {
            // Dry-run: показать что будет пересчитано
            $this->newLine();
            $this->info('[DRY-RUN] Пересчёт остатков (предварительный):');

            foreach (array_keys($affectedSkuIds) as $skuId) {
                $sku = Sku::with('productVariant')->find($skuId);
                if (! $sku || ! $sku->productVariant) {
                    $this->warn("  SKU #{$skuId}: вариант не найден");

                    continue;
                }

                $variant = $sku->productVariant;
                $currentStock = $variant->stock_default;

                // Текущий баланс ledger
                $currentLedgerBalance = (int) StockLedger::where('sku_id', $skuId)->sum('qty_delta');

                // Баланс после удаления дубликатов
                $duplicateDelta = StockLedger::whereIn('id', $duplicateIds)
                    ->where('sku_id', $skuId)
                    ->sum('qty_delta');

                $correctedBalance = $currentLedgerBalance - $duplicateDelta;
                $newStock = max(0, (int) $correctedBalance);

                $this->line(sprintf(
                    '  %s (variant #%d): текущий=%d, ledger=%s, дубликаты=%s, после=%d %s',
                    $variant->sku,
                    $variant->id,
                    $currentStock,
                    $currentLedgerBalance,
                    $duplicateDelta,
                    $newStock,
                    $currentStock !== $newStock ? '← ИЗМЕНИТСЯ' : '(без изменений)',
                ));
            }
        }

        $this->newLine();
        $prefix = $dryRun ? '[DRY-RUN] ' : '';
        $this->table(
            ['Метрика', 'Значение'],
            [
                ['Дубликатов найдено', count($duplicateIds)],
                ['SKU затронуто', count($affectedSkuIds)],
                ["{$prefix}Записей удалено", $dryRun ? 'N/A' : count($duplicateIds)],
            ]
        );

        return self::SUCCESS;
    }
}
