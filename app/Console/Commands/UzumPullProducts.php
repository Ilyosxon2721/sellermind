<?php

namespace App\Console\Commands;

use App\Models\MarketplaceAccount;
use App\Models\MarketplaceProduct;
use App\Services\Marketplaces\UzumClient;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;

class UzumPullProducts extends Command
{
    protected $signature = 'uzum:pull-products {accountId : ID аккаунта Uzum}';
    protected $description = 'Загрузить товары из Uzum (по shopIds аккаунта) в marketplace_products';

    public function handle(UzumClient $client): int
    {
        $accountId = (int) $this->argument('accountId');
        $account = MarketplaceAccount::find($accountId);

        if (!$account) {
            $this->error("Аккаунт {$accountId} не найден");
            return self::FAILURE;
        }

        if (!$account->isUzum()) {
            $this->error("Аккаунт {$accountId} не является Uzum");
            return self::FAILURE;
        }

        $this->info("Загружаем товары Uzum для аккаунта #{$account->id} ({$account->name})");

        // Используем syncCatalog который правильно сохраняет shop_id для каждого товара
        $result = $client->syncCatalog($account);

        $this->info("Синхронизировано магазинов: " . count($result['shops']));
        foreach ($result['shops'] as $shopId) {
            $this->line("  - Shop ID: {$shopId}");
        }

        // Show failed shops if any
        $failedShops = $result['failed_shops'] ?? [];
        if (!empty($failedShops)) {
            $this->warn("Пропущено магазинов (нет доступа): " . count($failedShops));
            foreach ($failedShops as $shopId) {
                $this->line("  - Shop ID: {$shopId} (403 Access Denied)");
            }
        }

        $this->info("Сохранено товаров: {$result['synced']}");
        $this->info("API запросов: {$result['requests']}");

        return self::SUCCESS;
    }
}
