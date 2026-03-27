<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Models\AP\SupplierInvoice;
use App\Models\Finance\FinanceSettings;
use App\Models\Warehouse\StockLedger;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Сервис для расчёта стоимости запасов, транзитов и себестоимости (COGS)
 */
final class InventoryValuationService
{
    /**
     * Получить сводку по остаткам на складах
     *
     * Рассчитывает стоимость из актуальных закупочных цен вариантов (с конвертацией валют)
     */
    public function getStockSummary(int $companyId, FinanceSettings $settings, $currencyService = null, string $displayCurrency = 'UZS'): array
    {
        $baseCurrency = 'UZS';

        try {
            $stockData = StockLedger::where('stock_ledger.company_id', $companyId)
                ->join('skus', 'stock_ledger.sku_id', '=', 'skus.id')
                ->leftJoin('product_variants', 'skus.product_variant_id', '=', 'product_variants.id')
                ->select(
                    'stock_ledger.sku_id',
                    DB::raw('SUM(stock_ledger.qty_delta) as qty'),
                    'product_variants.purchase_price',
                    'product_variants.purchase_price_currency',
                )
                ->groupBy('stock_ledger.sku_id', 'product_variants.purchase_price', 'product_variants.purchase_price_currency')
                ->having('qty', '>', 0)
                ->get();

            $totalQty = 0;
            $totalCostBase = 0;

            foreach ($stockData as $item) {
                $qty = (float) $item->qty;
                $totalQty += $qty;

                $purchasePrice = (float) ($item->purchase_price ?? 0);
                $currency = $item->purchase_price_currency ?? 'UZS';
                $priceInBase = $settings->convertToBase($purchasePrice, $currency);
                $totalCostBase += $priceInBase * $qty;
            }

            $totalCostBase = max(0, $totalCostBase);

            $totalCost = $currencyService
                ? $currencyService->convert($totalCostBase, $baseCurrency, $displayCurrency)
                : $totalCostBase;

            // Группировка по складам
            $byWarehouseData = StockLedger::where('stock_ledger.company_id', $companyId)
                ->join('warehouses', 'stock_ledger.warehouse_id', '=', 'warehouses.id')
                ->join('skus', 'stock_ledger.sku_id', '=', 'skus.id')
                ->leftJoin('product_variants', 'skus.product_variant_id', '=', 'product_variants.id')
                ->select(
                    'warehouses.id as warehouse_id',
                    'warehouses.name as warehouse_name',
                    'stock_ledger.sku_id',
                    DB::raw('SUM(stock_ledger.qty_delta) as qty'),
                    'product_variants.purchase_price',
                    'product_variants.purchase_price_currency',
                )
                ->groupBy('warehouses.id', 'warehouses.name', 'stock_ledger.sku_id', 'product_variants.purchase_price', 'product_variants.purchase_price_currency')
                ->having('qty', '>', 0)
                ->get();

            $warehouseMap = [];
            foreach ($byWarehouseData as $item) {
                $wId = $item->warehouse_id;
                if (! isset($warehouseMap[$wId])) {
                    $warehouseMap[$wId] = ['id' => $wId, 'name' => $item->warehouse_name, 'qty' => 0, 'cost_base' => 0];
                }
                $qty = (float) $item->qty;
                $purchasePrice = (float) ($item->purchase_price ?? 0);
                $currency = $item->purchase_price_currency ?? 'UZS';
                $priceInBase = $settings->convertToBase($purchasePrice, $currency);

                $warehouseMap[$wId]['qty'] += $qty;
                $warehouseMap[$wId]['cost_base'] += $priceInBase * $qty;
            }

            $warehouseData = collect($warehouseMap)->map(function ($w) use ($currencyService, $baseCurrency, $displayCurrency) {
                $costBase = max(0, $w['cost_base']);
                $cost = $currencyService
                    ? $currencyService->convert($costBase, $baseCurrency, $displayCurrency)
                    : $costBase;

                return [
                    'id' => $w['id'],
                    'name' => $w['name'],
                    'qty' => $w['qty'],
                    'cost' => $cost,
                ];
            })->values()->toArray();

            return [
                'total_qty' => $totalQty,
                'total_cost' => $totalCost,
                'by_warehouse' => $warehouseData,
            ];
        } catch (\Exception $e) {
            Log::error('getStockSummary error', ['error' => $e->getMessage()]);

            return [
                'total_qty' => 0,
                'total_cost' => 0,
                'by_warehouse' => [],
            ];
        }
    }

