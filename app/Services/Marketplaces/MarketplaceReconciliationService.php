<?php
// file: app/Services/Marketplaces/MarketplaceReconciliationService.php

namespace App\Services\Marketplaces;

use App\Models\MarketplacePayout;
use App\Models\MarketplacePayoutItem;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderItem;
use App\Models\MarketplaceSyncLog;
use Illuminate\Support\Facades\DB;

class MarketplaceReconciliationService
{
    /**
     * Reconcile a payout with expected values from orders
     *
     * @return array{success: bool, discrepancies: array, summary: array}
     */
    public function reconcilePayout(MarketplacePayout $payout): array
    {
        $discrepancies = [];
        $summary = [
            'total_items' => 0,
            'matched_items' => 0,
            'unmatched_items' => 0,
            'expected_sales' => 0,
            'actual_sales' => 0,
            'difference' => 0,
        ];

        $items = $payout->items()->get();
        $summary['total_items'] = $items->count();

        foreach ($items as $item) {
            if ($item->operation_type === MarketplacePayoutItem::TYPE_SALE) {
                $summary['actual_sales'] += $item->amount;

                // Try to match with order
                if ($item->marketplace_order_id) {
                    $order = MarketplaceOrder::find($item->marketplace_order_id);
                    if ($order) {
                        $summary['expected_sales'] += $order->total_amount;
                        $summary['matched_items']++;

                        // Check for discrepancy
                        $diff = abs($item->amount - $order->total_amount);
                        if ($diff > 0.01) {
                            $discrepancies[] = [
                                'type' => 'amount_mismatch',
                                'order_id' => $order->id,
                                'external_order_id' => $order->external_order_id,
                                'expected' => $order->total_amount,
                                'actual' => $item->amount,
                                'difference' => $item->amount - $order->total_amount,
                            ];
                        }
                    } else {
                        $summary['unmatched_items']++;
                        $discrepancies[] = [
                            'type' => 'order_not_found',
                            'payout_item_id' => $item->id,
                            'marketplace_order_id' => $item->marketplace_order_id,
                        ];
                    }
                } else {
                    $summary['unmatched_items']++;
                }
            }
        }

        $summary['difference'] = $summary['actual_sales'] - $summary['expected_sales'];

        // Log reconciliation result
        $this->logReconciliation($payout, $discrepancies, $summary);

        return [
            'success' => empty($discrepancies),
            'discrepancies' => $discrepancies,
            'summary' => $summary,
        ];
    }

    /**
     * Find orders that should be in payout but are missing
     */
    public function findMissingOrders(MarketplacePayout $payout): array
    {
        $payoutOrderIds = $payout->items()
            ->whereNotNull('marketplace_order_id')
            ->pluck('marketplace_order_id')
            ->toArray();

        $missingOrders = MarketplaceOrder::where('marketplace_account_id', $payout->marketplace_account_id)
            ->where('internal_status', MarketplaceOrder::INTERNAL_STATUS_DELIVERED)
            ->whereNotNull('ordered_at')
            ->when($payout->period_from, function ($query) use ($payout) {
                $query->where('ordered_at', '>=', $payout->period_from);
            })
            ->when($payout->period_to, function ($query) use ($payout) {
                $query->where('ordered_at', '<=', $payout->period_to->endOfDay());
            })
            ->whereNotIn('id', $payoutOrderIds)
            ->get();

        return $missingOrders->map(function ($order) {
            return [
                'order_id' => $order->id,
                'external_order_id' => $order->external_order_id,
                'total_amount' => $order->total_amount,
                'ordered_at' => $order->ordered_at,
            ];
        })->toArray();
    }

    /**
     * Calculate expected payout based on orders
     */
    public function calculateExpectedPayout(int $accountId, string $periodFrom, string $periodTo): array
    {
        $orders = MarketplaceOrder::where('marketplace_account_id', $accountId)
            ->where('internal_status', MarketplaceOrder::INTERNAL_STATUS_DELIVERED)
            ->whereBetween('ordered_at', [$periodFrom, $periodTo])
            ->get();

        $totalSales = $orders->sum('total_amount');

        return [
            'orders_count' => $orders->count(),
            'total_sales' => $totalSales,
            'orders' => $orders->pluck('total_amount', 'external_order_id')->toArray(),
        ];
    }

    /**
     * Log reconciliation result to sync logs
     */
    protected function logReconciliation(MarketplacePayout $payout, array $discrepancies, array $summary): void
    {
        MarketplaceSyncLog::create([
            'marketplace_account_id' => $payout->marketplace_account_id,
            'type' => 'reconciliation',
            'status' => empty($discrepancies) ? 'success' : 'warning',
            'message' => empty($discrepancies)
                ? "Сверка выплаты #{$payout->id} успешна"
                : "Сверка выплаты #{$payout->id}: найдено " . count($discrepancies) . " расхождений",
            'started_at' => now(),
            'finished_at' => now(),
            'request_payload' => ['payout_id' => $payout->id],
            'response_payload' => [
                'summary' => $summary,
                'discrepancies_count' => count($discrepancies),
            ],
        ]);
    }
}
