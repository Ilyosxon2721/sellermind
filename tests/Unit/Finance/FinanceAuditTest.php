<?php

declare(strict_types=1);

namespace Tests\Unit\Finance;

use App\Models\Company;
use App\Models\Finance\CashAccount;
use App\Models\Finance\CashTransaction;
use App\Models\Finance\FinanceCategory;
use App\Models\Finance\FinanceDebt;
use App\Models\Finance\FinanceDebtPayment;
use App\Models\Finance\FinanceSettings;
use App\Models\Finance\FinanceTransaction;
use App\Models\User;
use App\Services\Finance\DebtPaymentService;
use App\Services\Finance\FinanceReportService;
use App\Services\Finance\TransactionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class FinanceAuditTest extends TestCase
{
    use RefreshDatabase;

    private TransactionService $transactionService;

    private DebtPaymentService $debtPaymentService;

    private FinanceReportService $reportService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->transactionService = app(TransactionService::class);
        $this->debtPaymentService = app(DebtPaymentService::class);
        $this->reportService = app(FinanceReportService::class);
    }

    private function createCompany(): Company
    {
        return Company::create(['name' => 'Test Co']);
    }

    private function createUser(int $companyId): User
    {
        return User::create([
            'name' => 'Test User',
            'email' => 'test'.uniqid().'@example.com',
            'password' => 'password',
            'company_id' => $companyId,
        ]);
    }

    private function createCashAccount(int $companyId, float $balance = 0): CashAccount
    {
        return CashAccount::create([
            'company_id' => $companyId,
            'name' => 'Test Account '.uniqid(),
            'type' => CashAccount::TYPE_CASH,
            'currency_code' => 'UZS',
            'balance' => $balance,
            'is_active' => true,
        ]);
    }

    private function createConfirmedTransaction(int $companyId, string $type, float $amount, string $currency = 'UZS', float $exchangeRate = 1, ?int $categoryId = null): FinanceTransaction
    {
        return $this->transactionService->create([
            'company_id' => $companyId,
            'type' => $type,
            'amount' => $amount,
            'currency_code' => $currency,
            'exchange_rate' => $exchangeRate,
            'category_id' => $categoryId,
            'transaction_date' => now()->toDateString(),
            'description' => "Test {$type}",
            'status' => FinanceTransaction::STATUS_CONFIRMED,
            'confirmed_at' => now(),
        ]);
    }

    // -------------------------------------------------------------------------
    // P&L uses amount_base not amount
    // -------------------------------------------------------------------------

    public function test_pnl_uses_amount_base_not_amount(): void
    {
        $company = $this->createCompany();

        // Доход 100 USD * 12700 = 1 270 000 UZS (amount_base)
        $this->createConfirmedTransaction($company->id, FinanceTransaction::TYPE_INCOME, 100, 'USD', 12700);

        // Расход 5000 RUB * 140 = 700 000 UZS (amount_base)
        $this->createConfirmedTransaction($company->id, FinanceTransaction::TYPE_EXPENSE, 5000, 'RUB', 140);

        $from = now()->startOfDay();
        $to = now()->endOfDay();

        $pnl = $this->reportService->getProfitAndLoss($company->id, $from, $to);

        // Если бы суммировался amount: income=100, expense=5000 → profit=-4900 (НЕВЕРНО)
        // С amount_base: income=1270000, expense=700000 → profit=570000 (ВЕРНО)
        $this->assertEquals(1270000, $pnl['income']['total']);
        $this->assertEquals(700000, $pnl['expenses']['total']);
        $this->assertEquals(570000, $pnl['gross_profit']);
    }

    // -------------------------------------------------------------------------
    // Expenses by category uses amount_base
    // -------------------------------------------------------------------------

    public function test_expenses_by_category_uses_amount_base(): void
    {
        $company = $this->createCompany();

        $category = FinanceCategory::create([
            'code' => 'TEST_EXPENSE',
            'name' => 'Тест расход',
            'type' => FinanceCategory::TYPE_EXPENSE,
            'is_system' => true,
            'is_active' => true,
        ]);

        // Расход 200 USD * 12700 = 2 540 000 UZS
        $this->createConfirmedTransaction($company->id, FinanceTransaction::TYPE_EXPENSE, 200, 'USD', 12700, $category->id);

        // Расход 3000 RUB * 140 = 420 000 UZS
        $this->createConfirmedTransaction($company->id, FinanceTransaction::TYPE_EXPENSE, 3000, 'RUB', 140, $category->id);

        $from = now()->startOfDay();
        $to = now()->endOfDay();

        $expenses = $this->reportService->getExpensesByCategory($company->id, $from, $to);

        // Должно быть 2540000 + 420000 = 2960000 (amount_base)
        // А не 200 + 3000 = 3200 (amount)
        $this->assertCount(1, $expenses);
        $this->assertEquals(2960000, $expenses[0]['amount']);
        $this->assertEquals('Тест расход', $expenses[0]['category']);
    }

    // -------------------------------------------------------------------------
    // Cash flow uses amount_base
    // -------------------------------------------------------------------------

    public function test_cash_flow_uses_amount_base(): void
    {
        $company = $this->createCompany();

        // Доход 50 USD * 12700 = 635 000 UZS
        $this->createConfirmedTransaction($company->id, FinanceTransaction::TYPE_INCOME, 50, 'USD', 12700);

        // Расход 1000 RUB * 140 = 140 000 UZS
        $this->createConfirmedTransaction($company->id, FinanceTransaction::TYPE_EXPENSE, 1000, 'RUB', 140);

        $from = now()->startOfDay();
        $to = now()->endOfDay();

        $cashFlow = $this->reportService->getCashFlow($company->id, $from, $to);

        $this->assertCount(1, $cashFlow);
        $this->assertEquals(635000, $cashFlow[0]['income']);
        $this->assertEquals(140000, $cashFlow[0]['expense']);
        $this->assertEquals(495000, $cashFlow[0]['net']);
    }

    // -------------------------------------------------------------------------
    // Cancel payment reverses cash account balance
    // -------------------------------------------------------------------------

    public function test_cancel_payment_reverses_cash_account_balance(): void
    {
        $company = $this->createCompany();
        $user = $this->createUser($company->id);

        FinanceCategory::create([
            'code' => 'OTHER_EXPENSE',
            'name' => 'Прочий расход',
            'type' => FinanceCategory::TYPE_EXPENSE,
            'is_system' => true,
            'is_active' => true,
        ]);

        $account = $this->createCashAccount($company->id, 500000);
        $initialBalance = $account->balance;

        // Создаём долг (payable = мы должны)
        $debt = FinanceDebt::create([
            'company_id' => $company->id,
            'type' => FinanceDebt::TYPE_PAYABLE,
            'purpose' => FinanceDebt::PURPOSE_DEBT,
            'description' => 'Test debt',
            'original_amount' => 100000,
            'amount_paid' => 0,
            'amount_outstanding' => 100000,
            'currency_code' => 'UZS',
            'debt_date' => now(),
            'due_date' => now()->addDays(30),
            'status' => FinanceDebt::STATUS_ACTIVE,
            'created_by' => $user->id,
        ]);

        // Оплачиваем долг с кассового счёта
        $payment = $this->debtPaymentService->createPayment($debt, [
            'amount' => 100000,
            'payment_date' => now()->toDateString(),
            'payment_method' => 'cash',
            'cash_account_id' => $account->id,
        ], $user->id);

        // Баланс уменьшился (payable = расход)
        $account->refresh();
        $this->assertEquals($initialBalance - 100000, $account->balance);

        // Отменяем платёж
        $this->debtPaymentService->cancelPayment($payment);

        // Баланс должен вернуться к исходному
        $account->refresh();
        $this->assertEquals($initialBalance, $account->balance);

        // Кассовая транзакция отменена
        $cashTx = CashTransaction::where('source_type', FinanceDebtPayment::class)
            ->where('source_id', $payment->id)
            ->first();
        $this->assertNotNull($cashTx);
        $this->assertEquals(CashTransaction::STATUS_CANCELLED, $cashTx->status);
    }

    // -------------------------------------------------------------------------
    // Transfer records balance_before
    // -------------------------------------------------------------------------

    public function test_transfer_records_balance_before(): void
    {
        $company = $this->createCompany();
        $user = $this->createUser($company->id);

        $fromAccount = $this->createCashAccount($company->id, 300000);
        $toAccount = $this->createCashAccount($company->id, 50000);

        // Симулируем перевод напрямую (как делает CashAccountController::transfer)
        \DB::beginTransaction();

        $from = CashAccount::lockForUpdate()->findOrFail($fromAccount->id);
        $to = CashAccount::lockForUpdate()->findOrFail($toAccount->id);

        $fromBalanceBefore = $from->balance;
        $from->balance -= 100000;
        $from->save();

        $outTx = CashTransaction::create([
            'company_id' => $company->id,
            'cash_account_id' => $from->id,
            'type' => CashTransaction::TYPE_TRANSFER_OUT,
            'operation' => CashTransaction::OP_TRANSFER,
            'amount' => 100000,
            'balance_before' => $fromBalanceBefore,
            'balance_after' => $from->balance,
            'currency_code' => 'UZS',
            'transaction_date' => now()->toDateString(),
            'status' => CashTransaction::STATUS_CONFIRMED,
            'created_by' => $user->id,
        ]);

        $toBalanceBefore = $to->balance;
        $to->balance += 100000;
        $to->save();

        $inTx = CashTransaction::create([
            'company_id' => $company->id,
            'cash_account_id' => $to->id,
            'type' => CashTransaction::TYPE_TRANSFER_IN,
            'operation' => CashTransaction::OP_TRANSFER,
            'amount' => 100000,
            'balance_before' => $toBalanceBefore,
            'balance_after' => $to->balance,
            'currency_code' => 'UZS',
            'transaction_date' => now()->toDateString(),
            'status' => CashTransaction::STATUS_CONFIRMED,
            'created_by' => $user->id,
        ]);

        \DB::commit();

        // Проверяем balance_before
        $this->assertEquals(300000, $outTx->balance_before);
        $this->assertEquals(200000, $outTx->balance_after);
        $this->assertEquals(50000, $inTx->balance_before);
        $this->assertEquals(150000, $inTx->balance_after);
    }

    // -------------------------------------------------------------------------
    // Income records balance_before
    // -------------------------------------------------------------------------

    public function test_income_records_balance_before(): void
    {
        $company = $this->createCompany();
        $user = $this->createUser($company->id);

        $account = $this->createCashAccount($company->id, 200000);

        \DB::beginTransaction();
        $locked = CashAccount::lockForUpdate()->findOrFail($account->id);
        $balanceBefore = $locked->balance;
        $locked->balance += 50000;
        $locked->save();

        $tx = CashTransaction::create([
            'company_id' => $company->id,
            'cash_account_id' => $locked->id,
            'type' => CashTransaction::TYPE_INCOME,
            'amount' => 50000,
            'balance_before' => $balanceBefore,
            'balance_after' => $locked->balance,
            'currency_code' => 'UZS',
            'transaction_date' => now()->toDateString(),
            'status' => CashTransaction::STATUS_CONFIRMED,
            'created_by' => $user->id,
        ]);
        \DB::commit();

        $this->assertEquals(200000, $tx->balance_before);
        $this->assertEquals(250000, $tx->balance_after);
    }
}
