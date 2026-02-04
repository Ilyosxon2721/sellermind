<?php

namespace App\Services\Finance;

use App\Models\Finance\FinanceCategory;
use App\Models\Finance\FinanceSettings;
use App\Models\Finance\FinanceTransaction;
use App\Models\Finance\TaxCalculation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TaxCalculationService
{
    public function __construct(protected TransactionService $transactionService) {}

    public function calculate(
        int $companyId,
        string $taxType,
        string $periodType,
        int $year,
        ?int $month = null,
        ?int $quarter = null
    ): TaxCalculation {
        return DB::transaction(function () use ($companyId, $taxType, $periodType, $year, $month, $quarter) {
            $settings = FinanceSettings::getForCompany($companyId);

            // Определяем период
            [$from, $to] = $this->getPeriodDates($periodType, $year, $month, $quarter);

            // Определяем налоговую ставку
            $taxRate = $this->getTaxRate($taxType, $settings);

            // Рассчитываем налоговую базу
            $taxableBase = $this->calculateTaxableBase($companyId, $taxType, $from, $to);

            // Рассчитываем сумму налога
            $calculatedAmount = $taxableBase * ($taxRate / 100);

            // Срок уплаты (примерно)
            $dueDate = $this->getDueDate($periodType, $year, $month, $quarter);

            // Ищем существующий или создаём новый
            $calculation = TaxCalculation::updateOrCreate(
                [
                    'company_id' => $companyId,
                    'tax_type' => $taxType,
                    'tax_period_type' => $periodType,
                    'period_year' => $year,
                    'period_month' => $month,
                    'period_quarter' => $quarter,
                ],
                [
                    'taxable_base' => $taxableBase,
                    'tax_rate' => $taxRate,
                    'calculated_amount' => $calculatedAmount,
                    'status' => TaxCalculation::STATUS_CALCULATED,
                    'due_date' => $dueDate,
                ]
            );

            // Проверяем просрочку
            if ($calculation->isOverdue()) {
                $calculation->update(['status' => TaxCalculation::STATUS_OVERDUE]);
            }

            return $calculation;
        });
    }

    public function pay(TaxCalculation $tax, int $userId, string $paymentDate, float $amount): FinanceTransaction
    {
        return DB::transaction(function () use ($tax, $userId, $paymentDate, $amount) {
            $taxCategory = FinanceCategory::where('code', $this->getTaxCategoryCode($tax->tax_type))->first();

            $transaction = $this->transactionService->create([
                'company_id' => $tax->company_id,
                'type' => FinanceTransaction::TYPE_EXPENSE,
                'category_id' => $taxCategory?->id,
                'amount' => $amount,
                'description' => $tax->tax_type_label.' за '.$tax->period_label,
                'transaction_date' => $paymentDate,
                'reference' => 'TAX-'.$tax->id,
                'status' => FinanceTransaction::STATUS_CONFIRMED,
                'created_by' => $userId,
                'confirmed_by' => $userId,
                'confirmed_at' => now(),
            ]);

            $tax->paid_amount += $amount;

            if ($tax->paid_amount >= $tax->calculated_amount) {
                $tax->status = TaxCalculation::STATUS_PAID;
            }

            $tax->transaction_id = $transaction->id;
            $tax->save();

            return $transaction;
        });
    }

    protected function getPeriodDates(string $periodType, int $year, ?int $month, ?int $quarter): array
    {
        if ($periodType === TaxCalculation::PERIOD_MONTH) {
            $from = Carbon::create($year, $month, 1)->startOfMonth();
            $to = $from->copy()->endOfMonth();
        } elseif ($periodType === TaxCalculation::PERIOD_QUARTER) {
            $startMonth = ($quarter - 1) * 3 + 1;
            $from = Carbon::create($year, $startMonth, 1)->startOfMonth();
            $to = $from->copy()->addMonths(2)->endOfMonth();
        } else {
            $from = Carbon::create($year, 1, 1)->startOfYear();
            $to = Carbon::create($year, 12, 31)->endOfYear();
        }

        return [$from, $to];
    }

    protected function getTaxRate(string $taxType, FinanceSettings $settings): float
    {
        return match ($taxType) {
            TaxCalculation::TYPE_INCOME_TAX, TaxCalculation::TYPE_SIMPLIFIED => $settings->income_tax_rate,
            TaxCalculation::TYPE_VAT => $settings->vat_rate,
            TaxCalculation::TYPE_SOCIAL_TAX => $settings->social_tax_rate,
            default => 0,
        };
    }

    protected function calculateTaxableBase(int $companyId, string $taxType, Carbon $from, Carbon $to): float
    {
        $transactions = FinanceTransaction::byCompany($companyId)
            ->confirmed()
            ->inPeriod($from, $to);

        return match ($taxType) {
            // Упрощёнка - с дохода
            TaxCalculation::TYPE_SIMPLIFIED => (clone $transactions)->income()->sum('amount'),

            // Налог на прибыль - доход минус расход
            TaxCalculation::TYPE_INCOME_TAX => (clone $transactions)->income()->sum('amount')
                - (clone $transactions)->expense()->sum('amount'),

            // НДС - с дохода
            TaxCalculation::TYPE_VAT => (clone $transactions)->income()->sum('amount'),

            // Социальный налог - с ФОТ (зарплаты)
            TaxCalculation::TYPE_SOCIAL_TAX => $this->getPayrollBase($companyId, $from, $to),

            default => 0,
        };
    }

    protected function getPayrollBase(int $companyId, Carbon $from, Carbon $to): float
    {
        $salaryCategory = FinanceCategory::where('code', 'PAYROLL_SALARY')->first();

        if (! $salaryCategory) {
            return 0;
        }

        return FinanceTransaction::byCompany($companyId)
            ->confirmed()
            ->where('category_id', $salaryCategory->id)
            ->inPeriod($from, $to)
            ->sum('amount');
    }

    protected function getDueDate(string $periodType, int $year, ?int $month, ?int $quarter): Carbon
    {
        // Примерные сроки для Узбекистана
        if ($periodType === TaxCalculation::PERIOD_MONTH) {
            // До 25 числа следующего месяца
            return Carbon::create($year, $month, 1)->addMonth()->day(25);
        }

        if ($periodType === TaxCalculation::PERIOD_QUARTER) {
            // До 25 числа месяца после квартала
            $endMonth = $quarter * 3;

            return Carbon::create($year, $endMonth, 1)->addMonth()->day(25);
        }

        // Год - до 1 апреля следующего года
        return Carbon::create($year + 1, 4, 1);
    }

    protected function getTaxCategoryCode(string $taxType): string
    {
        return match ($taxType) {
            TaxCalculation::TYPE_INCOME_TAX => 'TAX_INCOME',
            TaxCalculation::TYPE_VAT => 'TAX_VAT',
            TaxCalculation::TYPE_SOCIAL_TAX => 'TAX_SOCIAL',
            TaxCalculation::TYPE_SIMPLIFIED => 'TAX_SIMPLIFIED',
            default => 'TAX_OTHER',
        };
    }
}
