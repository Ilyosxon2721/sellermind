<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

final class MarketplaceCustomer extends Model
{
    protected $fillable = [
        'company_id',
        'name',
        'phone',
        'address',
        'city',
        'source',
        'orders_count',
        'total_spent',
        'first_order_at',
        'last_order_at',
        'last_order_type',
        'last_order_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'orders_count' => 'integer',
            'total_spent' => 'decimal:2',
            'first_order_at' => 'datetime',
            'last_order_at' => 'datetime',
        ];
    }

    // ========== Relationships ==========

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Последний заказ (полиморфная связь)
     */
    public function lastOrder(): MorphTo
    {
        return $this->morphTo('last_order');
    }

    // ========== Scopes ==========

    public function scopeByCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeBySource($query, string $source)
    {
        return $query->where('source', $source);
    }

    public function scopeSearch($query, ?string $search)
    {
        if (! $search) {
            return $query;
        }

        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")
                ->orWhere('city', 'like', "%{$search}%")
                ->orWhere('address', 'like', "%{$search}%");
        });
    }

    // ========== Helpers ==========

    /**
     * Получить название маркетплейса
     */
    public function getSourceLabel(): string
    {
        return match ($this->source) {
            'uzum' => 'Uzum Market',
            'wb' => 'Wildberries',
            'ozon' => 'Ozon',
            'ym' => 'Yandex Market',
            default => $this->source,
        };
    }
}
