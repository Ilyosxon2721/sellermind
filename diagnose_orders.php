<?php

/**
 * Детальная диагностика конкретных заказов
 * Показывает ВСЮ цепочку поиска для каждого заказа
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\UzumOrder;
use App\Models\MarketplaceAccount;
use App\Models\VariantMarketplaceLink;
use App\Models\MarketplaceProduct;

// Проблемные заказы
$problemOrders = ['90002235', '90023032', '90023950'];

echo "=== ДИАГНОСТИКА ПРОБЛЕМНЫХ ЗАКАЗОВ ===\n\n";

foreach ($problemOrders as $extOrderId) {
    echo str_repeat('=', 80) . "\n";
    echo "ЗАКАЗ #{$extOrderId}\n";
    echo str_repeat('=', 80) . "\n";

    $order = UzumOrder::where('external_order_id', $extOrderId)->with('items')->first();
    if (!$order) {
        echo "  НЕ НАЙДЕН в базе!\n\n";
        continue;
    }

    $account = MarketplaceAccount::find($order->marketplace_account_id);
    echo "  Order ID: {$order->id}\n";
    echo "  Account ID: {$account->id} ({$account->name})\n";
    echo "  Status: {$order->status}\n";
    echo "  Stock Status: {$order->stock_status}\n\n";

    // Проверяем текущие резервы
    $reservations = \App\Models\Warehouse\StockReservation::where('source_type', 'marketplace_order')
        ->where('source_id', $order->id)
        ->with('sku.productVariant')
        ->get();

    echo "  ТЕКУЩИЕ РЕЗЕРВЫ: " . $reservations->count() . "\n";
    foreach ($reservations as $res) {
        $v = $res->sku?->productVariant;
        echo "    - Резерв ID: {$res->id}\n";
        echo "      SKU в резерве: " . ($v->sku ?? 'NULL') . "\n";
        echo "      Variant ID: " . ($v->id ?? 'NULL') . "\n";
    }

    echo "\n  ПОЗИЦИИ ЗАКАЗА:\n";
    foreach ($order->items as $item) {
        echo "\n  --- Позиция: {$item->name} ---\n";
        echo "    external_offer_id: {$item->external_offer_id}\n";

        $rawPayload = $item->raw_payload ?? [];
        $barcode = $rawPayload['barcode'] ?? null;

        echo "    barcode: " . ($barcode ?? 'NULL') . "\n";
        echo "    raw_payload: " . json_encode($rawPayload, JSON_UNESCAPED_UNICODE) . "\n";

        if (!$barcode) {
            echo "    !!! BARCODE ОТСУТСТВУЕТ - поиск невозможен\n";
            continue;
        }

        // Шаг 1: Поиск по marketplace_barcode
        echo "\n    [ШАГ 1] Поиск по marketplace_barcode в VariantMarketplaceLink:\n";
        $link1 = VariantMarketplaceLink::where('marketplace_account_id', $account->id)
            ->where('marketplace_barcode', $barcode)
            ->where('is_active', true)
            ->first();

        if ($link1) {
            echo "      НАЙДЕНО! Link ID: {$link1->id}\n";
            echo "      Variant: " . ($link1->variant->sku ?? 'NULL') . " (ID: {$link1->product_variant_id})\n";
        } else {
            echo "      Не найдено\n";
        }

        // Шаг 2: Поиск через skuList
        echo "\n    [ШАГ 2] Поиск MarketplaceProduct с barcode в skuList:\n";

        $allProducts = MarketplaceProduct::where('marketplace_account_id', $account->id)
            ->whereNotNull('raw_payload')
            ->get();

        echo "      Всего MarketplaceProduct для аккаунта: " . $allProducts->count() . "\n";

        $foundProduct = null;
        $foundSkuId = null;

        foreach ($allProducts as $product) {
            $skuList = $product->raw_payload['skuList'] ?? [];
            foreach ($skuList as $sku) {
                if (isset($sku['barcode']) && (string) $sku['barcode'] === (string) $barcode) {
                    $foundProduct = $product;
                    $foundSkuId = $sku['skuId'] ?? null;
                    echo "      НАЙДЕН MarketplaceProduct!\n";
                    echo "        Product ID: {$product->id}\n";
                    echo "        Product Name: {$product->name}\n";
                    echo "        external_product_id: {$product->external_product_id}\n";
                    echo "        Найденный skuId: {$foundSkuId}\n";
                    echo "        SKU entry: " . json_encode($sku, JSON_UNESCAPED_UNICODE) . "\n";
                    break 2;
                }
            }
        }

        if (!$foundProduct) {
            echo "      Barcode НЕ найден ни в одном skuList\n";
            echo "      >>> Это ПРАВИЛЬНО - товар не должен резервироваться\n";
            continue;
        }

        // Шаг 3: Поиск VariantMarketplaceLink по skuId
        echo "\n    [ШАГ 3] Поиск VariantMarketplaceLink по skuId={$foundSkuId}:\n";

        $link2 = VariantMarketplaceLink::where('marketplace_account_id', $account->id)
            ->where('external_sku_id', (string) $foundSkuId)
            ->where('is_active', true)
            ->first();

        if ($link2) {
            echo "      НАЙДЕНО! Link ID: {$link2->id}\n";
            echo "      Variant: " . ($link2->variant->sku ?? 'NULL') . " (ID: {$link2->product_variant_id})\n";
            echo "      >>> ЭТО ПРОБЛЕМА! Barcode заказа соответствует привязанному товару\n";
        } else {
            echo "      Не найдено\n";
            echo "      >>> Это правильно - нет связи по skuId\n";
        }

        // Дополнительно: показать все связи для этого MarketplaceProduct
        if ($foundProduct) {
            echo "\n    [ДОПЛН] Все активные связи для MarketplaceProduct ID={$foundProduct->id}:\n";
            $allLinks = VariantMarketplaceLink::where('marketplace_account_id', $account->id)
                ->where('marketplace_product_id', $foundProduct->id)
                ->where('is_active', true)
                ->get();

            if ($allLinks->isEmpty()) {
                echo "      Связей нет\n";
            } else {
                foreach ($allLinks as $l) {
                    echo "      - Link ID: {$l->id}\n";
                    echo "        external_sku_id: {$l->external_sku_id}\n";
                    echo "        Variant: " . ($l->variant->sku ?? 'NULL') . "\n";
                }
            }
        }
    }

    echo "\n\n";
}

// Показать все активные связи для Uzum аккаунтов
echo "\n" . str_repeat('=', 80) . "\n";
echo "ВСЕ АКТИВНЫЕ СВЯЗИ ДЛЯ UZUM АККАУНТОВ\n";
echo str_repeat('=', 80) . "\n";

$uzumAccounts = MarketplaceAccount::where('marketplace', 'uzum')->where('is_active', true)->get();

foreach ($uzumAccounts as $acc) {
    echo "\nАккаунт ID: {$acc->id} ({$acc->name})\n";

    $links = VariantMarketplaceLink::where('marketplace_account_id', $acc->id)
        ->where('is_active', true)
        ->with(['variant', 'marketplaceProduct'])
        ->get();

    echo "Активных связей: " . $links->count() . "\n";

    foreach ($links as $link) {
        echo "\n  Link ID: {$link->id}\n";
        echo "    external_sku_id: " . ($link->external_sku_id ?? 'NULL') . "\n";
        echo "    external_offer_id: " . ($link->external_offer_id ?? 'NULL') . "\n";
        echo "    marketplace_barcode: " . ($link->marketplace_barcode ?? 'NULL') . "\n";
        echo "    marketplace_product_id: " . ($link->marketplace_product_id ?? 'NULL') . "\n";
        echo "    Variant: " . ($link->variant->sku ?? 'NULL') . " (ID: " . ($link->variant->id ?? 'NULL') . ")\n";

        if ($link->marketplaceProduct) {
            $mp = $link->marketplaceProduct;
            echo "    MarketplaceProduct: {$mp->name}\n";
            echo "    MP external_product_id: " . ($mp->external_product_id ?? 'NULL') . "\n";

            // Показать все barcode в skuList
            $skuList = $mp->raw_payload['skuList'] ?? [];
            echo "    Barcodes в skuList:\n";
            foreach ($skuList as $sku) {
                $bc = $sku['barcode'] ?? 'NO_BARCODE';
                $skId = $sku['skuId'] ?? 'NO_SKUID';
                echo "      - skuId: {$skId}, barcode: {$bc}\n";
            }
        }
    }
}
