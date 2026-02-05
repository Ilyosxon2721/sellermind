<?php

// file: app/Console/Commands/WildberriesSyncLocalStocks.php

namespace App\Console\Commands;

use App\Models\MarketplaceAccount;
use App\Services\Marketplaces\Wildberries\WildberriesStockService;
use Illuminate\Console\Command;

class WildberriesSyncLocalStocks extends Command
{
    protected $signature = 'wb:sync-local-stocks {--account=} {--product=*}';

    protected $description = 'Push local stock quantities to linked Wildberries products (FBS stocks)';

    public function handle(WildberriesStockService $service): int
    {
        $accountId = $this->option('account');
        $productIds = $this->option('product');

        $accounts = $accountId
            ? MarketplaceAccount::where('id', $accountId)->get()
            : MarketplaceAccount::where('marketplace', 'wb')->get();

        if ($accounts->isEmpty()) {
            $this->warn('No WB accounts found');

            return self::SUCCESS;
        }

        foreach ($accounts as $account) {
            $this->info("Syncing account #{$account->id} ({$account->name})");
            $res = $service->pushLinkedProducts($account, $productIds ?: null);
            $this->line("Warehouses: {$res['warehouses']}, pushed: {$res['pushed']}, errors: {$res['errors']}");
            if (! empty($res['error_messages'])) {
                foreach ($res['error_messages'] as $msg) {
                    $this->error($msg);
                }
            }
        }

        return self::SUCCESS;
    }
}
