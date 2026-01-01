<?php
// file: app/Models/MarketplaceProduct.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceProduct extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_ERROR = 'error';
    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'marketplace_account_id',
        'product_id',
        'external_product_id',
        'external_offer_id',
        'external_sku',
        'status',
        'shop_id',
        'title',
        'category',
        'preview_image',
        'raw_payload',
        'last_synced_price',
        'last_synced_stock',
        'last_synced_at',
        'last_error',
    ];

    protected function casts(): array
    {
        return [
            'last_synced_price' => 'float',
            'last_synced_stock' => 'integer',
            'last_synced_at' => 'datetime',
            'raw_payload' => 'array',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(MarketplaceAccount::class, 'marketplace_account_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Mark product as synced successfully
     */
    public function markAsSynced(?float $price = null, ?int $stock = null): void
    {
        $this->update([
            'status' => self::STATUS_ACTIVE,
            'last_synced_at' => now(),
            'last_synced_price' => $price ?? $this->last_synced_price,
            'last_synced_stock' => $stock ?? $this->last_synced_stock,
            'last_error' => null,
        ]);
    }

    /**
     * Mark product sync as failed
     */
    public function markAsFailed(string $error): void
    {
        $this->update([
            'status' => self::STATUS_ERROR,
            'last_synced_at' => now(),
            'last_error' => $error,
        ]);
    }

    /**
     * Get status label
     */
    public function getStatusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Ожидает синхронизации',
            self::STATUS_ACTIVE => 'Активен',
            self::STATUS_ERROR => 'Ошибка',
            self::STATUS_ARCHIVED => 'В архиве',
            default => $this->status,
        };
    }

    /**
     * Get status color for UI
     */
    public function getStatusColor(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'yellow',
            self::STATUS_ACTIVE => 'green',
            self::STATUS_ERROR => 'red',
            self::STATUS_ARCHIVED => 'gray',
            default => 'gray',
        };
    }
}
