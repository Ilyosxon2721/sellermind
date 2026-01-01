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
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($company) {
            if (empty($company->slug)) {
                $company->slug = Str::slug($company->name) . '-' . Str::random(6);
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

    public function aiUsageLogs(): HasMany
    {
        return $this->hasMany(AIUsageLog::class);
    }

    public function owner(): ?User
    {
        return $this->users()->wherePivot('role', 'owner')->first();
    }
}
