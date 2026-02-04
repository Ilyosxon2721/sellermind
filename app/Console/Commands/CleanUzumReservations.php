<?php

namespace App\Console\Commands;

use App\Models\MarketplaceAccount;
use App\Models\UzumOrder;
use App\Models\Warehouse\StockLedger;
use App\Models\Warehouse\StockReservation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CleanUzumReservations extends Command
{
    protected $signature = 'reservations:clean-uzum
        {--dry-run : Show what would be done without making changes}
        {--keep-correct : Only remove reservations for items NOT linked via barcode}';

    protected $description = 'Clean ALL Uzum stock reservations and reset order stock_status. Use after fixing the barcode lookup logic.';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $keepCorrect = $this->option('keep-correct');

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

        $totalReservationsDeleted = 0;
        $totalOrdersReset = 0;
        $totalStockReturned = 0;

        foreach ($uzumAccounts as $account) {
            $this->info("\nProcessing account ID: {$account->id} ({$account->name})");

            // Get all Uzum orders with reservations
            $ordersWithReservations = UzumOrder::where('marketplace_account_id', $account->id)
                ->whereIn('stock_status', ['reserved', 'sold'])
                ->with('items')
                ->get();

            $this->info("  Found {$ordersWithReservations->count()} orders with stock_status reserved/sold");

            if ($ordersWithReservations->isEmpty()) {
                continue;
            }

            $progressBar = $this->output->createProgressBar($ordersWithReservations->count());
            $progressBar->start();

            foreach ($ordersWithReservations as $order) {
                try {
                    $result = $this->cleanOrderReservations($account, $order, $dryRun, $keepCorrect);

                    $totalReservationsDeleted += $result['reservations_deleted'];
                    $totalStockReturned += $result['stock_returned'];
                    if ($result['order_reset']) {
                        $totalOrdersReset++;
                    }
                } catch (\Throwable $e) {
                    $this->error("\nError processing order {$order->external_order_id}: {$e->getMessage()}");
                    Log::error('CleanUzumReservations: Exception', [
                        'order_id' => $order->id,
                        'error' => $e->getMessage(),
                    ]);
                }

                $progressBar->advance();
            }

            $progressBar->finish();
            $this->newLine();
        }

        $this->newLine();
        $this->info('Summary:');
        $this->info("  Reservations deleted: {$totalReservationsDeleted}");
        $this->info("  Orders reset: {$totalOrdersReset}");
        $this->info("  Total stock returned: {$totalStockReturned}");

        if (! $dryRun) {
            $this->warn("\nIMPORTANT: Run 'php artisan reservations:fix-uzum' to re-process orders with correct logic");
        }

        return 0;
    }

    protected function cleanOrderReservations(
        MarketplaceAccount $account,
        UzumOrder $order,
        bool $dryRun,
        bool $keepCorrect
    ): array {
        $result = [
            'reservations_deleted' => 0,
            'stock_returned' => 0,
            'order_reset' => false,
        ];

        // Get existing reservations for this order
        $reservations = StockReservation::where('source_type', 'marketplace_order')
            ->where('source_id', $order->id)
            ->with('sku.productVariant')
            ->get();

        if ($reservations->isEmpty()) {
            // No reservations, just reset status if needed
            if ($order->stock_status !== 'none' && ! $dryRun) {
                $order->update(['stock_status' => 'none', 'stock_reserved_at' => null, 'stock_sold_at' => null]);
                $result['order_reset'] = true;
            }

            return $result;
        }

        if ($dryRun) {
            $this->line("\n  Order #{$order->external_order_id}:");
            $this->line("    Status: {$order->stock_status}");
            $this->line("    Reservations: {$reservations->count()}");
            foreach ($reservations as $res) {
                $variantSku = $res->sku?->productVariant?->sku ?? 'UNKNOWN';
                $this->line("      - SKU: {$variantSku}, Qty: {$res->qty}");
            }

            // Show what items SHOULD be reserved based on correct barcode lookup
            $items = $order->items;
            $this->line('    Order items:');
            foreach ($items as $item) {
                $barcode = $item->raw_payload['barcode'] ?? 'NO_BARCODE';
                $correctVariant = $this->findVariantByBarcodeInSkuList($account, $barcode);
                $correctSku = $correctVariant ? $correctVariant->sku : 'NOT_LINKED';
                $this->line("      - {$item->name}, Barcode: {$barcode} => Should be: {$correctSku}");
            }

            $result['reservations_deleted'] = $reservations->count();

            return $result;
        }

        DB::beginTransaction();
        try {
            foreach ($reservations as $reservation) {
                $variant = $reservation->sku?->productVariant;
                $qty = $reservation->qty;

                // Return stock to variant
                if ($variant) {
                    $variant->incrementStock($qty);
                    $result['stock_returned'] += $qty;

                    // Create reversal ledger entry
                    StockLedger::create([
                        'company_id' => $account->company_id,
                        'occurred_at' => now(),
                        'warehouse_id' => $reservation->warehouse_id,
                        'sku_id' => $reservation->sku_id,
                        'qty_delta' => $qty,
                        'cost_delta' => 0,
                        'currency_code' => 'UZS',
                        'source_type' => 'reservation_cleanup_reversal',
                        'source_id' => $order->id,
                    ]);
                }

                // Delete reservation
                $reservation->delete();
                $result['reservations_deleted']++;
            }

            // Delete old ledger entries for this order
            StockLedger::where('source_type', 'marketplace_order_reserve')
                ->where('source_id', $order->id)
                ->delete();

            // Reset order status
            $order->update([
                'stock_status' => 'none',
                'stock_reserved_at' => null,
                'stock_sold_at' => null,
            ]);
            $result['order_reset'] = true;

            DB::commit();

            Log::info('CleanUzumReservations: Order cleaned', [
                'order_id' => $order->id,
                'external_order_id' => $order->external_order_id,
                'reservations_deleted' => $result['reservations_deleted'],
                'stock_returned' => $result['stock_returned'],
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return $result;
    }

    /**
     * Find variant by barcode using correct lookup (no fallbacks)
     */
    protected function findVariantByBarcodeInSkuList(
        MarketplaceAccount $account,
        string $barcode
    ): ?\App\Models\ProductVariant {
        if (empty($barcode) || $barcode === 'NO_BARCODE') {
            return null;
        }

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

        if (! $marketplaceProduct) {
            return null;
        }

        $skuList = $marketplaceProduct->raw_payload['skuList'] ?? [];
        $matchedSkuId = null;
        foreach ($skuList as $sku) {
            if (isset($sku['barcode']) && (string) $sku['barcode'] === (string) $barcode) {
                $matchedSkuId = $sku['skuId'] ?? null;
                break;
            }
        }

        if (! $matchedSkuId) {
            return null;
        }

        $link = \App\Models\VariantMarketplaceLink::query()
            ->where('marketplace_account_id', $account->id)
            ->where('external_sku_id', (string) $matchedSkuId)
            ->where('is_active', true)
            ->first();

        return $link?->variant;
    }
}
