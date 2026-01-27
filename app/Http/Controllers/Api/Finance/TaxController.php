<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\TaxCalculation;
use App\Services\Finance\TaxCalculationService;
use App\Support\ApiResponder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaxController extends Controller
{
    use ApiResponder;

    public function __construct(protected TaxCalculationService $service)
    {
    }

    public function index(Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (!$companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $query = TaxCalculation::byCompany($companyId);

        if ($year = $request->year) {
            $query->forYear($year);
        }
        if ($taxType = $request->tax_type) {
            $query->byType($taxType);
        }
        if ($status = $request->status) {
            $query->where('status', $status);
        }

        $taxes = $query->orderByDesc('period_year')
            ->orderByDesc('period_month')
            ->limit(50)
            ->get();

        return $this->successResponse($taxes);
    }

    public function show($id)
    {
        $companyId = Auth::user()?->company_id;
        if (!$companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $tax = TaxCalculation::byCompany($companyId)
            ->with('transaction')
            ->findOrFail($id);

        return $this->successResponse($tax);
    }

    public function calculate(Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (!$companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $data = $request->validate([
            'tax_type' => ['required', 'in:income_tax,vat,social_tax,simplified'],
            'period_type' => ['required', 'in:month,quarter,year'],
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'month' => ['required_if:period_type,month', 'integer', 'min:1', 'max:12'],
            'quarter' => ['required_if:period_type,quarter', 'integer', 'min:1', 'max:4'],
        ]);

        $calculation = $this->service->calculate(
            $companyId,
            $data['tax_type'],
            $data['period_type'],
            $data['year'],
            $data['month'] ?? null,
            $data['quarter'] ?? null
        );

        return $this->successResponse($calculation);
    }

    public function pay($id, Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (!$companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $tax = TaxCalculation::byCompany($companyId)->findOrFail($id);

        if ($tax->isPaid()) {
            return $this->errorResponse('Tax is already paid', 'invalid_state', null, 422);
        }

        $data = $request->validate([
            'payment_date' => ['nullable', 'date'],
            'amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $paymentDate = $data['payment_date'] ?? now()->format('Y-m-d');
        $amount = $data['amount'] ?? $tax->amount_outstanding;

        $transaction = $this->service->pay($tax, Auth::id(), $paymentDate, $amount);

        return $this->successResponse([
            'tax' => $tax->fresh('transaction'),
            'transaction' => $transaction,
        ]);
    }

    public function summary(Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (!$companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $year = $request->year ?? now()->year;

        $taxes = TaxCalculation::byCompany($companyId)
            ->forYear($year)
            ->get();

        $byType = $taxes->groupBy('tax_type')->map(function ($group) {
            return [
                'calculated' => $group->sum('calculated_amount'),
                'paid' => $group->sum('paid_amount'),
                'outstanding' => $group->sum(fn($t) => $t->amount_outstanding),
            ];
        });

        return $this->successResponse([
            'year' => $year,
            'total_calculated' => $taxes->sum('calculated_amount'),
            'total_paid' => $taxes->sum('paid_amount'),
            'total_outstanding' => $taxes->sum(fn($t) => $t->amount_outstanding),
            'by_type' => $byType,
        ]);
    }
}
