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

    // ========== Scopes ==========

    public function scopeForAccount($query, int $accountId)
    {
        return $query->where('marketplace_account_id', $accountId);
    }

    public function scopeInPeriod($query, $from, $to)
    {
        return $query->whereBetween('date_service', [$from, $to]);
    }

}
