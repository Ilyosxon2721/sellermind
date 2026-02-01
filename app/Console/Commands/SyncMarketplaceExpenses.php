<?php

namespace App\Console\Commands;

use App\Models\MarketplaceAccount;
use App\Models\MarketplaceExpenseCache;
use App\Services\Marketplaces\Wildberries\WildberriesFinanceService;
use App\Services\Marketplaces\Wildberries\WildberriesHttpClient;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncMarketplaceExpenses extends Command
{
    protected $signature = 'marketplace:sync-expenses
                            {--marketplace=all : Marketplace to sync (wb, ozon, uzum, yandex, all)}
                            {--account= : Specific account ID to sync}
                            {--period=30days : Period type (7days, 30days, 90days)}
                            {--force : Force sync even if cache is fresh}';

    protected $description = 'Sync marketplace expenses to cache for faster loading';

    protected array $periodDays = [
        '7days' => 7,
        '30days' => 30,
        '90days' => 90,
    ];

    public function handle(): int
    {
        $marketplace = $this->option('marketplace');
        $accountId = $this->option('account');
        $periodType = $this->option('period');
        $force = $this->option('force');

        if (! isset($this->periodDays[$periodType])) {
            $this->error("Invalid period type: {$periodType}. Use: 7days, 30days, 90days");

            return self::FAILURE;
        }

        $days = $this->periodDays[$periodType];
        $dateTo = now();
        $dateFrom = now()->subDays($days);

        $this->info("Syncing {$marketplace} expenses for period: {$periodType} ({$dateFrom->format('Y-m-d')} to {$dateTo->format('Y-m-d')})");

        $query = MarketplaceAccount::where('is_active', true);

        if ($accountId) {
            $query->where('id', $accountId);
        }

        if ($marketplace !== 'all') {
            $query->where('marketplace', $marketplace);
        }

        $accounts = $query->get();

        if ($accounts->isEmpty()) {
            $this->warn('No active marketplace accounts found');

            return self::SUCCESS;
        }

        $synced = 0;
        $errors = 0;

        foreach ($accounts as $account) {
            $this->line("Processing {$account->marketplace} account #{$account->id} ({$account->name})...");

            try {
                $result = $this->syncAccount($account, $periodType, $dateFrom, $dateTo, $force);

                if ($result) {
                    $synced++;
                    $this->info("  ✓ Synced: total={$result['total']}, currency={$result['currency']}");
                } else {
                    $this->line('  - Skipped (cache is fresh)');
                }
            } catch (\Exception $e) {
                $errors++;
                $this->error("  ✗ Error: {$e->getMessage()}");
                Log::error('Marketplace expense sync failed', [
                    'account_id' => $account->id,
                    'marketplace' => $account->marketplace,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->newLine();
        $this->info("Sync completed: {$synced} synced, {$errors} errors");

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }

    protected function syncAccount(
        MarketplaceAccount $account,
        string $periodType,
        Carbon $dateFrom,
        Carbon $dateTo,
        bool $force
    ): ?array {
        // Get or create cache record
        $cache = MarketplaceExpenseCache::firstOrCreate([
            'marketplace_account_id' => $account->id,
            'period_type' => $periodType,
        ], [
            'company_id' => $account->company_id,
            'marketplace' => $account->marketplace,
            'period_from' => $dateFrom,
            'period_to' => $dateTo,
            'sync_status' => 'pending',
        ]);

        // Skip if cache is fresh (unless forced)
        if (! $force && ! $cache->isStale(4)) {
            return null;
        }

        $cache->markSyncing();

        try {
            $expenses = match ($account->marketplace) {
                'wb' => $this->syncWildberries($account, $dateFrom, $dateTo),
                'ozon' => $this->syncOzon($account, $dateFrom, $dateTo),
                'uzum' => $this->syncUzum($account, $dateFrom, $dateTo),
                'yandex', 'ym' => $this->syncYandex($account, $dateFrom, $dateTo),
                default => throw new \Exception("Unsupported marketplace: {$account->marketplace}"),
            };

            $cache->markSuccess([
                'period_from' => $dateFrom,
                'period_to' => $dateTo,
                'commission' => $expenses['commission'] ?? 0,
                'logistics' => $expenses['logistics'] ?? 0,
                'storage' => $expenses['storage'] ?? 0,
                'advertising' => $expenses['advertising'] ?? 0,
                'penalties' => $expenses['penalties'] ?? 0,
                'returns' => $expenses['returns'] ?? 0,
                'other' => $expenses['other'] ?? 0,
                'total' => $expenses['total'] ?? 0,
                'gross_revenue' => $expenses['gross_revenue'] ?? 0,
                'orders_count' => $expenses['orders_count'] ?? 0,
                'returns_count' => $expenses['returns_count'] ?? 0,
                'currency' => $expenses['currency'] ?? 'UZS',
                'total_uzs' => $expenses['total_uzs'] ?? $expenses['total'] ?? 0,
            ]);

            return $expenses;
        } catch (\Exception $e) {
            $cache->markError($e->getMessage());
            throw $e;
        }
    }

    protected function syncWildberries(MarketplaceAccount $account, Carbon $dateFrom, Carbon $dateTo): array
    {
        $httpClient = new WildberriesHttpClient($account);
        $financeService = new WildberriesFinanceService($httpClient);

        $expenses = $financeService->getExpensesSummary($account, $dateFrom, $dateTo);

        // If currency is already UZS, no conversion needed
        $isUzs = ($expenses['currency'] ?? 'RUB') === 'UZS';
        $expenses['total_uzs'] = $isUzs ? $expenses['total'] : $expenses['total'] * 140; // Fallback rate

        return $expenses;
    }

    protected function syncOzon(MarketplaceAccount $account, Carbon $dateFrom, Carbon $dateTo): array
    {
        // Синхронизация расходов Ozon ещё не реализована — API не подключён
        Log::info('SyncMarketplaceExpenses: Ozon expense sync not implemented', [
            'account_id' => $account->id,
            'date_from' => $dateFrom->toDateString(),
            'date_to' => $dateTo->toDateString(),
        ]);

        return [
            'commission' => 0,
            'logistics' => 0,
            'storage' => 0,
            'advertising' => 0,
            'penalties' => 0,
            'returns' => 0,
            'other' => 0,
            'total' => 0,
            'gross_revenue' => 0,
            'orders_count' => 0,
            'returns_count' => 0,
            'currency' => 'RUB',
            'total_uzs' => 0,
            '_not_implemented' => true,
            '_message' => 'Синхронизация расходов Ozon ещё не реализована',
        ];
    }

    protected function syncUzum(MarketplaceAccount $account, Carbon $dateFrom, Carbon $dateTo): array
    {
        // Uzum expenses table structure:
        // - source_normalized: category (advertising, commission, delivery, storage, penalty, return, other)
        // - payment_price: amount in UZS (tiyin - 1/100 of sum)
        // - amount: quantity (usually 1)
        // - date_service: service date
        $expenses = \App\Models\UzumExpense::where('marketplace_account_id', $account->id)
            ->whereDate('date_service', '>=', $dateFrom->format('Y-m-d'))
            ->whereDate('date_service', '<=', $dateTo->format('Y-m-d'))
            ->selectRaw("
                SUM(CASE WHEN source_normalized = 'commission' THEN ABS(payment_price) ELSE 0 END) as commission,
                SUM(CASE WHEN source_normalized = 'delivery' THEN ABS(payment_price) ELSE 0 END) as logistics,
                SUM(CASE WHEN source_normalized = 'storage' THEN ABS(payment_price) ELSE 0 END) as storage,
                SUM(CASE WHEN source_normalized = 'advertising' THEN ABS(payment_price) ELSE 0 END) as advertising,
                SUM(CASE WHEN source_normalized = 'penalty' THEN ABS(payment_price) ELSE 0 END) as penalties,
                SUM(CASE WHEN source_normalized = 'return' THEN ABS(payment_price) ELSE 0 END) as returns,
                SUM(CASE WHEN source_normalized NOT IN ('commission', 'delivery', 'storage', 'advertising', 'penalty', 'return') THEN ABS(payment_price) ELSE 0 END) as other,
                SUM(ABS(payment_price)) as total
            ")
            ->first();

        // payment_price is in tiyin (1/100 of sum), convert to sum
        $total = (float) ($expenses->total ?? 0) / 100;

        return [
            'commission' => (float) ($expenses->commission ?? 0) / 100,
            'logistics' => (float) ($expenses->logistics ?? 0) / 100,
            'storage' => (float) ($expenses->storage ?? 0) / 100,
            'advertising' => (float) ($expenses->advertising ?? 0) / 100,
            'penalties' => (float) ($expenses->penalties ?? 0) / 100,
            'returns' => (float) ($expenses->returns ?? 0) / 100,
            'other' => (float) ($expenses->other ?? 0) / 100,
            'total' => $total,
            'gross_revenue' => 0,
            'orders_count' => 0,
            'returns_count' => 0,
            'currency' => 'UZS',
            'total_uzs' => $total,
        ];
    }

    protected function syncYandex(MarketplaceAccount $account, Carbon $dateFrom, Carbon $dateTo): array
    {
        // Синхронизация расходов Yandex Market ещё не реализована — API не подключён
        Log::info('SyncMarketplaceExpenses: Yandex expense sync not implemented', [
            'account_id' => $account->id,
            'date_from' => $dateFrom->toDateString(),
            'date_to' => $dateTo->toDateString(),
        ]);

        return [
            'commission' => 0,
            'logistics' => 0,
            'storage' => 0,
            'advertising' => 0,
            'penalties' => 0,
            'returns' => 0,
            'other' => 0,
            'total' => 0,
            'gross_revenue' => 0,
            'orders_count' => 0,
            'returns_count' => 0,
            'currency' => 'RUB',
            'total_uzs' => 0,
            '_not_implemented' => true,
            '_message' => 'Синхронизация расходов Yandex Market ещё не реализована',
        ];
    }
}
