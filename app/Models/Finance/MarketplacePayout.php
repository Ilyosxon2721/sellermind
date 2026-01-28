<?php

namespace App\Models\Finance;

use App\Models\Company;
use App\Models\MarketplaceAccount;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplacePayout extends Model
{
    protected $fillable = [
        'company_id',
        'marketplace_account_id',
        'marketplace',
        'payout_id',
        'payout_date',
        'period_from',
        'period_to',
        'gross_amount',
        'commission',
        'logistics',
        'storage',
        'advertising',
        'penalties',
        'returns',
        'other_deductions',
        'net_amount',
        'currency_code',
        'exchange_rate',
        'amount_base',
        'cash_transaction_id',
        'status',
        'raw_data',
    ];

    protected $casts = [
        'payout_date' => 'date',
        'period_from' => 'date',
        'period_to' => 'date',
        'gross_amount' => 'float',
        'commission' => 'float',
        'logistics' => 'float',
        'storage' => 'float',
        'advertising' => 'float',
        'penalties' => 'float',
        'returns' => 'float',
        'other_deductions' => 'float',
        'net_amount' => 'float',
        'exchange_rate' => 'float',
        'amount_base' => 'float',
        'raw_data' => 'array',
    ];

    // Statuses
    public const STATUS_PENDING = 'pending';
    public const STATUS_RECEIVED = 'received';
    public const STATUS_RECONCILED = 'reconciled';

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function marketplaceAccount(): BelongsTo
    {
        return $this->belongsTo(MarketplaceAccount::class);
    }

    public function cashTransaction(): BelongsTo
    {
        return $this->belongsTo(CashTransaction::class);
    }

    // Scopes
    public function scopeByCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeForAccount($query, int $accountId)
    {
        return $query->where('marketplace_account_id', $accountId);
    }

    public function scopeForMarketplace($query, string $marketplace)
    {
        return $query->where('marketplace', $marketplace);
    }

    public function scopeInPeriod($query, $from, $to)
    {
        return $query->whereBetween('payout_date', [$from, $to]);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeReceived($query)
    {
        return $query->where('status', self::STATUS_RECEIVED);
    }

    // Accessors
    public function getTotalDeductionsAttribute(): float
    {
        return $this->commission + $this->logistics + $this->storage +
               $this->advertising + $this->penalties + $this->returns + $this->other_deductions;
    }

    public function getMarketplaceNameAttribute(): string
    {
        return match ($this->marketplace) {
            'uzum' => 'Uzum',
            'wb' => 'Wildberries',
            'ozon' => 'Ozon',
            'ym' => 'Яндекс.Маркет',
            default => $this->marketplace,
        };
    }

    // Methods
    public function markAsReceived(CashTransaction $transaction): void
    {
        $this->status = self::STATUS_RECEIVED;
        $this->cash_transaction_id = $transaction->id;
        $this->save();
    }

    public function markAsReconciled(): void
    {
        $this->status = self::STATUS_RECONCILED;
        $this->save();
    }

    /**
     * Рассчитать сумму в базовой валюте
     */
    public function calculateAmountBase(float $exchangeRate): void
    {
        $this->exchange_rate = $exchangeRate;
        $this->amount_base = $this->net_amount * $exchangeRate;
        $this->save();
    }

    /**
     * Создать движение денег для этой выплаты
     */
    public function createCashTransaction(CashAccount $account): CashTransaction
    {
        $transaction = CashTransaction::createForMarketplacePayout(
            $account,
            $this->net_amount,
            $this->payout_id ?? "payout-{$this->id}",
            $this->payout_date,
            $this->id,
            self::class
        );

        $this->markAsReceived($transaction);

        return $transaction;
    }
}
