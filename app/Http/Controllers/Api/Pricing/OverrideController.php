<?php

namespace App\Http\Controllers\Api\Pricing;

use App\Http\Controllers\Controller;
use App\Models\Pricing\PricingChannelOverride;
use App\Models\Pricing\PricingSkuOverride;
use App\Support\ApiResponder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OverrideController extends Controller
{
    use ApiResponder;

    public function channel(Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (! $companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }
        $request->validate(['scenario_id' => ['required', 'integer']]);
        $query = PricingChannelOverride::byCompany($companyId)
            ->where('scenario_id', $request->scenario_id);
        if ($request->channel_code) {
            $query->where('channel_code', $request->channel_code);
        }

        return $this->successResponse($query->get());
    }

    public function storeChannel(Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (! $companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }
        $data = $request->validate([
            'scenario_id' => ['required', 'integer'],
            'channel_code' => ['required', 'string'],
            'override_target_margin_percent' => ['nullable', 'numeric'],
            'override_promo_reserve_percent' => ['nullable', 'numeric'],
            'override_rounding_step' => ['nullable', 'numeric'],
            'meta_json' => ['nullable', 'array'],
        ]);
        $data['company_id'] = $companyId;
        $override = PricingChannelOverride::updateOrCreate(
            [
                'company_id' => $companyId,
                'scenario_id' => $data['scenario_id'],
                'channel_code' => $data['channel_code'],
            ],
            $data
        );

        return $this->successResponse($override);
    }

    public function sku(Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (! $companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }
        $request->validate(['scenario_id' => ['required', 'integer']]);
        $query = PricingSkuOverride::byCompany($companyId)
            ->where('scenario_id', $request->scenario_id);
        if ($request->sku_id) {
            $query->where('sku_id', $request->sku_id);
        }
        if ($search = $request->get('query')) {
            $search = $this->escapeLike($search);
            $query->whereIn('sku_id', \App\Models\Warehouse\Sku::byCompany($companyId)
                ->where('sku_code', 'like', "%{$search}%")->pluck('id'));
        }

        return $this->successResponse($query->limit(200)->get());
    }

    public function storeSku(Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (! $companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }
        $data = $request->validate([
            'scenario_id' => ['required', 'integer'],
            'sku_id' => ['required', 'integer'],
            'cost_override' => ['nullable', 'numeric'],
            'min_profit_fixed' => ['nullable', 'numeric'],
            'target_margin_percent' => ['nullable', 'numeric'],
            'promo_reserve_percent' => ['nullable', 'numeric'],
            'is_excluded' => ['nullable', 'boolean'],
            'meta_json' => ['nullable', 'array'],
        ]);
        $data['company_id'] = $companyId;
        $override = PricingSkuOverride::updateOrCreate(
            [
                'company_id' => $companyId,
                'scenario_id' => $data['scenario_id'],
                'sku_id' => $data['sku_id'],
            ],
            $data
        );

        return $this->successResponse($override);
    }
}
