<?php

namespace App\Console\Commands;

use App\Models\MarketplaceAccount;
use App\Models\UzumOrder;
use App\Models\Warehouse\StockReservation;
use App\Models\Warehouse\StockLedger;
use App\Services\Stock\OrderStockService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FixUzumReservations extends Command
{
    protected $signature = 'reservations:fix-uzum
        {--dry-run : Show what would be done without making changes}
        {--order= : Fix only a specific order by external_order_id}
        {--limit=100 : Limit number of orders to process}';

    protected $description = 'Fix Uzum stock reservations that were created with wrong variant matching (before barcode lookup fix)';

    public function handle(OrderStockService $stockService): int
    {
        $dryRun = $this->option('dry-run');
        $specificOrder = $this->option('order');
        $limit = (int) $this->option('limit');

        if ($dryRun) {
            $this->info('DRY RUN MODE - No changes will be made');
        }

        // Get Uzum accounts
        $uzumAccounts = MarketplaceAccount::where('marketplace', 'uzum')
            ->where('is_active', true)
            ->get();

        if ($uzumAccounts->isEmpty()) {
            $this->error('No active Uzum accounts found');
            return 1;
        }

        $this->info("Found {$uzumAccounts->count()} active Uzum accounts");

        $totalFixed = 0;
        $totalFailed = 0;

        foreach ($uzumAccounts as $account) {
            $this->info("\nProcessing account ID: {$account->id}");

            // Get orders with stock_status = 'reserved' that need fixing
            $query = UzumOrder::where('marketplace_account_id', $account->id)
                ->where('stock_status', 'reserved')
                ->with('items');

            if ($specificOrder) {
                $query->where('external_order_id', $specificOrder);
            }

            $orders = $query->limit($limit)->get();

            if ($orders->isEmpty()) {
                $this->info('  No orders with reserved status found');
                continue;
            }

            $this->info("  Found {$orders->count()} orders with reserved status");

            $progressBar = $this->output->createProgressBar($orders->count());
            $progressBar->start();

            foreach ($orders as $order) {
                try {
                    $result = $this->fixOrderReservation($account, $order, $stockService, $dryRun);

                    if ($result['success']) {
                        $totalFixed++;
                    } else {
                        $totalFailed++;
                        Log::warning('FixUzumReservations: Failed to fix order', [
                            'order_id' => $order->id,
                            'external_order_id' => $order->external_order_id,
                            'error' => $result['error'] ?? 'Unknown error',
                        ]);
                    }
                } catch (\Throwable $e) {
                    $totalFailed++;
                    $this->error("\nError processing order {$order->external_order_id}: {$e->getMessage()}");
                    Log::error('FixUzumReservations: Exception', [
                        'order_id' => $order->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }

                $progressBar->advance();
            }

            $progressBar->finish();
            $this->newLine();
        }

        $this->newLine();
        $this->info("Fixed: {$totalFixed}, Failed: {$totalFailed}");

        return $totalFailed > 0 ? 1 : 0;
    }

    protected function fixOrderReservation(
        MarketplaceAccount $account,
        UzumOrder $order,
        OrderStockService $stockService,
        bool $dryRun
    ): array {
        $orderId = $order->external_order_id;

        // Get existing reservations for this order
        $existingReservations = StockReservation::where('source_type', 'marketplace_order')
            ->where('source_id', $order->id)
            ->with('sku.productVariant')
            ->get();

        // Get order items and what variants they SHOULD be linked to
        $items = $stockService->getOrderItems($order, 'uzum');
        $expectedVariants = [];

        foreach ($items as $item) {
            $barcode = $item['barcode'] ?? null;
            if (!$barcode) {
                continue;
            }

            // Find what variant this barcode should map to using the NEW lookup
            $variant = $this->findVariantByBarcodeInSkuList($account, $barcode);
            if ($variant) {
                $expectedVariants[$barcode] = [
                    'variant_id' => $variant->id,
                    'variant_sku' => $variant->sku,
                    'quantity' => $item['quantity'] ?? 1,
                ];
            }
        }

        // Check if current reservations match expected
        $needsFix = false;
        foreach ($existingReservations as $reservation) {
            $currentVariantId = $reservation->sku?->productVariant?->id;
            $matchFound = false;
            foreach ($expectedVariants as $barcode => $expected) {
                if ($currentVariantId === $expected['variant_id']) {
                    $matchFound = true;
                    break;
                }
            }
            if (!$matchFound) {
                $needsFix = true;
                break;
            }
        }

        if (!$needsFix && $existingReservations->isNotEmpty()) {
            // Reservations look correct
            return ['success' => true, 'action' => 'skipped', 'reason' => 'Already correct'];
        }

        if ($dryRun) {
            $this->line("\n  Order {$orderId}:");
            $this->line("    Current reservations:");
            foreach ($existingReservations as $res) {
                $variantSku = $res->sku?->productVariant?->sku ?? 'UNKNOWN';
                $this->line("      - SKU: {$variantSku}, Qty: {$res->qty}");
            }
            $this->line("    Expected variants:");
            foreach ($expectedVariants as $barcode => $expected) {
                $this->line("      - Barcode {$barcode} -> SKU: {$expected['variant_sku']}, Qty: {$expected['quantity']}");
            }
            return ['success' => true, 'action' => 'dry_run'];
        }

        // Actually fix the reservations
        return $this->doFixReservation($account, $order, $existingReservations, $stockService);
    }

    protected function doFixReservation(
        MarketplaceAccount $account,
        UzumOrder $order,
        $existingReservations,
        OrderStockService $stockService
    ): array {
        DB::beginTransaction();

        try {
            // 1. Delete old incorrect reservations and reverse their stock changes
            foreach ($existingReservations as $reservation) {
                $variant = $reservation->sku?->productVariant;
                $qty = $reservation->qty;

                // Reverse the stock deduction (add back)
                if ($variant) {
                    $variant->incrementStock($qty);

                    // Create reversal ledger entry
                    StockLedger::create([
                        'company_id' => $account->company_id,
                        'occurred_at' => now(),
                        'warehouse_id' => $reservation->warehouse_id,
                        'sku_id' => $reservation->sku_id,
                        'qty_delta' => $qty, // Positive to add back
                        'cost_delta' => 0,
                        'currency_code' => 'UZS',
                        'source_type' => 'reservation_fix_reversal',
                        'source_id' => $order->id,
                    ]);
                }

                // Delete the reservation
                $reservation->delete();
            }

            // Also delete any ledger entries created for this order (if they point to wrong SKUs)
            StockLedger::where('source_type', 'marketplace_order_reserve')
                ->where('source_id', $order->id)
                ->delete();

            // 2. Reset order stock status to 'none' so it can be reprocessed
            $order->update(['stock_status' => 'none', 'stock_reserved_at' => null]);

            // 3. Get items and reprocess with correct barcode lookup
            $items = $stockService->getOrderItems($order, 'uzum');

            // 4. Process with correct lookup (this will use findVariantByBarcodeInSkuList)
            $result = $stockService->processOrderStatusChange(
                $account,
                $order,
                null,
                $order->status,
                $items
            );

            DB::commit();

            Log::info('FixUzumReservations: Order fixed', [
                'order_id' => $order->id,
                'external_order_id' => $order->external_order_id,
                'result' => $result,
            ]);

            return ['success' => $result['success'] ?? true, 'action' => 'fixed', 'result' => $result];

        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Copy of findVariantByBarcodeInSkuList from OrderStockService for diagnostic purposes
     */
    protected function findVariantByBarcodeInSkuList(
        MarketplaceAccount $account,
        string $barcode
    ): ?\App\Models\ProductVariant {
        // Find MarketplaceProduct where skuList contains this barcode
        $marketplaceProduct = \App\Models\MarketplaceProduct::query()
            ->where('marketplace_account_id', $account->id)
            ->whereNotNull('raw_payload')
            ->get()
            ->first(function ($product) use ($barcode) {
                $skuList = $product->raw_payload['skuList'] ?? [];
                foreach ($skuList as $sku) {
                    if (isset($sku['barcode']) && (string) $sku['barcode'] === (string) $barcode) {
                        return true;
                    }
                }
                return false;
            });

        if (!$marketplaceProduct) {
            return null;
        }

        // Find skuId for this barcode
        $skuList = $marketplaceProduct->raw_payload['skuList'] ?? [];
        $matchedSkuId = null;
        foreach ($skuList as $sku) {
            if (isset($sku['barcode']) && (string) $sku['barcode'] === (string) $barcode) {
                $matchedSkuId = $sku['skuId'] ?? null;
                break;
            }
        }

        if (!$matchedSkuId) {
            return null;
        }

        // Find VariantMarketplaceLink by this skuId
        $link = \App\Models\VariantMarketplaceLink::query()
            ->where('marketplace_account_id', $account->id)
            ->where('external_sku_id', (string) $matchedSkuId)
            ->where('is_active', true)
            ->first();

        if ($link && $link->variant) {
            return $link->variant;
        }

        // Fallback: if no link found by skuId, try by product
        $link = \App\Models\VariantMarketplaceLink::query()
            ->where('marketplace_account_id', $account->id)
            ->where('marketplace_product_id', $marketplaceProduct->id)
            ->where('is_active', true)
            ->first();

        return $link?->variant;
    }
}
