<?php

namespace App\Console\Commands;

use App\Models\MarketplaceAccount;
use App\Services\Marketplaces\Wildberries\WildberriesStockService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncWildberriesWarehouses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wb:sync-warehouses 
                            {account? : Marketplace account ID (optional, syncs all WB accounts if not specified)}
                            {--force : Force sync even if warehouses already exist}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync FBS warehouses from Wildberries Marketplace API to update warehouse_id field';

    /**
     * Execute the console command.
     */
    public function handle(WildberriesStockService $stockService)
    {
        $this->info('🏭 Syncing Wildberries FBS Warehouses...');
        $this->newLine();

        $accountId = $this->argument('account');

        if ($accountId) {
            $accounts = MarketplaceAccount::where('id', $accountId)
                ->where('marketplace', 'wb')
                ->get();

            if ($accounts->isEmpty()) {
                $this->error("❌ No WB account found with ID: {$accountId}");

                return 1;
            }
        } else {
            $accounts = MarketplaceAccount::where('marketplace', 'wb')->get();

            if ($accounts->isEmpty()) {
                $this->error('❌ No Wildberries accounts found in database');

                return 1;
            }
        }

        $totalCreated = 0;
        $totalUpdated = 0;
        $totalErrors = 0;

        foreach ($accounts as $account) {
            $this->info("📦 Processing account ID: {$account->id}");

            try {
                $result = $stockService->syncWarehouses($account);

                $totalCreated += $result['created'];
                $totalUpdated += $result['updated'];
                $totalErrors += count($result['errors']);

                if ($result['created'] > 0 || $result['updated'] > 0) {
                    $this->line("   ✅ Created: {$result['created']}, Updated: {$result['updated']}");
                }

                if (! empty($result['errors'])) {
                    $this->warn('   ⚠️  Errors: '.count($result['errors']));
                    foreach ($result['errors'] as $error) {
                        $this->line("      - {$error}");
                    }
                }

            } catch (\Exception $e) {
                $this->error('   ❌ Failed: '.$e->getMessage());
                Log::error('Ошибка синхронизации складов WB', [
                    'account_id' => $account->id,
                    'error' => $e->getMessage(),
                ]);
                $totalErrors++;
            }

            $this->newLine();
        }

        // Summary
        $this->info('📊 Sync Summary:');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Warehouses Created', $totalCreated],
                ['Warehouses Updated', $totalUpdated],
                ['Errors', $totalErrors],
            ]
        );

        if ($totalErrors > 0) {
            $this->warn('⚠️  Some errors occurred during sync. Check logs for details.');
        } else {
            $this->info('✅ Warehouse sync completed successfully!');
        }

        return $totalErrors > 0 ? 1 : 0;
    }
}
