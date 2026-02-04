<?php

namespace App\Console\Commands;

use App\Models\MarketplaceAccount;
use App\Models\ProductVariant;
use App\Models\UzumOrder;
use App\Models\Warehouse\StockLedger;
use App\Models\Warehouse\StockReservation;
use App\Models\Warehouse\Warehouse;
use App\Models\WbOrder;
use App\Services\Stock\OrderStockService;
use App\Services\Stock\StockSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReleaseCancelledOrderReservations extends Command
{
    protected $signature = 'stock:release-cancelled
                            {--dry-run : Show what would be released without making changes}
                            {--company= : Process only specific company ID}';

    protected $description = 'Release stock reservations for cancelled marketplace orders and sync stock to marketplaces';

    protected OrderStockService $orderStockService;

    protected StockSyncService $stockSyncService;

    public function __construct(OrderStockService $orderStockService, StockSyncService $stockSyncService)
    {
        parent::__construct();
        $this->orderStockService = $orderStockService;
        $this->stockSyncService = $stockSyncService;
    }

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $companyId = $this->option('company');

        $this->info($dryRun ? '[DRY RUN] Checking cancelled orders with active reservations...' : 'Processing cancelled orders with active reservations...');

        $releasedCount = 0;
        $stockReturnedCount = 0;
        $syncedVariants = [];
        $errors = [];

        // Process Uzum cancelled orders
        $this->processUzumCancelledOrders($dryRun, $companyId, $releasedCount, $stockReturnedCount, $syncedVariants, $errors);

        // Process WB cancelled orders
        $this->processWbCancelledOrders($dryRun, $companyId, $releasedCount, $stockReturnedCount, $syncedVariants, $errors);

        // Summary
        $this->newLine();
        $this->info('=== Summary ===');
        $this->info("Reservations released: {$releasedCount}");
        $this->info("Stock returned (qty): {$stockReturnedCount}");
        $this->info('Variants synced to marketplaces: '.count($syncedVariants));

        if (! empty($errors)) {
            $this->warn('Errors: '.count($errors));
            foreach ($errors as $error) {
                $this->error("  - {$error}");
            }
        }

        if ($dryRun) {
            $this->warn('This was a dry run. No changes were made. Run without --dry-run to apply changes.');
        }

        return self::SUCCESS;
    }

    protected function processUzumCancelledOrders(
        bool $dryRun,
        ?string $companyId,
        int &$releasedCount,
        int &$stockReturnedCount,
        array &$syncedVariants,
        array &$errors
    ): void {
        $this->info('Processing Uzum orders...');

        // Find cancelled Uzum orders with reserved stock status
        $query = UzumOrder::query()
            ->whereIn('status', OrderStockService::CANCELLED_STATUSES['uzum'])
            ->where(function ($q) {
                $q->where('stock_status', 'reserved')
                    ->orWhereNull('stock_status');
            })
            ->with(['account', 'items']);

        if ($companyId) {
            $query->whereHas('account', function ($q) use ($companyId) {
                $q->where('company_id', $companyId);
            });
        }

        $cancelledOrders = $query->get();

        $this->info("Found {$cancelledOrders->count()} cancelled Uzum orders with active/reserved status");

        foreach ($cancelledOrders as $order) {
            $this->processOrder($order, 'uzum', $dryRun, $releasedCount, $stockReturnedCount, $syncedVariants, $errors);
        }
    }

    protected function processWbCancelledOrders(
        bool $dryRun,
        ?string $companyId,
        int &$releasedCount,
        int &$stockReturnedCount,
        array &$syncedVariants,
        array &$errors
    ): void {
        $this->info('Processing WB orders...');

        // Find cancelled WB orders with reserved stock status
        $query = WbOrder::query()
            ->whereIn('status', OrderStockService::CANCELLED_STATUSES['wb'])
            ->where(function ($q) {
                $q->where('stock_status', 'reserved')
                    ->orWhereNull('stock_status');
            })
            ->with(['account', 'items']);

        if ($companyId) {
            $query->whereHas('account', function ($q) use ($companyId) {
                $q->where('company_id', $companyId);
            });
        }

        $cancelledOrders = $query->get();

        $this->info("Found {$cancelledOrders->count()} cancelled WB orders with active/reserved status");

        foreach ($cancelledOrders as $order) {
            $this->processOrder($order, 'wb', $dryRun, $releasedCount, $stockReturnedCount, $syncedVariants, $errors);
        }
    }

    protected function processOrder(
        $order,
        string $marketplace,
        bool $dryRun,
        int &$releasedCount,
        int &$stockReturnedCount,
        array &$syncedVariants,
        array &$errors
    ): void {
        $account = $order->account;

        if (! $account) {
            $errors[] = "Order {$order->id}: No account found";

            return;
        }

        $this->line("  Processing order #{$order->external_order_id} (status: {$order->status}, stock_status: {$order->stock_status})");

        // Find active reservations for this order
        $reservations = StockReservation::where('source_type', 'marketplace_order')
            ->where('source_id', $order->id)
            ->where('status', StockReservation::STATUS_ACTIVE)
            ->with('sku.productVariant')
            ->get();

        if ($reservations->isEmpty()) {
            // Check if there are reservations but no stock was returned
            $cancelledReservations = StockReservation::where('source_type', 'marketplace_order')
                ->where('source_id', $order->id)
                ->where('status', StockReservation::STATUS_CANCELLED)
                ->get();

            if ($cancelledReservations->isNotEmpty()) {
                $this->line("    Reservations already cancelled ({$cancelledReservations->count()} items)");

                // Update order stock_status if not set
                if (! $dryRun && $order->stock_status !== 'released') {
                    $order->update([
                        'stock_status' => 'released',
                        'stock_released_at' => $order->stock_released_at ?? now(),
                    ]);
                }

                return;
            }

            $this->line('    No active reservations found, getting items from order...');

            // Try to release stock via OrderStockService using order items
            $items = $this->orderStockService->getOrderItems($order, $marketplace);

            if (empty($items)) {
                $this->warn('    No items found for order');

                // Still update stock_status
                if (! $dryRun) {
                    $order->update([
                        'stock_status' => 'released',
                        'stock_released_at' => now(),
                    ]);
                }

                return;
            }

            // Process items and return stock
            foreach ($items as $item) {
                $this->processOrderItem($order, $account, $item, $marketplace, $dryRun, $stockReturnedCount, $syncedVariants, $errors);
            }

            if (! $dryRun) {
                $order->update([
                    'stock_status' => 'released',
                    'stock_released_at' => now(),
                ]);
            }

            return;
        }

        // Process existing reservations
        $this->line("    Found {$reservations->count()} active reservations");

        if ($dryRun) {
            foreach ($reservations as $reservation) {
                $variant = $reservation->sku?->productVariant;
                $this->line("    [DRY] Would release: SKU {$reservation->sku_id}, qty: {$reservation->qty}".
                    ($variant ? ", variant: {$variant->sku}" : ''));
                $releasedCount++;
                $stockReturnedCount += $reservation->qty;
            }

            return;
        }

        // Actually release reservations
        DB::beginTransaction();

        try {
            foreach ($reservations as $reservation) {
                $variant = $reservation->sku?->productVariant;
                $qty = $reservation->qty;

                // 1. Cancel reservation
                $reservation->update(['status' => StockReservation::STATUS_CANCELLED]);
                $releasedCount++;

                // 2. Return stock to ProductVariant
                if ($variant) {
                    $stockBefore = $variant->stock_default;
                    $variant->incrementStock($qty);
                    $stockAfter = $variant->fresh()->stock_default;
                    $stockReturnedCount += $qty;

                    $this->line("    Released: {$variant->sku}, qty: +{$qty} (was: {$stockBefore}, now: {$stockAfter})");

                    // 3. Create positive ledger entry
                    $this->createLedgerEntry($account, $order, $reservation, $qty, $marketplace);

                    // Track for sync
                    if (! in_array($variant->id, $syncedVariants)) {
                        $syncedVariants[] = $variant->id;
                    }

                    // 4. Sync to marketplaces
                    $this->syncVariantToMarketplaces($variant, $account->id);
                } else {
                    $this->warn("    No variant found for reservation {$reservation->id}");
                }
            }

            // Update order status
            $order->update([
                'stock_status' => 'released',
                'stock_released_at' => now(),
            ]);

            DB::commit();

        } catch (\Throwable $e) {
            DB::rollBack();
            $errors[] = "Order {$order->id}: {$e->getMessage()}";
            $this->error("    Error: {$e->getMessage()}");
        }
    }

    protected function processOrderItem(
        $order,
        MarketplaceAccount $account,
        array $item,
        string $marketplace,
        bool $dryRun,
        int &$stockReturnedCount,
        array &$syncedVariants,
        array &$errors
    ): void {
        $qty = (int) ($item['quantity'] ?? $item['amount'] ?? $item['qty'] ?? 1);

        // Find variant
        $variant = $this->findVariant($account, $item, $marketplace);

        if (! $variant) {
            $this->warn('    Item not linked: '.json_encode($item));

            return;
        }

        if ($dryRun) {
            $this->line("    [DRY] Would return stock: {$variant->sku}, qty: +{$qty}");
            $stockReturnedCount += $qty;

            return;
        }

        // Return stock
        $stockBefore = $variant->stock_default;
        $variant->incrementStock($qty);
        $stockAfter = $variant->fresh()->stock_default;
        $stockReturnedCount += $qty;

        $this->line("    Returned stock: {$variant->sku}, qty: +{$qty} (was: {$stockBefore}, now: {$stockAfter})");

        // Create ledger entry
        $this->createLedgerEntryForVariant($account, $order, $variant, $qty, $marketplace);

        // Track and sync
        if (! in_array($variant->id, $syncedVariants)) {
            $syncedVariants[] = $variant->id;
        }

        $this->syncVariantToMarketplaces($variant, $account->id);
    }

    protected function findVariant(MarketplaceAccount $account, array $item, string $marketplace): ?ProductVariant
    {
        $skuId = $item['sku_id'] ?? $item['skuId'] ?? $item['external_offer_id'] ?? null;
        $offerId = $item['offer_id'] ?? $item['offerId'] ?? $item['article'] ?? $item['supplierArticle'] ?? null;
        $barcode = $item['barcode'] ?? null;

        // Search by barcode first
        if ($barcode) {
            $link = \App\Models\VariantMarketplaceLink::where('marketplace_account_id', $account->id)
                ->where('marketplace_barcode', $barcode)
                ->where('is_active', true)
                ->first();

            if ($link?->variant) {
                return $link->variant;
            }
        }

        // Search by external IDs
        if ($skuId || $offerId) {
            $link = \App\Models\VariantMarketplaceLink::where('marketplace_account_id', $account->id)
                ->where('is_active', true)
                ->where(function ($q) use ($skuId, $offerId) {
                    if ($skuId) {
                        $q->orWhere('external_sku_id', $skuId);
                    }
                    if ($offerId) {
                        $q->orWhere('external_offer_id', $offerId);
                    }
                })
                ->first();

            if ($link?->variant) {
                return $link->variant;
            }
        }

        // Fallback: by internal barcode or SKU
        if ($barcode) {
            $variant = ProductVariant::where('barcode', $barcode)
                ->where('company_id', $account->company_id)
                ->first();
            if ($variant) {
                return $variant;
            }
        }

        if ($offerId) {
            $variant = ProductVariant::where('sku', $offerId)
                ->where('company_id', $account->company_id)
                ->first();
            if ($variant) {
                return $variant;
            }
        }

        return null;
    }

    protected function createLedgerEntry(
        MarketplaceAccount $account,
        $order,
        StockReservation $reservation,
        int $qty,
        string $marketplace
    ): void {
        try {
            StockLedger::create([
                'company_id' => $account->company_id,
                'occurred_at' => now(),
                'warehouse_id' => $reservation->warehouse_id,
                'sku_id' => $reservation->sku_id,
                'qty_delta' => $qty, // Positive to return stock
                'cost_delta' => 0,
                'currency_code' => 'UZS',
                'source_type' => 'marketplace_order_cancel',
                'source_id' => $order->id,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Failed to create ledger entry', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function createLedgerEntryForVariant(
        MarketplaceAccount $account,
        $order,
        ProductVariant $variant,
        int $qty,
        string $marketplace
    ): void {
        try {
            // Get warehouse
            $warehouse = Warehouse::where('company_id', $account->company_id)
                ->where('is_active', true)
                ->first();

            if (! $warehouse) {
                return;
            }

            // Get or create warehouse SKU
            $warehouseSku = \App\Models\Warehouse\Sku::firstOrCreate(
                [
                    'product_variant_id' => $variant->id,
                    'company_id' => $account->company_id,
                ],
                [
                    'product_id' => $variant->product_id,
                    'sku_code' => $variant->sku,
                    'barcode_ean13' => $variant->barcode,
                    'is_active' => true,
                ]
            );

            StockLedger::create([
                'company_id' => $account->company_id,
                'occurred_at' => now(),
                'warehouse_id' => $warehouse->id,
                'sku_id' => $warehouseSku->id,
                'qty_delta' => $qty,
                'cost_delta' => 0,
                'currency_code' => 'UZS',
                'source_type' => 'marketplace_order_cancel',
                'source_id' => $order->id,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Failed to create ledger entry for variant', [
                'variant_id' => $variant->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function syncVariantToMarketplaces(ProductVariant $variant, int $excludeAccountId): void
    {
        try {
            $links = $variant->activeMarketplaceLinks()
                ->where('sync_stock_enabled', true)
                ->where('marketplace_account_id', '!=', $excludeAccountId)
                ->with('account')
                ->get();

            $currentStock = $variant->getCurrentStock();

            foreach ($links as $link) {
                try {
                    $this->stockSyncService->syncLinkStock($link, $currentStock);
                    $this->line("      Synced to {$link->account->marketplace}: stock={$currentStock}");
                } catch (\Throwable $e) {
                    Log::warning('Failed to sync stock', [
                        'variant_id' => $variant->id,
                        'link_id' => $link->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to sync variant to marketplaces', [
                'variant_id' => $variant->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
