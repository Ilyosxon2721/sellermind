<?php

namespace App\Http\Controllers\Api\Autopricing;

use App\Http\Controllers\Controller;
use App\Models\Autopricing\AutopricingRule;
use App\Support\ApiResponder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RuleController extends Controller
{
    use ApiResponder;

    public function index(Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (! $companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }
        $request->validate(['policy_id' => ['required', 'integer']]);
        $q = AutopricingRule::byCompany($companyId)->where('policy_id', $request->policy_id);

        return $this->successResponse($q->orderBy('priority')->get());
    }

    public function store(Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (! $companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }
        $data = $this->validateData($request);
        $data['company_id'] = $companyId;
        $rule = AutopricingRule::create($data);

        return $this->successResponse($rule);
    }

    public function update($id, Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (! $companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }
        $rule = AutopricingRule::byCompany($companyId)->findOrFail($id);
        $rule->update($this->validateData($request));

        return $this->successResponse($rule);
    }

    protected function validateData(Request $request): array
    {
        return $request->validate([
            'policy_id' => ['required', 'integer'],
            'scope_type' => ['required', 'string'],
            'scope_id' => ['nullable', 'integer'],
            'rule_type' => ['required', 'string'],
            'params_json' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
            'priority' => ['nullable', 'integer'],
        ]);
    }
}
