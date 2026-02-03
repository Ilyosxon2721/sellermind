<?php

namespace App\Console\Commands;

use App\Models\MarketplaceAccount;
use App\Models\UzumExpense;
use App\Services\Marketplaces\UzumClient;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UzumSyncExpenses extends Command
{
    protected $signature = 'uzum:sync-expenses
                            {--account= : Specific account ID to sync}
                            {--days=365 : Sync expenses for last N days (0 = all time)}';

    protected $description = 'Sync Uzum finance expenses (marketing, logistics, storage, penalties, commissions)';

    public function handle(UzumClient $client): int
    {
        $accountId = $this->option('account');
        $days = (int) $this->option('days');

        $dateFrom = null;
        $dateTo = now()->endOfDay();

        if ($days > 0) {
            $dateFrom = now()->subDays($days)->startOfDay();
            $this->info("Syncing expenses from last {$days} days (since {$dateFrom->format('Y-m-d')})");
        } else {
            $this->info('Full sync mode - fetching all expenses');
        }

        $query = MarketplaceAccount::where('marketplace', 'uzum')
            ->where('is_active', true);

        if ($accountId) {
            $query->where('id', $accountId);
        }

        $accounts = $query->get();

        if ($accounts->isEmpty()) {
            $this->warn('No active Uzum accounts found.');

            return Command::SUCCESS;
        }

        $this->info("Syncing expenses for {$accounts->count()} Uzum account(s)...");

        $totalCreated = 0;
        $totalUpdated = 0;
        $totalErrors = 0;

        foreach ($accounts as $account) {
            $this->line('');
            $this->info("Processing account: {$account->name} (ID: {$account->id})");

            try {
                $result = $this->syncAccountExpenses($client, $account, $dateFrom, $dateTo);

                $totalCreated += $result['created'];
                $totalUpdated += $result['updated'];
                $totalErrors += $result['errors'];

                $this->info("  Created: {$result['created']}, Updated: {$result['updated']}, Errors: {$result['errors']}");

                // Show summary by category
                if ($result['summary']) {
                    $this->line('');
                    $this->info('  Summary by category:');
                    foreach ($result['summary'] as $category => $amount) {
                        if ($category !== 'currency' && $amount > 0) {
                            $formatted = number_format($amount, 0, ',', ' ');
                            $this->line("    {$category}: {$formatted} UZS");
                        }
                    }
                }

            } catch (\Throwable $e) {
                $this->error("  Error: {$e->getMessage()}");
                Log::error('UzumSyncExpenses account failed', [
                    'account_id' => $account->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                $totalErrors++;
            }
        }

        $this->line('');
        $this->info("Sync completed. Total - Created: {$totalCreated}, Updated: {$totalUpdated}, Errors: {$totalErrors}");

        return Command::SUCCESS;
    }

    protected function syncAccountExpenses(
        UzumClient $client,
        MarketplaceAccount $account,
        ?Carbon $dateFrom,
        Carbon $dateTo
    ): array {
        $created = 0;
        $updated = 0;
        $errors = 0;
        $totalProcessed = 0;

        // Get list of shops
        $shops = $client->fetchShops($account);
        $shopIds = array_column($shops, 'id');

        $this->line('  Found '.count($shopIds).' shops');

        // Fetch all expenses (API date filters are unreliable, filter locally)
        $this->line('  Fetching all expenses...');

        $allExpenses = $client->fetchAllFinanceExpenses($account, $shopIds, null, null);

        $this->line('  Found '.count($allExpenses).' total expense records');

        // Convert dates to milliseconds for filtering
        $dateFromMs = $dateFrom ? $dateFrom->getTimestampMs() : null;
        $dateToMs = $dateTo->getTimestampMs();

        $summary = [
            'advertising' => 0,
            'logistics' => 0,
            'storage' => 0,
            'penalties' => 0,
            'commission' => 0,
            'other' => 0,
            'total' => 0,
        ];

        foreach ($allExpenses as $expense) {
            try {
                // Filter by dateService locally
                $dateService = $expense['dateService'] ?? $expense['dateCreated'] ?? 0;

                if ($dateFromMs && $dateService < $dateFromMs) {
                    continue;
                }
                if ($dateService > $dateToMs) {
                    continue;
                }

                $uzumId = $expense['id'] ?? null;
                if (! $uzumId) {
                    $errors++;

                    continue;
                }

                $source = $expense['source'] ?? 'Unknown';
                $name = $expense['name'] ?? '';
                $sourceNormalized = UzumExpense::normalizeSource($source, $name);

                $amount = abs((int) ($expense['amount'] ?? 0));

                $data = [
                    'marketplace_account_id' => $account->id,
                    'uzum_id' => $uzumId,
                    'shop_id' => $expense['shopId'] ?? 0,
                    'name' => $name,
                    'source' => $source,
                    'source_normalized' => $sourceNormalized,
                    'payment_price' => abs((int) ($expense['paymentPrice'] ?? 0)),
                    'amount' => $amount,
                    'date_created' => $this->parseTimestamp($expense['dateCreated'] ?? null),
                    'date_service' => $this->parseTimestamp($expense['dateService'] ?? null),
                    'status' => $expense['status'] ?? null,
                    'raw_data' => $expense,
                ];

                $record = UzumExpense::updateOrCreate(
                    [
                        'marketplace_account_id' => $account->id,
                        'uzum_id' => $uzumId,
                    ],
                    $data
                );

                if ($record->wasRecentlyCreated) {
                    $created++;
                } else {
                    $updated++;
                }
                $totalProcessed++;

                // Update summary
                if (isset($summary[$sourceNormalized])) {
                    $summary[$sourceNormalized] += $amount;
                } else {
                    $summary['other'] += $amount;
                }
                $summary['total'] += $amount;

                // Show progress
                if ($totalProcessed % 100 === 0) {
                    $this->line("    Processed: {$totalProcessed}");
                }

            } catch (\Throwable $e) {
                $errors++;
                Log::warning('UzumSyncExpenses item failed', [
                    'account_id' => $account->id,
                    'expense_id' => $expense['id'] ?? null,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->line("  Total processed (in period): {$totalProcessed}");

        return [
            'created' => $created,
            'updated' => $updated,
            'errors' => $errors,
            'summary' => $summary,
        ];
    }

    /**
     * Parse timestamp from milliseconds
     */
    protected function parseTimestamp($timestamp): ?Carbon
    {
        if (! $timestamp) {
            return null;
        }

        // API returns milliseconds
        if (is_numeric($timestamp) && $timestamp > 1000000000000) {
            return Carbon::createFromTimestampMs($timestamp);
        }

        if (is_numeric($timestamp)) {
            return Carbon::createFromTimestamp($timestamp);
        }

        try {
            return Carbon::parse($timestamp);
        } catch (\Exception $e) {
            return null;
        }
    }
}
