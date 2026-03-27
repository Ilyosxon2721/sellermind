<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Pricing;

use App\Http\Controllers\Controller;
use App\Models\Pricing\ChannelCostRule;
use App\Support\ApiResponder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final class ChannelCostRuleController extends Controller
{
    use ApiResponder;

    public function index()
    {
        $companyId = Auth::user()?->company_id;
        if (! $companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        return $this->successResponse(
            ChannelCostRule::byCompany($companyId)->orderBy('channel_code')->get()
        );
    }

    public function store(Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (! $companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $data = $this->validateData($request);
        $data['company_id'] = $companyId;

        $rule = ChannelCostRule::updateOrCreate(
            ['company_id' => $companyId, 'channel_code' => $data['channel_code']],
            $data
        );

        return $this->successResponse($rule);
    }

    public function update($id, Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (! $companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $rule = ChannelCostRule::byCompany($companyId)->findOrFail($id);
        $rule->update($this->validateData($request));

        return $this->successResponse($rule->fresh());
    }

    public function destroy($id)
    {
        $companyId = Auth::user()?->company_id;
        if (! $companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $rule = ChannelCostRule::byCompany($companyId)->findOrFail($id);
        $rule->delete();

        return $this->successResponse(null, ['message' => 'Удалено']);
    }

    protected function validateData(Request $request): array
    {
        return $request->validate([
            'channel_code' => ['required', 'string', 'max:32'],
            'name' => ['nullable', 'string', 'max:255'],
            'commission_percent' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'commission_fixed' => ['nullable', 'numeric', 'min:0'],
            'logistics_fixed' => ['nullable', 'numeric', 'min:0'],
            'return_logistics_fixed' => ['nullable', 'numeric', 'min:0'],
            'payment_fee_percent' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'return_percent' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'storage_cost_per_day' => ['nullable', 'numeric', 'min:0'],
            'vat_percent' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'turnover_tax_percent' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'profit_tax_percent' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'other_percent' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'other_fixed' => ['nullable', 'numeric', 'min:0'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);
    }
}
