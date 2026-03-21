<?php

namespace App\Console\Commands;

use App\Models\MarketplaceAccount;
use App\Models\VariantMarketplaceLink;
use App\Services\Uzum\Api\UzumApiManager;
use Illuminate\Console\Command;

class DebugUzumStockSync extends Command
{
    protected $signature = 'uzum:debug-stock
                            {--account= : ID аккаунта Uzum}
                            {--link= : ID связи VariantMarketplaceLink}
                            {--send : Отправить запрос в Uzum (без флага — только показать данные)}';

    protected $description = 'Диагностика синхронизации остатков Uzum: показывает что будет отправлено';

    public function handle(): int
    {
        $accountId = $this->option('account');
        $linkId = $this->option('link');
        $send = $this->option('send');

        // Загружаем аккаунт
        $query = MarketplaceAccount::where('marketplace', 'uzum');
        if ($accountId) {
            $query->where('id', $accountId);
        }
        $account = $query->first();

        if (! $account) {
            $this->error('Аккаунт Uzum не найден');
            return self::FAILURE;
        }

        $this->info("Аккаунт: #{$account->id} {$account->name}");
        $this->newLine();

        // Загружаем связи
        $linksQuery = VariantMarketplaceLink::where('marketplace_account_id', $account->id)
            ->where('is_active', true)
            ->with(['variant', 'marketplaceProduct']);

        if ($linkId) {
            $linksQuery->where('id', $linkId);
        } else {
            $linksQuery->whereNotNull('external_sku_id')->limit(5);
        }

        $links = $linksQuery->get();

        if ($links->isEmpty()) {
            $this->warn('Нет активных связей с external_sku_id');
            return self::FAILURE;
        }

        // --- Шаг 1: Показываем текущие остатки от Uzum ---
        $this->line('<fg=cyan>══ Шаг 1: GET /v2/fbs/sku/stocks (текущие остатки в Uzum) ══</>');
        $uzum = new UzumApiManager($account);
        try {
            $currentStocks = $uzum->stocks()->get();
            $skuListFromApi = $currentStocks['skuAmountList']
                ?? $currentStocks['payload']['skuAmountList']
                ?? $currentStocks['data']
                ?? $currentStocks;

            if (is_array($skuListFromApi) && ! empty($skuListFromApi)) {
                $this->line('Ключи ответа: ' . implode(', ', array_keys($currentStocks)));
                $this->line('Количество SKU в ответе: ' . count($skuListFromApi));
                // Показываем первые 3 записи
                foreach (array_slice($skuListFromApi, 0, 3) as $sku) {
                    $this->line('  SKU ' . ($sku['skuId'] ?? '?') . ': amount=' . ($sku['amount'] ?? '?') . ', barcode=' . ($sku['barcode'] ?? '?') . ', skuTitle=' . ($sku['skuTitle'] ?? '?'));
                }
            } else {
                $this->warn('GET stocks вернул пустой или неожиданный ответ:');
                $this->line(json_encode($currentStocks, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }
        } catch (\Exception $e) {
            $this->error('GET stocks ошибка: ' . $e->getMessage());
            $currentStocks = [];
            $skuListFromApi = [];
        }
        $this->newLine();

        // --- Шаг 2: Для каждой связи показываем что будем отправлять ---
        foreach ($links as $link) {
            $mpProduct = $link->marketplaceProduct;
            $skuId = $link->external_sku_id;
            $stock = $link->getCurrentStock();

            $this->line("<fg=cyan>══ Связь #{$link->id}: SKU {$skuId} ══</>");

            if (! $mpProduct) {
                $this->error('  marketplaceProduct = NULL! marketplace_product_id=' . $link->marketplace_product_id);
                continue;
            }

            $this->line('  MarketplaceProduct ID: ' . $mpProduct->id);
            $this->line('  external_product_id: ' . $mpProduct->external_product_id);
            $this->line('  title: ' . $mpProduct->title);
            $this->line('  Локальный остаток: ' . $stock);

            // Ищем в raw_payload
            $rawSkuList = $mpProduct->raw_payload['skuList'] ?? [];
            $this->line('  skuList в raw_payload: ' . count($rawSkuList) . ' SKU');

            $barcode = null;
            $skuTitle = null;
            foreach ($rawSkuList as $sku) {
                if (isset($sku['skuId']) && (string) $sku['skuId'] === (string) $skuId) {
                    $barcode = $sku['barcode'] ?? null;
                    $skuTitle = $sku['skuTitle'] ?? $sku['skuFullTitle'] ?? null;
                    $this->line('  Найден в raw_payload: skuTitle=' . ($skuTitle ?? 'NULL') . ', barcode=' . ($barcode ?? 'NULL'));
                    $this->line('  Все поля SKU: ' . implode(', ', array_keys($sku)));
                    break;
                }
            }

            if (! $barcode && ! empty($skuListFromApi)) {
                foreach ($skuListFromApi as $sku) {
                    if (isset($sku['skuId']) && (string) $sku['skuId'] === (string) $skuId) {
                        $barcode = $barcode ?? ($sku['barcode'] ?? null);
                        $skuTitle = $skuTitle ?? ($sku['skuTitle'] ?? null);
                        $this->line('  Найден в API response: skuTitle=' . ($skuTitle ?? 'NULL') . ', barcode=' . ($barcode ?? 'NULL'));
                        break;
                    }
                }
            }

            $productTitle = $mpProduct->title ?? '';
            $skuTitle = $skuTitle ?? $productTitle;

            $requestBody = [
                'skuAmountList' => [[
                    'skuId' => (int) $skuId,
                    'skuTitle' => $skuTitle,
                    'productTitle' => $productTitle,
                    'barcode' => (string) ($barcode ?? ''),
                    'amount' => $stock,
                    'fbsLinked' => true,
                    'dbsLinked' => false,
                ]],
            ];

            $this->newLine();
            $this->line('<fg=yellow>  Тело POST запроса (что будет отправлено):</>');
            $this->line(json_encode($requestBody, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            if (! $barcode) {
                $this->error('  ОШИБКА: barcode не найден — запрос будет отклонён!');
            }

            if ($send) {
                $this->newLine();
                $this->line('<fg=green>  Отправляем запрос в Uzum...</>');
                try {
                    $result = $uzum->stocks()->updateOne(
                        (int) $skuId,
                        $stock,
                        (string) ($barcode ?? ''),
                        $skuTitle,
                        $productTitle,
                    );
                    $this->line('<fg=green>  Ответ Uzum:</>');
                    $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

                    $updatedRecords = $result['payload']['updatedRecords']
                        ?? $result['updatedRecords']
                        ?? 'N/A';
                    $this->line("  updatedRecords: {$updatedRecords}");
                } catch (\Exception $e) {
                    $this->error('  Ошибка: ' . $e->getMessage());
                }
            }

            $this->newLine();
        }

        if (! $send) {
            $this->comment('Запуск с --send для реальной отправки в Uzum');
        }

        return self::SUCCESS;
    }
}
