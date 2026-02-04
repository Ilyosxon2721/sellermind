<?php

namespace App\Http\Controllers\Api\Replenishment;

use App\Http\Controllers\Controller;
use App\Models\Warehouse\Sku;
use App\Services\Replenishment\ReplenishmentService;
use App\Support\ApiResponder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReplenishmentRecommendationController extends Controller
{
    use ApiResponder;

    public function __construct(protected ReplenishmentService $service) {}

    public function index(Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (! $companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $request->validate([
            'warehouse_id' => ['required', 'integer'],
            'risk' => ['nullable', 'in:LOW,MEDIUM,HIGH'],
            'query' => ['nullable', 'string'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:500'],
        ]);

        $warehouseId = (int) $request->warehouse_id;

        try {
            $results = $this->service->calculateAll($companyId, $warehouseId);
        } catch (\Throwable $e) {
            report($e);
            return $this->errorResponse(
                'Ошибка расчёта рекомендаций: ' . $e->getMessage(),
                'calculation_error',
                null,
                500
            );
        }

        // Enrich with SKU info
        $skuMap = Sku::query()
            ->byCompany($companyId)
            ->whereIn('id', $results->pluck('sku_id'))
            ->with('product:id,name')
            ->get()
            ->keyBy('id');

        $filtered = $results->filter(function ($row) use ($request) {
            if ($request->risk && ($row['risk_level'] !== $request->risk)) {
                return false;
            }

            return true;
        })->map(function ($row) use ($skuMap) {
            $sku = $skuMap[$row['sku_id']] ?? null;
            $row['sku_code'] = $sku?->sku_code;
            $row['barcode'] = $sku?->barcode_ean13;
            $row['product_name'] = $sku?->product?->name;
            $row['preferred_supplier_id'] = $row['setting']->supplier_id ?? null;
            unset($row['setting']);

            return $row;
        });

        if ($query = $request->get('query')) {
            $filtered = $filtered->filter(function ($row) use ($query) {
                return str_contains(strtolower($row['sku_code'] ?? ''), strtolower($query))
                    || str_contains(strtolower($row['product_name'] ?? ''), strtolower($query));
            });
        }

        $limit = $request->integer('limit', 200);

        return $this->successResponse([
            'items' => $filtered->take($limit)->values(),
        ]);
    }

    public function calculate(Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (! $companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $data = $request->validate([
            'warehouse_id' => ['required', 'integer'],
            'sku_ids' => ['nullable', 'array'],
        ]);

        try {
            $results = $this->service->calculateAll($companyId, $data['warehouse_id']);
        } catch (\Throwable $e) {
            report($e);
            return $this->errorResponse(
                'Ошибка расчёта: ' . $e->getMessage(),
                'calculation_error',
                null,
                500
            );
        }

        if (!empty($data['sku_ids'])) {
            $ids = $data['sku_ids'];
            $results = $results->whereIn('sku_id', $ids);
        }

        $snapshots = $results->map(fn ($row) => $this->service->persistSnapshot($row));

        return $this->successResponse([
            'calculated' => $results->count(),
            'snapshots_saved' => $snapshots->count(),
        ]);
    }
}
