<?php

namespace App\Services\Finance;

use App\Models\Finance\Employee;
use App\Models\Finance\FinanceCategory;
use App\Models\Finance\FinanceSettings;
use App\Models\Finance\FinanceTransaction;
use App\Models\Finance\SalaryCalculation;
use App\Models\Finance\SalaryItem;
use Illuminate\Support\Facades\DB;

class SalaryCalculationService
{
    public function __construct(protected TransactionService $transactionService)
    {
    }

    public function calculate(int $companyId, int $year, int $month): SalaryCalculation
    {
        return DB::transaction(function () use ($companyId, $year, $month) {
            // Получаем или создаём расчёт
            $calculation = SalaryCalculation::updateOrCreate(
                [
                    'company_id' => $companyId,
                    'period_year' => $year,
                    'period_month' => $month,
                ],
                [
                    'status' => SalaryCalculation::STATUS_CALCULATED,
                ]
            );

            // Получаем настройки
            $settings = FinanceSettings::getForCompany($companyId);

            // Удаляем старые позиции (только если не оплачены)
            SalaryItem::where('salary_calculation_id', $calculation->id)
                ->where('is_paid', false)
                ->delete();

            // Получаем активных сотрудников
            $employees = Employee::byCompany($companyId)
                ->active()
                ->get();

            foreach ($employees as $employee) {
                // Проверяем, нет ли уже оплаченной позиции
                $existing = SalaryItem::where('salary_calculation_id', $calculation->id)
                    ->where('employee_id', $employee->id)
                    ->where('is_paid', true)
                    ->first();

                if ($existing) {
                    continue; // Пропускаем уже оплаченных
                }

                $grossAmount = $employee->base_salary;

                // Расчёт налогов (примерные ставки для Узбекистана)
                $taxAmount = $grossAmount * 0.12; // НДФЛ 12%
                $pensionAmount = $grossAmount * 0.01; // Накопительная пенсия 1%

                $netAmount = $grossAmount - $taxAmount - $pensionAmount;

                SalaryItem::create([
                    'salary_calculation_id' => $calculation->id,
                    'employee_id' => $employee->id,
                    'base_amount' => $employee->base_salary,
                    'bonuses' => 0,
                    'overtime' => 0,
                    'gross_amount' => $grossAmount,
                    'tax_amount' => $taxAmount,
                    'pension_amount' => $pensionAmount,
                    'other_deductions' => 0,
                    'net_amount' => $netAmount,
                    'is_paid' => false,
                ]);
            }

            // Пересчитываем итоги
            $calculation->recalculateTotals();

            return $calculation;
        });
    }

    public function pay(SalaryCalculation $calculation, int $userId, string $paymentDate): array
    {
        return DB::transaction(function () use ($calculation, $userId, $paymentDate) {
            $transactionsCount = 0;
            $salaryCategory = FinanceCategory::where('code', 'PAYROLL_SALARY')->first();

            $items = SalaryItem::where('salary_calculation_id', $calculation->id)
                ->where('is_paid', false)
                ->with('employee')
                ->get();

            foreach ($items as $item) {
                // Создаём транзакцию для каждого сотрудника
                $transaction = $this->transactionService->create([
                    'company_id' => $calculation->company_id,
                    'type' => FinanceTransaction::TYPE_EXPENSE,
                    'category_id' => $salaryCategory?->id,
                    'employee_id' => $item->employee_id,
                    'amount' => $item->net_amount,
                    'description' => 'Зарплата ' . $calculation->period_label . ': ' . $item->employee->full_name,
                    'transaction_date' => $paymentDate,
                    'reference' => 'SAL-' . $calculation->id . '-' . $item->id,
                    'status' => FinanceTransaction::STATUS_CONFIRMED,
                    'created_by' => $userId,
                    'confirmed_by' => $userId,
                    'confirmed_at' => now(),
                ]);

                $item->markAsPaid($transaction->id);
                $transactionsCount++;
            }

            $calculation->update(['status' => SalaryCalculation::STATUS_PAID]);

            return ['transactions_count' => $transactionsCount];
        });
    }

    public function payItem(SalaryItem $item, int $userId, string $paymentDate): FinanceTransaction
    {
        return DB::transaction(function () use ($item, $userId, $paymentDate) {
            $calculation = $item->calculation;
            $salaryCategory = FinanceCategory::where('code', 'PAYROLL_SALARY')->first();

            $transaction = $this->transactionService->create([
                'company_id' => $calculation->company_id,
                'type' => FinanceTransaction::TYPE_EXPENSE,
                'category_id' => $salaryCategory?->id,
                'employee_id' => $item->employee_id,
                'amount' => $item->net_amount,
                'description' => 'Зарплата ' . $calculation->period_label . ': ' . $item->employee->full_name,
                'transaction_date' => $paymentDate,
                'reference' => 'SAL-' . $calculation->id . '-' . $item->id,
                'status' => FinanceTransaction::STATUS_CONFIRMED,
                'created_by' => $userId,
                'confirmed_by' => $userId,
                'confirmed_at' => now(),
            ]);

            $item->markAsPaid($transaction->id);

            return $transaction;
        });
    }
}
