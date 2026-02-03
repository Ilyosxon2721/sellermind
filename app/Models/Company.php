<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Company extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'settings',
    ];

    protected $casts = [
        'settings' => 'array',
    ];

    /**
     * Настройки по умолчанию
     */
    public static array $defaultSettings = [
        'auto_sync_stock_on_link' => true,      // Автосинхронизация при привязке товара
        'auto_sync_stock_on_change' => true,    // Автосинхронизация при изменении остатков
        'stock_sync_enabled' => true,           // Общий выключатель синхронизации остатков
    ];

    /**
     * Получить значение настройки
     */
    public function getSetting(string $key, mixed $default = null): mixed
    {
        $settings = $this->settings ?? [];

        return $settings[$key] ?? self::$defaultSettings[$key] ?? $default;
    }

    /**
     * Установить значение настройки
     */
    public function setSetting(string $key, mixed $value): void
    {
        $settings = $this->settings ?? [];
        $settings[$key] = $value;
        $this->settings = $settings;
    }

    /**
     * Получить все настройки с дефолтами
     */
    public function getAllSettings(): array
    {
        return array_merge(self::$defaultSettings, $this->settings ?? []);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($company) {
            if (empty($company->slug)) {
                $company->slug = Str::slug($company->name).'-'.Str::random(6);
            }
        });
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_company_roles')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function dialogs(): HasMany
    {
        return $this->hasMany(Dialog::class);
    }

    public function generationTasks(): HasMany
    {
        return $this->hasMany(GenerationTask::class);
    }

    public function marketplaceAccounts(): HasMany
    {
        return $this->hasMany(MarketplaceAccount::class);
    }

    public function warehouses(): HasMany
    {
        return $this->hasMany(Warehouse\Warehouse::class);
    }

    public function aiUsageLogs(): HasMany
    {
        return $this->hasMany(AIUsageLog::class);
    }

    public function owner(): ?User
    {
        return $this->users()->wherePivot('role', 'owner')->first();
    }

    /**
     * Все подписки компании
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Активная подписка
     */
    public function activeSubscription()
    {
        return $this->hasOne(Subscription::class)
            ->where('status', 'active')
            ->latest('starts_at');
    }

    /**
     * Получить текущий тариф
     */
    public function getCurrentPlan(): ?Plan
    {
        return $this->activeSubscription?->plan;
    }

    /**
     * Проверить лимит
     */
    public function checkLimit(string $type, int $count = 1): bool
    {
        $subscription = $this->activeSubscription;
        if (! $subscription) {
            return false;
        }

        return match ($type) {
            'products' => $subscription->canAddProducts($count),
            'orders' => $subscription->canProcessOrders($count),
            'ai' => $subscription->canUseAI($count),
            default => true,
        };
    }
}
