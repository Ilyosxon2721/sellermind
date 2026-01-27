<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\FinanceDebt;
use App\Models\Finance\FinanceDebtPayment;
use App\Services\Finance\DebtPaymentService;
use App\Support\ApiResponder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DebtController extends Controller
{
    use ApiResponder;

    public function __construct(protected DebtPaymentService $service)
    {
    }

    public function index(Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (!$companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $query = FinanceDebt::byCompany($companyId)
            ->with(['counterparty', 'employee', 'payments']);

        if ($type = $request->type) {
            $query->where('type', $type);
        }
        if ($status = $request->status) {
            $query->where('status', $status);
        }
        if ($request->active_only) {
            $query->active();
        }
        if ($request->overdue_only) {
            $query->overdue();
        }
        if ($counterpartyId = $request->counterparty_id) {
            $query->where('counterparty_id', $counterpartyId);
        }
        if ($employeeId = $request->employee_id) {
            $query->where('employee_id', $employeeId);
        }

        $debts = $query->orderByDesc('debt_date')
            ->orderByDesc('id')
            ->limit(200)
            ->get();

        return $this->successResponse($debts);
    }

    public function show($id)
    {
        $companyId = Auth::user()?->company_id;
        if (!$companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $debt = FinanceDebt::byCompany($companyId)
            ->with(['counterparty', 'employee', 'payments.transaction', 'createdBy'])
            ->findOrFail($id);

        return $this->successResponse($debt);
    }

    public function store(Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (!$companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $data = $this->validateData($request);
        $data['company_id'] = $companyId;
        $data['created_by'] = Auth::id();
        $data['amount_outstanding'] = $data['original_amount'];
        $data['amount_paid'] = 0;

        $debt = FinanceDebt::create($data);

        return $this->successResponse($debt->load(['counterparty', 'employee']));
    }

    public function update($id, Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (!$companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $debt = FinanceDebt::byCompany($companyId)->findOrFail($id);

        if ($debt->isPaid() || $debt->isWrittenOff()) {
            return $this->errorResponse('Cannot edit paid or written off debt', 'invalid_state', null, 422);
        }

        $data = $request->validate([
            'description' => ['nullable', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:64'],
            'due_date' => ['nullable', 'date'],
            'interest_rate' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $debt->update($data);

        return $this->successResponse($debt->fresh(['counterparty', 'employee']));
    }

    public function addPayment($id, Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (!$companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $debt = FinanceDebt::byCompany($companyId)->findOrFail($id);

        if ($debt->isPaid() || $debt->isWrittenOff()) {
            return $this->errorResponse('Debt is already paid or written off', 'invalid_state', null, 422);
        }

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_date' => ['required', 'date'],
            'payment_method' => ['nullable', 'in:cash,bank,card'],
            'reference' => ['nullable', 'string', 'max:64'],
            'notes' => ['nullable', 'string'],
        ]);

        if ($data['amount'] > $debt->amount_outstanding) {
            return $this->errorResponse('Payment amount exceeds outstanding debt', 'validation_error', null, 422);
        }

        $payment = $this->service->createPayment($debt, $data, Auth::id());

        return $this->successResponse([
            'payment' => $payment,
            'debt' => $debt->fresh(['counterparty', 'employee', 'payments']),
        ]);
    }

    public function payments($id)
    {
        $companyId = Auth::user()?->company_id;
        if (!$companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $debt = FinanceDebt::byCompany($companyId)->findOrFail($id);

        $payments = FinanceDebtPayment::where('debt_id', $debt->id)
            ->with('transaction')
            ->orderByDesc('payment_date')
            ->get();

        return $this->successResponse($payments);
    }

    public function writeOff($id)
    {
        $companyId = Auth::user()?->company_id;
        if (!$companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $debt = FinanceDebt::byCompany($companyId)->findOrFail($id);

        if ($debt->isPaid()) {
            return $this->errorResponse('Debt is already paid', 'invalid_state', null, 422);
        }

        if ($debt->isWrittenOff()) {
            return $this->errorResponse('Debt is already written off', 'invalid_state', null, 422);
        }

        $debt->writeOff();

        return $this->successResponse($debt->fresh(['counterparty', 'employee', 'payments']));
    }

    public function summary(Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (!$companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $receivable = FinanceDebt::byCompany($companyId)->receivable()->active();
        $payable = FinanceDebt::byCompany($companyId)->payable()->active();

        return $this->successResponse([
            'receivable' => [
                'count' => (clone $receivable)->count(),
                'total' => (clone $receivable)->sum('amount_outstanding'),
                'overdue_count' => (clone $receivable)->overdue()->count(),
                'overdue_total' => (clone $receivable)->overdue()->sum('amount_outstanding'),
            ],
            'payable' => [
                'count' => (clone $payable)->count(),
                'total' => (clone $payable)->sum('amount_outstanding'),
                'overdue_count' => (clone $payable)->overdue()->count(),
                'overdue_total' => (clone $payable)->overdue()->sum('amount_outstanding'),
            ],
        ]);
    }

    protected function validateData(Request $request): array
    {
        return $request->validate([
            'type' => ['required', 'in:receivable,payable'],
            'counterparty_id' => ['nullable', 'integer'],
            'employee_id' => ['nullable', 'integer'],
            'description' => ['required', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:64'],
            'original_amount' => ['required', 'numeric', 'min:0.01'],
            'currency_code' => ['nullable', 'string', 'max:8'],
            'debt_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date'],
            'interest_rate' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
