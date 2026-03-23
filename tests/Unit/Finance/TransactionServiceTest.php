<?php

declare(strict_types=1);

namespace Tests\Unit\Finance;

use App\Models\Company;
use App\Models\Finance\CashAccount;
use App\Models\Finance\FinanceCategory;
use App\Models\Finance\FinanceDebt;
use App\Models\Finance\FinanceDebtPayment;
use App\Models\Finance\FinanceSettings;
use App\Models\Finance\FinanceTransaction;
use App\Models\User;
use App\Services\Finance\DebtPaymentService;
use App\Services\Finance\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class TransactionServiceTest extends TestCase
{
    use RefreshDatabase;

    private TransactionService $transactionService;

    private DebtPaymentService $debtPaymentService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->transactionService = app(TransactionService::class);
        $this->debtPaymentService = app(DebtPaymentService::class);
    }

    private function createCompany(): Company
    {
        return Company::create(['name' => 'Test Co']);
    }

    private function createUser(int $companyId): User
    {
        return User::create([
            'name' => 'Test User',
            'email' => 'test' . uniqid() . '@example.com',
            'password' => 'password',
            'company_id' => $companyId,
        ]);
    }

    private function createDebt(int $companyId, string $type, float $amount, int $userId): FinanceDebt
    {
        return FinanceDebt::create([
            'company_id' => $companyId,
            'type' => $type,
            'purpose' => FinanceDebt::PURPOSE_DEBT,
            'description' => 'Test debt',
            'original_amount' => $amount,
            'amount_paid' => 0,
            'amount_outstanding' => $amount,
            'currency_code' => 'UZS',
            'debt_date' => now(),
            'due_date' => now()->addDays(30),
            'status' => FinanceDebt::STATUS_ACTIVE,
            'created_by' => $userId,
        ]);
    }

    // -------------------------------------------------------------------------
    // TransactionService tests
    // -------------------------------------------------------------------------

    public function test_create_transaction_with_defaults(): void
    {
        // Arrange
        $company = $this->createCompany();

        // Act
        $transaction = $this->transactionService->create([
            'company_id' => $company->id,
            'type' => FinanceTransaction::TYPE_INCOME,
            'amount' => 50000,
            'transaction_date' => now()->toDateString(),
            'description' => 'Test income',
        ]);

        // Assert
        $this->assertInstanceOf(FinanceTransaction::class, $transaction);
        $this->assertTrue($transaction->exists);

        // Валюта по умолчанию из FinanceSettings
        $settings = FinanceSettings::getForCompany($company->id);
        $this->assertSame($settings->base_currency_code, $transaction->currency_code);
        $this->assertSame('UZS', $transaction->currency_code);

        // Статус по умолчанию - черновик
        $this->assertSame(FinanceTransaction::STATUS_DRAFT, $transaction->status);
        $this->assertTrue($transaction->isDraft());

        // amount_base = amount * exchange_rate (rate = 1 by default)
        $this->assertEquals(50000, $transaction->amount_base);
        $this->assertEquals(1, $transaction->exchange_rate);
    }

    public function test_create_transaction_with_custom_exchange_rate(): void
    {
        // Arrange
        $company = $this->createCompany();

        // Act
        $transaction = $this->transactionService->create([
            'company_id' => $company->id,
            'type' => FinanceTransaction::TYPE_EXPENSE,
            'amount' => 100,
            'currency_code' => 'USD',
            'exchange_rate' => 12700,
            'transaction_date' => now()->toDateString(),
            'description' => 'Purchase in USD',
        ]);

        // Assert
        $this->assertSame('USD', $transaction->currency_code);
        $this->assertEquals(12700, $transaction->exchange_rate);
        // amount_base = 100 * 12700 = 1 270 000
        $this->assertEquals(100 * 12700, $transaction->amount_base);
    }

    public function test_update_transaction_recalculates_amount_base(): void
    {
        // Arrange
        $company = $this->createCompany();
        $transaction = $this->transactionService->create([
            'company_id' => $company->id,
            'type' => FinanceTransaction::TYPE_INCOME,
            'amount' => 200,
            'currency_code' => 'USD',
            'exchange_rate' => 12700,
            'transaction_date' => now()->toDateString(),
            'description' => 'Original',
        ]);
        $this->assertEquals(200 * 12700, $transaction->amount_base);

        // Act -- изменяем сумму
        $updated = $this->transactionService->update($transaction, [
            'amount' => 300,
        ]);

        // Assert -- amount_base пересчитан с тем же курсом
        $this->assertEquals(300 * 12700, $updated->amount_base);

        // Act -- изменяем курс
        $updated2 = $this->transactionService->update($updated, [
            'exchange_rate' => 13000,
        ]);

        // Assert -- amount_base пересчитан с новым курсом и текущей суммой
        $this->assertEquals(300 * 13000, $updated2->amount_base);
    }

    public function test_create_from_source_sets_source_fields(): void
    {
        // Arrange
        $company = $this->createCompany();

        // Act
        $transaction = $this->transactionService->createFromSource(
            [
                'company_id' => $company->id,
                'type' => FinanceTransaction::TYPE_EXPENSE,
                'amount' => 75000,
                'transaction_date' => now()->toDateString(),
                'description' => 'From source',
            ],
            'App\\Models\\Sale',
            42
        );

        // Assert
        $this->assertSame('App\\Models\\Sale', $transaction->source_type);
        $this->assertEquals(42, $transaction->source_id);
        $this->assertTrue($transaction->exists);
        $this->assertEquals(75000, $transaction->amount);
    }

    public function test_transaction_confirm(): void
    {
        // Arrange
        $company = $this->createCompany();
        $user = $this->createUser($company->id);

        $transaction = $this->transactionService->create([
            'company_id' => $company->id,
            'type' => FinanceTransaction::TYPE_INCOME,
            'amount' => 10000,
            'transaction_date' => now()->toDateString(),
            'description' => 'To confirm',
        ]);
        $this->assertTrue($transaction->isDraft());

        // Act
        $result = $transaction->confirm($user->id);

        // Assert
        $this->assertTrue($result);
        $transaction->refresh();
        $this->assertSame(FinanceTransaction::STATUS_CONFIRMED, $transaction->status);
        $this->assertTrue($transaction->isConfirmed());
        $this->assertEquals($user->id, $transaction->confirmed_by);
        $this->assertNotNull($transaction->confirmed_at);
    }

    public function test_transaction_cancel(): void
    {
        // Arrange
        $company = $this->createCompany();
        $user = $this->createUser($company->id);

        $transaction = $this->transactionService->create([
            'company_id' => $company->id,
            'type' => FinanceTransaction::TYPE_EXPENSE,
            'amount' => 5000,
            'transaction_date' => now()->toDateString(),
            'description' => 'To cancel',
        ]);
        // Сначала подтверждаем, потом отменяем
        $transaction->confirm($user->id);
        $this->assertTrue($transaction->isConfirmed());

        // Act
        $result = $transaction->cancel();

        // Assert
        $this->assertTrue($result);
        $transaction->refresh();
        $this->assertSame(FinanceTransaction::STATUS_CANCELLED, $transaction->status);
        $this->assertTrue($transaction->isCancelled());

        // Повторная отмена возвращает false
        $this->assertFalse($transaction->cancel());
    }

    public function test_transaction_soft_delete_and_restore(): void
    {
        // Arrange
        $company = $this->createCompany();
        $transaction = $this->transactionService->create([
            'company_id' => $company->id,
            'type' => FinanceTransaction::TYPE_INCOME,
            'amount' => 8000,
            'transaction_date' => now()->toDateString(),
            'description' => 'To delete',
        ]);
        $this->assertTrue($transaction->isDraft());

        // Act -- soft delete
        $deleteResult = $transaction->softDelete();

        // Assert
        $this->assertTrue($deleteResult);
        $transaction->refresh();
        $this->assertSame(FinanceTransaction::STATUS_DELETED, $transaction->status);
        $this->assertTrue($transaction->isDeleted());

        // Повторное удаление возвращает false
        $this->assertFalse($transaction->softDelete());

        // Act -- restore
        $restoreResult = $transaction->restore();

        // Assert
        $this->assertTrue($restoreResult);
        $transaction->refresh();
        $this->assertSame(FinanceTransaction::STATUS_DRAFT, $transaction->status);
        $this->assertTrue($transaction->isDraft());
        $this->assertFalse($transaction->isDeleted());

        // Повторный restore не-удалённой транзакции возвращает false
        $this->assertFalse($transaction->restore());
    }

    // -------------------------------------------------------------------------
    // DebtPaymentService tests
    // -------------------------------------------------------------------------

    public function test_debt_payment_creates_payment_and_transaction(): void
    {
        // Arrange
        $company = $this->createCompany();
        $user = $this->createUser($company->id);

        // Создаём категорию для привязки транзакции
        FinanceCategory::create([
            'code' => 'INCOME_OTHER',
            'name' => 'Прочий доход',
            'type' => FinanceCategory::TYPE_INCOME,
            'is_system' => true,
            'is_active' => true,
        ]);

        $debt = $this->createDebt($company->id, FinanceDebt::TYPE_RECEIVABLE, 100000, $user->id);

        // Act
        $payment = $this->debtPaymentService->createPayment($debt, [
            'amount' => 40000,
            'payment_date' => now()->toDateString(),
            'payment_method' => 'cash',
        ], $user->id);

        // Assert -- платёж создан
        $this->assertInstanceOf(FinanceDebtPayment::class, $payment);
        $this->assertTrue($payment->exists);
        $this->assertSame(FinanceDebtPayment::STATUS_POSTED, $payment->status);
        $this->assertEquals(40000, $payment->amount);
        $this->assertEquals($debt->id, $payment->debt_id);
        $this->assertEquals($company->id, $payment->company_id);
        $this->assertEquals($user->id, $payment->created_by);

        // Assert -- финансовая транзакция создана (income для receivable)
        $this->assertNotNull($payment->transaction_id);
        $transaction = FinanceTransaction::find($payment->transaction_id);
        $this->assertNotNull($transaction);
        $this->assertSame(FinanceTransaction::TYPE_INCOME, $transaction->type);
        $this->assertSame(FinanceTransaction::STATUS_CONFIRMED, $transaction->status);
        $this->assertEquals(40000, $transaction->amount);
        $this->assertEquals($company->id, $transaction->company_id);
    }

    public function test_debt_payment_updates_debt_status(): void
    {
        // Arrange
        $company = $this->createCompany();
        $user = $this->createUser($company->id);

        FinanceCategory::create([
            'code' => 'INCOME_OTHER',
            'name' => 'Прочий доход',
            'type' => FinanceCategory::TYPE_INCOME,
            'is_system' => true,
            'is_active' => true,
        ]);

        $debt = $this->createDebt($company->id, FinanceDebt::TYPE_RECEIVABLE, 100000, $user->id);

        // Act -- частичная оплата
        $this->debtPaymentService->createPayment($debt, [
            'amount' => 40000,
            'payment_date' => now()->toDateString(),
        ], $user->id);

        // Assert -- статус partially_paid
        $debt->refresh();
        $this->assertSame(FinanceDebt::STATUS_PARTIALLY_PAID, $debt->status);
        $this->assertEquals(40000, $debt->amount_paid);
        $this->assertEquals(60000, $debt->amount_outstanding);

        // Act -- полная оплата оставшейся суммы
        $this->debtPaymentService->createPayment($debt, [
            'amount' => 60000,
            'payment_date' => now()->toDateString(),
        ], $user->id);

        // Assert -- статус paid
        $debt->refresh();
        $this->assertSame(FinanceDebt::STATUS_PAID, $debt->status);
        $this->assertEquals(100000, $debt->amount_paid);
        $this->assertEquals(0, $debt->amount_outstanding);
    }

    public function test_cancel_payment_reverses_debt(): void
    {
        // Arrange
        $company = $this->createCompany();
        $user = $this->createUser($company->id);

        FinanceCategory::create([
            'code' => 'INCOME_OTHER',
            'name' => 'Прочий доход',
            'type' => FinanceCategory::TYPE_INCOME,
            'is_system' => true,
            'is_active' => true,
        ]);

        $debt = $this->createDebt($company->id, FinanceDebt::TYPE_RECEIVABLE, 100000, $user->id);

        // Создаём два платежа
        $payment1 = $this->debtPaymentService->createPayment($debt, [
            'amount' => 30000,
            'payment_date' => now()->toDateString(),
        ], $user->id);

        $payment2 = $this->debtPaymentService->createPayment($debt, [
            'amount' => 70000,
            'payment_date' => now()->toDateString(),
        ], $user->id);

        // Проверяем что долг полностью погашен
        $debt->refresh();
        $this->assertSame(FinanceDebt::STATUS_PAID, $debt->status);
        $this->assertEquals(100000, $debt->amount_paid);
        $this->assertEquals(0, $debt->amount_outstanding);

        // Act -- отменяем второй платёж
        $result = $this->debtPaymentService->cancelPayment($payment2);

        // Assert
        $this->assertTrue($result);

        $debt->refresh();
        // Долг снова частично оплачен (30000 осталось)
        $this->assertSame(FinanceDebt::STATUS_PARTIALLY_PAID, $debt->status);
        $this->assertEquals(30000, $debt->amount_paid);
        $this->assertEquals(70000, $debt->amount_outstanding);

        // Платёж отменён
        $payment2->refresh();
        $this->assertSame(FinanceDebtPayment::STATUS_CANCELLED, $payment2->status);

        // Связанная транзакция тоже отменена
        $transaction = FinanceTransaction::find($payment2->transaction_id);
        $this->assertNotNull($transaction);
        $this->assertSame(FinanceTransaction::STATUS_CANCELLED, $transaction->status);

        // Act -- отменяем первый платёж
        $this->debtPaymentService->cancelPayment($payment1);

        // Assert -- долг снова активен
        $debt->refresh();
        $this->assertSame(FinanceDebt::STATUS_ACTIVE, $debt->status);
        $this->assertEquals(0, $debt->amount_paid);
        $this->assertEquals(100000, $debt->amount_outstanding);
    }
}
