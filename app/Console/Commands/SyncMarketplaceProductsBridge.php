<?php

namespace App\Console\Commands;

use App\Models\MarketplaceAccount;
use App\Services\Marketplaces\MarketplaceProductsBridgeService;
use Illuminate\Console\Command;

class SyncMarketplaceProductsBridge extends Command
{
    protected $signature = 'marketplace:bridge-sync
                            {--marketplace= : wb или ozon (по умолчанию оба)}
                            {--account=     : ID конкретного аккаунта}';

    protected $description = 'Копирует WB/Ozon товары из нативных таблиц в marketplace_products';

    public function handle(): int
    {
        $marketplace = $this->option('marketplace');
        $accountId   = $this->option('account');
        $bridge      = new MarketplaceProductsBridgeService;

        $query = MarketplaceAccount::where('is_active', true);

        if ($accountId) {
            $query->where('id', $accountId);
        } elseif ($marketplace) {
            $query->where('marketplace', $marketplace);
        } else {
            $query->whereIn('marketplace', ['wb', 'ozon']);
        }

        $accounts = $query->get();

        if ($accounts->isEmpty()) {
            $this->warn('Нет подходящих аккаунтов');
            return self::SUCCESS;
        }

        foreach ($accounts as $account) {
            $this->line("Аккаунт #{$account->id} ({$account->marketplace}) {$account->name}");

            try {
                $synced = match (strtolower($account->marketplace)) {
                    'wb'   => $bridge->syncFromWildberries($account),
                    'ozon' => $bridge->syncFromOzon($account),
                    default => 0,
                };

                $this->info("  ✓ Скопировано товаров: {$synced}");
            } catch (\Exception $e) {
                $this->error("  ✗ Ошибка: {$e->getMessage()}");
            }
        }

        return self::SUCCESS;
    }
}
