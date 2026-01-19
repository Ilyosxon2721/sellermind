<?php

namespace App\Console\Commands;

use App\Models\Finance\FinanceSettings;
use App\Models\ProductVariant;
use App\Models\Warehouse\StockLedger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixStockLedgerCosts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stock:fix-costs {--company_id=} {--dry-run} {--force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix stock_ledger cost_delta values: multiply by quantity and convert from USD to UZS';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $companyId = $this->option('company_id');
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        if ($dryRun) {
            $this->warn('DRY-RUN MODE: Changes will NOT be saved to database');
        }

        $this->info('Analyzing stock_ledger entries with incorrect cost_delta values...');

        // Get finance settings for currency conversion
        $financeSettings = $companyId
            ? FinanceSettings::getForCompany($companyId)
            : FinanceSettings::first();

        if (!$financeSettings) {
            $this->error('No finance settings found');
            return 1;
        }

        $usdRate = $financeSettings->usd_rate ?? 12700;
        $this->info("Using USD rate: {$usdRate}");

        // Find entries that need fixing:
        // - source_type = 'INITIAL_SYNC' (from variant sync)
        // - cost_delta is small (< 100) which suggests it's a unit cost in USD, not total in UZS
        $query = StockLedger::where('source_type', 'INITIAL_SYNC')
            ->where('cost_delta', '>', 0)
            ->where('cost_delta', '<', 1000); // USD unit costs are typically < $1000

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        $entries = $query->get();

        if ($entries->isEmpty()) {
            $this->info('No entries found that need fixing');
            return 0;
        }

        $this->info("Found {$entries->count()} entries to analyze");

        // Show preview
        $previewData = [];
        foreach ($entries as $entry) {
            // Get the related ProductVariant to get purchase_price
            $variant = ProductVariant::find($entry->source_id);

            if (!$variant) {
                $this->warn("Variant not found for source_id: {$entry->source_id}");
                continue;
            }

            $oldCost = $entry->cost_delta;
            $qty = $entry->qty_delta;
            $purchasePrice = $variant->purchase_price ?? 0;

            // Calculate correct cost: qty * purchase_price * usd_rate
            $newCost = abs($qty) * $purchasePrice * $usdRate;

            $previewData[] = [
                'id' => $entry->id,
                'sku_id' => $entry->sku_id,
                'qty' => $qty,
                'purchase_price' => $purchasePrice,
                'old_cost' => $oldCost,
                'new_cost' => $newCost,
                'variant_id' => $variant->id,
                'variant_sku' => $variant->sku,
            ];
        }

        // Display preview table
        $this->table(
            ['ID', 'SKU ID', 'Qty', 'Purchase Price (USD)', 'Old Cost', 'New Cost (UZS)', 'Variant SKU'],
            array_map(fn($row) => [
                $row['id'],
                $row['sku_id'],
                $row['qty'],
                '$' . number_format($row['purchase_price'], 2),
                number_format($row['old_cost'], 2),
                number_format($row['new_cost'], 0),
                $row['variant_sku'],
            ], $previewData)
        );

        // Calculate totals
        $totalOldCost = array_sum(array_column($previewData, 'old_cost'));
        $totalNewCost = array_sum(array_column($previewData, 'new_cost'));

        $this->newLine();
        $this->info("Total old cost: " . number_format($totalOldCost, 2));
        $this->info("Total new cost: " . number_format($totalNewCost, 0) . " UZS");

        if ($dryRun) {
            $this->warn('Dry run complete. Use --no-dry-run to apply changes.');
            return 0;
        }

        // Confirm before applying
        if (!$force && !$this->confirm('Do you want to apply these changes?')) {
            $this->info('Operation cancelled');
            return 0;
        }

        // Apply changes
        DB::beginTransaction();
        try {
            $updated = 0;
            foreach ($previewData as $row) {
                StockLedger::where('id', $row['id'])->update([
                    'cost_delta' => $row['new_cost'],
                    'currency_code' => 'UZS',
                ]);
                $updated++;
            }

            DB::commit();
            $this->info("Updated {$updated} entries");
            return 0;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Error: ' . $e->getMessage());
            return 1;
        }
    }
}
