<?php

namespace App\Http\Controllers\Api\Replenishment;

use App\Http\Controllers\Controller;
use App\Models\Replenishment\ReplenishmentSetting;
use App\Models\Warehouse\Sku;
use App\Support\ApiResponder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReplenishmentSettingsController extends Controller
{
    use ApiResponder;

    public function index(Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (! $companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $query = ReplenishmentSetting::byCompany($companyId)
            ->when($request->warehouse_id, fn ($q) => $q->where('warehouse_id', $request->warehouse_id))
            ->with(['sku' => function ($q) {
                $q->select('id', 'sku_code', 'barcode_ean13', 'product_id');
            }]);

        if ($search = $request->get('query')) {
            $search = $this->escapeLike($search);
            $query->whereIn('sku_id', Sku::query()
                ->byCompany($companyId)
                ->where(function ($q) use ($search) {
                    $q->where('sku_code', 'like', "%{$search}%")
                        ->orWhere('barcode_ean13', 'like', "%{$search}%");
                })
                ->pluck('id'));
        }

        return $this->successResponse($query->orderBy('id', 'desc')->limit(200)->get());
    }

    public function store(Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (! $companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $data = $this->validateData($request);
        $data['company_id'] = $companyId;

        $setting = ReplenishmentSetting::updateOrCreate(
            [
                'company_id' => $companyId,
                'warehouse_id' => $data['warehouse_id'],
                'sku_id' => $data['sku_id'],
            ],
            $data
        );

        return $this->successResponse($setting);
    }

    public function update($id, Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (! $companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $setting = ReplenishmentSetting::byCompany($companyId)->findOrFail($id);
        $setting->update($this->validateData($request));

        return $this->successResponse($setting->fresh());
    }

    protected function validateData(Request $request): array
    {
        return $request->validate([
            'warehouse_id' => ['required', 'integer'],
            'sku_id' => ['required', 'integer'],
            'is_enabled' => ['boolean'],
            'policy' => ['in:ROP,MIN_MAX'],
            'reorder_point' => ['nullable', 'numeric'],
            'min_qty' => ['nullable', 'numeric'],
            'max_qty' => ['nullable', 'numeric'],
            'safety_stock' => ['nullable', 'numeric'],
            'lead_time_days' => ['nullable', 'integer'],
            'review_period_days' => ['nullable', 'integer'],
            'demand_window_days' => ['nullable', 'integer'],
            'rounding_step' => ['nullable', 'numeric'],
            'min_order_qty' => ['nullable', 'numeric'],
            'supplier_id' => ['nullable', 'integer'],
        ]);
    }
}
