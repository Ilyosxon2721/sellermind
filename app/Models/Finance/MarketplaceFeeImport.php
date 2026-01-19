<?php

namespace App\Models\Finance;

use App\Models\Company;
use App\Models\MarketplaceAccount;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class MarketplaceFeeImport extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_IMPORTED = 'imported';
    public const STATUS_POSTED = 'posted';

    protected $fillable = [
        'company_id',
        'marketplace_account_id',
        'period_from',
        'period_to',
        'total_sales',
        'commission_amount',
        'logistics_amount',
        'storage_amount',
        'ads_amount',
        'penalties_amount',
        'other_fees',
        'net_payout',
        'source_type',
        'source_id',
        'status',
        'transaction_id',
    ];

    protected $casts = [
        'period_from' => 'date',
        'period_to' => 'date',
        'total_sales' => 'float',
        'commission_amount' => 'float',
        'logistics_amount' => 'float',
        'storage_amount' => 'float',
        'ads_amount' => 'float',
        'penalties_amount' => 'float',
        'other_fees' => 'float',
        'net_payout' => 'float',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function marketplaceAccount(): BelongsTo
    {
        return $this->belongsTo(MarketplaceAccount::class);
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(FinanceTransaction::class, 'transaction_id');
    }

    public function getTotalFeesAttribute(): float
    {
        return $this->commission_amount + $this->logistics_amount + $this->storage_amount
            + $this->ads_amount + $this->penalties_amount + $this->other_fees;
    }

    public function getPeriodLabelAttribute(): string
    {
        if ($this->period_from->eq($this->period_to)) {
            return $this->period_from->format('d.m.Y');
        }
        return $this->period_from->format('d.m.Y') . ' - ' . $this->period_to->format('d.m.Y');
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isImported(): bool
    {
        return $this->status === self::STATUS_IMPORTED;
    }

    public function isPosted(): bool
    {
        return $this->status === self::STATUS_POSTED;
    }

    public function scopeByCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeByAccount($query, int $accountId)
    {
        return $query->where('marketplace_account_id', $accountId);
    }

    public function scopeInPeriod($query, $from, $to)
    {
        return $query->where('period_from', '>=', $from)
            ->where('period_to', '<=', $to);
    }
}
