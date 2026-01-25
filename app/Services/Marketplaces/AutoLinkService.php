<?php

namespace App\Services\Marketplaces;

use App\Models\MarketplaceAccount;
use App\Models\MarketplaceProduct;
use App\Models\OzonProduct;
use App\Models\ProductVariant;
use App\Models\VariantMarketplaceLink;
use App\Models\WildberriesProduct;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Сервис автоматической привязки товаров маркетплейсов к внутренним вариантам
 */
class AutoLinkService
{
    protected array $stats = [
        'total_products' => 0,
        'already_linked' => 0,
        'linked_by_barcode' => 0,
        'linked_by_sku' => 0,
        'linked_by_article' => 0,
        'not_linked' => 0,
        'errors' => 0,
    ];

    /**
     * Автоматически привязать товары для всех активных аккаунтов компании
     */
    public function autoLinkForCompany(int $companyId): array
    {
        $accounts = MarketplaceAccount::where('company_id', $companyId)
            ->where('is_active', true)
            ->get();

        $results = [];
        foreach ($accounts as $account) {
            $results[$account->id] = $this->autoLinkForAccount($account);
        }

        return $results;
    }

    /**
     * Автоматически привязать товары для конкретного аккаунта
     */
    public function autoLinkForAccount(MarketplaceAccount $account): array
    {
        $this->resetStats();

        Log::info('AutoLinkService: Starting auto-link', [
            'account_id' => $account->id,
            'marketplace' => $account->marketplace,
        ]);

        // Получаем все внутренние варианты компании для поиска
        $variants = ProductVariant::where('company_id', $account->company_id)
            ->whereNotNull('sku')
            ->get()
            ->keyBy('sku');

        // Создаём индексы для быстрого поиска
        $variantsByBarcode = ProductVariant::where('company_id', $account->company_id)
            ->whereNotNull('barcode')
            ->get()
            ->keyBy('barcode');

        // Получаем товары маркетплейса
        $products = $this->getMarketplaceProducts($account);
        $this->stats['total_products'] = $products->count();

        foreach ($products as $product) {
            try {
                $this->processProduct($account, $product, $variants, $variantsByBarcode);
            } catch (\Throwable $e) {
                $this->stats['errors']++;
                Log::error('AutoLinkService: Error processing product', [
                    'product_id' => $product->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('AutoLinkService: Completed auto-link', [
            'account_id' => $account->id,
            'stats' => $this->stats,
        ]);

        return $this->stats;
    }

    /**
     * Обработать один товар маркетплейса
     */
    protected function processProduct(
        MarketplaceAccount $account,
        $product,
        Collection $variantsBySku,
        Collection $variantsByBarcode
    ): void {
        // Проверяем, есть ли уже привязка
        if ($this->hasActiveLink($account, $product)) {
            $this->stats['already_linked']++;
            return;
        }

        // Извлекаем идентификаторы из товара
        $identifiers = $this->extractIdentifiers($account, $product);

        // 1. Пробуем найти по баркоду
        if (!empty($identifiers['barcodes'])) {
            foreach ($identifiers['barcodes'] as $barcode) {
                if (isset($variantsByBarcode[$barcode])) {
                    $this->createLink($account, $product, $variantsByBarcode[$barcode], 'barcode', $barcode);
                    $this->stats['linked_by_barcode']++;
                    return;
                }
            }
        }

        // 2. Пробуем найти по SKU/артикулу
        if (!empty($identifiers['skus'])) {
            foreach ($identifiers['skus'] as $sku) {
                if (isset($variantsBySku[$sku])) {
                    $this->createLink($account, $product, $variantsBySku[$sku], 'sku', $sku);
                    $this->stats['linked_by_sku']++;
                    return;
                }
                // Поиск без учёта регистра
                $variant = $variantsBySku->first(fn($v) => strcasecmp($v->sku, $sku) === 0);
                if ($variant) {
                    $this->createLink($account, $product, $variant, 'sku', $sku);
                    $this->stats['linked_by_sku']++;
                    return;
                }
            }
        }

        // 3. Пробуем найти по артикулу (article)
        if (!empty($identifiers['articles'])) {
            foreach ($identifiers['articles'] as $article) {
                // Ищем вариант где SKU начинается с артикула
                $variant = $variantsBySku->first(function ($v) use ($article) {
                    return stripos($v->sku, $article) === 0 || strcasecmp($v->sku, $article) === 0;
                });
                if ($variant) {
                    $this->createLink($account, $product, $variant, 'article', $article);
                    $this->stats['linked_by_article']++;
                    return;
                }
            }
        }

        $this->stats['not_linked']++;
    }

    /**
     * Извлечь идентификаторы из товара маркетплейса
     */
    protected function extractIdentifiers(MarketplaceAccount $account, $product): array
    {
        $barcodes = [];
        $skus = [];
        $articles = [];

        $marketplace = $account->marketplace;

        if ($marketplace === 'wb') {
            // WildberriesProduct
            if (!empty($product->barcode)) {
                $barcodes[] = $product->barcode;
            }
            if (!empty($product->supplier_article)) {
                $articles[] = $product->supplier_article;
                $skus[] = $product->supplier_article;
            }
            // Из sizes
            $sizes = $product->raw_payload['sizes'] ?? [];
            foreach ($sizes as $size) {
                foreach ($size['skus'] ?? [] as $sku) {
                    if (!empty($sku)) {
                        $barcodes[] = $sku; // В WB skus это баркоды
                    }
                }
            }
        } elseif ($marketplace === 'ozon') {
            // OzonProduct
            if (!empty($product->barcode)) {
                $barcodes[] = $product->barcode;
            }
            if (!empty($product->external_offer_id)) {
                $skus[] = $product->external_offer_id;
                $articles[] = $product->external_offer_id;
            }
            // Из raw_payload
            $sources = $product->raw_payload['sources'] ?? [];
            foreach ($sources as $source) {
                if (!empty($source['sku'])) {
                    $skus[] = $source['sku'];
                }
            }
        } elseif ($marketplace === 'uzum') {
            // MarketplaceProduct (Uzum)
            if (!empty($product->external_offer_id)) {
                $skus[] = $product->external_offer_id;
            }
            // Из skuList
            $skuList = $product->raw_payload['skuList'] ?? [];
            foreach ($skuList as $sku) {
                if (!empty($sku['barcode'])) {
                    $barcodes[] = (string) $sku['barcode'];
                }
                if (!empty($sku['skuTitle'])) {
                    // Пробуем извлечь артикул из названия
                    $articles[] = $sku['skuTitle'];
                }
            }
            // Артикул из title
            if (!empty($product->title)) {
                // Ищем артикул в начале названия (часто формат "АРТИКУЛ - Название")
                if (preg_match('/^([A-Z0-9\-]+)/i', $product->title, $m)) {
                    $articles[] = $m[1];
                }
            }
        } elseif ($marketplace === 'ym') {
            // MarketplaceProduct (Yandex Market)
            if (!empty($product->external_offer_id)) {
                $skus[] = $product->external_offer_id;
                $articles[] = $product->external_offer_id;
            }
            // Из raw_payload
            if (!empty($product->raw_payload['shopSku'])) {
                $skus[] = $product->raw_payload['shopSku'];
            }
            if (!empty($product->raw_payload['barcodes'])) {
                foreach ($product->raw_payload['barcodes'] as $bc) {
                    $barcodes[] = $bc;
                }
            }
        }

        return [
            'barcodes' => array_unique(array_filter($barcodes)),
            'skus' => array_unique(array_filter($skus)),
            'articles' => array_unique(array_filter($articles)),
        ];
    }

    /**
     * Проверить есть ли активная привязка
     */
    protected function hasActiveLink(MarketplaceAccount $account, $product): bool
    {
        return VariantMarketplaceLink::where('marketplace_account_id', $account->id)
            ->where('marketplace_product_id', $product->id)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Создать привязку
     */
    protected function createLink(
        MarketplaceAccount $account,
        $product,
        ProductVariant $variant,
        string $matchType,
        string $matchValue
    ): void {
        $marketplace = $account->marketplace;

        // Определяем external_offer_id и external_sku
        if ($marketplace === 'wb') {
            $externalOfferId = $product->nm_id;
            $externalSku = $product->barcode ?? $variant->sku;
            $marketplaceBarcode = $product->barcode;
        } elseif ($marketplace === 'ozon') {
            $externalOfferId = $product->external_product_id;
            $externalSku = $product->external_offer_id ?? $product->external_product_id;
            $marketplaceBarcode = $product->barcode;
        } else {
            // Uzum, YM
            $externalOfferId = $product->external_offer_id;
            $externalSku = $product->external_sku ?? $variant->sku;
            // Для Uzum берём баркод из skuList
            $marketplaceBarcode = null;
            if ($marketplace === 'uzum') {
                $skuList = $product->raw_payload['skuList'] ?? [];
                if (!empty($skuList[0]['barcode'])) {
                    $marketplaceBarcode = (string) $skuList[0]['barcode'];
                }
            }
        }

        VariantMarketplaceLink::updateOrCreate(
            [
                'product_variant_id' => $variant->id,
                'marketplace_product_id' => $product->id,
                'marketplace_code' => $marketplace,
            ],
            [
                'company_id' => $account->company_id,
                'marketplace_account_id' => $account->id,
                'external_offer_id' => $externalOfferId,
                'external_sku' => $externalSku,
                'marketplace_barcode' => $marketplaceBarcode,
                'is_active' => true,
                'sync_stock_enabled' => true,
                'sync_price_enabled' => false,
            ]
        );

        Log::info('AutoLinkService: Created link', [
            'account_id' => $account->id,
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'match_type' => $matchType,
            'match_value' => $matchValue,
        ]);
    }

    /**
     * Получить товары маркетплейса
     */
    protected function getMarketplaceProducts(MarketplaceAccount $account): Collection
    {
        $marketplace = $account->marketplace;

        if ($marketplace === 'wb') {
            return WildberriesProduct::where('marketplace_account_id', $account->id)->get();
        }

        if ($marketplace === 'ozon') {
            return OzonProduct::where('marketplace_account_id', $account->id)->get();
        }

        // Uzum, YM и другие
        return MarketplaceProduct::where('marketplace_account_id', $account->id)->get();
    }

    protected function resetStats(): void
    {
        $this->stats = [
            'total_products' => 0,
            'already_linked' => 0,
            'linked_by_barcode' => 0,
            'linked_by_sku' => 0,
            'linked_by_article' => 0,
            'not_linked' => 0,
            'errors' => 0,
        ];
    }
}
