<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Uzum Expense - расходы маркетплейса из Finance Expenses API
 * Категории: Marketing (реклама), Logistika, Ombor (хранение), Uzum Market (штрафы), Obuna (подписка)
 *
 * Суммы хранятся в UZS
 */
class UzumExpense extends Model
{
    protected $fillable = [
        'marketplace_account_id',
        'uzum_id',
        'shop_id',
        'name',
        'source',
        'source_normalized',
        'payment_price',
        'amount',
        'date_created',
        'date_service',
        'status',
        'raw_data',
    ];

    protected $casts = [
        'uzum_id' => 'integer',
        'shop_id' => 'integer',
        'payment_price' => 'integer',
        'amount' => 'integer',
        'date_created' => 'datetime',
        'date_service' => 'datetime',
        'raw_data' => 'array',
    ];

    /**
     * Map Uzbek source names to normalized categories
     */
    public const SOURCE_MAP = [
        'marketing' => 'advertising',
        'logistika' => 'logistics',
        'ombor' => 'storage',
        'uzum market' => 'penalties',
        'obuna' => 'commission',
    ];

    // ========== Relationships ==========

    public function account(): BelongsTo
    {
        return $this->belongsTo(MarketplaceAccount::class, 'marketplace_account_id');
    }

    // ========== Helpers ==========

    /**
     * Get currency code - always UZS for Uzum
     */
    public function getCurrencyCode(): string
    {
        return 'UZS';
    }

    /**
     * Normalize source name to standard category
     */
    public static function normalizeSource(string $source, ?string $name = null): string
    {
        $sourceLower = mb_strtolower(trim($source));
        $nameLower = $name ? mb_strtolower($name) : '';

        // Check source first
        if (isset(self::SOURCE_MAP[$sourceLower])) {
            return self::SOURCE_MAP[$sourceLower];
        }

        // Check name for additional hints
        if (str_contains($nameLower, 'targibot') || str_contains($nameLower, 'reklama')) {
            return 'advertising';
        }
        if (str_contains($nameLower, 'saqlash') || str_contains($nameLower, 'ombor')) {
            return 'storage';
        }
        if (str_contains($nameLower, 'jarima') || str_contains($nameLower, 'shtraf')) {
            return 'penalties';
        }
        if (str_contains($nameLower, 'yetkazib') || str_contains($nameLower, 'dostavka')) {
            return 'logistics';
        }

        return 'other';
    }

    /**
     * Get amount in UZS
     */
    public function getAmountUzs(): float
    {
        return (float) $this->amount;
    }

    /**
     * Check if this is advertising expense
     */
    public function isAdvertising(): bool
    {
        return $this->source_normalized === 'advertising';
    }

    /**
     * Check if this is logistics expense
     */
    public function isLogistics(): bool
    {
        return $this->source_normalized === 'logistics';
    }

    /**
     * Check if this is storage expense
     */
    public function isStorage(): bool
    {
        return $this->source_normalized === 'storage';
    }

    /**
     * Check if this is penalty expense
     */
    public function isPenalty(): bool
    {
        return $this->source_normalized === 'penalties';
    }

    /**
     * Check if this is commission expense
     */
    public function isCommission(): bool
    {
        return $this->source_normalized === 'commission';
    }

    // ========== Scopes ==========

    public function scopeForAccount($query, int $accountId)
    {
        return $query->where('marketplace_account_id', $accountId);
    }

    public function scopeForShop($query, int $shopId)
    {
        return $query->where('shop_id', $shopId);
    }

    public function scopeInPeriod($query, $from, $to)
    {
        return $query->whereBetween('date_service', [$from, $to]);
    }

    public function scopeBySource($query, string $source)
    {
        return $query->where('source', $source);
    }

    public function scopeByNormalizedSource($query, string $normalizedSource)
    {
        return $query->where('source_normalized', $normalizedSource);
    }

    public function scopeAdvertising($query)
    {
        return $query->where('source_normalized', 'advertising');
    }

    public function scopeLogistics($query)
    {
        return $query->where('source_normalized', 'logistics');
    }

    public function scopeStorage($query)
    {
        return $query->where('source_normalized', 'storage');
    }

    public function scopePenalties($query)
    {
        return $query->where('source_normalized', 'penalties');
    }

    public function scopeCommission($query)
    {
        return $query->where('source_normalized', 'commission');
    }

    /**
     * Get expenses summary for period
     *
     * Note: API returns 'amount' as quantity (usually 1) and 'paymentPrice' as actual cost.
     * We use payment_price for calculating totals.
     */
    public static function getSummaryForAccount(int $accountId, $from, $to): array
    {
        $expenses = self::forAccount($accountId)
            ->inPeriod($from, $to)
            ->get();

        $summary = [
            'commission' => 0,
            'logistics' => 0,
            'storage' => 0,
            'advertising' => 0,
            'penalties' => 0,
            'other' => 0,
            'total' => 0,
            'items_count' => 0,
            'currency' => 'UZS',
        ];

        foreach ($expenses as $expense) {
            // Use payment_price for actual cost (amount is quantity, usually 1)
            $price = abs($expense->payment_price);
            $category = $expense->source_normalized ?? 'other';

            if (isset($summary[$category])) {
                $summary[$category] += $price;
            } else {
                $summary['other'] += $price;
            }
            $summary['total'] += $price;
            $summary['items_count']++;
        }

        return $summary;
    }
}
