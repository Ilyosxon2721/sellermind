<?php

namespace App\Models\Warehouse;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Warehouse extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'code',
        'address',
        'address_comment',
        'comment',
        'group_name',
        'external_code',
        'meta_json',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'meta_json' => 'array',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function scopeByCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function locations(): HasMany
    {
        return $this->hasMany(WarehouseLocation::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(InventoryDocument::class, 'warehouse_id');
    }

    /**
     * Пользователи, привязанные к складу
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'warehouse_user')
            ->withPivot('is_default')
            ->withTimestamps();
    }
}
