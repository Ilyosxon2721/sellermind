<?php

namespace App\Models\Finance;

use App\Models\AP\Supplier;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class FinanceDebt extends Model
{
    use HasFactory;

    public const TYPE_RECEIVABLE = 'receivable'; // дебиторка (нам должны)
    public const TYPE_PAYABLE = 'payable';       // кредиторка (мы должны)

    public const STATUS_ACTIVE = 'active';
    public const STATUS_PARTIALLY_PAID = 'partially_paid';
    public const STATUS_PAID = 'paid';
    public const STATUS_WRITTEN_OFF = 'written_off';

    protected $fillable = [
        'company_id',
        'type',
        'counterparty_id',
        'employee_id',
        'description',
        'reference',
        'original_amount',
        'amount_paid',
        'amount_outstanding',
        'currency_code',
        'debt_date',
        'due_date',
        'status',
        'source_type',
        'source_id',
        'interest_rate',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'debt_date' => 'date',
        'due_date' => 'date',
        'original_amount' => 'float',
        'amount_paid' => 'float',
        'amount_outstanding' => 'float',
        'interest_rate' => 'float',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function counterparty(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'counterparty_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    public function payments(): HasMany
    {
        return $this->hasMany(FinanceDebtPayment::class, 'debt_id');
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isPartiallyPaid(): bool
    {
        return $this->status === self::STATUS_PARTIALLY_PAID;
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public function isWrittenOff(): bool
    {
        return $this->status === self::STATUS_WRITTEN_OFF;
    }

    public function isOverdue(): bool
    {
        return $this->due_date && $this->due_date->isPast() && !$this->isPaid();
    }

    public function applyPayment(float $amount): void
    {
        $this->amount_paid += $amount;
        $this->amount_outstanding = max(0, $this->original_amount - $this->amount_paid);

        if ($this->amount_outstanding <= 0) {
            $this->status = self::STATUS_PAID;
        } elseif ($this->amount_paid > 0) {
            $this->status = self::STATUS_PARTIALLY_PAID;
        }

        $this->save();
    }

    public function writeOff(): bool
    {
        if ($this->isPaid()) {
            return false;
        }

        return $this->update([
            'status' => self::STATUS_WRITTEN_OFF,
            'amount_outstanding' => 0,
        ]);
    }

    public function getCounterpartyNameAttribute(): ?string
    {
        if ($this->counterparty) {
            return $this->counterparty->name;
        }
        if ($this->employee) {
            return $this->employee->full_name;
        }
        return null;
    }

    public function scopeByCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeReceivable($query)
    {
        return $query->where('type', self::TYPE_RECEIVABLE);
    }

    public function scopePayable($query)
    {
        return $query->where('type', self::TYPE_PAYABLE);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', [self::STATUS_ACTIVE, self::STATUS_PARTIALLY_PAID]);
    }

    public function scopeOverdue($query)
    {
        return $query->active()
            ->whereNotNull('due_date')
            ->where('due_date', '<', now());
    }
}
