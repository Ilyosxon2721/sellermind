<?php

namespace App\Console\Commands;

use App\Models\MarketplaceAccount;
use App\Models\VariantMarketplaceLink;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FixUzumStockSyncLinks extends Command
{
    protected $signature = 'uzum:fix-stock-links
                            {--company= : Process only specific company ID}
                            {--account= : Process only specific account ID}
                            {--dry-run : Show what would be fixed without making changes}';

    protected $description = 'Fix Uzum variant links that are missing external_sku_id required for stock sync';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $companyId = $this->option('company');
        $accountId = $this->option('account');

        $this->info($dryRun ? '[DRY RUN] Checking Uzum links...' : 'Fixing Uzum links...');

        // Get Uzum accounts
        $accountsQuery = MarketplaceAccount::where('marketplace', 'uzum')
            ->where('is_active', true);

        if ($companyId) {
            $accountsQuery->where('company_id', $companyId);
        }

        if ($accountId) {
            $accountsQuery->where('id', $accountId);
        }

        $accounts = $accountsQuery->get();

        $this->info("Found {$accounts->count()} Uzum accounts");

        $totalFixed = 0;
        $totalSkipped = 0;
        $totalErrors = 0;

        foreach ($accounts as $account) {
            $this->line("\nProcessing account: {$account->name} (ID: {$account->id})");

            // Get links without external_sku_id
            $links = VariantMarketplaceLink::where('marketplace_account_id', $account->id)
                ->where('is_active', true)
                ->where(function ($q) {
                    $q->whereNull('external_sku_id')
                      ->orWhere('external_sku_id', '');
                })
                ->with(['variant', 'marketplaceProduct'])
                ->get();

            $this->info("  Found {$links->count()} links without external_sku_id");

            foreach ($links as $link) {
                $variantSku = $link->variant?->sku ?? 'N/A';
                $this->line("  Processing link {$link->id} (variant: {$variantSku})");

                $mpProduct = $link->marketplaceProduct;

                if (!$mpProduct) {
                    $this->warn("    Skipped: No MarketplaceProduct found");
                    $totalSkipped++;
                    continue;
                }

                $skuList = $mpProduct->raw_payload['skuList'] ?? [];

                if (empty($skuList)) {
                    // Try external_offer_id as fallback
                    if ($mpProduct->external_offer_id) {
                        if ($dryRun) {
                            $this->info("    [DRY] Would set external_sku_id = {$mpProduct->external_offer_id} (from external_offer_id)");
                        } else {
                            $link->update(['external_sku_id' => (string) $mpProduct->external_offer_id]);
                            $this->info("    Fixed: external_sku_id = {$mpProduct->external_offer_id} (from external_offer_id)");
                        }
                        $totalFixed++;
                    } else {
                        $this->warn("    Skipped: No skuList and no external_offer_id");
                        $totalSkipped++;
                    }
                    continue;
                }

                // Try to find matching SKU
                $foundSkuId = null;
                $foundBarcode = null;

                // 1. By marketplace_barcode
                $linkBarcode = $link->marketplace_barcode ?? $link->variant?->barcode ?? null;

                if ($linkBarcode) {
                    foreach ($skuList as $sku) {
                        $skuBarcode = isset($sku['barcode']) ? (string) $sku['barcode'] : null;
                        if ($skuBarcode && $skuBarcode === (string) $linkBarcode) {
                            $foundSkuId = isset($sku['skuId']) ? (string) $sku['skuId'] : null;
                            $foundBarcode = $skuBarcode;
                            break;
                        }
                    }
                }

                // 2. By external_sku (title match)
                if (!$foundSkuId && $link->external_sku) {
                    foreach ($skuList as $sku) {
                        $skuTitle = $sku['skuTitle'] ?? $sku['skuFullTitle'] ?? null;
                        if ($skuTitle && stripos($skuTitle, $link->external_sku) !== false) {
                            $foundSkuId = isset($sku['skuId']) ? (string) $sku['skuId'] : null;
                            $foundBarcode = $sku['barcode'] ?? null;
                            break;
                        }
                    }
                }

                // 3. Use first SKU if only one exists
                if (!$foundSkuId && count($skuList) === 1) {
                    $firstSku = $skuList[0];
                    $foundSkuId = isset($firstSku['skuId']) ? (string) $firstSku['skuId'] : null;
                    $foundBarcode = $firstSku['barcode'] ?? null;
                }

                if ($foundSkuId) {
                    if ($dryRun) {
                        $this->info("    [DRY] Would set external_sku_id = {$foundSkuId}" .
                            ($foundBarcode ? ", marketplace_barcode = {$foundBarcode}" : ""));
                    } else {
                        $updateData = ['external_sku_id' => $foundSkuId];
                        if ($foundBarcode && !$link->marketplace_barcode) {
                            $updateData['marketplace_barcode'] = $foundBarcode;
                        }
                        $link->update($updateData);
                        $this->info("    Fixed: external_sku_id = {$foundSkuId}" .
                            (isset($updateData['marketplace_barcode']) ? ", marketplace_barcode = {$foundBarcode}" : ""));
                    }
                    $totalFixed++;
                } else {
                    $this->warn("    Skipped: Could not determine skuId (multi-SKU product without barcode match)");
                    $this->line("      Available SKUs in product:");
                    foreach ($skuList as $sku) {
                        $this->line("        - skuId: " . ($sku['skuId'] ?? 'N/A') .
                            ", barcode: " . ($sku['barcode'] ?? 'N/A') .
                            ", title: " . ($sku['skuTitle'] ?? $sku['skuFullTitle'] ?? 'N/A'));
                    }
                    $totalSkipped++;
                }
            }
        }

        $this->newLine();
        $this->info("=== Summary ===");
        $this->info("Fixed: {$totalFixed}");
        $this->info("Skipped: {$totalSkipped}");
        $this->info("Errors: {$totalErrors}");

        if ($dryRun) {
            $this->warn('This was a dry run. Run without --dry-run to apply changes.');
        }

        return self::SUCCESS;
    }
}
