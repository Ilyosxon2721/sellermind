<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WildberriesPass extends Model
{
    use HasFactory;

    protected $fillable = [
        'marketplace_account_id',
        'pass_id',
        'office_id',
        'first_name',
        'last_name',
        'patronymic',
        'car_model',
        'car_number',
        'date_from',
        'date_to',
        'status',
        'raw_data',
    ];

    protected $casts = [
        'date_from' => 'date',
        'date_to' => 'date',
        'raw_data' => 'array',
    ];

    /**
     * Get the marketplace account that owns the pass
     */
    public function marketplaceAccount(): BelongsTo
    {
        return $this->belongsTo(MarketplaceAccount::class);
    }

    /**
     * Check if pass is expired
     */
    public function isExpired(): bool
    {
        return $this->date_to < now()->startOfDay();
    }

    /**
     * Check if pass is expiring soon
     */
    public function isExpiringSoon(int $days = 7): bool
    {
        $cutoffDate = now()->addDays($days)->startOfDay();
        return $this->date_to <= $cutoffDate && $this->date_to >= now()->startOfDay();
    }

    /**
     * Scope for active passes
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where('date_to', '>=', now()->startOfDay());
    }

    /**
     * Scope for expired passes
     */
    public function scopeExpired($query)
    {
        return $query->where('date_to', '<', now()->startOfDay());
    }

    /**
     * Scope for expiring soon
     */
    public function scopeExpiringSoon($query, int $days = 7)
    {
        $cutoffDate = now()->addDays($days)->startOfDay();
        return $query->where('date_to', '<=', $cutoffDate)
            ->where('date_to', '>=', now()->startOfDay());
    }
}
