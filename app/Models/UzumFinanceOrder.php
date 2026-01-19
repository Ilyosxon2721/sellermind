<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Uzum Finance Order - используется для аналитики, дашборда, отчётов
 * Содержит все типы заказов: FBO/FBS/DBS/EDBS
 *
 * Финансовые поля хранятся в тийинах (1/100 сума)
 */
class UzumFinanceOrder extends Model
{
    protected $fillable = [
        'marketplace_account_id',
        'uzum_id',
        'order_id',
        'shop_id',
        'product_id',
        'sku_title',
        'product_image_url',
        'status',
        'status_normalized',
        'sell_price',
        'purchase_price',
        'commission',
        'seller_profit',
        'logistic_delivery_fee',
        'withdrawn_profit',
        'amount',
        'amount_returns',
        'order_date',
        'date_issued',
        'comment',
        'return_cause',
        'raw_data',
    ];

    protected $casts = [
        'uzum_id' => 'integer',
        'order_id' => 'integer',
        'shop_id' => 'integer',
        'product_id' => 'integer',
        'sell_price' => 'integer',
        'purchase_price' => 'integer',
        'commission' => 'integer',
        'seller_profit' => 'integer',
        'logistic_delivery_fee' => 'integer',
        'withdrawn_profit' => 'integer',
        'amount' => 'integer',
        'amount_returns' => 'integer',
        'order_date' => 'datetime',
        'date_issued' => 'datetime',
        'raw_data' => 'array',
    ];

    // ========== Relationships ==========

    public function account(): BelongsTo
    {
        return $this->belongsTo(MarketplaceAccount::class, 'marketplace_account_id');
    }

    // ========== Accessors for UZS amounts ==========
    // Note: Uzum API returns amounts in UZS (not tiyin), so no division needed

    /**
     * Get sell price in UZS
     */
    public function getSellPriceUzsAttribute(): float
    {
        return (float) $this->sell_price;
    }

    /**
     * Get purchase price in UZS
     */
    public function getPurchasePriceUzsAttribute(): ?float
    {
        return $this->purchase_price !== null ? (float) $this->purchase_price : null;
    }

    /**
     * Get commission in UZS
     */
    public function getCommissionUzsAttribute(): float
    {
        return (float) $this->commission;
    }

    /**
     * Get seller profit in UZS
     */
    public function getSellerProfitUzsAttribute(): float
    {
        return (float) $this->seller_profit;
    }

    /**
     * Get delivery fee in UZS
     */
    public function getDeliveryFeeUzsAttribute(): float
    {
        return (float) $this->logistic_delivery_fee;
    }

    /**
     * Get total revenue (sell_price * amount) in UZS
     */
    public function getTotalRevenueAttribute(): float
    {
        return (float) ($this->sell_price * $this->amount);
    }

    /**
     * Get total seller profit in UZS
     */
    public function getTotalProfitAttribute(): float
    {
        return (float) $this->seller_profit;
    }

    // ========== Helpers ==========

    /**
     * Check if order is cancelled
     */
    public function isCancelled(): bool
    {
        return $this->status === 'CANCELED';
    }

    /**
     * Check if order is completed (including TO_WITHDRAW = money withdrawn)
     */
    public function isCompleted(): bool
    {
        return in_array($this->status, ['COMPLETED', 'TO_WITHDRAW']);
    }

    /**
     * Check if order is processing (active)
     */
    public function isProcessing(): bool
    {
        return $this->status === 'PROCESSING';
    }

    /**
     * Check if order has returns
     */
    public function hasReturns(): bool
    {
        return $this->amount_returns > 0;
    }

    /**
     * Get effective amount (sold minus returned)
     */
    public function getEffectiveAmount(): int
    {
        return max(0, $this->amount - $this->amount_returns);
    }

    /**
     * Get currency code - always UZS for Uzum
     */
    public function getCurrencyCode(): string
    {
        return 'UZS';
    }

    /**
     * Get normalized status
     * TO_WITHDRAW = деньги выведены (завершённая продажа)
     */
    public function getNormalizedStatus(): string
    {
        if ($this->status_normalized) {
            return $this->status_normalized;
        }

        return match ($this->status) {
            'PROCESSING' => 'processing',
            'COMPLETED', 'TO_WITHDRAW' => 'delivered',
            'CANCELED' => 'cancelled',
            default => strtolower($this->status),
        };
    }

    // ========== Scopes ==========

    public function scopeForAccount($query, int $accountId)
    {
        return $query->where('marketplace_account_id', $accountId);
    }

    public function scopeNotCancelled($query)
    {
        return $query->where('status', '!=', 'CANCELED');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'CANCELED');
    }

    public function scopeCompleted($query)
    {
        return $query->whereIn('status', ['COMPLETED', 'TO_WITHDRAW']);
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', 'PROCESSING');
    }

    public function scopeInPeriod($query, $from, $to)
    {
        return $query->whereBetween('order_date', [$from, $to]);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByShop($query, int $shopId)
    {
        return $query->where('shop_id', $shopId);
    }

    /**
     * Scope: Sold orders (confirmed revenue)
     */
    public function scopeSold($query)
    {
        return $query->whereIn('status', ['COMPLETED', 'TO_WITHDRAW']);
    }

    /**
     * Scope: Orders in transit
     */
    public function scopeInTransit($query)
    {
        return $query->where('status', 'PROCESSING');
    }

    /**
     * Scope: Orders awaiting pickup at ПВЗ
     * Uzum doesn't have a separate "awaiting pickup" status in Finance API
     * PROCESSING covers all active orders
     */
    public function scopeAwaitingPickup($query)
    {
        // Uzum does not have a separate status for awaiting pickup
        // Return empty result set - all transit orders are in PROCESSING
        return $query->whereRaw('1 = 0');
    }
}
