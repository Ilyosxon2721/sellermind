<?php

namespace App\Models\Finance;

use App\Models\AP\Supplier;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class FinanceTransaction extends Model
{
    use HasFactory;

    public const TYPE_INCOME = 'income';
    public const TYPE_EXPENSE = 'expense';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'company_id',
        'type',
        'category_id',
        'subcategory_id',
        'source_type',
        'source_id',
        'counterparty_id',
        'employee_id',
        'amount',
        'currency_code',
        'exchange_rate',
        'amount_base',
        'description',
        'transaction_date',
        'reference',
        'status',
        'tags',
        'metadata',
        'created_by',
        'confirmed_by',
        'confirmed_at',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'confirmed_at' => 'datetime',
        'amount' => 'float',
        'exchange_rate' => 'float',
        'amount_base' => 'float',
        'tags' => 'array',
        'metadata' => 'array',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(FinanceCategory::class, 'category_id');
    }

    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(FinanceCategory::class, 'subcategory_id');
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
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

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isConfirmed(): bool
    {
        return $this->status === self::STATUS_CONFIRMED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function confirm(int $userId): bool
    {
        if (!$this->isDraft()) {
            return false;
        }

        return $this->update([
            'status' => self::STATUS_CONFIRMED,
            'confirmed_by' => $userId,
            'confirmed_at' => now(),
        ]);
    }

    public function cancel(): bool
    {
        if ($this->isCancelled()) {
            return false;
        }

        return $this->update([
            'status' => self::STATUS_CANCELLED,
        ]);
    }

    public function scopeByCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeIncome($query)
    {
        return $query->where('type', self::TYPE_INCOME);
    }

    public function scopeExpense($query)
    {
        return $query->where('type', self::TYPE_EXPENSE);
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', self::STATUS_CONFIRMED);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function scopeInPeriod($query, $from, $to)
    {
        return $query->whereBetween('transaction_date', [$from, $to]);
    }

    public function scopeByCategory($query, int $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }
}
