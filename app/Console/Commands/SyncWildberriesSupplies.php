<?php

namespace App\Console\Commands;

use App\Jobs\SyncWildberriesSupplies as SyncWildberriesSuppliesJob;
use App\Models\MarketplaceAccount;
use Illuminate\Console\Command;

class SyncWildberriesSupplies extends Command
{
    protected $signature = 'wb:sync-supplies {--account_id= : Specific account ID to sync}';

    protected $description = 'Sync supplies from Wildberries API to local database';

    public function handle(): int
    {
        $this->info('Starting Wildberries supplies sync...');

        $accountId = $this->option('account_id');

        $query = MarketplaceAccount::where('marketplace', 'wb');

        if ($accountId) {
            $query->where('id', $accountId);
        }

        $accounts = $query->get();

        if ($accounts->isEmpty()) {
            $this->warn('No Wildberries accounts found.');
            return self::SUCCESS;
        }

        $this->info("Found {$accounts->count()} Wildberries account(s) to sync.");

        foreach ($accounts as $account) {
            $this->info("Syncing supplies for account #{$account->id} ({$account->name})...");

            try {
                SyncWildberriesSuppliesJob::dispatch($account);
                $this->info("✓ Job dispatched for account #{$account->id}");
            } catch (\Exception $e) {
                $this->error("✗ Failed to dispatch job for account #{$account->id}: {$e->getMessage()}");
            }
        }

        $this->info('All sync jobs have been dispatched.');

        return self::SUCCESS;
    }
}
