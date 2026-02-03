<?php

namespace App\Models\Finance;

use App\Models\Company;
use App\Models\Counterparty;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashTransaction extends Model
{
    public const TYPE_INCOME = 'income';

    public const TYPE_EXPENSE = 'expense';

    public const TYPE_TRANSFER_IN = 'transfer_in';

    public const TYPE_TRANSFER_OUT = 'transfer_out';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_CANCELLED = 'cancelled';

    // Operations
    public const OP_MARKETPLACE_PAYOUT = 'marketplace_payout';

    public const OP_SALE = 'sale';

    public const OP_PURCHASE = 'purchase';

    public const OP_EXPENSE = 'expense';

    public const OP_SALARY = 'salary';

    public const OP_TAX = 'tax';

    public const OP_LOAN_IN = 'loan_in';

    public const OP_LOAN_OUT = 'loan_out';

    public const OP_TRANSFER = 'transfer';

    public const OP_ADJUSTMENT = 'adjustment';

    public const OP_INITIAL = 'initial';

    public const OP_DEBT_PAYMENT = 'debt_payment';

    public const OP_OTHER = 'other';

    protected $fillable = [
        'company_id',
        'cash_account_id',
        'type',
        'operation',
        'amount',
        'balance_before',
        'balance_after',
        'currency_code',
        'category_id',
        'counterparty_id',
        'employee_id',
        'finance_transaction_id',
        'transfer_to_account_id',
        'transfer_from_transaction_id',
        'source_type',
        'source_id',
        'description',
        'reference',
        'transaction_date',
        'status',
        'meta_json',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'transaction_date' => 'date',
        'meta_json' => 'array',
    ];

    public function scopeByCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', self::STATUS_CONFIRMED);
    }

    public function scopeInPeriod($query, $from, $to)
    {
        return $query->whereBetween('transaction_date', [$from, $to]);
    }

    public function scopeIncome($query)
    {
        return $query->whereIn('type', [self::TYPE_INCOME, self::TYPE_TRANSFER_IN]);
    }

    public function scopeExpense($query)
    {
        return $query->whereIn('type', [self::TYPE_EXPENSE, self::TYPE_TRANSFER_OUT]);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function cashAccount(): BelongsTo
    {
        return $this->belongsTo(CashAccount::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(FinanceCategory::class);
    }

    public function counterparty(): BelongsTo
    {
        return $this->belongsTo(Counterparty::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function financeTransaction(): BelongsTo
    {
        return $this->belongsTo(FinanceTransaction::class);
    }

    public function transferToAccount(): BelongsTo
    {
        return $this->belongsTo(CashAccount::class, 'transfer_to_account_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeForAccount($query, int $accountId)
    {
        return $query->where('cash_account_id', $accountId);
    }

    public function scopeByOperation($query, string $operation)
    {
        return $query->where('operation', $operation);
    }

    /**
     * Создать транзакцию для выплаты маркетплейса
     */
    public static function createForMarketplacePayout(
        CashAccount $account,
        float $amount,
        string $reference,
        $payoutDate,
        ?int $sourceId = null,
        ?string $sourceType = null
    ): self {
        $balanceBefore = $account->balance;
        $balanceAfter = $balanceBefore + $amount;

        $transaction = self::create([
            'company_id' => $account->company_id,
            'cash_account_id' => $account->id,
            'type' => self::TYPE_INCOME,
            'operation' => self::OP_MARKETPLACE_PAYOUT,
            'amount' => $amount,
            'currency_code' => $account->currency_code,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'reference' => $reference,
            'description' => 'Выплата от маркетплейса',
            'transaction_date' => $payoutDate,
            'status' => self::STATUS_CONFIRMED,
        ]);

        // Обновляем баланс счёта
        $account->update(['balance' => $balanceAfter]);

        return $transaction;
    }

    /**
     * Получить название операции
     */
    public function getOperationNameAttribute(): string
    {
        return match ($this->operation) {
            self::OP_MARKETPLACE_PAYOUT => 'Выплата маркетплейса',
            self::OP_SALE => 'Продажа',
            self::OP_PURCHASE => 'Закупка',
            self::OP_EXPENSE => 'Расход',
            self::OP_SALARY => 'Зарплата',
            self::OP_TAX => 'Налоги',
            self::OP_LOAN_IN => 'Получение займа',
            self::OP_LOAN_OUT => 'Выдача займа',
            self::OP_TRANSFER => 'Перевод',
            self::OP_ADJUSTMENT => 'Корректировка',
            self::OP_INITIAL => 'Начальный остаток',
            self::OP_DEBT_PAYMENT => 'Погашение долга',
            default => 'Прочее',
        };
    }
}
