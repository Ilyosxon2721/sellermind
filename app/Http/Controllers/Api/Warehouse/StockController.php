<?php

namespace App\Http\Controllers\Api\Warehouse;

use App\Http\Controllers\Controller;
use App\Support\ApiResponder;
use App\Services\Warehouse\StockBalanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
            'limit' => ['nullable', 'integer', 'min:1', 'max:200'],
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

        // Query mode: find SKUs by code/barcode and return balances list
        $limit = $request->integer('limit', 50);
        $query = \App\Models\Warehouse\Sku::query()
            ->byCompany($companyId)
            ->with('product')
            ->orderBy('sku_code');

        if ($search = $request->get('query')) {
            $query->where(function ($q) use ($search) {
                $q->where('sku_code', 'like', '%' . $search . '%')
                    ->orWhere('barcode_ean13', 'like', '%' . $search . '%');
            });
        }

        $skus = $query->limit($limit)->get(['id', 'sku_code', 'barcode_ean13', 'product_id', 'company_id']);
        if ($skus->isEmpty()) {
            return $this->successResponse(['items' => []]);
        }

        $balances = $service->bulkBalance($companyId, $warehouseId, $skus->pluck('id')->all());

        $items = $skus->map(function ($sku) use ($balances) {
            $balance = $balances[$sku->id] ?? ['on_hand' => 0, 'reserved' => 0, 'available' => 0];
            return [
                'sku_id' => $sku->id,
                'sku_code' => $sku->sku_code,
                'barcode' => $sku->barcode_ean13,
                'product_name' => $sku->product?->name,
                'on_hand' => $balance['on_hand'] ?? 0,
                'reserved' => $balance['reserved'] ?? 0,
                'available' => $balance['available'] ?? 0,
            ];
        });

        return $this->successResponse(['items' => $items]);
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
}