    /**
     * Получить сводку по товарам в транзитах
     */
    public function getTransitSummary(int $companyId, FinanceSettings $settings, $currencyService = null, string $displayCurrency = 'UZS'): array
    {
        $rubToUzs = $settings->rub_rate ?? 140;
        $baseCurrency = 'UZS';

        $result = [
            'orders_in_transit' => [
                'count' => 0,
                'amount' => 0,
                'by_marketplace' => [],
            ],
            'purchases_in_transit' => [
                'count' => 0,
                'amount' => 0,
            ],
            'total_amount' => 0,
        ];

        // 1. Uzum заказы в пути
        try {
            if (class_exists(\App\Models\UzumOrder::class)) {
                $uzumTransit = \App\Models\UzumOrder::whereHas('account', fn ($q) => $q->where('company_id', $companyId))
                    ->whereIn('status', ['waiting_pickup', 'accepted_uzum', 'in_supply', 'in_assembly'])
                    ->selectRaw('COUNT(*) as cnt, SUM(total_amount) as total')
                    ->first();

                $uzumCount = (int) ($uzumTransit?->cnt ?? 0);
                $uzumAmount = (float) ($uzumTransit?->total ?? 0);

                $result['orders_in_transit']['count'] += $uzumCount;
                $result['orders_in_transit']['amount'] += $uzumAmount;
                if ($uzumCount > 0) {
                    $result['orders_in_transit']['by_marketplace']['uzum'] = [
                        'count' => $uzumCount,
                        'amount' => $uzumAmount,
                        'currency' => 'UZS',
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::warning('Ошибка получения транзитных данных Uzum', ['error' => $e->getMessage()]);
        }

        // 2. WB заказы в пути
        try {
            if (class_exists(\App\Models\WildberriesOrder::class)) {
                $wbTransit = \App\Models\WildberriesOrder::whereHas('account', fn ($q) => $q->where('company_id', $companyId))
                    ->where('is_realization', false)
                    ->where('is_cancel', false)
                    ->selectRaw('COUNT(*) as cnt, SUM(COALESCE(for_pay, finished_price, total_price, 0)) as total_rub')
                    ->first();

                $wbCount = (int) ($wbTransit?->cnt ?? 0);
                $wbAmountRub = (float) ($wbTransit?->total_rub ?? 0);
                $wbAmountUzs = $wbAmountRub * $rubToUzs;

                $result['orders_in_transit']['count'] += $wbCount;
                $result['orders_in_transit']['amount'] += $wbAmountUzs;
                if ($wbCount > 0) {
                    $result['orders_in_transit']['by_marketplace']['wb'] = [
                        'count' => $wbCount,
                        'amount' => $wbAmountUzs,
                        'amount_original' => $wbAmountRub,
                        'currency' => 'RUB',
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::warning('Ошибка получения транзитных данных WB', ['error' => $e->getMessage()]);
        }

        // 3. Ozon заказы в пути
        try {
            if (class_exists(\App\Models\OzonOrder::class)) {
                $ozonTransit = \App\Models\OzonOrder::whereHas('account', fn ($q) => $q->where('company_id', $companyId))
                    ->inTransit()
                    ->selectRaw('COUNT(*) as cnt, SUM(COALESCE(total_price, 0)) as total_rub')
                    ->first();

                $ozonCount = (int) ($ozonTransit?->cnt ?? 0);
                $ozonAmountRub = (float) ($ozonTransit?->total_rub ?? 0);
                $ozonAmountUzs = $ozonAmountRub * $rubToUzs;

                $result['orders_in_transit']['count'] += $ozonCount;
                $result['orders_in_transit']['amount'] += $ozonAmountUzs;
                if ($ozonCount > 0) {
                    $result['orders_in_transit']['by_marketplace']['ozon'] = [
                        'count' => $ozonCount,
                        'amount' => $ozonAmountUzs,
                        'amount_original' => $ozonAmountRub,
                        'currency' => 'RUB',
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::warning('Ошибка получения транзитных данных Ozon', ['error' => $e->getMessage()]);
        }

        // 4. Закупки в пути
        try {
            if (class_exists(SupplierInvoice::class)) {
                $purchaseTransit = SupplierInvoice::byCompany($companyId)
                    ->whereIn('status', ['confirmed', 'partially_paid'])
                    ->selectRaw('COUNT(*) as cnt, SUM(total_amount - amount_paid) as total')
                    ->first();

                $result['purchases_in_transit']['count'] = (int) ($purchaseTransit?->cnt ?? 0);
                $result['purchases_in_transit']['amount'] = (float) ($purchaseTransit?->total ?? 0);
            }
        } catch (\Exception $e) {
            Log::warning('Ошибка получения данных о закупках в пути', ['error' => $e->getMessage()]);
        }

        $result['total_amount'] = $result['orders_in_transit']['amount'] + $result['purchases_in_transit']['amount'];

        // Конвертируем суммы в выбранную валюту
        if ($currencyService && $displayCurrency !== $baseCurrency) {
            $result['orders_in_transit']['amount'] = $currencyService->convert($result['orders_in_transit']['amount'], $baseCurrency, $displayCurrency);
            $result['purchases_in_transit']['amount'] = $currencyService->convert($result['purchases_in_transit']['amount'], $baseCurrency, $displayCurrency);
            $result['total_amount'] = $currencyService->convert($result['total_amount'], $baseCurrency, $displayCurrency);

            foreach ($result['orders_in_transit']['by_marketplace'] as $key => &$mp) {
                $mp['amount'] = $currencyService->convert($mp['amount'], $baseCurrency, $displayCurrency);
            }
        }

        return $result;
    }

    /**
     * Рассчитать себестоимость проданных товаров (COGS)
     */
    public function calculateCogs(int $companyId, Carbon $from, Carbon $to, float $rubToUzs): array
    {
        $financeSettings = FinanceSettings::getForCompany($companyId);

        $result = [
            'total' => 0,
            'total_items' => 0,
            'by_marketplace' => [],
            'gross_margin' => 0,
            'margin_percent' => 0,
        ];

        $totalRevenue = 0;

        // ========== 1. UZUM COGS ==========
        try {
            if (class_exists(\App\Models\UzumFinanceOrder::class)) {
                $uzumResult = $this->calculateUzumCogs($companyId, $from, $to, $financeSettings);
                if ($uzumResult) {
                    $result['by_marketplace']['uzum'] = $uzumResult;
                    $result['total'] += $uzumResult['cogs'];
                    $result['total_items'] += $uzumResult['items_count'];
                    $totalRevenue += $uzumResult['revenue'];
                }
            }
        } catch (\Exception $e) {
            Log::error('Uzum COGS calculation error', ['error' => $e->getMessage()]);
        }

        // ========== 2. WILDBERRIES COGS ==========
        try {
            if (class_exists(\App\Models\WildberriesOrder::class)) {
                $wbResult = $this->calculateWbCogs($companyId, $from, $to, $rubToUzs, $financeSettings);
                if ($wbResult) {
                    $result['by_marketplace']['wb'] = $wbResult;
                    $result['total'] += $wbResult['cogs'];
                    $result['total_items'] += $wbResult['items_count'];
                    $totalRevenue += $wbResult['revenue'];
                }
            }
        } catch (\Exception $e) {
            Log::error('WB COGS calculation error', ['error' => $e->getMessage()]);
        }

        // ========== 3. OZON COGS ==========
        try {
            if (class_exists(\App\Models\OzonOrder::class)) {
                $ozonResult = $this->calculateOzonCogs($companyId, $from, $to, $rubToUzs, $financeSettings);
                if ($ozonResult) {
                    $result['by_marketplace']['ozon'] = $ozonResult;
                    $result['total'] += $ozonResult['cogs'];
                    $result['total_items'] += $ozonResult['items_count'];
                    $totalRevenue += $ozonResult['revenue'];
                }
            }
        } catch (\Exception $e) {
            Log::error('Ozon COGS calculation error', ['error' => $e->getMessage()]);
        }

        // ========== 4. РУЧНЫЕ ПРОДАЖИ (Sale) COGS ==========
        try {
            if (class_exists(\App\Models\Sale::class)) {
                $offlineResult = $this->calculateOfflineCogs($companyId, $from, $to, $financeSettings);
                if ($offlineResult) {
                    $result['by_marketplace']['offline'] = $offlineResult;
                    $result['total'] += $offlineResult['cogs'];
                    $result['total_items'] += $offlineResult['items_count'];
                    $totalRevenue += $offlineResult['revenue'];
                }
            }
        } catch (\Exception $e) {
            Log::error('Offline COGS calculation error', ['error' => $e->getMessage()]);
        }

        // Рассчитываем общую маржу
        $result['gross_margin'] = $totalRevenue - $result['total'];
        $result['margin_percent'] = $totalRevenue > 0 ? round((($totalRevenue - $result['total']) / $totalRevenue) * 100, 1) : 0;
        $result['total_revenue'] = $totalRevenue;

        return $result;
    }

    /**
     * Расчёт COGS для Uzum
     */
    protected function calculateUzumCogs(int $companyId, Carbon $from, Carbon $to, FinanceSettings $financeSettings): ?array
    {
        $uzumOrders = \App\Models\UzumFinanceOrder::whereHas('account', fn ($q) => $q->where('company_id', $companyId))
            ->whereIn('status', ['TO_WITHDRAW', 'COMPLETED', 'PROCESSING'])
            ->where('status', '!=', 'CANCELED')
            ->where(function ($q) use ($from, $to) {
                $q->where(function ($sub) use ($from, $to) {
                    $sub->whereNotNull('date_issued')
                        ->whereDate('date_issued', '>=', $from)
                        ->whereDate('date_issued', '<=', $to);
                })
                    ->orWhere(function ($sub) use ($from, $to) {
                        $sub->whereNull('date_issued')
                            ->whereDate('order_date', '>=', $from)
                            ->whereDate('order_date', '<=', $to);
                    });
            })
            ->select('sku_id', 'offer_id', 'barcode', 'amount', 'purchase_price', 'marketplace_account_id')
            ->get();

        if ($uzumOrders->isEmpty()) {
            return null;
        }

        // Предзагрузка данных для устранения N+1 запросов
        $accountIds = $uzumOrders->pluck('marketplace_account_id')->unique()->values()->all();

        $offerIds = $uzumOrders->pluck('offer_id')->filter()->unique()->values()->all();
        $linksByOfferId = ! empty($offerIds)
            ? \App\Models\VariantMarketplaceLink::whereIn('marketplace_account_id', $accountIds)
                ->whereIn('external_offer_id', $offerIds)
                ->with('variant')
                ->get()
                ->groupBy(fn ($link) => $link->marketplace_account_id.':'.$link->external_offer_id)
            : collect();

        $barcodes = $uzumOrders->pluck('barcode')->filter()->unique()->values()->all();
        $linksByBarcode = ! empty($barcodes)
            ? \App\Models\VariantMarketplaceLink::whereIn('marketplace_account_id', $accountIds)
                ->whereIn('marketplace_barcode', $barcodes)
                ->with('variant')
                ->get()
                ->groupBy(fn ($link) => $link->marketplace_account_id.':'.$link->marketplace_barcode)
            : collect();

        $cogs = 0;
        $revenue = 0;
        $itemsCount = $uzumOrders->count();
        $withCogs = 0;
        $fromInternal = 0;
        $fromMarketplace = 0;

        foreach ($uzumOrders as $order) {
            $rev = (float) ($order->amount ?? 0);
            $revenue += $rev;

            $purchasePrice = $this->findPurchasePriceFromCache($order, $financeSettings, $linksByOfferId, $linksByBarcode);

            if ($purchasePrice !== null) {
                $cogs += $purchasePrice;
                $withCogs++;
                $fromInternal++;
            } elseif ($order->purchase_price) {
                $cogs += (float) $order->purchase_price;
                $withCogs++;
                $fromMarketplace++;
            }
        }

        return [
            'cogs' => $cogs,
            'items_count' => $itemsCount,
            'items_with_cogs' => $withCogs,
            'from_internal' => $fromInternal,
            'from_marketplace' => $fromMarketplace,
            'revenue' => $revenue,
            'margin' => $revenue - $cogs,
            'margin_percent' => $revenue > 0 ? round((($revenue - $cogs) / $revenue) * 100, 1) : 0,
            'currency' => 'UZS',
            'note' => $withCogs < $itemsCount ? 'Не все товары имеют закупочную цену' : null,
        ];
    }

    /**
     * Расчёт COGS для WB
     */
    protected function calculateWbCogs(int $companyId, Carbon $from, Carbon $to, float $rubToUzs, FinanceSettings $financeSettings): ?array
    {
        $wbOrders = \App\Models\WildberriesOrder::whereHas('account', fn ($q) => $q->where('company_id', $companyId))
            ->where('is_realization', true)
            ->where('is_cancel', false)
            ->where('is_return', false)
            ->whereDate('order_date', '>=', $from)
            ->whereDate('order_date', '<=', $to)
            ->select('barcode', 'nm_id', 'supplier_article', 'for_pay', 'finished_price', 'total_price', 'marketplace_account_id')
            ->get();

        if ($wbOrders->isEmpty()) {
            return null;
        }

        // Предзагрузка данных для устранения N+1 запросов
        $accountIds = $wbOrders->pluck('marketplace_account_id')->unique()->values()->all();

        $barcodes = $wbOrders->pluck('barcode')->filter()->unique()->values()->all();
        $linksByBarcode = ! empty($barcodes)
            ? \App\Models\VariantMarketplaceLink::whereIn('marketplace_account_id', $accountIds)
                ->whereIn('marketplace_barcode', $barcodes)
                ->with('variant')
                ->get()
                ->groupBy(fn ($link) => $link->marketplace_account_id.':'.$link->marketplace_barcode)
            : collect();

        $nmIds = $wbOrders->pluck('nm_id')->filter()->unique()->values()->all();
        $linksByNmId = ! empty($nmIds)
            ? \App\Models\VariantMarketplaceLink::whereIn('marketplace_account_id', $accountIds)
                ->whereHas('marketplaceProduct', fn ($q) => $q->whereIn('external_id', $nmIds))
                ->with(['variant', 'marketplaceProduct'])
                ->get()
                ->groupBy(fn ($link) => $link->marketplace_account_id.':'.($link->marketplaceProduct->external_id ?? ''))
            : collect();

        $skus = $wbOrders->pluck('supplier_article')->filter()->unique()->values()->all();
        $variantsBySku = ! empty($skus)
            ? \App\Models\ProductVariant::where('company_id', $companyId)
                ->whereIn('sku', $skus)
                ->get()
                ->keyBy('sku')
            : collect();

        $cogs = 0;
        $revenueRub = 0;
        $itemsCount = $wbOrders->count();
        $withCogs = 0;
        $fromInternal = 0;

        foreach ($wbOrders as $order) {
            $rev = (float) ($order->for_pay ?? $order->finished_price ?? $order->total_price ?? 0);
            $revenueRub += $rev;

            $purchasePrice = null;

            if ($order->barcode) {
                $key = $order->marketplace_account_id.':'.$order->barcode;
                $link = $linksByBarcode->get($key)?->first();
                if ($link?->variant?->purchase_price) {
                    $purchasePrice = $link->variant->getPurchasePriceInBase($financeSettings);
                    $fromInternal++;
                }
            }

            if (! $purchasePrice && $order->nm_id) {
                $key = $order->marketplace_account_id.':'.$order->nm_id;
                $link = $linksByNmId->get($key)?->first();
                if ($link?->variant?->purchase_price) {
                    $purchasePrice = $link->variant->getPurchasePriceInBase($financeSettings);
                    $fromInternal++;
                }
            }

            if (! $purchasePrice && $order->supplier_article) {
                $variant = $variantsBySku->get($order->supplier_article);
                if ($variant?->purchase_price) {
                    $purchasePrice = $variant->getPurchasePriceInBase($financeSettings);
                    $fromInternal++;
                }
            }

            if ($purchasePrice) {
                $cogs += (float) $purchasePrice;
                $withCogs++;
            }
        }

        $revenueUzs = $revenueRub * $rubToUzs;

        return [
            'cogs' => $cogs,
            'items_count' => $itemsCount,
            'items_with_cogs' => $withCogs,
            'from_internal' => $fromInternal,
            'revenue' => $revenueUzs,
            'revenue_rub' => $revenueRub,
            'margin' => $revenueUzs - $cogs,
            'margin_percent' => $revenueUzs > 0 ? round((($revenueUzs - $cogs) / $revenueUzs) * 100, 1) : 0,
            'currency' => 'UZS',
            'note' => $withCogs < $itemsCount ? 'Не все товары связаны с внутренними' : null,
        ];
    }

    /**
     * Расчёт COGS для Ozon
     */
    protected function calculateOzonCogs(int $companyId, Carbon $from, Carbon $to, float $rubToUzs, FinanceSettings $financeSettings): ?array
    {
        $ozonOrders = \App\Models\OzonOrder::whereHas('account', fn ($q) => $q->where('company_id', $companyId))
            ->whereIn('status', ['delivered', 'completed'])
            ->whereDate('created_at_ozon', '>=', $from)
            ->whereDate('created_at_ozon', '<=', $to)
            ->select('offer_id', 'sku', 'total_price', 'marketplace_account_id')
            ->get();

        if ($ozonOrders->isEmpty()) {
            return null;
        }

        // Предзагрузка данных для устранения N+1 запросов
        $accountIds = $ozonOrders->pluck('marketplace_account_id')->unique()->values()->all();

        $offerIds = $ozonOrders->pluck('offer_id')->filter()->unique()->values()->all();
        $linksByOfferId = ! empty($offerIds)
            ? \App\Models\VariantMarketplaceLink::whereIn('marketplace_account_id', $accountIds)
                ->whereIn('external_offer_id', $offerIds)
                ->with('variant')
                ->get()
                ->groupBy(fn ($link) => $link->marketplace_account_id.':'.$link->external_offer_id)
            : collect();

        $skus = $ozonOrders->pluck('sku')->filter()->unique()->values()->all();
        $variantsBySku = ! empty($skus)
            ? \App\Models\ProductVariant::where('company_id', $companyId)
                ->whereIn('sku', $skus)
                ->get()
                ->keyBy('sku')
            : collect();

        $cogs = 0;
        $revenueRub = 0;
        $itemsCount = $ozonOrders->count();
        $withCogs = 0;
        $fromInternal = 0;

        foreach ($ozonOrders as $order) {
            $rev = (float) ($order->total_price ?? 0);
            $revenueRub += $rev;

            $purchasePrice = null;

            if ($order->offer_id) {
                $key = $order->marketplace_account_id.':'.$order->offer_id;
                $link = $linksByOfferId->get($key)?->first();
                if ($link?->variant?->purchase_price) {
                    $purchasePrice = $link->variant->getPurchasePriceInBase($financeSettings);
                    $fromInternal++;
                }
            }

            if (! $purchasePrice && $order->sku) {
                $variant = $variantsBySku->get($order->sku);
                if ($variant?->purchase_price) {
                    $purchasePrice = $variant->getPurchasePriceInBase($financeSettings);
                    $fromInternal++;
                }
            }

            if ($purchasePrice) {
                $cogs += (float) $purchasePrice;
                $withCogs++;
            }
        }

        $revenueUzs = $revenueRub * $rubToUzs;

        return [
            'cogs' => $cogs,
            'items_count' => $itemsCount,
            'items_with_cogs' => $withCogs,
            'from_internal' => $fromInternal,
            'revenue' => $revenueUzs,
            'revenue_rub' => $revenueRub,
            'margin' => $revenueUzs - $cogs,
            'margin_percent' => $revenueUzs > 0 ? round((($revenueUzs - $cogs) / $revenueUzs) * 100, 1) : 0,
            'currency' => 'UZS',
            'note' => $withCogs < $itemsCount ? 'Не все товары связаны с внутренними' : null,
        ];
    }

    /**
     * Расчёт COGS для ручных продаж (offline)
     */
    protected function calculateOfflineCogs(int $companyId, Carbon $from, Carbon $to, FinanceSettings $financeSettings): ?array
    {
        $offlineSales = \App\Models\Sale::byCompany($companyId)
            ->where('type', 'manual')
            ->where('status', 'completed')
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to)
            ->with(['items.productVariant'])
            ->get();

        if ($offlineSales->isEmpty()) {
            return null;
        }

        $cogs = 0;
        $revenue = 0;
        $itemsCount = 0;
        $withCogs = 0;
        $fromInternal = 0;
        $fromSaleItem = 0;

        foreach ($offlineSales as $sale) {
            $revenue += $sale->total_amount;
            foreach ($sale->items as $item) {
                $itemsCount++;
                $costPrice = null;

                if ($item->productVariant?->purchase_price) {
                    $costPrice = $item->productVariant->getPurchasePriceInBase($financeSettings);
                    $fromInternal++;
                } elseif ($item->cost_price) {
                    $costPrice = (float) $item->cost_price;
                    $fromSaleItem++;
                }

                if ($costPrice) {
                    $cogs += $costPrice * $item->quantity;
                    $withCogs++;
                }
            }
        }

        if ($itemsCount === 0) {
            return null;
        }

        return [
            'cogs' => $cogs,
            'items_count' => $itemsCount,
            'items_with_cogs' => $withCogs,
            'from_internal' => $fromInternal,
            'from_sale_item' => $fromSaleItem,
            'revenue' => $revenue,
            'margin' => $revenue - $cogs,
            'margin_percent' => $revenue > 0 ? round((($revenue - $cogs) / $revenue) * 100, 1) : 0,
            'currency' => 'UZS',
            'note' => $withCogs < $itemsCount ? 'Не все товары имеют закупочную цену' : null,
        ];
    }

    /**
     * Найти закупочную цену через предзагруженные коллекции (без N+1)
     */
    protected function findPurchasePriceFromCache(
        $order,
        FinanceSettings $financeSettings,
        $linksByOfferId,
        $linksByBarcode
    ): ?float {
        // 1. По offer_id
        if ($order->offer_id) {
            $key = $order->marketplace_account_id.':'.$order->offer_id;
            $link = $linksByOfferId->get($key)?->first();
            if ($link?->variant?->purchase_price) {
                return $link->variant->getPurchasePriceInBase($financeSettings);
            }
        }

        // 2. По barcode
        if (! empty($order->barcode)) {
            $key = $order->marketplace_account_id.':'.$order->barcode;
            $link = $linksByBarcode->get($key)?->first();
            if ($link?->variant?->purchase_price) {
                return $link->variant->getPurchasePriceInBase($financeSettings);
            }
        }

        return null;
    }
}
