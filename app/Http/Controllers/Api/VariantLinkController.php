<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceAccount;
use App\Models\MarketplaceProduct;
use App\Models\ProductVariant;
use App\Models\VariantMarketplaceLink;
use App\Models\WildberriesProduct;
use App\Services\Stock\StockSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Контроллер для управления связями товаров и синхронизацией остатков
 */
class VariantLinkController extends Controller
{
    public function __construct(
        protected StockSyncService $stockSyncService
    ) {}

    /**
     * Привязать вариант товара к карточке на маркетплейсе
     */
    public function linkVariant(Request $request, MarketplaceAccount $account, int $marketplaceProductId): JsonResponse
    {
        $this->authorizeAccount($request, $account);

        $validated = $request->validate([
            'product_variant_id' => 'required|integer|exists:product_variants,id',
            'external_sku_id' => 'nullable|string', // For Uzum SKU-level linking
            'marketplace_barcode' => 'nullable|string', // Баркод товара на маркетплейсе (может отличаться от внутреннего)
            'sync_stock_enabled' => 'boolean',
            'sync_price_enabled' => 'boolean',
        ]);

        $variant = ProductVariant::where('company_id', $account->company_id)
            ->findOrFail($validated['product_variant_id']);

        // Determine the correct product model based on marketplace type
        if ($account->isWildberries()) {
            // For WB, use WildberriesProduct model
            $mpProduct = WildberriesProduct::where('marketplace_account_id', $account->id)
                ->findOrFail($marketplaceProductId);
            $externalOfferId = $mpProduct->nm_id;
            // CRITICAL: Store barcode (not supplier_article) to identify exact WB characteristic
            $externalSku = $mpProduct->barcode ?? $variant->barcode ?? $variant->sku;
        } elseif ($account->marketplace === 'ozon') {
            // For Ozon, use OzonProduct model
            $mpProduct = \App\Models\OzonProduct::where('marketplace_account_id', $account->id)
                ->findOrFail($marketplaceProductId);
            $externalOfferId = $mpProduct->external_product_id;
            $externalSku = $mpProduct->external_offer_id ?? $mpProduct->external_product_id;
        } else {
            // For Uzum, Yandex Market, and others, use MarketplaceProduct
            $mpProduct = MarketplaceProduct::where('marketplace_account_id', $account->id)
                ->findOrFail($marketplaceProductId);
            $externalOfferId = $mpProduct->external_offer_id;
            $externalSku = $mpProduct->external_sku ?? $variant->sku;
        }

        // Для Uzum: автоматически получаем marketplace_barcode из skuList, если не передан вручную
        $marketplaceBarcode = $validated['marketplace_barcode'] ?? null;
        if (!$marketplaceBarcode && $account->marketplace === 'uzum') {
            $skuList = $mpProduct->raw_payload['skuList'] ?? [];
            $externalSkuId = $validated['external_sku_id'] ?? null;

            foreach ($skuList as $sku) {
                // Ищем SKU по ID или берём первый с баркодом
                if ($externalSkuId && (string)($sku['skuId'] ?? '') === $externalSkuId) {
                    $marketplaceBarcode = $sku['barcode'] ?? null;
                    break;
                }
            }
            // Если не нашли по ID, берём первый баркод
            if (!$marketplaceBarcode && !empty($skuList[0]['barcode'])) {
                $marketplaceBarcode = $skuList[0]['barcode'];
            }
        }

        // Build unique key for the link (include SKU ID for multi-SKU products like Uzum)
        $linkKey = [
            'product_variant_id' => $variant->id,
            'marketplace_product_id' => $mpProduct->id,
            'marketplace_code' => $account->marketplace,
        ];
        
        // If external_sku_id is provided, include it in the key for unique SKU-level links
        if (!empty($validated['external_sku_id'])) {
            $linkKey['external_sku_id'] = $validated['external_sku_id'];
        }

        // Создать или обновить связь
        $link = VariantMarketplaceLink::updateOrCreate(
            $linkKey,
            [
                'company_id' => $account->company_id,
                'marketplace_account_id' => $account->id,
                'external_offer_id' => $externalOfferId,
                'external_sku_id' => $validated['external_sku_id'] ?? null,
                'external_sku' => $externalSku,
                'marketplace_barcode' => $marketplaceBarcode, // Баркод маркетплейса (автозаполнение из API или ручной ввод)
                'is_active' => true,
                'sync_stock_enabled' => $validated['sync_stock_enabled'] ?? true,
                'sync_price_enabled' => $validated['sync_price_enabled'] ?? false,
            ]
        );

        // Автоматическая синхронизация остатков после привязки (если включена в настройках аккаунта)
        $syncResult = null;
        $autoSyncEnabled = $account->isAutoSyncOnLinkEnabled();

        if ($link->sync_stock_enabled && $autoSyncEnabled) {
            try {
                $syncResult = $this->stockSyncService->syncLinkStock($link);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning('Auto stock sync after linking failed', [
                    'link_id' => $link->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $warehouseStock = $variant->getTotalWarehouseStock();

        return response()->json([
            'success' => true,
            'message' => 'Товар успешно привязан' . ($syncResult ? ' и остатки синхронизированы' : ''),
            'link' => $link->load(['variant']),
            'stock_synced' => $syncResult !== null,
            'current_stock' => (int) $warehouseStock,
        ]);
    }

    /**
     * Отвязать вариант от карточки
     */
    public function unlinkVariant(Request $request, MarketplaceAccount $account, int $marketplaceProductId): JsonResponse
    {
        $this->authorizeAccount($request, $account);
        
        $externalSkuId = $request->input('external_sku_id');

        $query = VariantMarketplaceLink::where('marketplace_account_id', $account->id)
            ->where('marketplace_product_id', $marketplaceProductId)
            ->where('is_active', true); // Only target active links
        
        // If external_sku_id is provided, filter by it (for Uzum SKU-level unlinking)
        if ($externalSkuId) {
            $query->where('external_sku_id', $externalSkuId);
        }
        
        // Get ALL matching links (not just first) to handle duplicate links
        $links = $query->get();

        if ($links->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Связь не найдена',
            ], 404);
        }

        // Deactivate ALL matching links
        $deactivatedCount = 0;
        foreach ($links as $link) {
            $link->update(['is_active' => false]);
            $deactivatedCount++;
        }

        $message = $deactivatedCount === 1
            ? 'Связь отключена'
            : "Отключено связей: {$deactivatedCount}";

        return response()->json([
            'success' => true,
            'message' => $message,
            'deactivated_count' => $deactivatedCount,
        ]);
    }

    /**
     * Получить связи для товара на маркетплейсе
     */
    public function getProductLinks(Request $request, MarketplaceAccount $account, int $marketplaceProductId): JsonResponse
    {
        $this->authorizeAccount($request, $account);

        $links = VariantMarketplaceLink::where('marketplace_product_id', $marketplaceProductId)
            ->where('is_active', true)
            ->with(['variant.product', 'variant.mainImage'])
            ->get()
            ->map(function($link) {
                $warehouseStock = $link->variant ? $link->variant->getTotalWarehouseStock() : 0;
                return [
                    'id' => $link->id,
                    'external_sku_id' => $link->external_sku_id,
                    'external_sku' => $link->external_sku,
                    'marketplace_barcode' => $link->marketplace_barcode, // Баркод маркетплейса
                    'variant' => $link->variant ? [
                        'id' => $link->variant->id,
                        'sku' => $link->variant->sku,
                        'name' => $link->variant->product?->name,
                        'stock' => (int) $warehouseStock,
                        'warehouse_stock' => (int) $warehouseStock,
                        'stock_default' => $link->variant->stock_default,
                        'options' => $link->variant->option_values_summary,
                        'barcode' => $link->variant->barcode,
                    ] : null,
                ];
            });

        return response()->json([
            'success' => true,
            'links' => $links,
        ]);
    }

    /**
     * Синхронизировать остаток одного товара
     */
    public function syncProductStock(Request $request, MarketplaceAccount $account, int $marketplaceProductId): JsonResponse
    {
        $this->authorizeAccount($request, $account);

        $link = VariantMarketplaceLink::where('marketplace_product_id', $marketplaceProductId)
            ->where('marketplace_account_id', $account->id) // Prevent ID collision with other marketplaces
            ->where('is_active', true)
            ->where('sync_stock_enabled', true)
            ->with('variant')
            ->first();

        if (!$link) {
            return response()->json([
                'success' => false,
                'message' => 'Нет активной связи для синхронизации',
            ], 404);
        }

        try {
            $result = $this->stockSyncService->syncLinkStock($link);
            
            return response()->json([
                'success' => true,
                'message' => 'Остаток синхронизирован',
                'stock' => $link->getCurrentStock(),
                'result' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Синхронизировать все остатки для аккаунта
     */
    public function syncAllStocks(Request $request, MarketplaceAccount $account): JsonResponse
    {
        $this->authorizeAccount($request, $account);

        try {
            $result = $this->stockSyncService->syncAllStocksForAccount($account);
            
            return response()->json([
                'success' => true,
                'message' => "Синхронизировано: {$result['success']} из {$result['total']}",
                'result' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Поиск вариантов товаров для привязки
     * Если вариантов нет, ищет по Products и предлагает их
     */
    public function searchVariants(Request $request): JsonResponse
    {
        $user = $request->user();
        $companyId = $user->company_id;
        $query = $request->input('q', '');

        // First try to find variants
        $variants = ProductVariant::query()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->where('is_deleted', false)
            ->where(function ($q) use ($query) {
                $q->where('sku', 'like', "%{$query}%")
                    ->orWhere('barcode', 'like', "%{$query}%")
                    ->orWhere('option_values_summary', 'like', "%{$query}%")
                    ->orWhereHas('product', function ($pq) use ($query) {
                        $pq->where('name', 'like', "%{$query}%");
                    });
            })
            ->with(['product:id,name', 'mainImage'])
            ->limit(20)
            ->get();

        // If no variants found, search in Products and offer to create default variant
        if ($variants->isEmpty()) {
            $products = \App\Models\Product::query()
                ->where('company_id', $companyId)
                ->where(function ($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                        ->orWhere('article', 'like', "%{$query}%")
                        ->orWhere('brand_name', 'like', "%{$query}%");
                })
                ->limit(20)
                ->get();

            // Auto-create default variants for products without variants
            foreach ($products as $product) {
                $existingVariant = ProductVariant::where('product_id', $product->id)->first();
                if (!$existingVariant) {
                    ProductVariant::create([
                        'company_id' => $companyId,
                        'product_id' => $product->id,
                        'sku' => $product->article ?: 'SKU-' . $product->id,
                        'is_active' => true,
                        'is_deleted' => false,
                        'option_values_summary' => 'Основной',
                        'stock_default' => 0,
                        'price_default' => 0,
                    ]);
                }
            }

            // Reload variants after creation
            $variants = ProductVariant::query()
                ->where('company_id', $companyId)
                ->where('is_active', true)
                ->where('is_deleted', false)
                ->whereIn('product_id', $products->pluck('id'))
                ->with(['product:id,name', 'mainImage'])
                ->limit(20)
                ->get();
        }

        return response()->json([
            'success' => true,
            'variants' => $variants->map(function($v) {
                $warehouseStock = $v->getTotalWarehouseStock();
                return [
                    'id' => $v->id,
                    'sku' => $v->sku,
                    'barcode' => $v->barcode,
                    'name' => $v->product?->name,
                    'product' => $v->product ? [
                        'id' => $v->product->id,
                        'name' => $v->product->name,
                    ] : null,
                    'option_values_summary' => $v->option_values_summary,
                    'options' => $v->option_values_summary,
                    'stock' => (int) $warehouseStock,
                    'stock_default' => $v->stock_default,
                    'warehouse_stock' => (int) $warehouseStock,
                    'price' => $v->price_default,
                    'price_default' => $v->price_default,
                    'image' => $v->mainImage?->url,
                ];
            }),
        ]);
    }

    /**
     * Получить информацию о связи варианта
     */
    public function getVariantLinks(Request $request, int $variantId): JsonResponse
    {
        $user = $request->user();
        
        $variant = ProductVariant::where('company_id', $user->company_id)
            ->findOrFail($variantId);

        $links = $variant->marketplaceLinks()
            ->where('is_active', true)
            ->with(['account', 'marketplaceProduct'])
            ->get();

        $warehouseStock = $variant->getTotalWarehouseStock();

        return response()->json([
            'success' => true,
            'variant' => [
                'id' => $variant->id,
                'sku' => $variant->sku,
                'stock' => (int) $warehouseStock,
                'warehouse_stock' => (int) $warehouseStock,
                'stock_default' => $variant->stock_default,
            ],
            'links' => $links,
        ]);
    }

    /**
     * Автоматически привязать товары маркетплейса к внутренним вариантам
     */
    public function autoLink(Request $request, MarketplaceAccount $account): JsonResponse
    {
        $this->authorizeAccount($request, $account);

        $autoLinkService = app(\App\Services\Marketplaces\AutoLinkService::class);
        $stats = $autoLinkService->autoLinkForAccount($account);

        $newlyLinked = $stats['linked_by_barcode'] + $stats['linked_by_sku'] + $stats['linked_by_article'];

        return response()->json([
            'success' => true,
            'message' => $newlyLinked > 0
                ? "Привязано {$newlyLinked} товаров"
                : 'Новых привязок не найдено',
            'stats' => $stats,
            'newly_linked' => $newlyLinked,
        ]);
    }

    protected function authorizeAccount(Request $request, MarketplaceAccount $account): void
    {
        if (!$request->user()->hasCompanyAccess($account->company_id)) {
            abort(403, 'Доступ запрещён');
        }
    }
}
