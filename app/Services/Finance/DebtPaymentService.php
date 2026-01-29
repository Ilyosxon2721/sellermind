<?php

namespace App\Services\Finance;

use App\Models\Finance\FinanceCategory;
use App\Models\Finance\FinanceDebt;
use App\Models\Finance\FinanceDebtPayment;
use App\Models\Finance\FinanceTransaction;
use Illuminate\Support\Facades\DB;

class DebtPaymentService
{
    public function __construct(protected TransactionService $transactionService)
    {
    }

    public function createPayment(FinanceDebt $debt, array $data, int $userId): FinanceDebtPayment
    {
        return DB::transaction(function () use ($debt, $data, $userId) {
            // Создаём платёж
            $payment = FinanceDebtPayment::create([
                'company_id' => $debt->company_id,
                'debt_id' => $debt->id,
                'amount' => $data['amount'],
                'payment_date' => $data['payment_date'],
                'payment_method' => $data['payment_method'] ?? 'cash',
                'reference' => $data['reference'] ?? null,
                'notes' => $data['notes'] ?? null,
                'cash_account_id' => $data['cash_account_id'] ?? null,
                'status' => FinanceDebtPayment::STATUS_POSTED,
                'created_by' => $userId,
            ]);

            // Создаём транзакцию
            $transactionType = $debt->type === FinanceDebt::TYPE_RECEIVABLE
                ? FinanceTransaction::TYPE_INCOME  // Получили деньги
                : FinanceTransaction::TYPE_EXPENSE; // Заплатили

            $categoryCode = $debt->type === FinanceDebt::TYPE_RECEIVABLE
                ? 'INCOME_OTHER'
                : 'OTHER_EXPENSE';

            $category = FinanceCategory::where('code', $categoryCode)->first();

            $transaction = $this->transactionService->create([
                'company_id' => $debt->company_id,
                'type' => $transactionType,
                'category_id' => $category?->id,
                'counterparty_id' => $debt->counterparty_id,
                'employee_id' => $debt->employee_id,
                'amount' => $data['amount'],
                'currency_code' => $debt->currency_code,
                'description' => 'Погашение долга: ' . $debt->description,
                'transaction_date' => $data['payment_date'],
                'reference' => $data['reference'] ?? $payment->id,
                'status' => FinanceTransaction::STATUS_CONFIRMED,
                'created_by' => $userId,
                'confirmed_by' => $userId,
                'confirmed_at' => now(),
            ]);

            $payment->update(['transaction_id' => $transaction->id]);

            // Обновляем долг
            $debt->applyPayment($data['amount']);

            return $payment;
        });
    }

    public function cancelPayment(FinanceDebtPayment $payment): bool
    {
        return DB::transaction(function () use ($payment) {
            $debt = $payment->debt;

            // Откатываем платёж
            $debt->amount_paid -= $payment->amount;
            $debt->amount_outstanding += $payment->amount;

            if ($debt->amount_paid <= 0) {
                $debt->status = FinanceDebt::STATUS_ACTIVE;
            } else {
                $debt->status = FinanceDebt::STATUS_PARTIALLY_PAID;
            }

            $debt->save();

            // Отменяем транзакцию
            if ($payment->transaction) {
                $payment->transaction->cancel();
            }

            $payment->update(['status' => FinanceDebtPayment::STATUS_CANCELLED]);

            return true;
        });
    }
}
