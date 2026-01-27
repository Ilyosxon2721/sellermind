<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\Employee;
use App\Models\Finance\FinanceSettings;
use App\Models\Finance\SalaryCalculation;
use App\Models\Finance\SalaryItem;
use App\Services\Finance\SalaryCalculationService;
use App\Support\ApiResponder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SalaryController extends Controller
{
    use ApiResponder;

    public function __construct(protected SalaryCalculationService $service)
    {
    }

    public function calculations(Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (!$companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $query = SalaryCalculation::byCompany($companyId)
            ->withCount('items');

        if ($year = $request->year) {
            $query->where('period_year', $year);
        }
        if ($status = $request->status) {
            $query->where('status', $status);
        }

        $calculations = $query->orderByDesc('period_year')
            ->orderByDesc('period_month')
            ->limit(24)
            ->get();

        return $this->successResponse($calculations);
    }

    public function showCalculation($id)
    {
        $companyId = Auth::user()?->company_id;
        if (!$companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $calculation = SalaryCalculation::byCompany($companyId)
            ->with(['items.employee', 'approvedBy'])
            ->findOrFail($id);

        return $this->successResponse($calculation);
    }

    public function calculate(Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (!$companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $data = $request->validate([
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
        ]);

        // Проверяем существование
        $existing = SalaryCalculation::byCompany($companyId)
            ->forPeriod($data['year'], $data['month'])
            ->first();

        if ($existing && $existing->isPaid()) {
            return $this->errorResponse('Salary for this period is already paid', 'invalid_state', null, 422);
        }

        $calculation = $this->service->calculate($companyId, $data['year'], $data['month']);

        return $this->successResponse($calculation->load(['items.employee']));
    }

    public function updateItem($calculationId, $itemId, Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (!$companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $calculation = SalaryCalculation::byCompany($companyId)->findOrFail($calculationId);

        if ($calculation->isPaid()) {
            return $this->errorResponse('Cannot edit paid calculation', 'invalid_state', null, 422);
        }

        $item = SalaryItem::where('salary_calculation_id', $calculation->id)
            ->findOrFail($itemId);

        $data = $request->validate([
            'bonuses' => ['nullable', 'numeric', 'min:0'],
            'overtime' => ['nullable', 'numeric', 'min:0'],
            'other_deductions' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $item->update($data);

        // Пересчитываем
        $item->calculateTotals();
        $item->save();

        // Пересчитываем итоги расчёта
        $calculation->recalculateTotals();

        return $this->successResponse([
            'item' => $item->fresh('employee'),
            'calculation' => $calculation->fresh(),
        ]);
    }

    public function approveCalculation($id)
    {
        $companyId = Auth::user()?->company_id;
        if (!$companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $calculation = SalaryCalculation::byCompany($companyId)->findOrFail($id);

        if (!$calculation->isCalculated()) {
            return $this->errorResponse('Calculation must be in calculated status', 'invalid_state', null, 422);
        }

        $calculation->approve(Auth::id());

        return $this->successResponse($calculation->fresh(['items.employee', 'approvedBy']));
    }

    public function payCalculation($id, Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (!$companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $calculation = SalaryCalculation::byCompany($companyId)->findOrFail($id);

        if (!$calculation->isApproved()) {
            return $this->errorResponse('Calculation must be approved first', 'invalid_state', null, 422);
        }

        $paymentDate = $request->payment_date ?? now()->format('Y-m-d');

        $result = $this->service->pay($calculation, Auth::id(), $paymentDate);

        return $this->successResponse([
            'calculation' => $calculation->fresh(['items.employee']),
            'transactions_created' => $result['transactions_count'],
        ]);
    }

    public function payItem($calculationId, $itemId, Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (!$companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $calculation = SalaryCalculation::byCompany($companyId)->findOrFail($calculationId);

        if (!$calculation->isApproved()) {
            return $this->errorResponse('Calculation must be approved first', 'invalid_state', null, 422);
        }

        $item = SalaryItem::where('salary_calculation_id', $calculation->id)
            ->findOrFail($itemId);

        if ($item->is_paid) {
            return $this->errorResponse('Item is already paid', 'invalid_state', null, 422);
        }

        $paymentDate = $request->payment_date ?? now()->format('Y-m-d');

        $transaction = $this->service->payItem($item, Auth::id(), $paymentDate);

        // Проверяем все ли позиции оплачены
        $allPaid = SalaryItem::where('salary_calculation_id', $calculation->id)
            ->where('is_paid', false)
            ->doesntExist();

        if ($allPaid) {
            $calculation->update(['status' => SalaryCalculation::STATUS_PAID]);
        }

        return $this->successResponse([
            'item' => $item->fresh('employee'),
            'transaction' => $transaction,
            'calculation' => $calculation->fresh(),
        ]);
    }
}
