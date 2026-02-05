<?php

namespace App\Http\Controllers\Api\Pricing;

use App\Http\Controllers\Controller;
use App\Models\Pricing\PriceCalculation;
use App\Services\Pricing\PriceEngineService;
use App\Support\ApiResponder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CalculationController extends Controller
{
    use ApiResponder;

    public function __construct(protected PriceEngineService $engine) {}

    public function calculate(Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (! $companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }
        $data = $request->validate([
            'scenario_id' => ['required', 'integer'],
            'channel_code' => ['required', 'string'],
            'sku_ids' => ['required', 'array'],
        ]);

        $rows = $this->engine->calculateBulk($companyId, $data['scenario_id'], $data['channel_code'], $data['sku_ids']);
        $this->engine->upsertCalculations($rows);

        return $this->successResponse($rows->values());
    }

    public function index(Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (! $companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }
        $q = PriceCalculation::byCompany($companyId);
        if ($request->scenario_id) {
            $q->where('scenario_id', $request->scenario_id);
        }
        if ($request->channel_code) {
            $q->where('channel_code', $request->channel_code);
        }
        if ($search = $request->get('query')) {
            $search = $this->escapeLike($search);
            $q->whereIn('sku_id', \App\Models\Warehouse\Sku::byCompany($companyId)
                ->where('sku_code', 'like', "%{$search}%")->pluck('id'));
        }

        return $this->successResponse($q->limit(500)->get());
    }
}
