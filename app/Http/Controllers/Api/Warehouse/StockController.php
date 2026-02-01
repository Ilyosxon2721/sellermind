<?php

namespace App\Http\Controllers\Api\Warehouse;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCompanyScope;
use App\Models\OzonOrder;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\UzumOrder;
use App\Models\WbOrder;
use App\Models\YandexMarketOrder;
use App\Services\Warehouse\StockBalanceService;
use App\Support\ApiResponder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class StockController extends Controller
{
    use ApiResponder;
    use HasCompanyScope;

    public function balance(Request $request)
    {
        $request->validate([
            'warehouse_id' => ['required', 'integer'],
            'sku_id' => ['nullable', 'integer'],
            'sku_ids' => ['nullable', 'array'],
            'query' => ['nullable', 'string'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $companyId = $this->getCompanyId();
        if (! $companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $warehouseId = (int) $request->warehouse_id;
        $service = app(StockBalanceService::class);

        // Single SKU
        if ($request->sku_id) {
            $data = $service->balance($companyId, $warehouseId, (int) $request->sku_id);

            return $this->successResponse($data);
        }

        // Explicit list of SKUs
        if (! empty($request->sku_ids ?? [])) {
            $skuIds = array_map('intval', $request->sku_ids);
            $balances = $service->bulkBalance($companyId, $warehouseId, $skuIds);

            return $this->successResponse([
                'items' => $balances->map(function ($balance, $skuId) {
                    return [
                        'sku_id' => $skuId,
                        'on_hand' => $balance['on_hand'],
                        'reserved' => $balance['reserved'],
                        'available' => $balance['available'],
                    ];
                })->values(),
            ]);
        }

        // Paginated mode: find SKUs by code/barcode and return balances list with pagination
        $perPage = min(max((int) ($request->per_page ?? 30), 1), 100);
        $page = max((int) ($request->page ?? 1), 1);

        $query = \App\Models\Warehouse\Sku::query()
            ->byCompany($companyId)
            ->with(['product.images', 'productVariant.mainImage', 'productVariant.optionValues'])
            ->orderBy('sku_code');

        if ($search = $request->get('query')) {
            $search = $this->escapeLike($search);
            $query->where(function ($q) use ($search) {
                $q->where('sku_code', 'like', '%'.$search.'%')
                    ->orWhere('barcode_ean13', 'like', '%'.$search.'%')
                    ->orWhereHas('product', function ($pq) use ($search) {
                        $pq->where('name', 'like', '%'.$search.'%');
                    });
            });
        }

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);
        $skus = $paginator->items();

        if (empty($skus)) {
            return $this->successResponse([
                'items' => [],
                'pagination' => [
                    'total' => 0,
                    'per_page' => $perPage,
                    'current_page' => $page,
                    'last_page' => 1,
                ],
            ]);
        }

        $skuIds = collect($skus)->pluck('id')->all();
        $balances = $service->bulkBalance($companyId, $warehouseId, $skuIds);
        $costs = $service->bulkCost($companyId, $warehouseId, $skuIds);

        $items = collect($skus)->map(function ($sku) use ($balances, $costs) {
            $balance = $balances[$sku->id] ?? ['on_hand' => 0, 'reserved' => 0, 'available' => 0];
            $cost = $costs[$sku->id] ?? ['total_cost' => 0, 'unit_cost' => 0];

            // Get unit cost: prefer ledger cost, fallback to ProductVariant.purchase_price
            $unitCost = $cost['unit_cost'] ?? 0;
            if ($unitCost == 0 && $sku->productVariant?->purchase_price) {
                $unitCost = (float) $sku->productVariant->purchase_price;
            }

            // Calculate total cost using the determined unit cost
            $onHand = $balance['on_hand'] ?? 0;
            $totalCost = $cost['total_cost'] ?? 0;
            if ($totalCost == 0 && $unitCost > 0 && $onHand > 0) {
                $totalCost = $unitCost * $onHand;
            }

            // Get image URL (variant image first, then product image)
            $imageUrl = null;
            if ($sku->productVariant?->mainImage) {
                $imageUrl = $sku->productVariant->mainImage->url ?? $sku->productVariant->mainImage->path;
            } elseif ($sku->product?->images?->first()) {
                $imageUrl = $sku->product->images->first()->url ?? $sku->product->images->first()->path;
            }

            // Get variant options (e.g., "Размер: XL, Цвет: Красный")
            $optionsSummary = $sku->productVariant?->option_values_summary;
            if (! $optionsSummary && $sku->productVariant?->optionValues) {
                $optionsSummary = $sku->productVariant->optionValues
                    ->map(fn ($ov) => $ov->value)
                    ->join(', ');
            }

            return [
                'sku_id' => $sku->id,
                'sku_code' => $sku->sku_code,
                'barcode' => $sku->barcode_ean13,
                'product_name' => $sku->product?->name,
                'product_id' => $sku->product_id,
                'image_url' => $imageUrl,
                'options_summary' => $optionsSummary,
                'on_hand' => $onHand,
                'reserved' => $balance['reserved'] ?? 0,
                'available' => $balance['available'] ?? 0,
                'unit_cost' => $unitCost,
                'total_cost' => $totalCost,
            ];
        });

        return $this->successResponse([
            'items' => $items,
            'pagination' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function ledger(Request $request)
    {
        $request->validate([
            'warehouse_id' => ['required', 'integer'],
            'sku_id' => ['nullable', 'integer'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'page' => ['nullable', 'integer'],
            'per_page' => ['nullable', 'integer'],
            'query' => ['nullable', 'string'],
        ]);

        $companyId = $this->getCompanyId();
        if (! $companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $query = \App\Models\Warehouse\StockLedger::query()
            ->with(['document:id,doc_no,type', 'warehouse:id,name', 'sku:id,sku_code'])
            ->where('company_id', $companyId)
            ->where('warehouse_id', $request->warehouse_id)
            ->orderBy('occurred_at', 'desc');

        if ($request->sku_id) {
            $query->where('sku_id', $request->sku_id);
        }

        if ($request->from) {
            $query->where('occurred_at', '>=', $request->from);
        }
        if ($request->to) {
            $query->where('occurred_at', '<=', $request->to);
        }

        if ($request->query('query')) {
            $search = $this->escapeLike($request->query('query'));
            $query->where(function ($q) use ($search) {
                $q->whereHas('document', fn ($dq) => $dq->where('doc_no', 'like', '%'.$search.'%'))
                    ->orWhereHas('sku', fn ($sq) => $sq->where('sku_code', 'like', '%'.$search.'%'))
                    ->orWhere('sku_id', $search);
            });
        }

        $perPage = min(max((int) ($request->per_page ?? 50), 1), 200);
        $page = max((int) ($request->page ?? 1), 1);
        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        // Enrich ledger entries with order info (order number, marketplace, shop, order type)
        $items = collect($paginator->items());
        $enrichedItems = $this->enrichLedgerWithOrderInfo($items, $companyId);

        return $this->successResponse([
            'data' => $enrichedItems,
            'pagination' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    /**
     * Enrich ledger entries with order info: order_number, marketplace, shop_name, order_type
     */
    private function enrichLedgerWithOrderInfo($items, int $companyId): array
    {
        // Classify source_ids by type
        $wbIds = [];
        $uzumIds = [];
        $ozonIds = [];
        $ymIds = [];
        $unifiedIds = []; // marketplace_order_reserve / marketplace_order_cancel
        $saleItemIds = [];
        $saleIds = [];

        foreach ($items as $item) {
            if (! $item->source_type || ! $item->source_id) {
                continue;
            }

            switch ($item->source_type) {
                case 'WB_ORDER':
                case 'WB_ORDER_CANCEL':
                    $wbIds[] = $item->source_id;
                    break;
                case 'UZUM_ORDER':
                    $uzumIds[] = $item->source_id;
                    break;
                case 'OZON_ORDER':
                case 'OZON_ORDER_CANCEL':
                    $ozonIds[] = $item->source_id;
                    break;
                case 'YANDEX_ORDER':
                case 'YANDEX_ORDER_CANCEL':
                    $ymIds[] = $item->source_id;
                    break;
                case 'marketplace_order_reserve':
                case 'marketplace_order_cancel':
                    $unifiedIds[] = $item->source_id;
                    break;
                case 'offline_sale':
                case 'offline_sale_return':
                    $saleItemIds[] = $item->source_id;
                    break;
                case Sale::class:
                    $saleIds[] = $item->source_id;
                    break;
            }
        }

        // Batch fetch orders with account info
        $wbOrders = ! empty($wbIds) || ! empty($unifiedIds)
            ? WbOrder::with('account:id,name,marketplace')
                ->whereIn('id', array_unique(array_merge($wbIds, $unifiedIds)))
                ->get()->keyBy('id')
            : collect();

        $uzumOrders = ! empty($uzumIds) || ! empty($unifiedIds)
            ? UzumOrder::with('account:id,name,marketplace')
                ->whereIn('id', array_unique(array_merge($uzumIds, $unifiedIds)))
                ->get()->keyBy('id')
            : collect();

        $ozonOrders = ! empty($ozonIds) || ! empty($unifiedIds)
            ? OzonOrder::with('account:id,name,marketplace')
                ->whereIn('id', array_unique(array_merge($ozonIds, $unifiedIds)))
                ->get()->keyBy('id')
            : collect();

        $ymOrders = ! empty($ymIds) || ! empty($unifiedIds)
            ? YandexMarketOrder::with('account:id,name,marketplace')
                ->whereIn('id', array_unique(array_merge($ymIds, $unifiedIds)))
                ->get()->keyBy('id')
            : collect();

        // Fetch sale items with sale info
        $saleItems = ! empty($saleItemIds)
            ? SaleItem::with('sale:id,sale_number,source,type,counterparty_id')
                ->whereIn('id', array_unique($saleItemIds))
                ->get()->keyBy('id')
            : collect();

        $sales = ! empty($saleIds)
            ? Sale::whereIn('id', array_unique($saleIds))
                ->get(['id', 'sale_number', 'source', 'type', 'counterparty_id'])
                ->keyBy('id')
            : collect();

        // Marketplace labels
        $marketplaceLabels = [
            'wb' => 'Wildberries',
            'uzum' => 'Uzum Market',
            'ozon' => 'Ozon',
            'ym' => 'Yandex Market',
            'yandex_market' => 'Yandex Market',
        ];

        // Order type labels based on sale source
        $orderTypeLabels = [
            'uzum' => 'Маркетплейс',
            'wb' => 'Маркетплейс',
            'ozon' => 'Маркетплейс',
            'ym' => 'Маркетплейс',
            'manual' => 'Офлайн продажа',
            'pos' => 'Офлайн продажа',
            'instagram' => 'Инстаграм',
            'online_store' => 'Интернет магазин',
            'wholesale' => 'Оптовая продажа',
        ];

        // Enrich each item
        $result = [];
        foreach ($items as $item) {
            $data = $item->toArray();
            $data['order_number'] = null;
            $data['marketplace'] = null;
            $data['shop_name'] = null;
            $data['order_type'] = null;

            if (! $item->source_type || ! $item->source_id) {
                $result[] = $data;

                continue;
            }

            $order = null;
            $marketplace = null;

            switch ($item->source_type) {
                case 'WB_ORDER':
                case 'WB_ORDER_CANCEL':
                    $order = $wbOrders->get($item->source_id);
                    $marketplace = 'wb';
                    break;

                case 'UZUM_ORDER':
                    $order = $uzumOrders->get($item->source_id);
                    $marketplace = 'uzum';
                    break;

                case 'OZON_ORDER':
                case 'OZON_ORDER_CANCEL':
                    $order = $ozonOrders->get($item->source_id);
                    $marketplace = 'ozon';
                    break;

                case 'YANDEX_ORDER':
                case 'YANDEX_ORDER_CANCEL':
                    $order = $ymOrders->get($item->source_id);
                    $marketplace = 'ym';
                    break;

                case 'marketplace_order_reserve':
                case 'marketplace_order_cancel':
                    // Try each marketplace model (unified source_type)
                    if ($order = $wbOrders->get($item->source_id)) {
                        $marketplace = 'wb';
                    } elseif ($order = $uzumOrders->get($item->source_id)) {
                        $marketplace = 'uzum';
                    } elseif ($order = $ozonOrders->get($item->source_id)) {
                        $marketplace = 'ozon';
                    } elseif ($order = $ymOrders->get($item->source_id)) {
                        $marketplace = 'ym';
                    }
                    break;

                case 'offline_sale':
                case 'offline_sale_return':
                    $saleItem = $saleItems->get($item->source_id);
                    if ($saleItem && $saleItem->sale) {
                        $sale = $saleItem->sale;
                        $data['order_number'] = $sale->sale_number;
                        $data['order_type'] = $orderTypeLabels[$sale->source ?? ''] ?? 'Офлайн продажа';

                        if ($sale->source && isset($marketplaceLabels[$sale->source])) {
                            $data['marketplace'] = $marketplaceLabels[$sale->source];
                        }
                    }
                    $result[] = $data;

                    continue 2;

                case Sale::class:
                    $sale = $sales->get($item->source_id);
                    if ($sale) {
                        $data['order_number'] = $sale->sale_number;
                        $data['order_type'] = $orderTypeLabels[$sale->source ?? ''] ?? 'Офлайн продажа';

                        if ($sale->source && isset($marketplaceLabels[$sale->source])) {
                            $data['marketplace'] = $marketplaceLabels[$sale->source];
                        }
                    }
                    $result[] = $data;

                    continue 2;

                default:
                    // Non-order source types (initial_stock, stock_adjustment, etc.)
                    $result[] = $data;

                    continue 2;
            }

            // Set marketplace order info
            if ($order) {
                $data['order_number'] = $order->external_order_id
                    ?? $order->posting_number
                    ?? $order->order_id
                    ?? null;
                $data['marketplace'] = $marketplaceLabels[$marketplace] ?? $marketplace;
                $data['shop_name'] = $order->account?->name ?? null;
                $data['order_type'] = 'Маркетплейс';
            }

            $result[] = $data;
        }

        return $result;
    }

    /**
     * Update cost for a SKU
     * Creates an adjustment entry in stock_ledger to correct the cost
     */
    public function updateCost(Request $request)
    {
        $request->validate([
            'warehouse_id' => ['required', 'integer'],
            'sku_id' => ['required', 'integer'],
            'unit_cost' => ['required', 'numeric', 'min:0'],
        ]);

        $companyId = $this->getCompanyId();
        if (! $companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        try {
            $service = app(StockBalanceService::class);
            $result = $service->updateCost(
                $companyId,
                (int) $request->warehouse_id,
                (int) $request->sku_id,
                (float) $request->unit_cost,
                Auth::id()
            );

            Log::info('Stock cost updated', [
                'company_id' => $companyId,
                'warehouse_id' => $request->warehouse_id,
                'sku_id' => $request->sku_id,
                'user_id' => Auth::id(),
                'result' => $result,
            ]);

            return $this->successResponse($result);
        } catch (\RuntimeException $e) {
            return $this->errorResponse($e->getMessage(), 'update_cost_failed', null, 422);
        } catch (\Throwable $e) {
            Log::error('Failed to update stock cost', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->errorResponse('Failed to update cost', 'error', null, 500);
        }
    }
}
