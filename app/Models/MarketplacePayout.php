<?php
// file: app/Models/MarketplacePayout.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketplacePayout extends Model
{
    protected $fillable = [
        'marketplace_account_id',
        'external_payout_id',
        'period_from',
        'period_to',
        'amount',
        'currency',
        'sales_amount',
        'returns_amount',
        'commission_amount',
        'logistics_amount',
        'storage_amount',
        'ads_amount',
        'penalties_amount',
        'raw_payload',
    ];

    protected function casts(): array
    {
        return [
            'period_from' => 'date',
            'period_to' => 'date',
            'amount' => 'float',
            'sales_amount' => 'float',
            'returns_amount' => 'float',
            'commission_amount' => 'float',
            'logistics_amount' => 'float',
            'storage_amount' => 'float',
            'ads_amount' => 'float',
            'penalties_amount' => 'float',
            'raw_payload' => 'array',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(MarketplaceAccount::class, 'marketplace_account_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(MarketplacePayoutItem::class);
    }

    /**
     * Get period as formatted string
     */
    public function getPeriodLabel(): string
    {
        if ($this->period_from && $this->period_to) {
            return $this->period_from->format('d.m.Y') . ' - ' . $this->period_to->format('d.m.Y');
        }

        return 'Период не указан';
    }

    /**
     * Get formatted amount
     */
    public function getFormattedAmount(): string
    {
        $currency = $this->currency ?? 'UZS';
        return number_format($this->amount ?? 0, 2, '.', ' ') . ' ' . $currency;
    }

    /**
     * Calculate net profit (sales - all deductions)
     */
    public function getNetProfit(): float
    {
        return ($this->sales_amount ?? 0)
            - ($this->returns_amount ?? 0)
            - ($this->commission_amount ?? 0)
            - ($this->logistics_amount ?? 0)
            - ($this->storage_amount ?? 0)
            - ($this->ads_amount ?? 0)
            - ($this->penalties_amount ?? 0);
    }

    /**
     * Get profit margin percentage
     */
    public function getProfitMargin(): float
    {
        if (!$this->sales_amount || $this->sales_amount == 0) {
            return 0;
        }

        return ($this->getNetProfit() / $this->sales_amount) * 100;
    }
}
