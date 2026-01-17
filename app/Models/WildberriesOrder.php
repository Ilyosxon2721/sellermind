<?php
// file: app/Models/WildberriesOrder.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WildberriesOrder extends Model
{
    protected $fillable = [
        'marketplace_account_id',
        'srid',
        'order_id',
        'odid',
        'rid',
        'nm_id',
        'supplier_article',
        'barcode',
        'tech_size',
        'brand',
        'subject',
        'category',
        'warehouse_name',
        'warehouse_type',
        'status',
        'wb_status',
        'is_cancel',
        'is_return',
        'is_realization',
        'price',
        'discount_percent',
        'total_price',
        'finished_price',
        'for_pay',
        'spp',
        'commission_percent',
        'region_name',
        'oblast_okrug_name',
        'country_name',
        'order_date',
        'cancel_date',
        'last_change_date',
        'sticker',
        'delivery_type',
        'income_id',
        'supply_id',
        'sgtin',
        'uin',
        'imei',
        'gtin',
        'expiration_date',
        'raw_data',
    ];

    protected $casts = [
        'order_id' => 'integer',
        'odid' => 'integer',
        'nm_id' => 'integer',
        'is_cancel' => 'boolean',
        'is_return' => 'boolean',
        'is_realization' => 'boolean',
        'price' => 'decimal:2',
        'discount_percent' => 'integer',
        'total_price' => 'decimal:2',
        'finished_price' => 'decimal:2',
        'for_pay' => 'decimal:2',
        'spp' => 'decimal:2',
        'commission_percent' => 'decimal:2',
        'order_date' => 'datetime',
        'cancel_date' => 'datetime',
        'last_change_date' => 'datetime',
        'income_id' => 'integer',
        'expiration_date' => 'date',
        'raw_data' => 'array',
    ];

    // ========== Relationships ==========

    public function account(): BelongsTo
    {
        return $this->belongsTo(MarketplaceAccount::class, 'marketplace_account_id');
    }

    /**
     * Get related WB product by nm_id
     */
    public function wildberriesProduct(): BelongsTo
    {
        return $this->belongsTo(WildberriesProduct::class, 'nm_id', 'nm_id')
            ->where('marketplace_account_id', $this->marketplace_account_id);
    }

    /**
     * Get the supply that this order belongs to
     */
    public function supply(): BelongsTo
    {
        return $this->belongsTo(WildberriesSupply::class, 'supply_id', 'supply_id');
    }

    // ========== Helpers ==========

    /**
     * Check if order has marking metadata
     */
    public function hasMarking(): bool
    {
        return !empty($this->sgtin) || !empty($this->uin) || !empty($this->imei) || !empty($this->gtin);
    }

    /**
     * Check if order has expiration date
     */
    public function hasExpirationDate(): bool
    {
        return !empty($this->expiration_date);
    }

    /**
     * Check if order is in a supply
     */
    public function isInSupply(): bool
    {
        return !empty($this->supply_id);
    }

    /**
     * Get normalized status
     */
    public function getNormalizedStatus(): string
    {
        return $this->status ?? $this->mapWbStatus($this->wb_status);
    }

    /**
     * Map WB status to internal status
     */
    protected function mapWbStatus(?string $wbStatus): string
    {
        if (!$wbStatus) {
            return 'unknown';
        }

        $statusMap = [
            'waiting' => 'new',
            'sorted' => 'processing',
            'sold' => 'delivered',
            'canceled' => 'cancelled',
            'canceled_by_client' => 'cancelled',
            'defect' => 'cancelled',
            'ready_for_pickup' => 'shipped',
        ];

        return $statusMap[$wbStatus] ?? $wbStatus;
    }

    /**
     * Get effective revenue (for_pay or calculated)
     */
    public function getEffectiveRevenue(): float
    {
        if ($this->is_cancel || $this->is_return) {
            return 0;
        }

        return $this->for_pay ?? $this->finished_price ?? $this->total_price ?? 0;
    }

    /**
     * Get currency code for this order
     * Statistics API returns amounts in RUB, but we can detect country-based currency
     *
     * @return string 3-letter currency code
     */
    public function getCurrencyCode(): string
    {
        // Statistics API always returns amounts in RUB
        // (WB converts all to RUB for reporting)
        return 'RUB';
    }

    /**
     * Get the original sale currency based on country
     * This is informational - amounts in DB are already in RUB
     *
     * @return string|null Original currency code or null
     */
    public function getOriginalSaleCurrency(): ?string
    {
        $country = $this->country_name ?? ($this->raw_data['countryName'] ?? null);

        if (!$country) {
            return null;
        }

        return match ($country) {
            'Россия' => 'RUB',
            'Беларусь' => 'BYN',
            'Казахстан' => 'KZT',
            'Кыргызстан' => 'KGS',
            'Армения' => 'AMD',
            'Узбекистан' => 'UZS',
            default => null,
        };
    }

    /**
     * Check if order is completed sale
     */
    public function isCompletedSale(): bool
    {
        return !$this->is_cancel && !$this->is_return && $this->is_realization;
    }

    // ========== Scopes ==========

    public function scopeForAccount($query, int $accountId)
    {
        return $query->where('marketplace_account_id', $accountId);
    }

    public function scopeNotCancelled($query)
    {
        return $query->where('is_cancel', false);
    }

    public function scopeRealized($query)
    {
        return $query->where('is_realization', true);
    }

    public function scopeInPeriod($query, $from, $to)
    {
        return $query->whereBetween('order_date', [$from, $to]);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }
}
