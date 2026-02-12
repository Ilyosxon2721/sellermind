<?php

namespace App\Models\Store;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreDeliveryMethod extends Model
{
    public $timestamps = false;

    const CREATED_AT = 'created_at';

    const UPDATED_AT = null;

    protected $fillable = [
        'store_id',
        'name',
        'description',
        'type',
        'price',
        'free_from',
        'min_days',
        'max_days',
        'zones',
        'position',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'free_from' => 'decimal:2',
        'zones' => 'array',
        'is_active' => 'boolean',
        'position' => 'integer',
    ];

    // ==================
    // Relationships
    // ==================

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    // ==================
    // Methods
    // ==================

    /**
     * Проверить, бесплатна ли доставка для указанной суммы заказа
     */
    public function isFreeFor(float $orderTotal): bool
    {
        return $this->free_from && $orderTotal >= (float) $this->free_from;
    }

    /**
     * Получить строку с днями доставки
     */
    public function getDeliveryDays(): string
    {
        if ($this->min_days === $this->max_days) {
            return "{$this->min_days} день";
        }

        return "{$this->min_days}-{$this->max_days} дней";
    }
}
