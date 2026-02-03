<?php

namespace App\Models\Finance;

use App\Models\AP\Supplier;
use App\Models\Company;
use App\Models\Counterparty;
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

    public const PURPOSE_DEBT = 'debt';

    public const PURPOSE_PREPAYMENT = 'prepayment';

    public const PURPOSE_ADVANCE = 'advance';

    public const PURPOSE_LOAN = 'loan';

    public const PURPOSE_OTHER = 'other';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_PARTIALLY_PAID = 'partially_paid';

    public const STATUS_PAID = 'paid';

    public const STATUS_WRITTEN_OFF = 'written_off';

    protected $fillable = [
        'company_id',
        'type',
        'purpose',
        'counterparty_id',
        'counterparty_entity_id',
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
        'cash_account_id',
        'written_off_at',
        'written_off_by',
        'written_off_reason',
        'created_by',
    ];

    protected $casts = [
        'debt_date' => 'date',
        'due_date' => 'date',
        'original_amount' => 'float',
        'amount_paid' => 'float',
        'amount_outstanding' => 'float',
        'interest_rate' => 'float',
        'written_off_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function counterparty(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'counterparty_id');
    }

    public function counterpartyEntity(): BelongsTo
    {
        return $this->belongsTo(Counterparty::class, 'counterparty_entity_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function writtenOffByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'written_off_by');
    }

    public function cashAccount(): BelongsTo
    {
        return $this->belongsTo(CashAccount::class);
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
        return $this->due_date && $this->due_date->isPast() && ! $this->isPaid();
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

    public function writeOff(int $userId, ?string $reason = null): bool
    {
        if ($this->isPaid()) {
            return false;
        }

        return $this->update([
            'status' => self::STATUS_WRITTEN_OFF,
            'amount_outstanding' => 0,
            'written_off_at' => now(),
            'written_off_by' => $userId,
            'written_off_reason' => $reason,
        ]);
    }

    public function getCounterpartyNameAttribute(): ?string
    {
        if ($this->counterpartyEntity) {
            return $this->counterpartyEntity->getDisplayName();
        }
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

    public function scopeByPurpose($query, string $purpose)
    {
        return $query->where('purpose', $purpose);
    }

    public function scopeByCounterpartyEntity($query, int $id)
    {
        return $query->where('counterparty_entity_id', $id);
    }
}
