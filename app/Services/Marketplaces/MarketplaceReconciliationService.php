<?php

// file: app/Services/Marketplaces/MarketplaceReconciliationService.php

namespace App\Services\Marketplaces;

use App\Models\MarketplaceAccount;
use App\Models\MarketplacePayout;
use App\Models\MarketplacePayoutItem;
use App\Models\MarketplaceSyncLog;

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

        $account = MarketplaceAccount::find($payout->marketplace_account_id);

        foreach ($items as $item) {
            if ($item->operation_type === MarketplacePayoutItem::TYPE_SALE) {
                $summary['actual_sales'] += $item->amount;

                // Try to match with order
                if ($item->marketplace_order_id) {
                    $order = $this->findOrderById($account, $item->marketplace_order_id);
                    if ($order) {
                        $totalAmount = $order->total_amount ?? $order->total_price ?? 0;
                        $summary['expected_sales'] += $totalAmount;
                        $summary['matched_items']++;

                        // Check for discrepancy
                        $diff = abs($item->amount - $totalAmount);
                        if ($diff > 0.01) {
                            $discrepancies[] = [
                                'type' => 'amount_mismatch',
                                'order_id' => $order->id,
                                'external_order_id' => $order->external_order_id ?? $order->order_id ?? null,
                                'expected' => $totalAmount,
                                'actual' => $item->amount,
                                'difference' => $item->amount - $totalAmount,
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
        $account = MarketplaceAccount::find($payout->marketplace_account_id);
        if (! $account) {
            return [];
        }

        $payoutOrderIds = $payout->items()
            ->whereNotNull('marketplace_order_id')
            ->pluck('marketplace_order_id')
            ->toArray();

        $modelClass = $this->getOrderModelClass($account);
        if (! $modelClass) {
            return [];
        }

        // Определяем поля в зависимости от модели
        $statusField = match ($account->marketplace) {
            'ym' => 'status_normalized',
            default => 'status',
        };
        $deliveredStatus = match ($account->marketplace) {
            'wb' => 'completed',
            'ym' => 'delivered',
            default => 'delivered',
        };
        $dateField = match ($account->marketplace) {
            'wb' => 'ordered_at',
            'uzum' => 'ordered_at',
            'ozon' => 'created_at_ozon',
            'ym' => 'created_at_ym',
            default => 'created_at',
        };
        $amountField = match ($account->marketplace) {
            'wb', 'uzum' => 'total_amount',
            default => 'total_price',
        };
        $externalIdField = match ($account->marketplace) {
            'ozon', 'ym' => 'order_id',
            default => 'external_order_id',
        };

        $missingOrders = $modelClass::where('marketplace_account_id', $payout->marketplace_account_id)
            ->where($statusField, $deliveredStatus)
            ->whereNotNull($dateField)
            ->when($payout->period_from, function ($query) use ($payout, $dateField) {
                $query->where($dateField, '>=', $payout->period_from);
            })
            ->when($payout->period_to, function ($query) use ($payout, $dateField) {
                $query->where($dateField, '<=', $payout->period_to->endOfDay());
            })
            ->whereNotIn('id', $payoutOrderIds)
            ->get();

        return $missingOrders->map(function ($order) use ($amountField, $externalIdField, $dateField) {
            return [
                'order_id' => $order->id,
                'external_order_id' => $order->{$externalIdField},
                'total_amount' => $order->{$amountField},
                'ordered_at' => $order->{$dateField},
            ];
        })->toArray();
    }

    /**
     * Calculate expected payout based on orders
     */
    public function calculateExpectedPayout(int $accountId, string $periodFrom, string $periodTo): array
    {
        $account = MarketplaceAccount::find($accountId);
        if (! $account) {
            return ['orders_count' => 0, 'total_sales' => 0, 'orders' => []];
        }

        $modelClass = $this->getOrderModelClass($account);
        if (! $modelClass) {
            return ['orders_count' => 0, 'total_sales' => 0, 'orders' => []];
        }

        $statusField = match ($account->marketplace) {
            'ym' => 'status_normalized',
            default => 'status',
        };
        $deliveredStatus = match ($account->marketplace) {
            'wb' => 'completed',
            'ym' => 'delivered',
            default => 'delivered',
        };
        $dateField = match ($account->marketplace) {
            'wb' => 'ordered_at',
            'uzum' => 'ordered_at',
            'ozon' => 'created_at_ozon',
            'ym' => 'created_at_ym',
            default => 'created_at',
        };
        $amountField = match ($account->marketplace) {
            'wb', 'uzum' => 'total_amount',
            default => 'total_price',
        };
        $externalIdField = match ($account->marketplace) {
            'ozon', 'ym' => 'order_id',
            default => 'external_order_id',
        };

        $orders = $modelClass::where('marketplace_account_id', $accountId)
            ->where($statusField, $deliveredStatus)
            ->whereBetween($dateField, [$periodFrom, $periodTo])
            ->get();

        $totalSales = $orders->sum($amountField);

        return [
            'orders_count' => $orders->count(),
            'total_sales' => $totalSales,
            'orders' => $orders->pluck($amountField, $externalIdField)->toArray(),
        ];
    }

    /**
     * Найти заказ по ID в соответствующей таблице маркетплейса
     */
    protected function findOrderById(?MarketplaceAccount $account, int $orderId): ?object
    {
        if (! $account) {
            return null;
        }

        $modelClass = $this->getOrderModelClass($account);
        if (! $modelClass) {
            return null;
        }

        return $modelClass::find($orderId);
    }

    /**
     * Получить класс модели заказов по маркетплейсу
     */
    protected function getOrderModelClass(MarketplaceAccount $account): ?string
    {
        return match ($account->marketplace) {
            'wb' => \App\Models\WbOrder::class,
            'uzum' => \App\Models\UzumOrder::class,
            'ozon' => \App\Models\OzonOrder::class,
            'ym' => \App\Models\YandexMarketOrder::class,
            default => null,
        };
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
                : "Сверка выплаты #{$payout->id}: найдено ".count($discrepancies).' расхождений',
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
