<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductVariant;
use App\Models\UzumOrder;
use App\Models\VariantMarketplaceLink;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StockRecalculateController extends Controller
{
    // Statuses that should reduce stock (sold items)
    protected array $soldStatuses = [
        'new', 'in_assembly', 'in_supply', 'accepted_uzum',
        'waiting_pickup', 'issued',
        'CREATED', 'PACKING', 'PENDING_DELIVERY', 'DELIVERING',
        'ACCEPTED_AT_DP', 'DELIVERED_TO_CUSTOMER_DELIVERY_POINT',
        'DELIVERED', 'COMPLETED',
    ];

    /**
     * Show current stock status and what would be recalculated (dry-run)
     * GET /api/stock/recalculate-preview
     */
    public function preview(Request $request): JsonResponse
    {
        $accountId = $request->query('account_id');

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

        // Предзагрузка всех VariantMarketplaceLink по баркодам (N+1 fix)
        $allBarcodes = $orders->flatMap(fn ($order) => $order->items->map(
            fn ($item) => $item->raw_payload['barcode'] ?? null
        ))->filter()->unique()->values()->all();

        $allLinks = VariantMarketplaceLink::query()
            ->where('is_active', true)
            ->whereHas('variant', fn ($q) => $q->whereIn('barcode', $allBarcodes))
            ->with('variant')
            ->get()
            ->groupBy(fn ($link) => $link->marketplace_account_id.':'.$link->variant->barcode);

        $stockChanges = [];

        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                $rawPayload = $item->raw_payload ?? [];
                $barcode = $rawPayload['barcode'] ?? null;

                if (! $barcode) {
                    continue;
                }

                $link = ($allLinks[$order->marketplace_account_id.':'.$barcode] ?? collect())->first();

                if (! $link || ! $link->variant) {
                    continue;
                }

                $variantId = $link->variant->id;
                $sku = $link->variant->sku;

                if (! isset($stockChanges[$variantId])) {
                    $stockChanges[$variantId] = [
                        'variant_id' => $variantId,
                        'sku' => $sku,
                        'barcode' => $link->variant->barcode,
                        'current_stock' => $link->variant->stock_default ?? 0,
                        'to_deduct' => 0,
                        'new_stock' => 0,
                        'orders_count' => 0,
                    ];
                }

                $stockChanges[$variantId]['to_deduct'] += $item->quantity;
                $stockChanges[$variantId]['orders_count']++;
            }
        }

        // Calculate new stock
        foreach ($stockChanges as $variantId => &$data) {
            $data['new_stock'] = max(0, $data['current_stock'] - $data['to_deduct']);
        }

        return response()->json([
            'total_unprocessed_orders' => $orders->count(),
            'stock_changes' => array_values($stockChanges),
            'message' => 'This is a preview. Use POST /api/stock/recalculate to apply changes.',
        ]);
    }

    /**
     * Set initial stock for variants by SKU
     * POST /api/stock/set-initial
     * Body: { "stocks": { "FH25201-L-PINK": 129, "FH25201-M-PINK": 118, ... } }
     */
    public function setInitialStock(Request $request): JsonResponse
    {
        $stocks = $request->input('stocks', []);

        if (empty($stocks)) {
            return response()->json(['error' => 'No stocks provided'], 400);
        }

        $results = [];

        // Предзагрузка всех вариантов по SKU (N+1 fix)
        $skus = array_keys($stocks);
        $variants = ProductVariant::whereIn('sku', $skus)->get()->keyBy('sku');

        foreach ($stocks as $sku => $stock) {
            $variant = $variants[$sku] ?? null;
            if ($variant) {
                $old = $variant->stock_default;
                $variant->update(['stock_default' => (int) $stock]);
                $results[] = [
                    'sku' => $sku,
                    'old_stock' => $old,
                    'new_stock' => $stock,
                    'status' => 'updated',
                ];
            } else {
                $results[] = [
                    'sku' => $sku,
                    'status' => 'not_found',
                ];
            }
        }

        return response()->json([
            'message' => 'Stock updated',
            'results' => $results,
        ]);
    }

    /**
     * Apply stock recalculation
     * POST /api/stock/recalculate
     */
    public function recalculate(Request $request): JsonResponse
    {
        $accountId = $request->input('account_id');

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

        // Предзагрузка всех VariantMarketplaceLink по баркодам (N+1 fix)
        $allBarcodes = $orders->flatMap(fn ($order) => $order->items->map(
            fn ($item) => $item->raw_payload['barcode'] ?? null
        ))->filter()->unique()->values()->all();

        $allLinks = VariantMarketplaceLink::query()
            ->where('is_active', true)
            ->whereHas('variant', fn ($q) => $q->whereIn('barcode', $allBarcodes))
            ->with('variant')
            ->get()
            ->groupBy(fn ($link) => $link->marketplace_account_id.':'.$link->variant->barcode);

        $stockChanges = [];
        $processedOrders = [];

        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                $rawPayload = $item->raw_payload ?? [];
                $barcode = $rawPayload['barcode'] ?? null;

                if (! $barcode) {
                    continue;
                }

                $link = ($allLinks[$order->marketplace_account_id.':'.$barcode] ?? collect())->first();

                if (! $link || ! $link->variant) {
                    continue;
                }

                $variantId = $link->variant->id;

                if (! isset($stockChanges[$variantId])) {
                    $stockChanges[$variantId] = [
                        'variant' => $link->variant,
                        'sku' => $link->variant->sku,
                        'current_stock' => $link->variant->stock_default ?? 0,
                        'to_deduct' => 0,
                    ];
                }

                $stockChanges[$variantId]['to_deduct'] += $item->quantity;
                $processedOrders[$order->id] = $order;
            }
        }

        if (empty($stockChanges)) {
            return response()->json([
                'message' => 'No stock changes needed',
                'processed_orders' => 0,
            ]);
        }

        DB::beginTransaction();
        try {
            $results = [];

            foreach ($stockChanges as $variantId => $data) {
                $variant = $data['variant'];
                $oldStock = $data['current_stock'];
                $newStock = max(0, $oldStock - $data['to_deduct']);

                $variant->update(['stock_default' => $newStock]);

                Log::info('Stock recalculated from orders', [
                    'variant_id' => $variantId,
                    'sku' => $data['sku'],
                    'old_stock' => $oldStock,
                    'deducted' => $data['to_deduct'],
                    'new_stock' => $newStock,
                ]);

                $results[] = [
                    'sku' => $data['sku'],
                    'old_stock' => $oldStock,
                    'deducted' => $data['to_deduct'],
                    'new_stock' => $newStock,
                ];
            }

            // Mark orders as processed
            foreach ($processedOrders as $order) {
                $order->update(['stock_status' => 'sold']);
            }

            DB::commit();

            return response()->json([
                'message' => 'Stock recalculated successfully',
                'processed_orders' => count($processedOrders),
                'results' => $results,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Stock recalculation failed', ['error' => $e->getMessage()]);

            return response()->json([
                'error' => 'Recalculation failed: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all variants with their current stock
     * GET /api/stock/variants
     */
    public function variants(Request $request): JsonResponse
    {
        $search = $request->query('search');

        $query = ProductVariant::query()
            ->select(['id', 'sku', 'barcode', 'stock_default'])
            ->where('is_active', true)
            ->where('is_deleted', false);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('sku', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        $variants = $query->orderBy('sku')->limit(100)->get();

        return response()->json([
            'variants' => $variants,
        ]);
    }
}
