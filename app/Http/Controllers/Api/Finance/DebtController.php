<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Controller;
use App\Models\Counterparty;
use App\Models\Finance\Employee;
use App\Models\Finance\FinanceDebt;
use App\Models\Finance\FinanceDebtPayment;
use App\Services\Finance\DebtPaymentService;
use App\Support\ApiResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DebtController extends Controller
{
    use ApiResponder;

    public function __construct(protected DebtPaymentService $service) {}

    public function index(Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (! $companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $query = FinanceDebt::byCompany($companyId)
            ->with(['counterparty', 'counterpartyEntity', 'employee', 'payments']);

        if ($type = $request->type) {
            $query->where('type', $type);
        }
        if ($purpose = $request->purpose) {
            $query->byPurpose($purpose);
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
        if ($counterpartyEntityId = $request->counterparty_entity_id) {
            $query->byCounterpartyEntity($counterpartyEntityId);
        }
        if ($employeeId = $request->employee_id) {
            $query->where('employee_id', $employeeId);
        }
        if ($search = $request->search) {
            $search = $this->escapeLike($search);
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('reference', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
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
        if (! $companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $debt = FinanceDebt::byCompany($companyId)
            ->with(['counterparty', 'counterpartyEntity', 'employee', 'payments.transaction', 'createdBy', 'writtenOffByUser', 'cashAccount'])
            ->findOrFail($id);

        return $this->successResponse($debt);
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
        $data['amount_outstanding'] = $data['original_amount'];
        $data['amount_paid'] = 0;

        $debt = FinanceDebt::create($data);

        return $this->successResponse($debt->load(['counterparty', 'counterpartyEntity', 'employee']));
    }

    public function update($id, Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (! $companyId) {
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
            'purpose' => ['nullable', 'in:debt,prepayment,advance,loan,other'],
            'counterparty_entity_id' => ['nullable', 'integer', 'exists:counterparties,id'],
            'cash_account_id' => ['nullable', 'integer', 'exists:cash_accounts,id'],
        ]);

        $debt->update($data);

        return $this->successResponse($debt->fresh(['counterparty', 'counterpartyEntity', 'employee']));
    }

    public function addPayment($id, Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (! $companyId) {
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
            'cash_account_id' => ['nullable', 'integer', 'exists:cash_accounts,id'],
        ]);

        if ($data['amount'] > $debt->amount_outstanding) {
            return $this->errorResponse('Payment amount exceeds outstanding debt', 'validation_error', null, 422);
        }

        $payment = $this->service->createPayment($debt, $data, Auth::id());

        return $this->successResponse([
            'payment' => $payment,
            'debt' => $debt->fresh(['counterparty', 'counterpartyEntity', 'employee', 'payments']),
        ]);
    }

    public function payments($id)
    {
        $companyId = Auth::user()?->company_id;
        if (! $companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $debt = FinanceDebt::byCompany($companyId)->findOrFail($id);

        $payments = FinanceDebtPayment::where('debt_id', $debt->id)
            ->with('transaction')
            ->orderByDesc('payment_date')
            ->get();

        return $this->successResponse($payments);
    }

    public function writeOff($id, Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (! $companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $debt = FinanceDebt::byCompany($companyId)->findOrFail($id);

        if ($debt->isPaid()) {
            return $this->errorResponse('Debt is already paid', 'invalid_state', null, 422);
        }

        if ($debt->isWrittenOff()) {
            return $this->errorResponse('Debt is already written off', 'invalid_state', null, 422);
        }

        $reason = $request->input('reason');
        $debt->writeOff(Auth::id(), $reason);

        return $this->successResponse($debt->fresh(['counterparty', 'counterpartyEntity', 'employee', 'payments']));
    }

    public function summary(Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (! $companyId) {
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

    public function counterpartySummary(Request $request): JsonResponse
    {
        $companyId = Auth::user()?->company_id;
        if (! $companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $summary = FinanceDebt::byCompany($companyId)
            ->active()
            ->whereNotNull('counterparty_entity_id')
            ->select(
                'counterparty_entity_id',
                DB::raw("SUM(CASE WHEN type = 'receivable' THEN amount_outstanding ELSE 0 END) as receivable_total"),
                DB::raw("SUM(CASE WHEN type = 'payable' THEN amount_outstanding ELSE 0 END) as payable_total"),
                DB::raw('COUNT(*) as debt_count')
            )
            ->groupBy('counterparty_entity_id')
            ->get();

        $counterpartyIds = $summary->pluck('counterparty_entity_id')->filter();
        $counterparties = Counterparty::whereIn('id', $counterpartyIds)->get()->keyBy('id');

        $result = $summary->map(function ($row) use ($counterparties) {
            $cp = $counterparties->get($row->counterparty_entity_id);

            return [
                'counterparty_entity_id' => $row->counterparty_entity_id,
                'counterparty_name' => $cp?->getDisplayName(),
                'counterparty_type' => $cp?->type,
                'receivable_total' => (float) $row->receivable_total,
                'payable_total' => (float) $row->payable_total,
                'balance' => (float) $row->receivable_total - (float) $row->payable_total,
                'debt_count' => (int) $row->debt_count,
            ];
        });

        return $this->successResponse($result);
    }

    public function employeeSummary(Request $request): JsonResponse
    {
        $companyId = Auth::user()?->company_id;
        if (! $companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $summary = FinanceDebt::byCompany($companyId)
            ->active()
            ->whereNotNull('employee_id')
            ->select(
                'employee_id',
                DB::raw("SUM(CASE WHEN type = 'receivable' THEN amount_outstanding ELSE 0 END) as receivable_total"),
                DB::raw("SUM(CASE WHEN type = 'payable' THEN amount_outstanding ELSE 0 END) as payable_total"),
                DB::raw('COUNT(*) as debt_count')
            )
            ->groupBy('employee_id')
            ->get();

        $employeeIds = $summary->pluck('employee_id')->filter();
        $employees = Employee::whereIn('id', $employeeIds)->get()->keyBy('id');

        $result = $summary->map(function ($row) use ($employees) {
            $emp = $employees->get($row->employee_id);

            return [
                'employee_id' => $row->employee_id,
                'employee_name' => $emp?->full_name,
                'employee_position' => $emp?->position,
                'receivable_total' => (float) $row->receivable_total,
                'payable_total' => (float) $row->payable_total,
                'balance' => (float) $row->receivable_total - (float) $row->payable_total,
                'debt_count' => (int) $row->debt_count,
            ];
        });

        return $this->successResponse($result);
    }

    public function counterpartyLedger(int $counterpartyId): JsonResponse
    {
        $companyId = Auth::user()?->company_id;
        if (! $companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $counterparty = Counterparty::where('company_id', $companyId)->findOrFail($counterpartyId);

        $debts = FinanceDebt::byCompany($companyId)
            ->byCounterpartyEntity($counterpartyId)
            ->with(['payments.createdBy', 'createdBy'])
            ->orderByDesc('debt_date')
            ->get();

        return $this->successResponse([
            'counterparty' => $counterparty,
            'debts' => $debts,
            'totals' => [
                'receivable' => $debts->where('type', 'receivable')->sum('amount_outstanding'),
                'payable' => $debts->where('type', 'payable')->sum('amount_outstanding'),
            ],
        ]);
    }

    public function employeeLedger(int $employeeId): JsonResponse
    {
        $companyId = Auth::user()?->company_id;
        if (! $companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $employee = Employee::where('company_id', $companyId)->findOrFail($employeeId);

        $debts = FinanceDebt::byCompany($companyId)
            ->where('employee_id', $employeeId)
            ->with(['payments.createdBy', 'createdBy'])
            ->orderByDesc('debt_date')
            ->get();

        return $this->successResponse([
            'employee' => $employee,
            'debts' => $debts,
            'totals' => [
                'receivable' => $debts->where('type', 'receivable')->sum('amount_outstanding'),
                'payable' => $debts->where('type', 'payable')->sum('amount_outstanding'),
            ],
        ]);
    }

    protected function validateData(Request $request): array
    {
        return $request->validate([
            'type' => ['required', 'in:receivable,payable'],
            'purpose' => ['nullable', 'in:debt,prepayment,advance,loan,other'],
            'counterparty_id' => ['nullable', 'integer'],
            'counterparty_entity_id' => ['nullable', 'integer', 'exists:counterparties,id'],
            'employee_id' => ['nullable', 'integer'],
            'description' => ['required', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:64'],
            'original_amount' => ['required', 'numeric', 'min:0.01'],
            'currency_code' => ['nullable', 'string', 'max:8'],
            'debt_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date'],
            'interest_rate' => ['nullable', 'numeric', 'min:0'],
            'cash_account_id' => ['nullable', 'integer', 'exists:cash_accounts,id'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
