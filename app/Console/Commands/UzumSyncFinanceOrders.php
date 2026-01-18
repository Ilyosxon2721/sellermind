<?php

namespace App\Console\Commands;

use App\Models\MarketplaceAccount;
use App\Models\UzumFinanceOrder;
use App\Services\Marketplaces\UzumClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UzumSyncFinanceOrders extends Command
{
    protected $signature = 'uzum:sync-finance-orders
                            {--account= : Specific account ID to sync}
                            {--pages=10 : Max pages to fetch per shop (0 = unlimited)}
                            {--force : Force full sync even if recently synced}';

    protected $description = 'Sync Uzum finance orders for analytics (all order types: FBO/FBS/DBS/EDBS)';

    public function handle(UzumClient $client): int
    {
        $accountId = $this->option('account');
        $maxPages = (int) $this->option('pages');
        $force = $this->option('force');

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

        $this->info("Syncing finance orders for {$accounts->count()} Uzum account(s)...");

        $totalCreated = 0;
        $totalUpdated = 0;
        $totalErrors = 0;

        foreach ($accounts as $account) {
            $this->line('');
            $this->info("Processing account: {$account->name} (ID: {$account->id})");

            try {
                $result = $this->syncAccountFinanceOrders($client, $account, $maxPages);

                $totalCreated += $result['created'];
                $totalUpdated += $result['updated'];
                $totalErrors += $result['errors'];

                $this->info("  Created: {$result['created']}, Updated: {$result['updated']}, Errors: {$result['errors']}");
            } catch (\Throwable $e) {
                $this->error("  Error: {$e->getMessage()}");
                Log::error('UzumSyncFinanceOrders account failed', [
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

    protected function syncAccountFinanceOrders(UzumClient $client, MarketplaceAccount $account, int $maxPages): array
    {
        $created = 0;
        $updated = 0;
        $errors = 0;

        // Fetch all finance orders
        $items = $client->fetchAllFinanceOrders($account, [], $maxPages);
        $itemsCount = count($items);

        $this->line("  Fetched {$itemsCount} order items from API");

        $bar = $this->output->createProgressBar(count($items));
        $bar->start();

        foreach ($items as $item) {
            try {
                $data = $client->mapFinanceOrderData($item);

                if (!$data['uzum_id']) {
                    $errors++;
                    $bar->advance();
                    continue;
                }

                $order = UzumFinanceOrder::updateOrCreate(
                    [
                        'marketplace_account_id' => $account->id,
                        'uzum_id' => $data['uzum_id'],
                    ],
                    array_merge($data, [
                        'marketplace_account_id' => $account->id,
                    ])
                );

                if ($order->wasRecentlyCreated) {
                    $created++;
                } else {
                    $updated++;
                }
            } catch (\Throwable $e) {
                $errors++;
                Log::warning('UzumSyncFinanceOrders item failed', [
                    'account_id' => $account->id,
                    'item_id' => $item['id'] ?? null,
                    'error' => $e->getMessage(),
                ]);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->line('');

        return [
            'created' => $created,
            'updated' => $updated,
            'errors' => $errors,
        ];
    }
}
