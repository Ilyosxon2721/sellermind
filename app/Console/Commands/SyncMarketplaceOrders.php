<?php

namespace App\Console\Commands;

use App\Models\MarketplaceAccount;
use App\Services\Marketplaces\MarketplaceSyncService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncMarketplaceOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'marketplace:sync-orders
                            {--account= : Specific marketplace account ID to sync}
                            {--days=7 : Number of days to sync (default: 7)}
                            {--force : Force sync even if recently synced}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync orders from all marketplace accounts (Wildberries, Uzum, Ozon, etc.)';

    /**
     * Execute the console command.
     */
    public function handle(MarketplaceSyncService $syncService)
    {
        $this->info('🔄 Starting marketplace orders synchronization...');
        $this->newLine();

        // Get accounts to sync
        $accountsQuery = MarketplaceAccount::where('is_active', true);

        if ($accountId = $this->option('account')) {
            $accountsQuery->where('id', $accountId);
        }

        $accounts = $accountsQuery->get();

        if ($accounts->isEmpty()) {
            $this->warn('⚠️  No active marketplace accounts found.');

            return self::FAILURE;
        }

        $this->info("Found {$accounts->count()} active marketplace account(s)");
        $this->newLine();

        $days = (int) $this->option('days');
        $from = Carbon::now()->subDays($days);
        $to = Carbon::now();

        $successCount = 0;
        $errorCount = 0;

        foreach ($accounts as $account) {
            $this->line("📦 Syncing: {$account->marketplace} (ID: {$account->id})");

            try {
                // Get order count before sync
                $beforeCount = $account->orders()->count();

                // Sync orders
                $syncService->syncOrders($account, $from, $to);

                // Get order count after sync
                $afterCount = $account->orders()->count();
                $newOrders = $afterCount - $beforeCount;

                if ($newOrders > 0) {
                    $this->info("   ✓ Synced {$newOrders} new orders (Total: {$afterCount})");
                } else {
                    $this->comment("   → No new orders (Total: {$afterCount})");
                }

                $successCount++;

            } catch (\Exception $e) {
                $this->error("   ✗ Error: {$e->getMessage()}");
                Log::error('Ошибка синхронизации заказов маркетплейса', [
                    'account_id' => $account->id,
                    'marketplace' => $account->marketplace,
                    'error' => $e->getMessage(),
                ]);
                $errorCount++;
            }

            $this->newLine();
        }

        // Summary
        $this->newLine();
        $this->info('═══════════════════════════════════════════');
        $this->info('📊 Synchronization Summary:');
        $this->info("   ✓ Successful: {$successCount}");

        if ($errorCount > 0) {
            $this->error("   ✗ Failed: {$errorCount}");
        }

        $this->info('═══════════════════════════════════════════');

        return $errorCount > 0 ? self::FAILURE : self::SUCCESS;
    }
}
