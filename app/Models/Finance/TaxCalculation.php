<?php

namespace App\Models\Finance;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxCalculation extends Model
{
    use HasFactory;

    public const PERIOD_MONTH = 'month';
    public const PERIOD_QUARTER = 'quarter';
    public const PERIOD_YEAR = 'year';

    public const TYPE_INCOME_TAX = 'income_tax';    // Налог на прибыль / Упрощёнка
    public const TYPE_VAT = 'vat';                   // НДС
    public const TYPE_SOCIAL_TAX = 'social_tax';    // Социальный налог
    public const TYPE_SIMPLIFIED = 'simplified';     // Упрощённая система

    public const STATUS_DRAFT = 'draft';
    public const STATUS_CALCULATED = 'calculated';
    public const STATUS_PAID = 'paid';
    public const STATUS_OVERDUE = 'overdue';

    protected $fillable = [
        'company_id',
        'tax_period_type',
        'period_year',
        'period_month',
        'period_quarter',
        'tax_type',
        'taxable_base',
        'tax_rate',
        'calculated_amount',
        'paid_amount',
        'status',
        'due_date',
        'transaction_id',
        'notes',
    ];

    protected $casts = [
        'period_year' => 'integer',
        'period_month' => 'integer',
        'period_quarter' => 'integer',
        'taxable_base' => 'float',
        'tax_rate' => 'float',
        'calculated_amount' => 'float',
        'paid_amount' => 'float',
        'due_date' => 'date',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(FinanceTransaction::class, 'transaction_id');
    }

    public function getPeriodLabelAttribute(): string
    {
        $months = [
            1 => 'Январь', 2 => 'Февраль', 3 => 'Март',
            4 => 'Апрель', 5 => 'Май', 6 => 'Июнь',
            7 => 'Июль', 8 => 'Август', 9 => 'Сентябрь',
            10 => 'Октябрь', 11 => 'Ноябрь', 12 => 'Декабрь',
        ];

        $quarters = [1 => 'I кв.', 2 => 'II кв.', 3 => 'III кв.', 4 => 'IV кв.'];

        if ($this->tax_period_type === self::PERIOD_MONTH) {
            return ($months[$this->period_month] ?? '') . ' ' . $this->period_year;
        }

        if ($this->tax_period_type === self::PERIOD_QUARTER) {
            return ($quarters[$this->period_quarter] ?? '') . ' ' . $this->period_year;
        }

        return (string) $this->period_year;
    }

    public function getTaxTypeLabelAttribute(): string
    {
        return match ($this->tax_type) {
            self::TYPE_INCOME_TAX => 'Налог на прибыль',
            self::TYPE_VAT => 'НДС',
            self::TYPE_SOCIAL_TAX => 'Социальный налог',
            self::TYPE_SIMPLIFIED => 'Упрощёнка',
            default => $this->tax_type,
        };
    }

    public function getAmountOutstandingAttribute(): float
    {
        return max(0, $this->calculated_amount - $this->paid_amount);
    }

    public function isOverdue(): bool
    {
        return $this->due_date && $this->due_date->isPast() && $this->amount_outstanding > 0;
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public function markAsPaid(int $transactionId): void
    {
        $this->update([
            'status' => self::STATUS_PAID,
            'paid_amount' => $this->calculated_amount,
            'transaction_id' => $transactionId,
        ]);
    }

    public function scopeByCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('tax_type', $type);
    }

    public function scopeForYear($query, int $year)
    {
        return $query->where('period_year', $year);
    }

    public function scopeUnpaid($query)
    {
        return $query->whereIn('status', [self::STATUS_DRAFT, self::STATUS_CALCULATED, self::STATUS_OVERDUE]);
    }
}
