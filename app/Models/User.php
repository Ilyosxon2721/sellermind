<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
        ];
    }

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
}
