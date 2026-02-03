<?php

// file: app/Console/Commands/WildberriesFullStockSync.php

namespace App\Console\Commands;

use App\Models\MarketplaceAccount;
use App\Services\Marketplaces\Wildberries\WildberriesHttpClient;
use App\Services\Marketplaces\Wildberries\WildberriesStockService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class WildberriesFullStockSync extends Command
{
    protected $signature = 'wb:full-stock-sync
                            {--account= : ID аккаунта WB}
                            {--from= : Дата начала для pull (YYYY-MM-DD)}
                            {--pull-only : Только загрузить из WB}
                            {--push-only : Только отправить в WB}';

    protected $description = 'Полный цикл синхронизации остатков WB: pull из Statistics API и push локальных остатков в FBS';

    public function handle(): int
    {
        $accountId = $this->option('account');
        $fromOption = $this->option('from');
        $pullOnly = (bool) $this->option('pull-only');
        $pushOnly = (bool) $this->option('push-only');
        $from = $fromOption ? Carbon::parse($fromOption) : null;

        $accounts = $accountId
            ? MarketplaceAccount::where('id', $accountId)->get()
            : MarketplaceAccount::where('marketplace', 'wb')->where('is_active', true)->get();

        if ($accounts->isEmpty()) {
            $this->warn('Нет активных WB аккаунтов');

            return self::SUCCESS;
        }

        foreach ($accounts as $account) {
            $this->info("Аккаунт #{$account->id} ({$account->name})");

            $client = new WildberriesHttpClient($account);
            $service = new WildberriesStockService($client);

            if (! $pushOnly) {
                $this->line('→ Pull остатков из WB Statistics...');
                $pullResult = $service->syncStocks($account, $from);
                $this->line("   Обработано: {$pullResult['synced']}, ошибок: ".count($pullResult['errors']));
            }

            if (! $pullOnly) {
                $this->line('→ Push локальных остатков в WB (FBS)...');
                $pushResult = $service->pushLinkedProducts($account, null);
                $this->line("   Складов: {$pushResult['warehouses']}, отправлено позиций: {$pushResult['pushed']}, ошибок: {$pushResult['errors']}");
            }

            $this->newLine();
        }

        return self::SUCCESS;
    }
}
