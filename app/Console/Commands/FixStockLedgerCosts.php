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
    protected $signature = 'stock:fix-costs {--company_id=} {--dry-run} {--force} {--all : Fix all INITIAL_SYNC entries regardless of cost_delta value}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix stock_ledger cost_delta values: recalculate from ProductVariant purchase_price and convert to UZS';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $companyId = $this->option('company_id');
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');
        $fixAll = $this->option('all');

        if ($dryRun) {
            $this->warn('DRY-RUN MODE: Changes will NOT be saved to database');
        }

        $this->info('Analyzing stock_ledger entries...');

        // Get finance settings for currency conversion
        $financeSettings = $companyId
            ? FinanceSettings::getForCompany($companyId)
            : FinanceSettings::first();

        if (! $financeSettings) {
            $this->error('No finance settings found. Please set up finance settings first.');

            return 1;
        }

        $usdRate = $financeSettings->usd_rate ?? 12700;
        $this->info("Using USD rate: {$usdRate}");

        // Find entries that need fixing:
        // - source_type = 'INITIAL_SYNC' (from variant sync)
        $query = StockLedger::where('source_type', 'INITIAL_SYNC');

        // If --all is not set, only look for entries with small cost_delta (likely wrong)
        if (! $fixAll) {
            $query->where(function ($q) {
                $q->where('cost_delta', '<', 1000)
                    ->orWhereNull('cost_delta')
                    ->orWhere('cost_delta', 0);
            });
        }

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        $entries = $query->get();

        if ($entries->isEmpty()) {
            $this->info('No entries found that need fixing.');
            $this->info('Tip: Use --all to recalculate ALL INITIAL_SYNC entries.');

            return 0;
        }

        $this->info("Found {$entries->count()} entries to analyze");

        // Show preview
        $previewData = [];
        $skippedCount = 0;

        foreach ($entries as $entry) {
            // Get the related ProductVariant to get purchase_price
            $variant = ProductVariant::find($entry->source_id);

            if (! $variant) {
                $this->warn("Variant not found for source_id: {$entry->source_id}, skipping...");
                $skippedCount++;

                continue;
            }

            $oldCost = $entry->cost_delta ?? 0;
            $qty = $entry->qty_delta;
            $purchasePrice = $variant->purchase_price ?? 0;

            // Calculate correct cost: qty * purchase_price * usd_rate
            $newCost = abs($qty) * $purchasePrice * $usdRate;

            // Skip if new cost would be the same (or both are 0)
            if (abs($oldCost - $newCost) < 0.01) {
                continue;
            }

            $previewData[] = [
                'entry_id' => $entry->id,
                'sku_id' => $entry->sku_id,
                'qty' => $qty,
                'purchase_price' => $purchasePrice,
                'old_cost' => $oldCost,
                'new_cost' => $newCost,
                'variant_id' => $variant->id,
                'variant_sku' => $variant->sku ?? 'N/A',
            ];
        }

        if (empty($previewData)) {
            $this->info('No entries need updating (all costs are already correct).');
            if ($skippedCount > 0) {
                $this->warn("Skipped {$skippedCount} entries due to missing variants.");
            }

            return 0;
        }

        $this->info('Found '.count($previewData).' entries to update');

        // Display preview table
        $tableRows = [];
        foreach ($previewData as $row) {
            $tableRows[] = [
                $row['entry_id'],
                $row['sku_id'],
                $row['qty'],
                '$'.number_format($row['purchase_price'], 2),
                number_format($row['old_cost'], 2),
                number_format($row['new_cost'], 0),
                $row['variant_sku'],
            ];
        }

        $this->table(
            ['ID', 'SKU ID', 'Qty', 'Purchase Price (USD)', 'Old Cost', 'New Cost (UZS)', 'Variant SKU'],
            $tableRows
        );

        // Calculate totals
        $totalOldCost = 0;
        $totalNewCost = 0;
        foreach ($previewData as $row) {
            $totalOldCost += $row['old_cost'];
            $totalNewCost += $row['new_cost'];
        }

        $this->newLine();
        $this->info('Total old cost: '.number_format($totalOldCost, 2));
        $this->info('Total new cost: '.number_format($totalNewCost, 0).' UZS');

        if ($skippedCount > 0) {
            $this->warn("Skipped {$skippedCount} entries due to missing variants.");
        }

        if ($dryRun) {
            $this->warn('Dry run complete. Remove --dry-run to apply changes.');

            return 0;
        }

        // Confirm before applying
        if (! $force && ! $this->confirm('Do you want to apply these changes?')) {
            $this->info('Operation cancelled');

            return 0;
        }

        // Apply changes
        DB::beginTransaction();
        try {
            $updated = 0;
            foreach ($previewData as $row) {
                StockLedger::where('id', $row['entry_id'])->update([
                    'cost_delta' => $row['new_cost'],
                    'currency_code' => 'UZS',
                ]);
                $updated++;
            }

            DB::commit();
            $this->info("Successfully updated {$updated} entries");

            return 0;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Error: '.$e->getMessage());

            return 1;
        }
    }
}
