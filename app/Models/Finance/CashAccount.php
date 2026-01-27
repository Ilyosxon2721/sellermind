<?php

namespace App\Models\Finance;

use App\Models\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashAccount extends Model
{
    public const TYPE_CASH = 'cash';
    public const TYPE_BANK = 'bank';
    public const TYPE_CARD = 'card';
    public const TYPE_EWALLET = 'ewallet';

    protected $fillable = [
        'company_id',
        'name',
        'type',
        'currency_code',
        'balance',
        'initial_balance',
        'bank_name',
        'account_number',
        'card_number',
        'is_default',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'initial_balance' => 'decimal:2',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function scopeByCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(CashTransaction::class);
    }

    /**
     * Получить название типа счёта
     */
    public function getTypeNameAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_CASH => 'Касса',
            self::TYPE_BANK => 'Банковский счёт',
            self::TYPE_CARD => 'Карта',
            self::TYPE_EWALLET => 'Электронный кошелёк',
            default => $this->type,
        };
    }

    /**
     * Пересчитать баланс на основе транзакций
     */
    public function recalculateBalance(): void
    {
        $income = $this->transactions()
            ->where('status', 'confirmed')
            ->whereIn('type', ['income', 'transfer_in'])
            ->sum('amount');

        $expense = $this->transactions()
            ->where('status', 'confirmed')
            ->whereIn('type', ['expense', 'transfer_out'])
            ->sum('amount');

        $this->balance = $this->initial_balance + $income - $expense;
        $this->save();
    }
}
