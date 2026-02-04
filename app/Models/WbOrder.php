<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WbOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'marketplace_account_id',
        'external_order_id',
        'rid',
        'order_uid',
        'nm_id',
        'chrt_id',
        'article',
        'sku',
        'status',
        'status_normalized',
        'wb_status',
        'wb_status_group',
        'wb_supplier_status',
        'wb_delivery_type',
        'warehouse_id',
        'supply_id',
        'tare_id',
        'office',
        'customer_name',
        'customer_phone',
        'total_amount',
        'price',
        'scan_price',
        'converted_price',
        'currency',
        'currency_code',
        'converted_currency_code',
        'cargo_type',
        'is_b2b',
        'is_zero_order',
        'ordered_at',
        'delivered_at',
        'raw_payload',
        // Stock tracking fields
        'stock_status',
        'stock_reserved_at',
        'stock_sold_at',
        'stock_released_at',
    ];

    protected $casts = [
        'ordered_at' => 'datetime',
        'delivered_at' => 'datetime',
        'raw_payload' => 'array',
        'total_amount' => 'decimal:2',
        'is_b2b' => 'boolean',
        'is_zero_order' => 'boolean',
        'stock_reserved_at' => 'datetime',
        'stock_sold_at' => 'datetime',
        'stock_released_at' => 'datetime',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(MarketplaceAccount::class, 'marketplace_account_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(WbOrderItem::class);
    }

    public function tare(): BelongsTo
    {
        return $this->belongsTo(Tare::class);
    }

    /**
     * Получить URL фото товара из WB CDN
     */
    public function getPhotoUrlAttribute(): ?string
    {
        if (! $this->nm_id) {
            return null;
        }

        $nmId = (int) $this->nm_id;

        // Вычисляем vol и part
        if ($nmId <= 143) {
            $vol = 0;
        } elseif ($nmId <= 287) {
            $vol = 1;
        } elseif ($nmId <= 431) {
            $vol = 2;
        } elseif ($nmId <= 719) {
            $vol = 3;
        } elseif ($nmId <= 1007) {
            $vol = 4;
        } elseif ($nmId <= 1061) {
            $vol = 5;
        } elseif ($nmId <= 1115) {
            $vol = 6;
        } elseif ($nmId <= 1169) {
            $vol = 7;
        } elseif ($nmId <= 1313) {
            $vol = 8;
        } elseif ($nmId <= 1601) {
            $vol = 9;
        } elseif ($nmId <= 1655) {
            $vol = 10;
        } elseif ($nmId <= 1919) {
            $vol = 11;
        } elseif ($nmId <= 2045) {
            $vol = 12;
        } elseif ($nmId <= 2189) {
            $vol = 13;
        } elseif ($nmId <= 2405) {
            $vol = 14;
        } else {
            $vol = (int) ($nmId / 100000);
        }

        $part = (int) ($nmId / 1000);

        // Определяем basket
        $basket = match (true) {
            $vol >= 0 && $vol <= 143 => '01',
            $vol >= 144 && $vol <= 287 => '02',
            $vol >= 288 && $vol <= 431 => '03',
            $vol >= 432 && $vol <= 719 => '04',
            $vol >= 720 && $vol <= 1007 => '05',
            $vol >= 1008 && $vol <= 1061 => '06',
            $vol >= 1062 && $vol <= 1115 => '07',
            $vol >= 1116 && $vol <= 1169 => '08',
            $vol >= 1170 && $vol <= 1313 => '09',
            $vol >= 1314 && $vol <= 1601 => '10',
            $vol >= 1602 && $vol <= 1655 => '11',
            $vol >= 1656 && $vol <= 1919 => '12',
            $vol >= 1920 && $vol <= 2045 => '13',
            $vol >= 2046 && $vol <= 2189 => '14',
            $vol >= 2190 && $vol <= 2405 => '15',
            $vol >= 2406 && $vol <= 2621 => '16',
            $vol >= 2622 && $vol <= 2837 => '17',
            default => '01',
        };

        return "https://basket-{$basket}.wbbasket.ru/vol{$vol}/part{$part}/{$nmId}/images/big/1.jpg";
    }

    /**
     * Получить название товара (из первого item)
     */
    public function getProductNameAttribute(): ?string
    {
        $firstItem = $this->items->first();

        return $firstItem ? $firstItem->name : $this->article;
    }

    /**
     * Получить время с момента заказа в формате "X дн. Y ч. Z мин."
     */
    public function getTimeElapsedAttribute(): string
    {
        if (! $this->ordered_at) {
            return '';
        }

        $diff = $this->ordered_at->diff(now());

        $parts = [];
        if ($diff->days > 0) {
            $parts[] = $diff->days.' дн.';
        }
        if ($diff->h > 0) {
            $parts[] = $diff->h.' ч.';
        }
        if ($diff->i > 0 || empty($parts)) {
            $parts[] = $diff->i.' мин.';
        }

        return implode(' ', $parts);
    }
}
