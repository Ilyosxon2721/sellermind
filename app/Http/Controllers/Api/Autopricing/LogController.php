<?php

namespace App\Http\Controllers\Api\Autopricing;

use App\Http\Controllers\Controller;
use App\Models\Autopricing\AutopricingChangeLog;
use App\Support\ApiResponder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LogController extends Controller
{
    use ApiResponder;

    public function index(Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (!$companyId) return $this->errorResponse('No company', 'forbidden', null, 403);

        $q = AutopricingChangeLog::byCompany($companyId);
        if ($request->channel_code) $q->where('channel_code', $request->channel_code);
        if ($request->sku_id) $q->where('sku_id', $request->sku_id);
        if ($request->from) $q->where('created_at', '>=', $request->from);
        if ($request->to) $q->where('created_at', '<=', $request->to);

        return $this->successResponse($q->orderByDesc('id')->limit(500)->get());
    }
}
