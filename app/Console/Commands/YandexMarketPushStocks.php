<?php

namespace App\Console\Commands;

use App\Models\MarketplaceAccount;
use App\Models\VariantMarketplaceLink;
use App\Services\Marketplaces\YandexMarket\YandexMarketClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class YandexMarketPushStocks extends Command
{
    protected $signature = 'ym:push-stocks
                            {--account= : ID аккаунта Yandex Market}
                            {--dry-run : Показать что будет отправлено, без реальной синхронизации}';

    protected $description = 'Push локальных остатков в Yandex Market (отправка наших остатков в YM)';

    public function handle(): int
    {
        $accountId = $this->option('account');
        $dryRun = $this->option('dry-run');

        $accounts = $accountId
            ? MarketplaceAccount::where('id', $accountId)->whereIn('marketplace', ['yandex_market', 'ym'])->get()
            : MarketplaceAccount::whereIn('marketplace', ['yandex_market', 'ym'])->where('is_active', true)->get();

        if ($accounts->isEmpty()) {
            $this->warn('Нет активных Yandex Market аккаунтов');
            return self::SUCCESS;
        }

        foreach ($accounts as $account) {
            $this->info("Аккаунт #{$account->id} ({$account->name})");

            try {
                $this->pushStocks($account, $dryRun);
            } catch (\Exception $e) {
                $this->error("Ошибка синхронизации: {$e->getMessage()}");
                Log::error('Yandex Market stocks push failed', [
                    'account_id' => $account->id,
                    'error' => $e->getMessage(),
                ]);
            }

            $this->newLine();
        }

        return self::SUCCESS;
    }

    protected function pushStocks(MarketplaceAccount $account, bool $dryRun): void
    {
        $client = app(YandexMarketClient::class);

        $this->line('  Начинаем отправку остатков в Yandex Market...');
        $startTime = microtime(true);

        // Получить все активные связи для этого аккаунта
        $links = VariantMarketplaceLink::where('marketplace_account_id', $account->id)
            ->whereIn('marketplace_code', ['yandex_market', 'ym'])
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

            // Для YM API нужен shopSku (артикул), проверяем сначала external_sku
            $offerId = $link->external_sku ?? $link->external_offer_id ?? $link->marketplaceProduct?->external_offer_id;

            if (!$offerId) {
                $this->warn("  Пропуск связи #{$link->id}: отсутствует offer_id/external_sku");
                continue;
            }

            $this->line("    Offer {$offerId}: остаток {$stock} (variant_id: {$link->product_variant_id})");

            $stocksData[] = [
                'link_id' => $link->id,
                'offer_id' => $offerId,
                'stock' => max(0, $stock),
            ];
        }

        if (empty($stocksData)) {
            $this->warn('  Нет данных для синхронизации');
            return;
        }

        if ($dryRun) {
            $this->info('  [DRY-RUN] Данные для отправки:');
            foreach ($stocksData as $item) {
                $this->line("    Offer: {$item['offer_id']}, Amount: {$item['stock']}");
            }
            return;
        }

        $synced = 0;
        $errors = 0;

        // Синхронизируем каждый offer отдельно
        foreach ($stocksData as $index => $stockItem) {
            try {
                $client->updateStock(
                    $account,
                    $stockItem['offer_id'],
                    $stockItem['stock']
                );
                $synced++;

                // Задержка между запросами
                if ($index < count($stocksData) - 1) {
                    usleep(300000); // 300ms
                }
            } catch (\Exception $e) {
                $errors++;
                Log::error('Failed to push YM stock for offer', [
                    'account_id' => $account->id,
                    'offer_id' => $stockItem['offer_id'],
                    'error' => $e->getMessage(),
                ]);
                $this->error("  Ошибка для offer {$stockItem['offer_id']}: " . $e->getMessage());
            }
        }

        $duration = round(microtime(true) - $startTime, 2);

        $this->info("  ✓ Синхронизация завершена за {$duration} сек");
        $this->line("    Синхронизировано: {$synced}");

        if ($errors > 0) {
            $this->warn("    Ошибок: {$errors}");
        }

        Log::info('Yandex Market stocks pushed', [
            'account_id' => $account->id,
            'synced' => $synced,
            'errors' => $errors,
            'duration' => $duration,
        ]);
    }
}
