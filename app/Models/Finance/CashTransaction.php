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

    protected $fillable = [
        'company_id',
        'cash_account_id',
        'type',
        'amount',
        'balance_after',
        'currency_code',
        'category_id',
        'counterparty_id',
        'employee_id',
        'finance_transaction_id',
        'transfer_to_account_id',
        'transfer_from_transaction_id',
        'description',
        'reference',
        'transaction_date',
        'status',
        'meta_json',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
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
}
