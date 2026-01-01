<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OzonWarehouse extends Model
{
    protected $table = 'ozon_warehouses';

    protected $fillable = [
        'marketplace_account_id',
        'warehouse_id',
        'name',
        'type',
        'is_active',
        'has_entrusting',
        'can_print_act_in_advance',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'has_entrusting' => 'boolean',
            'can_print_act_in_advance' => 'boolean',
            'metadata' => 'array',
        ];
    }

    /**
     * Get the marketplace account
     */
    public function marketplaceAccount(): BelongsTo
    {
        return $this->belongsTo(MarketplaceAccount::class);
    }

    /**
     * Scope for active warehouses
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for specific type
     */
    public function scopeType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
