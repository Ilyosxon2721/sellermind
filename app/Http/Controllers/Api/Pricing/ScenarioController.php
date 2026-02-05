<?php

namespace App\Http\Controllers\Api\Pricing;

use App\Http\Controllers\Controller;
use App\Models\Pricing\PricingScenario;
use App\Support\ApiResponder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ScenarioController extends Controller
{
    use ApiResponder;

    public function index()
    {
        $companyId = Auth::user()?->company_id;
        if (! $companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        return $this->successResponse(PricingScenario::byCompany($companyId)->orderByDesc('is_default')->get());
    }

    public function store(Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (! $companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }
        $data = $this->validateData($request);
        $data['company_id'] = $companyId;
        $scenario = PricingScenario::create($data);

        return $this->successResponse($scenario);
    }

    public function update($id, Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (! $companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }
        $scenario = PricingScenario::byCompany($companyId)->findOrFail($id);
        $scenario->update($this->validateData($request));

        return $this->successResponse($scenario);
    }

    public function setDefault($id)
    {
        $companyId = Auth::user()?->company_id;
        if (! $companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }
        PricingScenario::byCompany($companyId)->update(['is_default' => false]);
        $scenario = PricingScenario::byCompany($companyId)->findOrFail($id);
        $scenario->is_default = true;
        $scenario->save();

        return $this->successResponse($scenario);
    }

    protected function validateData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string'],
            'description' => ['nullable', 'string'],
            'target_margin_percent' => ['nullable', 'numeric'],
            'target_profit_fixed' => ['nullable', 'numeric'],
            'promo_reserve_percent' => ['nullable', 'numeric'],
            'tax_mode' => ['nullable', 'string'],
            'vat_percent' => ['nullable', 'numeric'],
            'profit_tax_percent' => ['nullable', 'numeric'],
            'rounding_mode' => ['nullable', 'string'],
            'rounding_step' => ['nullable', 'numeric'],
        ]);
    }
}
