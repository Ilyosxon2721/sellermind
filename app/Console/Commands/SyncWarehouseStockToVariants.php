<?php

namespace App\Console\Commands;

use App\Models\Warehouse\Sku;
use App\Models\Warehouse\StockLedger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncWarehouseStockToVariants extends Command
{
    protected $signature = 'warehouse:sync-to-variants
                            {--company_id= : Filter by company ID}
                            {--variant_id= : Sync specific variant}
                            {--dry-run : Show what would be done without making changes}';

    protected $description = 'Синхронизация остатков из stock_ledger в ProductVariant.stock_default';

    public function handle(): int
    {
        $companyId = $this->option('company_id');
        $variantId = $this->option('variant_id');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - изменения НЕ будут сохранены');
        }

        $this->info('Синхронизация stock_ledger → ProductVariant.stock_default...');

        // Get all warehouse SKUs with their ledger balances
        $query = Sku::query()
            ->whereNotNull('product_variant_id')
            ->with('productVariant');

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        if ($variantId) {
            $query->where('product_variant_id', $variantId);
        }

        $skus = $query->get();
        $this->info("Найдено SKU: {$skus->count()}");

        $updated = 0;
        $skipped = 0;
        $results = [];

        DB::beginTransaction();

        try {
            foreach ($skus as $sku) {
                $variant = $sku->productVariant;
                if (! $variant) {
                    $skipped++;

                    continue;
                }

                // Calculate total stock from ledger
                $ledgerStock = (float) StockLedger::where('sku_id', $sku->id)->sum('qty_delta');
                $currentStock = (float) ($variant->stock_default ?? 0);

                // Skip if already in sync
                if (abs($ledgerStock - $currentStock) < 0.001) {
                    $skipped++;

                    continue;
                }

                $results[] = [
                    'sku' => $variant->sku,
                    'barcode' => $variant->barcode,
                    'current_stock_default' => $currentStock,
                    'ledger_stock' => $ledgerStock,
                    'action' => $dryRun ? 'would update' : 'updated',
                ];

                if (! $dryRun) {
                    $variant->update(['stock_default' => $ledgerStock]);

                    Log::info('Synced warehouse stock to variant', [
                        'variant_id' => $variant->id,
                        'sku' => $variant->sku,
                        'old_stock' => $currentStock,
                        'new_stock' => $ledgerStock,
                    ]);
                }

                $updated++;
            }

            if ($dryRun) {
                DB::rollBack();
            } else {
                DB::commit();
            }

            if (! empty($results)) {
                $this->table(
                    ['SKU', 'Barcode', 'stock_default', 'stock_ledger', 'Action'],
                    collect($results)->map(fn ($r) => [
                        $r['sku'],
                        $r['barcode'],
                        $r['current_stock_default'],
                        $r['ledger_stock'],
                        $r['action'],
                    ])->toArray()
                );
            }

            $this->newLine();
            $this->info("Обновлено: {$updated}");
            $this->info("Пропущено (уже синхронизировано): {$skipped}");

            if ($dryRun && $updated > 0) {
                $this->warn('Запустите без --dry-run чтобы применить изменения');
            }

            return 0;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Ошибка: '.$e->getMessage());
            Log::error('Warehouse to variant sync failed', ['error' => $e->getMessage()]);

            return 1;
        }
    }
}
