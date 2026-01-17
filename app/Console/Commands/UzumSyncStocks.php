<?php

namespace App\Console\Commands;

use App\Models\MarketplaceAccount;
use App\Models\VariantMarketplaceLink;
use App\Services\Marketplaces\UzumClient;
use App\Services\Marketplaces\MarketplaceHttpClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UzumSyncStocks extends Command
{
    protected $signature = 'uzum:sync-stocks
                            {--account= : ID аккаунта Uzum}';

    protected $description = 'Синхронизация остатков Uzum Market (push локальных остатков в Uzum)';

    public function handle(): int
    {
        $accountId = $this->option('account');

        $accounts = $accountId
            ? MarketplaceAccount::where('id', $accountId)->where('marketplace', 'uzum')->get()
            : MarketplaceAccount::where('marketplace', 'uzum')->where('is_active', true)->get();

        if ($accounts->isEmpty()) {
            $this->warn('Нет активных Uzum Market аккаунтов');
            return self::SUCCESS;
        }

        foreach ($accounts as $account) {
            $this->info("Аккаунт #{$account->id} ({$account->name})");

            try {
                $this->syncStocks($account);
            } catch (\Exception $e) {
                $this->error("Ошибка синхронизации: {$e->getMessage()}");
                Log::error('Uzum stocks sync failed', [
                    'account_id' => $account->id,
                    'error' => $e->getMessage(),
                ]);
            }

            $this->newLine();
        }

        return self::SUCCESS;
    }

    protected function syncStocks(MarketplaceAccount $account): void
    {
        $httpClient = app(MarketplaceHttpClient::class);
        $issueDetector = app(\App\Services\Marketplaces\IssueDetectorService::class);
        $client = new UzumClient($httpClient, $issueDetector);

        $this->line('  Начинаем синхронизацию остатков...');
        $startTime = microtime(true);

        // Получить все активные связи для этого аккаунта
        $links = VariantMarketplaceLink::where('marketplace_account_id', $account->id)
            ->where('marketplace_code', 'uzum')
            ->where('is_active', true)
            ->where('sync_stock_enabled', true)
            ->with(['variant', 'marketplaceProduct'])
            ->get();

        if ($links->isEmpty()) {
            $this->warn('  Нет активных связей для синхронизации');
            return;
        }

        $this->line("  Найдено связей: " . $links->count());

        // Подготовить данные для синхронизации
        $stocksData = [];
        foreach ($links as $link) {
            $stock = $link->getCurrentStock();

            // Для Uzum нужны productId (из MarketplaceProduct) и skuId (из связи)
            $productId = $link->marketplaceProduct?->external_product_id;
            $skuId = $link->external_sku_id;

            if (!$productId || !$skuId) {
                $this->warn("  Пропуск связи #{$link->id}: отсутствует productId ({$productId}) или skuId ({$skuId})");
                continue;
            }

            $this->line("    SKU {$skuId} (product {$productId}): остаток {$stock}");

            $stocksData[] = [
                'productId' => $productId,
                'skuId' => $skuId,
                'stock' => max(0, $stock), // Uzum не принимает отрицательные остатки
            ];
        }

        if (empty($stocksData)) {
            $this->warn('  Нет данных для синхронизации');
            return;
        }

        $synced = 0;
        $errors = 0;

        try {
            // Отправить остатки в Uzum (используем метод syncStocks из UzumClient)
            $client->syncStocks($account, $stocksData);
            $synced = count($stocksData);
        } catch (\Exception $e) {
            $errors++;
            Log::error('Failed to sync Uzum stocks', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);
            $this->error("  Ошибка синхронизации: " . $e->getMessage());
        }

        $duration = round(microtime(true) - $startTime, 2);

        $this->info("  ✓ Синхронизация завершена за {$duration} сек");
        $this->line("    Синхронизировано: {$synced}");

        if ($errors > 0) {
            $this->warn("    Ошибок: {$errors}");
        }

        Log::info('Uzum stocks synced', [
            'account_id' => $account->id,
            'synced' => $synced,
            'errors' => $errors,
            'duration' => $duration,
        ]);
    }
}
