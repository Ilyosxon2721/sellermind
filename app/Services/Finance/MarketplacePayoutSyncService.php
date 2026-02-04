<?php

namespace App\Services\Finance;

use App\Models\Finance\CashAccount;
use App\Models\Finance\CashTransaction;
use App\Models\Finance\MarketplacePayout;
use App\Models\MarketplaceAccount;
use App\Models\UzumFinanceOrder;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Сервис для синхронизации выплат маркетплейсов в CashAccount
 *
 * Агрегирует заказы по датам и создаёт записи о выплатах
 */
class MarketplacePayoutSyncService
{
    /**
     * Синхронизировать все маркетплейсы для компании
     */
    public function syncAll(int $companyId, ?string $from = null, ?string $to = null): array
    {
        $results = [
            'uzum' => $this->syncUzum($companyId, $from, $to),
        ];

        $total = [
            'payouts_created' => 0,
            'payouts_updated' => 0,
            'payouts_skipped' => 0,
            'transactions_created' => 0,
            'total_amount' => 0,
            'errors' => 0,
            'by_marketplace' => [],
        ];

        foreach ($results as $mp => $result) {
            $total['payouts_created'] += $result['payouts_created'];
            $total['payouts_updated'] += $result['payouts_updated'];
            $total['payouts_skipped'] += $result['payouts_skipped'];
            $total['transactions_created'] += $result['transactions_created'];
            $total['total_amount'] += $result['total_amount'];
            $total['errors'] += $result['errors'];
            $total['by_marketplace'][$mp] = $result['total_amount'];
        }

        $results['total'] = $total;

        return $results;
    }

    /**
     * Синхронизировать выплаты Uzum
     */
    public function syncUzum(int $companyId, ?string $from = null, ?string $to = null): array
    {
        $result = $this->emptyResult();

        $accounts = MarketplaceAccount::where('company_id', $companyId)
            ->where('marketplace', 'uzum')
            ->where('is_active', true)
            ->get();

        if ($accounts->isEmpty()) {
            Log::info('MarketplacePayoutSyncService: No active Uzum accounts found', [
                'company_id' => $companyId,
            ]);

            return $result;
        }

        foreach ($accounts as $account) {
            $accountResult = $this->syncUzumAccount($account, $from, $to);
            $this->mergeResults($result, $accountResult);
        }

        return $result;
    }

