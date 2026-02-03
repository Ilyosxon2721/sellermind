<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $fillable = [
        'name',
        'email',
        'password',
        'locale',
        'company_id',
        'telegram_id',
        'telegram_username',
        'telegram_notifications_enabled',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'telegram_notifications_enabled' => 'boolean',
        ];
    }

    /**
     * Основная (текущая) компания пользователя
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Alias for company() - used by CurrencySettingsController
     */
    public function getCurrentCompanyAttribute(): ?Company
    {
        return $this->company;
    }

    /**
     * Все компании, к которым пользователь имеет доступ
     */
    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class, 'user_company_roles')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function dialogs(): HasMany
    {
        return $this->hasMany(Dialog::class);
    }

    public function generationTasks(): HasMany
    {
        return $this->hasMany(GenerationTask::class);
    }

    public function aiUsageLogs(): HasMany
    {
        return $this->hasMany(AIUsageLog::class);
    }

    /**
     * Склады, к которым привязан пользователь
     */
    public function warehouses(): BelongsToMany
    {
        return $this->belongsToMany(\App\Models\Warehouse\Warehouse::class, 'warehouse_user')
            ->withPivot('is_default')
            ->withTimestamps();
    }

    /**
     * Получить склад по умолчанию для пользователя
     */
    public function defaultWarehouse()
    {
        return $this->warehouses()->wherePivot('is_default', true)->first();
    }

    /**
     * Получить доступные склады для компании пользователя
     */
    public function getAvailableWarehouses(?int $companyId = null)
    {
        $companyId = $companyId ?? $this->company_id;

        if (!$companyId) {
            return collect();
        }

        return $this->warehouses()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->get();
    }

    public function hasCompanyAccess(int $companyId): bool
    {
        return $this->companies()->where('companies.id', $companyId)->exists();
    }

    public function isOwnerOf(int $companyId): bool
    {
        return $this->companies()
            ->where('companies.id', $companyId)
            ->wherePivot('role', 'owner')
            ->exists();
    }

    /**
     * Get the user's notification settings
     */
    public function notificationSettings(): HasOne
    {
        return $this->hasOne(UserNotificationSetting::class);
    }

    /**
     * Route notifications for the Telegram channel
     */
    public function routeNotificationForTelegram(): ?string
    {
        return $this->telegram_id;
    }
}
