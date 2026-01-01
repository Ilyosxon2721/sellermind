<?php

namespace App\Http\Controllers\Api\Autopricing;

use App\Http\Controllers\Controller;
use App\Models\Autopricing\AutopricingPolicy;
use App\Support\ApiResponder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PolicyController extends Controller
{
    use ApiResponder;

    public function index()
    {
        $companyId = Auth::user()?->company_id;
        if (!$companyId) return $this->errorResponse('No company', 'forbidden', null, 403);
        return $this->successResponse(AutopricingPolicy::byCompany($companyId)->orderBy('priority')->get());
    }

    public function store(Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (!$companyId) return $this->errorResponse('No company', 'forbidden', null, 403);
        $data = $this->validateData($request);
        $data['company_id'] = $companyId;
        $policy = AutopricingPolicy::create($data);
        return $this->successResponse($policy);
    }

    public function update($id, Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (!$companyId) return $this->errorResponse('No company', 'forbidden', null, 403);
        $policy = AutopricingPolicy::byCompany($companyId)->findOrFail($id);
        $policy->update($this->validateData($request));
        return $this->successResponse($policy);
    }

    protected function validateData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'channel_code' => ['nullable', 'string'],
            'scenario_id' => ['required', 'integer'],
            'mode' => ['nullable', 'string'],
            'priority' => ['nullable', 'integer'],
            'cooldown_hours' => ['nullable', 'integer'],
            'max_changes_per_day' => ['nullable', 'integer'],
            'max_delta_percent' => ['nullable', 'numeric'],
            'max_delta_amount' => ['nullable', 'numeric'],
            'min_price_guard' => ['nullable', 'boolean'],
            'max_price_guard' => ['nullable', 'boolean'],
            'max_price_value' => ['nullable', 'numeric'],
            'comment' => ['nullable', 'string'],
        ]);
    }
}
