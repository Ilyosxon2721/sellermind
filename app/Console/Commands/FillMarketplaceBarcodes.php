<?php

namespace App\Console\Commands;

use App\Models\MarketplaceProduct;
use App\Models\OzonProduct;
use App\Models\VariantMarketplaceLink;
use App\Models\WildberriesProduct;
use Illuminate\Console\Command;

class FillMarketplaceBarcodes extends Command
{
    protected $signature = 'marketplace:fill-barcodes
                            {--marketplace=all : Маркетплейс (uzum, wb, ozon, yandex, all)}
                            {--force : Перезаписать существующие баркоды}';

    protected $description = 'Заполнить marketplace_barcode в связях товаров из данных API маркетплейса';

    public function handle(): int
    {
        $marketplace = $this->option('marketplace');
        $force = $this->option('force');

        if ($marketplace === 'all') {
            $this->fillForMarketplace('uzum', $force);
            $this->fillForMarketplace('wb', $force);
            $this->fillForMarketplace('ozon', $force);
            $this->fillForMarketplace('yandex', $force);
        } else {
            $this->fillForMarketplace($marketplace, $force);
        }

        return self::SUCCESS;
    }

    protected function fillForMarketplace(string $marketplace, bool $force): void
    {
        $this->info("\n=== Заполнение marketplace_barcode для {$marketplace} ===");

        $updated = 0;
        $skipped = 0;
        $notFound = 0;

        // Получаем все связи для данного маркетплейса
        // Map short names to database marketplace names
        $marketplaceMap = [
            'wb' => 'wildberries',
            'yandex' => 'yandex_market',
        ];
        $dbMarketplace = $marketplaceMap[$marketplace] ?? $marketplace;

        $query = VariantMarketplaceLink::query()
            ->where('is_active', true)
            ->whereHas('account', fn ($q) => $q->where('marketplace', $dbMarketplace))
            ->with(['marketplaceProduct', 'variant', 'account']);

        // Если не force, то только без заполненного marketplace_barcode
        if (! $force) {
            $query->whereNull('marketplace_barcode');
        }

        $links = $query->get();

        $this->info("Найдено связей: {$links->count()}");

        if ($links->isEmpty()) {
            $this->info('Нет связей для обработки.');

            return;
        }

        $bar = $this->output->createProgressBar($links->count());
        $bar->start();

        foreach ($links as $link) {
            $bar->advance();

            $barcode = $this->extractBarcode($link, $marketplace);

            if ($barcode) {
                $link->update(['marketplace_barcode' => $barcode]);
                $updated++;
                $this->line("\n  Link #{$link->id}: установлен barcode {$barcode}");
            } else {
                $skipped++;
            }
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Готово для {$marketplace}!");
        $this->table(
            ['Метрика', 'Значение'],
            [
                ['Обновлено', $updated],
                ['Пропущено (нет баркода)', $skipped],
            ]
        );
    }

    /**
     * Извлечь баркод в зависимости от маркетплейса
     */
    protected function extractBarcode(VariantMarketplaceLink $link, string $marketplace): ?string
    {
        switch ($marketplace) {
            case 'uzum':
                return $this->extractUzumBarcode($link);
            case 'wb':
                return $this->extractWbBarcode($link);
            case 'ozon':
                return $this->extractOzonBarcode($link);
            case 'yandex':
                return $this->extractYandexBarcode($link);
            default:
                return null;
        }
    }

    /**
     * Извлечь баркод для Uzum
     */
    protected function extractUzumBarcode(VariantMarketplaceLink $link): ?string
    {
        $mpProduct = $link->marketplaceProduct;
        if (! $mpProduct) {
            return null;
        }

        $rawPayload = $mpProduct->raw_payload ?? [];
        $skuList = $rawPayload['skuList'] ?? [];

        if (empty($skuList)) {
            return null;
        }

        // Ищем SKU по external_sku_id
        foreach ($skuList as $sku) {
            $skuId = (string) ($sku['skuId'] ?? '');

            if ($link->external_sku_id && $skuId === $link->external_sku_id) {
                return $sku['barcode'] ?? null;
            }
        }

        // Если не нашли по SKU ID, берём первый с баркодом
        return $skuList[0]['barcode'] ?? null;
    }

    /**
     * Извлечь баркод для Wildberries
     */
    protected function extractWbBarcode(VariantMarketplaceLink $link): ?string
    {
        // Для WB используем WildberriesProduct
        $wbProduct = WildberriesProduct::where('marketplace_account_id', $link->marketplace_account_id)
            ->where('id', $link->marketplace_product_id)
            ->first();

        if ($wbProduct && $wbProduct->barcode) {
            return $wbProduct->barcode;
        }

        // Fallback: попробовать найти по nm_id
        if ($link->external_offer_id) {
            $wbProduct = WildberriesProduct::where('marketplace_account_id', $link->marketplace_account_id)
                ->where('nm_id', $link->external_offer_id)
                ->first();

            if ($wbProduct && $wbProduct->barcode) {
                return $wbProduct->barcode;
            }
        }

        return null;
    }

    /**
     * Извлечь баркод для Ozon
     */
    protected function extractOzonBarcode(VariantMarketplaceLink $link): ?string
    {
        // Для Ozon используем OzonProduct
        $ozonProduct = OzonProduct::where('marketplace_account_id', $link->marketplace_account_id)
            ->where('id', $link->marketplace_product_id)
            ->first();

        if ($ozonProduct && $ozonProduct->barcode) {
            return $ozonProduct->barcode;
        }

        // Fallback: попробовать найти по external_product_id
        if ($link->external_offer_id) {
            $ozonProduct = OzonProduct::where('marketplace_account_id', $link->marketplace_account_id)
                ->where('external_product_id', $link->external_offer_id)
                ->first();

            if ($ozonProduct && $ozonProduct->barcode) {
                return $ozonProduct->barcode;
            }
        }

        return null;
    }

    /**
     * Извлечь баркод для Yandex Market
     * Yandex Market пока не имеет отдельной модели продуктов,
     * поэтому используем MarketplaceProduct
     */
    protected function extractYandexBarcode(VariantMarketplaceLink $link): ?string
    {
        $mpProduct = $link->marketplaceProduct;
        if (! $mpProduct) {
            return null;
        }

        // Пробуем получить баркод из raw_payload
        $rawPayload = $mpProduct->raw_payload ?? [];

        // Yandex Market может хранить баркоды в разных местах
        if (! empty($rawPayload['barcodes']) && is_array($rawPayload['barcodes'])) {
            return $rawPayload['barcodes'][0] ?? null;
        }

        if (! empty($rawPayload['barcode'])) {
            return $rawPayload['barcode'];
        }

        // Fallback: попробовать external_sku как баркод
        return $link->external_sku;
    }
}
