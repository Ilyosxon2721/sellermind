<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'currency',
        'billing_period',
        'max_marketplace_accounts',
        'max_products',
        'max_orders_per_month',
        'max_users',
        'max_warehouses',
        'max_ai_requests',
        'data_retention_days',
        'has_api_access',
        'has_priority_support',
        'has_telegram_notifications',
        'has_auto_pricing',
        'has_analytics',
        'allowed_marketplaces',
        'features',
        'sort_order',
        'is_active',
        'is_popular',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'has_api_access' => 'boolean',
        'has_priority_support' => 'boolean',
        'has_telegram_notifications' => 'boolean',
        'has_auto_pricing' => 'boolean',
        'has_analytics' => 'boolean',
        'allowed_marketplaces' => 'array',
        'features' => 'array',
        'is_active' => 'boolean',
        'is_popular' => 'boolean',
    ];

    /**
     * Подписки на этот тариф
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Активные подписки
     */
    public function activeSubscriptions(): HasMany
    {
        return $this->subscriptions()->where('status', 'active');
    }

    /**
     * Проверка доступа к маркетплейсу
     */
    public function hasMarketplace(string $marketplace): bool
    {
        if (empty($this->allowed_marketplaces)) {
            return false;
        }

        return in_array($marketplace, $this->allowed_marketplaces);
    }

    /**
     * Форматированная цена
     */
    public function getFormattedPriceAttribute(): string
    {
        return number_format($this->price, 0, '.', ' ').' '.$this->currency;
    }

    /**
     * Scope для активных тарифов
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope для сортировки
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('price');
    }
}
