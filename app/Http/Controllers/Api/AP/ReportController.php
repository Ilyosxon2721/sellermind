<?php

namespace App\Http\Controllers\Api\AP;

use App\Http\Controllers\Controller;
use App\Services\AP\APReportsService;
use App\Support\ApiResponder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    use ApiResponder;

    public function __construct(protected APReportsService $service)
    {
    }

    public function aging(Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (!$companyId) return $this->errorResponse('No company', 'forbidden', null, 403);
        $data = $this->service->aging($companyId, $request->get('as_of'));
        return $this->successResponse($data);
    }

    public function overdue(Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (!$companyId) return $this->errorResponse('No company', 'forbidden', null, 403);
        return $this->successResponse($this->service->overdue($companyId, $request->get('as_of')));
    }

    public function calendar(Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (!$companyId) return $this->errorResponse('No company', 'forbidden', null, 403);
        $request->validate([
            'from' => ['required', 'date'],
            'to' => ['required', 'date'],
        ]);
        return $this->successResponse($this->service->calendar($companyId, $request->get('from'), $request->get('to')));
    }
}
