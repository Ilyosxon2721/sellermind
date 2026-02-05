<?php

namespace App\Http\Controllers\Api\AP;

use App\Http\Controllers\Controller;
use App\Models\AP\SupplierPayment;
use App\Services\AP\PaymentService;
use App\Support\ApiResponder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    use ApiResponder;

    public function __construct(protected PaymentService $service) {}

    public function index(Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (! $companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $q = SupplierPayment::byCompany($companyId);
        if ($request->supplier_id) {
            $q->where('supplier_id', $request->supplier_id);
        }
        if ($request->status) {
            $q->where('status', $request->status);
        }
        if ($request->from) {
            $q->whereDate('paid_at', '>=', $request->from);
        }
        if ($request->to) {
            $q->whereDate('paid_at', '<=', $request->to);
        }
        if ($search = $request->get('query')) {
            $search = $this->escapeLike($search);
            $q->where('payment_no', 'like', '%'.$search.'%');
        }

        return $this->successResponse($q->orderByDesc('id')->limit(200)->get());
    }

    public function show($id)
    {
        $companyId = Auth::user()?->company_id;
        if (! $companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }
        $payment = SupplierPayment::byCompany($companyId)->with('allocations')->findOrFail($id);

        return $this->successResponse($payment);
    }

    public function store(Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (! $companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }
        $data = $this->validateData($request);
        $data['company_id'] = $companyId;
        $data['created_by'] = Auth::id();
        $payment = $this->service->create($data);

        return $this->successResponse($payment);
    }

    public function update($id, Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (! $companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }
        $data = $this->validateData($request);
        $payment = $this->service->updateDraft($id, $companyId, $data);

        return $this->successResponse($payment);
    }

    public function allocations($id, Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (! $companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $allocs = $request->validate([
            'allocations' => ['required', 'array'],
            'allocations.*.invoice_id' => ['required', 'integer'],
            'allocations.*.amount' => ['required', 'numeric'],
        ])['allocations'];

        $payment = $this->service->setAllocations($id, $companyId, $allocs);

        return $this->successResponse($payment);
    }

    public function post($id)
    {
        $companyId = Auth::user()?->company_id;
        if (! $companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }
        try {
            $payment = $this->service->post($id, $companyId, Auth::id());

            return $this->successResponse($payment);
        } catch (\Throwable $e) {
            return $this->errorResponse($e->getMessage(), 'post_failed', null, 422);
        }
    }

    public function reverse($id)
    {
        $companyId = Auth::user()?->company_id;
        if (! $companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }
        try {
            $payment = $this->service->reverse($id, $companyId, Auth::id());

            return $this->successResponse($payment);
        } catch (\Throwable $e) {
            return $this->errorResponse($e->getMessage(), 'reverse_failed', null, 422);
        }
    }

    protected function validateData(Request $request): array
    {
        return $request->validate([
            'supplier_id' => ['required', 'integer'],
            'payment_no' => ['required', 'string'],
            'status' => ['nullable', 'string'],
            'paid_at' => ['required', 'date'],
            'currency_code' => ['nullable', 'string'],
            'exchange_rate' => ['nullable', 'numeric'],
            'amount_total' => ['required', 'numeric'],
            'method' => ['nullable', 'string'],
            'reference' => ['nullable', 'string'],
            'comment' => ['nullable', 'string'],
        ]);
    }
}
