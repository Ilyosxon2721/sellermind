<?php

namespace App\Http\Controllers\Api\Autopricing;

use App\Http\Controllers\Controller;
use App\Models\Autopricing\AutopricingProposal;
use App\Services\Autopricing\AutopricingApplyService;
use App\Services\Autopricing\AutopricingEngineService;
use App\Support\ApiResponder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProposalController extends Controller
{
    use ApiResponder;

    public function __construct(
        protected AutopricingEngineService $engine,
        protected AutopricingApplyService $applyService
    ) {}

    public function index(Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (! $companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }
        $q = AutopricingProposal::byCompany($companyId);
        if ($request->policy_id) {
            $q->where('policy_id', $request->policy_id);
        }
        if ($request->channel_code) {
            $q->where('channel_code', $request->channel_code);
        }
        if ($request->status) {
            $q->where('status', $request->status);
        }
        if ($request->from) {
            $q->where('calculated_at', '>=', $request->from);
        }
        if ($request->to) {
            $q->where('calculated_at', '<=', $request->to);
        }
        if ($search = $request->get('query')) {
            $q->where('sku_id', $search);
        }

        return $this->successResponse($q->orderByDesc('id')->limit(500)->get());
    }

    public function calculate(Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (! $companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }
        $data = $request->validate([
            'policy_id' => ['required', 'integer'],
            'channel_code' => ['required', 'string'],
            'sku_ids' => ['required', 'array'],
        ]);

        $created = 0;
        foreach ($data['sku_ids'] as $skuId) {
            $prop = $this->engine->calculateProposal($companyId, $data['policy_id'], $data['channel_code'], (int) $skuId);
            if ($prop) {
                AutopricingProposal::create($prop);
                $created++;
            }
        }

        return $this->successResponse(['created' => $created]);
    }

    public function approve($id)
    {
        $companyId = Auth::user()?->company_id;
        if (! $companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }
        $prop = $this->applyService->approve($id, $companyId, Auth::id());

        return $this->successResponse($prop);
    }

    public function reject($id, Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (! $companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }
        $prop = $this->applyService->reject($id, $companyId, $request->input('reason'));

        return $this->successResponse($prop);
    }

    public function apply(Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (! $companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }
        $data = $request->validate([
            'policy_id' => ['required', 'integer'],
            'channel_code' => ['required', 'string'],
            'status_to_apply' => ['nullable', 'string'],
            'limit' => ['nullable', 'integer'],
        ]);
        $res = $this->applyService->applyApprovedBatch(
            $companyId,
            $data['policy_id'],
            $data['channel_code'],
            $data['status_to_apply'] ?? 'APPROVED',
            $data['limit'] ?? 100,
            Auth::id()
        );

        return $this->successResponse($res);
    }
}
