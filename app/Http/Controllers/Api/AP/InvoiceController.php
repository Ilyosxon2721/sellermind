<?php

namespace App\Http\Controllers\Api\AP;

use App\Http\Controllers\Controller;
use App\Models\AP\SupplierInvoice;
use App\Services\AP\InvoiceService;
use App\Support\ApiResponder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InvoiceController extends Controller
{
    use ApiResponder;

    public function __construct(protected InvoiceService $service)
    {
    }

    public function index(Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (!$companyId) return $this->errorResponse('No company', 'forbidden', null, 403);

        $q = SupplierInvoice::byCompany($companyId);
        if ($request->status) $q->where('status', $request->status);
        if ($request->supplier_id) $q->where('supplier_id', $request->supplier_id);
        if ($request->from) $q->whereDate('issue_date', '>=', $request->from);
        if ($request->to) $q->whereDate('issue_date', '<=', $request->to);
        if ($request->due_from) $q->whereDate('due_date', '>=', $request->due_from);
        if ($request->due_to) $q->whereDate('due_date', '<=', $request->due_to);
        if ($search = $request->get('query')) {
            $q->where('invoice_no', 'like', '%' . $search . '%');
        }

        return $this->successResponse($q->orderByDesc('id')->limit(200)->get());
    }

    public function show($id)
    {
        $companyId = Auth::user()?->company_id;
        if (!$companyId) return $this->errorResponse('No company', 'forbidden', null, 403);

        $inv = SupplierInvoice::byCompany($companyId)->with('lines')->findOrFail($id);
        return $this->successResponse($inv);
    }

    public function store(Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (!$companyId) return $this->errorResponse('No company', 'forbidden', null, 403);

        $data = $this->validateData($request);
        $data['company_id'] = $companyId;
        $data['created_by'] = Auth::id();

        $invoice = $this->service->create($data, $request->input('lines', []));
        return $this->successResponse($invoice);
    }

    public function update($id, Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (!$companyId) return $this->errorResponse('No company', 'forbidden', null, 403);

        $data = $this->validateData($request);
        $invoice = $this->service->updateDraft($id, $companyId, $data, $request->input('lines', []));
        return $this->successResponse($invoice);
    }

    public function confirm($id)
    {
        $companyId = Auth::user()?->company_id;
        if (!$companyId) return $this->errorResponse('No company', 'forbidden', null, 403);

        $inv = $this->service->confirm($id, $companyId, Auth::id());
        return $this->successResponse($inv);
    }

    public function lines($id, Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (!$companyId) return $this->errorResponse('No company', 'forbidden', null, 403);
        $invoice = SupplierInvoice::byCompany($companyId)->findOrFail($id);
        if ($invoice->status !== SupplierInvoice::STATUS_DRAFT) {
            return $this->errorResponse('Invoice not in draft', 'invalid_state', null, 422);
        }
        $lines = $request->validate([
            'lines' => ['required', 'array'],
        ])['lines'];
        $this->service->replaceLines($invoice, $lines);
        $this->service->recalc($invoice->id);
        return $this->successResponse($invoice->fresh('lines'));
    }

    protected function validateData(Request $request): array
    {
        return $request->validate([
            'supplier_id' => ['required', 'integer'],
            'invoice_no' => ['required', 'string'],
            'status' => ['nullable', 'string'],
            'issue_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'currency_code' => ['nullable', 'string'],
            'exchange_rate' => ['nullable', 'numeric'],
            'amount_tax' => ['nullable', 'numeric'],
            'amount_total' => ['nullable', 'numeric'],
            'related_type' => ['nullable', 'string'],
            'related_id' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
