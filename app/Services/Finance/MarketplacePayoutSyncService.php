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
            'wb' => $this->syncWildberries($companyId, $from, $to),
            'ozon' => $this->syncOzon($companyId, $from, $to),
            'ym' => $this->syncYandexMarket($companyId, $from, $to),
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
     * Синхронизировать выплаты Wildberries
     * Агрегирует из wildberries_orders по неделям (WB платит еженедельно)
     */
    public function syncWildberries(int $companyId, ?string $from = null, ?string $to = null): array
    {
        $result = $this->emptyResult();

        $accounts = MarketplaceAccount::where('company_id', $companyId)
            ->where('marketplace', 'wb')
            ->where('is_active', true)
            ->get();

        foreach ($accounts as $account) {
            $cashAccount = CashAccount::getOrCreateForMarketplace($account->company_id, $account);

            $query = DB::table('wildberries_orders')
                ->where('marketplace_account_id', $account->id)
                ->where('is_cancel', false)
                ->where('is_return', false)
                ->whereNotNull('order_date');

            if ($from) {
                $query->whereDate('order_date', '>=', $from);
            }
            if ($to) {
                $query->whereDate('order_date', '<=', $to);
            }

            // Группируем по началу недели (понедельник)
            $weeklyPayouts = $query->select(
                DB::raw("DATE(DATE_SUB(order_date, INTERVAL WEEKDAY(order_date) DAY)) as week_start"),
                DB::raw('SUM(for_pay) as net_amount'),
                DB::raw('SUM(total_price) as gross_amount'),
                DB::raw('COUNT(*) as orders_count')
            )
                ->groupBy('week_start')
                ->orderBy('week_start')
                ->get();

            foreach ($weeklyPayouts as $week) {
                if (! $week->week_start || $week->net_amount <= 0) {
                    continue;
                }

                try {
                    $payoutDate = Carbon::parse($week->week_start);
                    $payoutId = "wb-{$account->id}-{$payoutDate->format('Y-m-d')}";

                    $payoutResult = $this->createOrUpdatePayoutForMarketplace(
                        $account,
                        $cashAccount,
                        'wb',
                        $payoutId,
                        $payoutDate,
                        (float) $week->gross_amount,
                        0, // WB комиссия отдельно не берётся из этой таблицы
                        0, // логистика
                        (float) $week->net_amount,
                        (int) $week->orders_count
                    );

                    $result[$payoutResult['status']]++;
                    if ($payoutResult['status'] !== 'payouts_skipped') {
                        $result['total_amount'] += $week->net_amount;
                    }
                    if ($payoutResult['transaction_created']) {
                        $result['transactions_created']++;
                    }
                } catch (\Exception $e) {
                    $result['errors']++;
                    Log::error('MarketplacePayoutSyncService WB: Failed to create payout', [
                        'account_id' => $account->id,
                        'week_start' => $week->week_start,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return $result;
    }

    /**
     * Синхронизировать выплаты Ozon
     * Агрегирует из ozon_orders по месяцам (Ozon платит ежемесячно)
     */
    public function syncOzon(int $companyId, ?string $from = null, ?string $to = null): array
    {
        $result = $this->emptyResult();

        $accounts = MarketplaceAccount::where('company_id', $companyId)
            ->where('marketplace', 'ozon')
            ->where('is_active', true)
            ->get();

        foreach ($accounts as $account) {
            $cashAccount = CashAccount::getOrCreateForMarketplace($account->company_id, $account);

            $query = DB::table('ozon_orders')
                ->where('marketplace_account_id', $account->id)
                ->whereNotIn('status', ['cancelled', 'canceled'])
                ->whereNotNull('created_at_ozon');

            if ($from) {
                $query->whereDate('created_at_ozon', '>=', $from);
            }
            if ($to) {
                $query->whereDate('created_at_ozon', '<=', $to);
            }

            // Группируем по месяцу
            $monthlyPayouts = $query->select(
                DB::raw("DATE_FORMAT(created_at_ozon, '%Y-%m-01') as period_start"),
                DB::raw('SUM(total_price) as gross_amount'),
                DB::raw('COUNT(*) as orders_count')
            )
                ->groupBy('period_start')
                ->orderBy('period_start')
                ->get();

            foreach ($monthlyPayouts as $month) {
                if (! $month->period_start || $month->gross_amount <= 0) {
                    continue;
                }

                try {
                    $payoutDate = Carbon::parse($month->period_start);
                    $payoutId = "ozon-{$account->id}-{$payoutDate->format('Y-m')}";

                    // Ozon берёт ~7% комиссии (приблизительно)
                    $commission = round($month->gross_amount * 0.07, 2);
                    $netAmount = round($month->gross_amount - $commission, 2);

                    $payoutResult = $this->createOrUpdatePayoutForMarketplace(
                        $account,
                        $cashAccount,
                        'ozon',
                        $payoutId,
                        $payoutDate,
                        (float) $month->gross_amount,
                        $commission,
                        0,
                        $netAmount,
                        (int) $month->orders_count
                    );

                    $result[$payoutResult['status']]++;
                    if ($payoutResult['status'] !== 'payouts_skipped') {
                        $result['total_amount'] += $netAmount;
                    }
                    if ($payoutResult['transaction_created']) {
                        $result['transactions_created']++;
                    }
                } catch (\Exception $e) {
                    $result['errors']++;
                    Log::error('MarketplacePayoutSyncService Ozon: Failed to create payout', [
                        'account_id' => $account->id,
                        'period' => $month->period_start,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return $result;
    }

    /**
     * Синхронизировать выплаты Yandex Market
     * Агрегирует из yandex_market_orders по месяцам
     */
    public function syncYandexMarket(int $companyId, ?string $from = null, ?string $to = null): array
    {
        $result = $this->emptyResult();

        $accounts = MarketplaceAccount::where('company_id', $companyId)
            ->where('marketplace', 'ym')
            ->where('is_active', true)
            ->get();

        foreach ($accounts as $account) {
            $cashAccount = CashAccount::getOrCreateForMarketplace($account->company_id, $account);

            $query = DB::table('yandex_market_orders')
                ->where('marketplace_account_id', $account->id)
                ->whereNotIn('status_normalized', ['cancelled', 'canceled', 'CANCELLED'])
                ->whereNotNull('created_at_ym');

            if ($from) {
                $query->whereDate('created_at_ym', '>=', $from);
            }
            if ($to) {
                $query->whereDate('created_at_ym', '<=', $to);
            }

            // Группируем по месяцу
            $monthlyPayouts = $query->select(
                DB::raw("DATE_FORMAT(created_at_ym, '%Y-%m-01') as period_start"),
                DB::raw('SUM(total_price) as gross_amount'),
                DB::raw('COUNT(*) as orders_count')
            )
                ->groupBy('period_start')
                ->orderBy('period_start')
                ->get();

            foreach ($monthlyPayouts as $month) {
                if (! $month->period_start || $month->gross_amount <= 0) {
                    continue;
                }

                try {
                    $payoutDate = Carbon::parse($month->period_start);
                    $payoutId = "ym-{$account->id}-{$payoutDate->format('Y-m')}";

                    // YM берёт ~10% комиссии (приблизительно)
                    $commission = round($month->gross_amount * 0.10, 2);
                    $netAmount = round($month->gross_amount - $commission, 2);

                    $payoutResult = $this->createOrUpdatePayoutForMarketplace(
                        $account,
                        $cashAccount,
                        'ym',
                        $payoutId,
                        $payoutDate,
                        (float) $month->gross_amount,
                        $commission,
                        0,
                        $netAmount,
                        (int) $month->orders_count
                    );

                    $result[$payoutResult['status']]++;
                    if ($payoutResult['status'] !== 'payouts_skipped') {
                        $result['total_amount'] += $netAmount;
                    }
                    if ($payoutResult['transaction_created']) {
                        $result['transactions_created']++;
                    }
                } catch (\Exception $e) {
                    $result['errors']++;
                    Log::error('MarketplacePayoutSyncService YM: Failed to create payout', [
                        'account_id' => $account->id,
                        'period' => $month->period_start,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return $result;
    }

    /**
     * Создать или обновить запись о выплате (универсальный метод для всех маркетплейсов)
     */
    protected function createOrUpdatePayoutForMarketplace(
        MarketplaceAccount $account,
        CashAccount $cashAccount,
        string $marketplace,
        string $payoutId,
        Carbon $payoutDate,
        float $grossAmount,
        float $commission,
        float $logistics,
        float $netAmount,
        int $ordersCount
    ): array {
        $existing = MarketplacePayout::where('marketplace_account_id', $account->id)
            ->where('payout_id', $payoutId)
            ->first();

        $transactionCreated = false;

        if ($existing) {
            if (abs($existing->net_amount - $netAmount) < 0.01) {
                return ['status' => 'payouts_skipped', 'transaction_created' => false];
            }

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

        DB::beginTransaction();
        try {
            $payout = MarketplacePayout::create([
                'company_id' => $account->company_id,
                'marketplace_account_id' => $account->id,
                'marketplace' => $marketplace,
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

            $transaction = CashTransaction::createForMarketplacePayout(
                $cashAccount,
                $netAmount,
                $payoutId,
                $payoutDate,
                $payout->id,
                MarketplacePayout::class
            );

            $payout->update(['cash_transaction_id' => $transaction->id]);
            $transactionCreated = true;

            DB::commit();

            Log::info('MarketplacePayoutSyncService: Payout created', [
                'marketplace' => $marketplace,
                'payout_id' => $payoutId,
                'account_id' => $account->id,
                'net_amount' => $netAmount,
            ]);

            return ['status' => 'payouts_created', 'transaction_created' => $transactionCreated];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
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
        return $this->createOrUpdatePayoutForMarketplace(
            $account,
            $cashAccount,
            'uzum',
            "uzum-{$account->id}-{$payoutDate->format('Y-m-d')}",
            $payoutDate,
            $grossAmount,
            $commission,
            $logistics,
            $netAmount,
            $ordersCount
        );
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
