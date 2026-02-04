<?php

namespace App\Console\Commands;

use App\Models\UzumOrder;
use App\Models\VariantMarketplaceLink;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RecalculateStockFromOrders extends Command
{
    protected $signature = 'stock:recalculate-from-orders
                            {--account= : Marketplace account ID}
                            {--dry-run : Show what would be done without making changes}';

    protected $description = 'Recalculate internal stock based on unprocessed Uzum orders';

    // Statuses that should reduce stock (sold items)
    protected array $soldStatuses = [
        'new', 'in_assembly', 'in_supply', 'accepted_uzum',
        'waiting_pickup', 'issued',
        'CREATED', 'PACKING', 'PENDING_DELIVERY', 'DELIVERING',
        'ACCEPTED_AT_DP', 'DELIVERED_TO_CUSTOMER_DELIVERY_POINT',
        'DELIVERED', 'COMPLETED',
    ];

    public function handle(): int
    {
        $accountId = $this->option('account');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - no changes will be made');
        }

        // Get all unprocessed orders
        $query = UzumOrder::with('items')
            ->whereIn('status', $this->soldStatuses)
            ->where(function ($q) {
                $q->where('stock_status', 'none')
                    ->orWhereNull('stock_status');
            });

        if ($accountId) {
            $query->where('marketplace_account_id', $accountId);
        }

        $orders = $query->get();
        $this->info("Found {$orders->count()} unprocessed orders");

        $stockChanges = [];
        $processedOrders = [];

        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                $rawPayload = $item->raw_payload ?? [];
                $barcode = $rawPayload['barcode'] ?? null;

                if (! $barcode) {
                    continue;
                }

                // Find linked variant by barcode
                $link = VariantMarketplaceLink::query()
                    ->where('marketplace_account_id', $order->marketplace_account_id)
                    ->where('is_active', true)
                    ->whereHas('variant', fn ($q) => $q->where('barcode', $barcode))
                    ->with('variant')
                    ->first();

                if (! $link || ! $link->variant) {
                    continue;
                }

                $variantId = $link->variant->id;
                $sku = $link->variant->sku;

                if (! isset($stockChanges[$variantId])) {
                    $stockChanges[$variantId] = [
                        'variant' => $link->variant,
                        'sku' => $sku,
                        'current_stock' => $link->variant->stock_default,
                        'to_deduct' => 0,
                        'orders' => [],
                    ];
                }

                $stockChanges[$variantId]['to_deduct'] += $item->quantity;
                $stockChanges[$variantId]['orders'][] = $order->external_order_id;
                $processedOrders[$order->id] = $order;
            }
        }

        if (empty($stockChanges)) {
            $this->info('No stock changes needed');

            return 0;
        }

        $this->info('');
        $this->info('Stock changes to apply:');
        $this->table(
            ['SKU', 'Current Stock', 'To Deduct', 'New Stock', 'Orders Count'],
            collect($stockChanges)->map(fn ($data) => [
                $data['sku'],
                $data['current_stock'],
                $data['to_deduct'],
                $data['current_stock'] - $data['to_deduct'],
                count(array_unique($data['orders'])),
            ])->toArray()
        );

        if ($dryRun) {
            $this->warn('DRY RUN - no changes made. Remove --dry-run to apply changes.');

            return 0;
        }

        if (! $this->confirm('Apply these stock changes?')) {
            $this->info('Cancelled');

            return 0;
        }

        DB::beginTransaction();
        try {
            foreach ($stockChanges as $variantId => $data) {
                $variant = $data['variant'];
                $oldStock = $variant->stock_default;
                $newStock = max(0, $oldStock - $data['to_deduct']);

                $variant->update(['stock_default' => $newStock]);

                Log::info('Stock recalculated from orders', [
                    'variant_id' => $variantId,
                    'sku' => $data['sku'],
                    'old_stock' => $oldStock,
                    'deducted' => $data['to_deduct'],
                    'new_stock' => $newStock,
                    'orders_count' => count(array_unique($data['orders'])),
                ]);

                $this->line("Updated {$data['sku']}: {$oldStock} -> {$newStock}");
            }

            // Mark orders as processed
            foreach ($processedOrders as $order) {
                $order->update(['stock_status' => 'sold']);
            }

            DB::commit();
            $this->info('Stock recalculated successfully!');
            $this->info('Processed '.count($processedOrders).' orders');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Error: '.$e->getMessage());
            Log::error('Stock recalculation failed', ['error' => $e->getMessage()]);

            return 1;
        }

        return 0;
    }
}