    /**
     * Синхронизировать выплаты для конкретного аккаунта Uzum
     */
    public function syncUzumAccount(MarketplaceAccount $account, ?string $from = null, ?string $to = null): array
    {
        $result = $this->emptyResult();

        // Получаем или создаём кассу для этого маркетплейса
        $cashAccount = CashAccount::getOrCreateForMarketplace($account->company_id, $account);

        // Агрегируем заказы TO_WITHDRAW по датам
        $query = UzumFinanceOrder::where('marketplace_account_id', $account->id)
            ->where('status', 'TO_WITHDRAW');

        if ($from) {
            $query->whereDate('date_issued', '>=', $from);
        }
        if ($to) {
            $query->whereDate('date_issued', '<=', $to);
        }

        // Группируем по дате выплаты
        $dailyPayouts = $query
            ->select(
                DB::raw('DATE(date_issued) as payout_date'),
                DB::raw('SUM(sell_price * amount) as gross_amount'),
                DB::raw('SUM(commission) as commission'),
                DB::raw('SUM(logistic_delivery_fee) as logistics'),
                DB::raw('SUM(seller_profit) as net_amount'),
                DB::raw('COUNT(*) as orders_count')
            )
            ->whereNotNull('date_issued')
            ->groupBy(DB::raw('DATE(date_issued)'))
            ->orderBy('payout_date')
            ->get();

        Log::info('MarketplacePayoutSyncService: Processing Uzum payouts', [
            'account_id' => $account->id,
            'account_name' => $account->name,
            'periods_found' => $dailyPayouts->count(),
            'from' => $from,
            'to' => $to,
        ]);

        foreach ($dailyPayouts as $daily) {
            if (! $daily->payout_date || $daily->net_amount <= 0) {
                continue;
            }

            try {
                $payoutResult = $this->createOrUpdatePayout(
                    $account,
                    $cashAccount,
                    Carbon::parse($daily->payout_date),
                    (float) $daily->gross_amount,
                    (float) $daily->commission,
                    (float) $daily->logistics,
                    (float) $daily->net_amount,
                    (int) $daily->orders_count
                );

                $result[$payoutResult['status']]++;
                if ($payoutResult['status'] !== 'payouts_skipped') {
                    $result['total_amount'] += $daily->net_amount;
                }
                if ($payoutResult['transaction_created']) {
                    $result['transactions_created']++;
                }
            } catch (\Exception $e) {
                $result['errors']++;
                Log::error('MarketplacePayoutSyncService: Failed to create payout', [
                    'account_id' => $account->id,
                    'payout_date' => $daily->payout_date,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $result;
    }

    /**
     * Создать или обновить запись о выплате
     */
    protected function createOrUpdatePayout(
        MarketplaceAccount $account,
        CashAccount $cashAccount,
        Carbon $payoutDate,
        float $grossAmount,
        float $commission,
        float $logistics,
        float $netAmount,
        int $ordersCount
    ): array {
        $payoutId = "uzum-{$account->id}-{$payoutDate->format('Y-m-d')}";

        $existing = MarketplacePayout::where('marketplace_account_id', $account->id)
            ->where('payout_id', $payoutId)
            ->first();

        $transactionCreated = false;

        if ($existing) {
            // Проверяем изменилась ли сумма
            if (abs($existing->net_amount - $netAmount) < 0.01) {
                return ['status' => 'payouts_skipped', 'transaction_created' => false];
            }

            // Обновляем существующую запись
            $existing->update([
                'gross_amount' => $grossAmount,
                'commission' => $commission,
                'logistics' => $logistics,
                'net_amount' => $netAmount,
                'raw_data' => [
                    'orders_count' => $ordersCount,
                    'synced_at' => now()->toIso8601String(),
                ],
            ]);

            return ['status' => 'payouts_updated', 'transaction_created' => false];
        }

        // Создаём новую запись о выплате
        DB::beginTransaction();
        try {
            $payout = MarketplacePayout::create([
                'company_id' => $account->company_id,
                'marketplace_account_id' => $account->id,
                'marketplace' => 'uzum',
                'payout_id' => $payoutId,
                'payout_date' => $payoutDate,
                'period_from' => $payoutDate,
                'period_to' => $payoutDate,
                'gross_amount' => $grossAmount,
                'commission' => $commission,
                'logistics' => $logistics,
                'storage' => 0,
                'advertising' => 0,
                'penalties' => 0,
                'returns' => 0,
                'other_deductions' => 0,
                'net_amount' => $netAmount,
                'currency_code' => 'UZS',
                'exchange_rate' => 1,
                'amount_base' => $netAmount,
                'status' => MarketplacePayout::STATUS_RECEIVED,
                'raw_data' => [
                    'orders_count' => $ordersCount,
                    'synced_at' => now()->toIso8601String(),
                ],
            ]);

            // Создаём транзакцию в кассе
            $transaction = CashTransaction::createForMarketplacePayout(
                $cashAccount,
                $netAmount,
                $payoutId,
                $payoutDate,
                $payout->id,
                MarketplacePayout::class
            );

            // Связываем выплату с транзакцией
            $payout->update(['cash_transaction_id' => $transaction->id]);
            $transactionCreated = true;

            DB::commit();

            Log::info('MarketplacePayoutSyncService: Payout created', [
                'payout_id' => $payoutId,
                'account_id' => $account->id,
                'net_amount' => $netAmount,
                'transaction_id' => $transaction->id,
            ]);

            return ['status' => 'payouts_created', 'transaction_created' => $transactionCreated];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Получить статистику выплат для компании
     */
    public function getStats(int $companyId): array
    {
        $payouts = MarketplacePayout::byCompany($companyId)
            ->select(
                'marketplace',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(net_amount) as total_amount'),
                DB::raw('MIN(payout_date) as first_payout'),
                DB::raw('MAX(payout_date) as last_payout')
            )
            ->groupBy('marketplace')
            ->get();

        $stats = [
            'total_payouts' => 0,
            'total_amount' => 0,
            'by_marketplace' => [],
        ];

        foreach ($payouts as $mp) {
            $stats['total_payouts'] += $mp->count;
            $stats['total_amount'] += $mp->total_amount;
            $stats['by_marketplace'][$mp->marketplace] = [
                'count' => $mp->count,
                'total_amount' => $mp->total_amount,
                'first_payout' => $mp->first_payout,
                'last_payout' => $mp->last_payout,
            ];
        }

        return $stats;
    }

    /**
     * Получить доступный баланс для вывода (заказы TO_WITHDRAW без созданных выплат)
     */
    public function getPendingWithdrawals(int $companyId): array
    {
        $accounts = MarketplaceAccount::where('company_id', $companyId)
            ->where('is_active', true)
            ->get();

        $pending = [];

        foreach ($accounts as $account) {
            $query = UzumFinanceOrder::where('marketplace_account_id', $account->id)
                ->where('status', 'TO_WITHDRAW')
                ->whereNotNull('date_issued');

            // Находим последнюю синхронизированную выплату
            $lastPayout = MarketplacePayout::where('marketplace_account_id', $account->id)
                ->orderByDesc('payout_date')
                ->first();

            if ($lastPayout) {
                $query->whereDate('date_issued', '>', $lastPayout->payout_date);
            }

            $pendingAmount = $query->sum('seller_profit');
            $pendingCount = $query->count();

            if ($pendingAmount > 0) {
                $pending[$account->marketplace][$account->id] = [
                    'account_name' => $account->name,
                    'amount' => $pendingAmount,
                    'orders_count' => $pendingCount,
                ];
            }
        }

        return $pending;
    }

    protected function emptyResult(): array
    {
        return [
            'payouts_created' => 0,
            'payouts_updated' => 0,
            'payouts_skipped' => 0,
            'transactions_created' => 0,
            'total_amount' => 0,
            'errors' => 0,
        ];
    }

    protected function mergeResults(array &$target, array $source): void
    {
        $target['payouts_created'] += $source['payouts_created'];
        $target['payouts_updated'] += $source['payouts_updated'];
        $target['payouts_skipped'] += $source['payouts_skipped'];
        $target['transactions_created'] += $source['transactions_created'];
        $target['total_amount'] += $source['total_amount'];
        $target['errors'] += $source['errors'];
    }
}
