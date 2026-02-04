<?php

namespace App\Http\Controllers\Api\Risment;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Warehouse\Sku;
use App\Models\Warehouse\StockLedger;
use App\Models\Warehouse\Warehouse;
use App\Models\WbOrder;
use App\Models\UzumOrder;
use App\Models\OzonOrder;
use App\Models\YandexMarketOrder;
use App\Models\OfflineSale;
use App\Models\MarketplaceAccount;
use App\Services\Risment\RismentWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class IntegrationController extends Controller
{
    public function __construct(
        protected RismentWebhookService $webhookService,
    ) {}

    /**
     * POST /api/v1/integration/products
     * RISMENT creates a product in SellerMind
     */
    public function createProduct(Request $request): JsonResponse
    {
        $company = $this->getCompany($request);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'article' => 'nullable|string|max:100',
            'brand_name' => 'nullable|string|max:255',
            'sku' => 'nullable|string|max:100',
            'barcode' => 'nullable|string|max:100',
            'price' => 'required|numeric|min:0',
            'purchase_price' => 'nullable|numeric|min:0',
            'weight_g' => 'nullable|integer|min:0',
            'length_mm' => 'nullable|integer|min:0',
            'width_mm' => 'nullable|integer|min:0',
            'height_mm' => 'nullable|integer|min:0',
            'description' => 'nullable|string',
            'category_id' => 'nullable|integer',
            'country_of_origin' => 'nullable|string|max:100',
            'is_active' => 'nullable|boolean',
        ]);

        DB::beginTransaction();
        try {
            $product = Product::create([
                'company_id' => $company->id,
                'name' => $validated['name'],
                'article' => $validated['article'] ?? null,
                'brand_name' => $validated['brand_name'] ?? null,
                'category_id' => $validated['category_id'] ?? null,
                'description_short' => $validated['description'] ?? null,
                'country_of_origin' => $validated['country_of_origin'] ?? null,
                'package_weight_g' => $validated['weight_g'] ?? null,
                'package_length_mm' => $validated['length_mm'] ?? null,
                'package_width_mm' => $validated['width_mm'] ?? null,
                'package_height_mm' => $validated['height_mm'] ?? null,
                'is_active' => $validated['is_active'] ?? true,
            ]);

            $variant = ProductVariant::create([
                'product_id' => $product->id,
                'company_id' => $company->id,
                'sku' => $validated['sku'] ?? null,
                'barcode' => $validated['barcode'] ?? null,
                'price_default' => $validated['price'],
                'purchase_price' => $validated['purchase_price'] ?? null,
                'weight_g' => $validated['weight_g'] ?? null,
                'length_mm' => $validated['length_mm'] ?? null,
                'width_mm' => $validated['width_mm'] ?? null,
                'height_mm' => $validated['height_mm'] ?? null,
                'stock_default' => 0,
                'is_active' => $validated['is_active'] ?? true,
            ]);

            // Create warehouse SKU for stock tracking
            Sku::create([
                'product_id' => $product->id,
                'product_variant_id' => $variant->id,
                'company_id' => $company->id,
                'sku_code' => $validated['sku'] ?? "V{$variant->id}",
                'barcode_ean13' => $validated['barcode'] ?? null,
                'weight_g' => $validated['weight_g'] ?? null,
                'length_mm' => $validated['length_mm'] ?? null,
                'width_mm' => $validated['width_mm'] ?? null,
                'height_mm' => $validated['height_mm'] ?? null,
                'is_active' => true,
            ]);

            DB::commit();

            $this->webhookService->dispatch($company->id, 'product.created', [
                'product_id' => $product->id,
                'variant_id' => $variant->id,
                'name' => $product->name,
                'sku' => $variant->sku,
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $variant->id,
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'sku' => $variant->sku,
                    'barcode' => $variant->barcode,
                    'price' => $variant->price_default,
                    'is_active' => $product->is_active,
                    'created_at' => $product->created_at,
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Risment: Failed to create product', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create product',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * PUT /api/v1/integration/products/{id}
     * RISMENT updates a product in SellerMind
     */
    public function updateProduct(Request $request, int $id): JsonResponse
    {
        $company = $this->getCompany($request);

        $variant = ProductVariant::where('id', $id)
            ->where('company_id', $company->id)
            ->firstOrFail();

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'article' => 'sometimes|string|max:100',
            'brand_name' => 'sometimes|string|max:255',
            'sku' => 'sometimes|string|max:100',
            'barcode' => 'sometimes|string|max:100',
            'price' => 'sometimes|numeric|min:0',
            'purchase_price' => 'sometimes|numeric|min:0',
            'weight_g' => 'sometimes|integer|min:0',
            'length_mm' => 'sometimes|integer|min:0',
            'width_mm' => 'sometimes|integer|min:0',
            'height_mm' => 'sometimes|integer|min:0',
            'description' => 'sometimes|string',
            'country_of_origin' => 'sometimes|string|max:100',
            'is_active' => 'sometimes|boolean',
        ]);

        DB::beginTransaction();
        try {
            // Update product-level fields
            $productFields = [];
            if (isset($validated['name'])) $productFields['name'] = $validated['name'];
            if (isset($validated['article'])) $productFields['article'] = $validated['article'];
            if (isset($validated['brand_name'])) $productFields['brand_name'] = $validated['brand_name'];
            if (isset($validated['description'])) $productFields['description_short'] = $validated['description'];
            if (isset($validated['country_of_origin'])) $productFields['country_of_origin'] = $validated['country_of_origin'];
            if (isset($validated['weight_g'])) $productFields['package_weight_g'] = $validated['weight_g'];
            if (isset($validated['length_mm'])) $productFields['package_length_mm'] = $validated['length_mm'];
            if (isset($validated['width_mm'])) $productFields['package_width_mm'] = $validated['width_mm'];
            if (isset($validated['height_mm'])) $productFields['package_height_mm'] = $validated['height_mm'];
            if (isset($validated['is_active'])) $productFields['is_active'] = $validated['is_active'];

            if (!empty($productFields)) {
                $variant->product->update($productFields);
            }

            // Update variant-level fields
            $variantFields = [];
            if (isset($validated['sku'])) $variantFields['sku'] = $validated['sku'];
            if (isset($validated['barcode'])) $variantFields['barcode'] = $validated['barcode'];
            if (isset($validated['price'])) $variantFields['price_default'] = $validated['price'];
            if (isset($validated['purchase_price'])) $variantFields['purchase_price'] = $validated['purchase_price'];
            if (isset($validated['weight_g'])) $variantFields['weight_g'] = $validated['weight_g'];
            if (isset($validated['length_mm'])) $variantFields['length_mm'] = $validated['length_mm'];
            if (isset($validated['width_mm'])) $variantFields['width_mm'] = $validated['width_mm'];
            if (isset($validated['height_mm'])) $variantFields['height_mm'] = $validated['height_mm'];
            if (isset($validated['is_active'])) $variantFields['is_active'] = $validated['is_active'];

            if (!empty($variantFields)) {
                $variant->update($variantFields);
            }

            DB::commit();

            $variant->refresh();
            $variant->load('product');

            $this->webhookService->dispatch($company->id, 'product.updated', [
                'product_id' => $variant->product_id,
                'variant_id' => $variant->id,
                'name' => $variant->product->name,
                'sku' => $variant->sku,
                'updated_fields' => array_keys(array_merge($productFields, $variantFields)),
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $variant->id,
                    'product_id' => $variant->product_id,
                    'name' => $variant->product->name,
                    'sku' => $variant->sku,
                    'barcode' => $variant->barcode,
                    'price' => $variant->price_default,
                    'is_active' => $variant->product->is_active,
                    'updated_at' => $variant->updated_at,
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Risment: Failed to update product', [
                'variant_id' => $id,
                'company_id' => $company->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update product',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * POST /api/v1/integration/stock/update
     * RISMENT updates stock levels
     */
    public function updateStock(Request $request): JsonResponse
    {
        $company = $this->getCompany($request);

        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.variant_id' => 'required|integer',
            'items.*.warehouse_id' => 'required|integer',
            'items.*.quantity' => 'required|numeric',
            'items.*.type' => 'required|in:set,adjust',
        ]);

        $results = [];
        $errors = [];

        foreach ($validated['items'] as $index => $item) {
            try {
                $variant = ProductVariant::where('id', $item['variant_id'])
                    ->where('company_id', $company->id)
                    ->first();

                if (!$variant) {
                    $errors[] = [
                        'index' => $index,
                        'variant_id' => $item['variant_id'],
                        'error' => 'Variant not found',
                    ];
                    continue;
                }

                $warehouse = Warehouse::where('id', $item['warehouse_id'])
                    ->where('company_id', $company->id)
                    ->first();

                if (!$warehouse) {
                    $errors[] = [
                        'index' => $index,
                        'warehouse_id' => $item['warehouse_id'],
                        'error' => 'Warehouse not found',
                    ];
                    continue;
                }

                $sku = Sku::where('product_variant_id', $variant->id)->first();
                if (!$sku) {
                    $sku = Sku::create([
                        'product_id' => $variant->product_id,
                        'product_variant_id' => $variant->id,
                        'company_id' => $company->id,
                        'sku_code' => $variant->sku ?? "V{$variant->id}",
                        'barcode_ean13' => $variant->barcode,
                        'is_active' => true,
                    ]);
                }

                $currentBalance = $sku->getCurrentBalance($warehouse->id);

                if ($item['type'] === 'set') {
                    $qtyDelta = $item['quantity'] - $currentBalance;
                } else {
                    $qtyDelta = $item['quantity'];
                }

                if ($qtyDelta != 0) {
                    StockLedger::create([
                        'company_id' => $company->id,
                        'occurred_at' => now(),
                        'warehouse_id' => $warehouse->id,
                        'sku_id' => $sku->id,
                        'qty_delta' => $qtyDelta,
                        'source_type' => 'risment_stock_update',
                        'source_id' => $variant->id,
                    ]);

                    // Update variant's stock_default using saveQuietly to avoid observer double-writes
                    $newTotal = StockLedger::where('sku_id', $sku->id)->sum('qty_delta');
                    $variant->stock_default = max(0, (int) $newTotal);
                    $variant->saveQuietly();
                }

                $newBalance = $sku->getCurrentBalance($warehouse->id);

                $results[] = [
                    'variant_id' => $variant->id,
                    'warehouse_id' => $warehouse->id,
                    'previous_quantity' => $currentBalance,
                    'new_quantity' => $newBalance,
                    'delta' => $qtyDelta,
                ];

            } catch (\Exception $e) {
                Log::error('Risment: Stock update item failed', [
                    'index' => $index,
                    'item' => $item,
                    'error' => $e->getMessage(),
                ]);
                $errors[] = [
                    'index' => $index,
                    'variant_id' => $item['variant_id'],
                    'error' => config('app.debug') ? $e->getMessage() : 'Internal error',
                ];
            }
        }

        if (!empty($results)) {
            $this->webhookService->dispatch($company->id, 'stock.updated', [
                'items' => $results,
                'updated_at' => now()->toIso8601String(),
            ]);
        }

        return response()->json([
            'success' => empty($errors),
            'data' => [
                'updated' => $results,
                'errors' => $errors,
            ],
        ], empty($errors) ? 200 : 207);
    }

    /**
     * GET /api/v1/integration/orders
     * RISMENT fetches orders for fulfillment/shipment
     */
    public function getOrders(Request $request): JsonResponse
    {
        $company = $this->getCompany($request);

        $request->validate([
            'status' => 'nullable|string',
            'marketplace' => 'nullable|string|in:wb,uzum,ozon,ym,offline',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
        ]);

        $perPage = $request->integer('per_page', 20);
        $orders = collect();

        // Get marketplace account IDs for this company
        $accountIds = MarketplaceAccount::where('company_id', $company->id)
            ->where('is_active', true)
            ->pluck('id', 'marketplace');

        $statusFilter = $request->input('status');
        $marketplaceFilter = $request->input('marketplace');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        // Helper to build marketplace order queries
        $buildQuery = function ($model, $marketplace, $accountId) use ($statusFilter, $dateFrom, $dateTo) {
            $query = $model::where('marketplace_account_id', $accountId);

            if ($statusFilter) {
                $query->where('status_normalized', $statusFilter);
            }
            if ($dateFrom) {
                $query->where('ordered_at', '>=', $dateFrom);
            }
            if ($dateTo) {
                $query->where('ordered_at', '<=', $dateTo . ' 23:59:59');
            }

            return $query;
        };

        // Wildberries orders
        if ((!$marketplaceFilter || $marketplaceFilter === 'wb') && isset($accountIds['wb'])) {
            $wbOrders = $buildQuery(WbOrder::class, 'wb', $accountIds['wb'])
                ->orderByDesc('ordered_at')
                ->limit(200)
                ->get()
                ->map(fn($o) => $this->formatOrder($o, 'wb'));
            $orders = $orders->merge($wbOrders);
        }

        // Uzum orders
        if ((!$marketplaceFilter || $marketplaceFilter === 'uzum') && isset($accountIds['uzum'])) {
            $uzumOrders = $buildQuery(UzumOrder::class, 'uzum', $accountIds['uzum'])
                ->orderByDesc('ordered_at')
                ->limit(200)
                ->get()
                ->map(fn($o) => $this->formatOrder($o, 'uzum'));
            $orders = $orders->merge($uzumOrders);
        }

        // Ozon orders
        if ((!$marketplaceFilter || $marketplaceFilter === 'ozon') && isset($accountIds['ozon'])) {
            $ozonOrders = $buildQuery(OzonOrder::class, 'ozon', $accountIds['ozon'])
                ->orderByDesc('ordered_at')
                ->limit(200)
                ->get()
                ->map(fn($o) => $this->formatOrder($o, 'ozon'));
            $orders = $orders->merge($ozonOrders);
        }

        // Yandex Market orders
        if ((!$marketplaceFilter || $marketplaceFilter === 'ym') && isset($accountIds['ym'])) {
            $ymOrders = $buildQuery(YandexMarketOrder::class, 'ym', $accountIds['ym'])
                ->orderByDesc('ordered_at')
                ->limit(200)
                ->get()
                ->map(fn($o) => $this->formatOrder($o, 'ym'));
            $orders = $orders->merge($ymOrders);
        }

        // Offline sales (fulfillment-type)
        if (!$marketplaceFilter || $marketplaceFilter === 'offline') {
            $offlineQuery = OfflineSale::where('company_id', $company->id);
            if ($statusFilter) {
                $offlineQuery->where('status', $statusFilter);
            }
            if ($dateFrom) {
                $offlineQuery->where('sale_date', '>=', $dateFrom);
            }
            if ($dateTo) {
                $offlineQuery->where('sale_date', '<=', $dateTo);
            }
            $offlineOrders = $offlineQuery
                ->with('items.productVariant.product')
                ->orderByDesc('sale_date')
                ->limit(200)
                ->get()
                ->map(fn($o) => $this->formatOfflineOrder($o));
            $orders = $orders->merge($offlineOrders);
        }

        // Sort by date descending and paginate manually
        $sorted = $orders->sortByDesc('ordered_at')->values();
        $page = $request->integer('page', 1);
        $paginated = $sorted->forPage($page, $perPage)->values();

        return response()->json([
            'success' => true,
            'data' => $paginated,
            'meta' => [
                'total' => $sorted->count(),
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => max(1, (int) ceil($sorted->count() / $perPage)),
            ],
        ]);
    }

    /**
     * PATCH /api/v1/integration/orders/{id}/status
     * RISMENT updates order status (shipped, delivered, etc.)
     */
    public function updateOrderStatus(Request $request, int $id): JsonResponse
    {
        $company = $this->getCompany($request);

        $validated = $request->validate([
            'marketplace' => 'required|string|in:wb,uzum,ozon,ym,offline',
            'status' => 'required|string',
            'tracking_number' => 'nullable|string|max:100',
            'shipped_at' => 'nullable|date',
            'delivered_at' => 'nullable|date',
            'note' => 'nullable|string|max:500',
        ]);

        $marketplace = $validated['marketplace'];
        $newStatus = $validated['status'];

        try {
            $order = $this->findOrder($company, $marketplace, $id);

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found',
                ], 404);
            }

            $oldStatus = $order->status ?? $order->status_normalized ?? null;

            // Update status on the order model
            if ($marketplace === 'offline') {
                $order->status = $newStatus;
                if ($validated['shipped_at'] ?? null) {
                    $order->shipped_date = $validated['shipped_at'];
                }
                if ($validated['delivered_at'] ?? null) {
                    $order->delivered_date = $validated['delivered_at'];
                }
            } else {
                $order->status_normalized = $newStatus;
                if (isset($validated['tracking_number']) && $marketplace === 'ozon') {
                    $order->tracking_number = $validated['tracking_number'];
                }
                if ($validated['delivered_at'] ?? null) {
                    $order->delivered_at = $validated['delivered_at'];
                }
            }

            $order->save();

            // Dispatch webhooks based on status
            $webhookEvent = null;
            if (in_array($newStatus, ['shipped', 'SHIPPED', 'in_delivery', 'IN_DELIVERY'])) {
                $webhookEvent = 'order.shipped';
            } elseif (in_array($newStatus, ['delivered', 'DELIVERED', 'completed'])) {
                $webhookEvent = 'order.delivered';
            }

            if ($webhookEvent) {
                $this->webhookService->dispatch($company->id, $webhookEvent, [
                    'order_id' => $id,
                    'marketplace' => $marketplace,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'tracking_number' => $validated['tracking_number'] ?? null,
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'order_id' => $id,
                    'marketplace' => $marketplace,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Risment: Failed to update order status', [
                'order_id' => $id,
                'marketplace' => $marketplace,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update order status',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    // ========== Private helpers ==========

    protected function getCompany(Request $request): Company
    {
        return $request->attributes->get('risment_company');
    }

    protected function formatOrder($order, string $marketplace): array
    {
        $orderNumber = match ($marketplace) {
            'wb' => $order->external_order_id,
            'uzum' => $order->external_order_id,
            'ozon' => $order->posting_number ?? $order->order_id,
            'ym' => $order->order_id,
            default => $order->id,
        };

        return [
            'id' => $order->id,
            'marketplace' => $marketplace,
            'order_number' => $orderNumber,
            'status' => $order->status_normalized ?? $order->status,
            'stock_status' => $order->stock_status ?? null,
            'total_amount' => $order->total_amount ?? $order->price ?? null,
            'currency' => $order->currency ?? $order->currency_code ?? null,
            'customer_name' => $order->customer_name ?? null,
            'customer_phone' => $order->customer_phone ?? null,
            'ordered_at' => $order->ordered_at,
            'delivered_at' => $order->delivered_at ?? null,
        ];
    }

    protected function formatOfflineOrder(OfflineSale $order): array
    {
        return [
            'id' => $order->id,
            'marketplace' => 'offline',
            'order_number' => $order->sale_number,
            'status' => $order->status,
            'stock_status' => $order->stock_status ?? null,
            'total_amount' => $order->total_amount,
            'currency' => $order->currency_code,
            'customer_name' => $order->customer_name,
            'customer_phone' => $order->customer_phone,
            'ordered_at' => $order->sale_date,
            'delivered_at' => $order->delivered_date,
            'items' => $order->items->map(fn($item) => [
                'variant_id' => $item->product_variant_id,
                'product_name' => $item->productVariant?->product?->name,
                'sku' => $item->productVariant?->sku,
                'quantity' => $item->quantity,
                'price' => $item->unit_price ?? null,
                'total' => $item->line_total ?? null,
            ])->toArray(),
        ];
    }

    protected function findOrder(Company $company, string $marketplace, int $id)
    {
        $accountIds = MarketplaceAccount::where('company_id', $company->id)
            ->pluck('id');

        return match ($marketplace) {
            'wb' => WbOrder::where('id', $id)->whereIn('marketplace_account_id', $accountIds)->first(),
            'uzum' => UzumOrder::where('id', $id)->whereIn('marketplace_account_id', $accountIds)->first(),
            'ozon' => OzonOrder::where('id', $id)->whereIn('marketplace_account_id', $accountIds)->first(),
            'ym' => YandexMarketOrder::where('id', $id)->whereIn('marketplace_account_id', $accountIds)->first(),
            'offline' => OfflineSale::where('id', $id)->where('company_id', $company->id)->first(),
            default => null,
        };
    }
}
