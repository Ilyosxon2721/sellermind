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

    /**
     * Get sell price in UZS (from tiyin)
     */
    public function getSellPriceUzsAttribute(): float
    {
        return $this->sell_price / 100;
    }

    /**
     * Get purchase price in UZS (from tiyin)
     */
    public function getPurchasePriceUzsAttribute(): ?float
    {
        return $this->purchase_price !== null ? $this->purchase_price / 100 : null;
    }

    /**
     * Get commission in UZS (from tiyin)
     */
    public function getCommissionUzsAttribute(): float
    {
        return $this->commission / 100;
    }

    /**
     * Get seller profit in UZS (from tiyin)
     */
    public function getSellerProfitUzsAttribute(): float
    {
        return $this->seller_profit / 100;
    }

    /**
     * Get delivery fee in UZS (from tiyin)
     */
    public function getDeliveryFeeUzsAttribute(): float
    {
        return $this->logistic_delivery_fee / 100;
    }

    /**
     * Get total revenue (sell_price * amount) in UZS
     */
    public function getTotalRevenueAttribute(): float
    {
        return ($this->sell_price * $this->amount) / 100;
    }

    /**
     * Get total seller profit in UZS
     */
    public function getTotalProfitAttribute(): float
    {
        return $this->seller_profit / 100;
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
     * Check if order is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'COMPLETED';
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
     */
    public function getNormalizedStatus(): string
    {
        if ($this->status_normalized) {
            return $this->status_normalized;
        }

        return match ($this->status) {
            'PROCESSING' => 'processing',
            'COMPLETED' => 'delivered',
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
        return $query->where('status', 'COMPLETED');
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
}
