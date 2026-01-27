<?php

namespace App\Http\Controllers\Api\Warehouse;

use App\Http\Controllers\Controller;
use App\Support\ApiResponder;
use App\Services\Warehouse\StockBalanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StockController extends Controller
{
    use ApiResponder;

    /**
     * Get company ID with fallback to companies relationship
     */
    private function getCompanyId(): ?int
    {
        $user = Auth::user();
        if (!$user) {
            return null;
        }
        return $user->company_id ?? $user->companies()->first()?->id;
    }

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
        if (!$companyId) {
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
        if (!empty($request->sku_ids ?? [])) {
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
            $query->where(function ($q) use ($search) {
                $q->where('sku_code', 'like', '%' . $search . '%')
                    ->orWhere('barcode_ean13', 'like', '%' . $search . '%')
                    ->orWhereHas('product', function ($pq) use ($search) {
                        $pq->where('name', 'like', '%' . $search . '%');
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
            if (!$optionsSummary && $sku->productVariant?->optionValues) {
                $optionsSummary = $sku->productVariant->optionValues
                    ->map(fn($ov) => $ov->value)
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
        if (!$companyId) {
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
            $search = $request->query('query');
            $query->where(function ($q) use ($search) {
                $q->whereHas('document', fn($dq) => $dq->where('doc_no', 'like', '%' . $search . '%'))
                    ->orWhereHas('sku', fn($sq) => $sq->where('sku_code', 'like', '%' . $search . '%'))
                    ->orWhere('sku_id', $search);
            });
        }

        $perPage = min(max((int) ($request->per_page ?? 50), 1), 200);
        $page = max((int) ($request->page ?? 1), 1);
        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        return $this->successResponse([
            'data' => $paginator->items(),
            'pagination' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
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
        if (!$companyId) {
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
