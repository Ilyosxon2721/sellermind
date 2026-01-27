<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceExpenseCache extends Model
{
    protected $table = 'marketplace_expense_cache';

    protected $fillable = [
        'company_id',
        'marketplace_account_id',
        'marketplace',
        'period_type',
        'period_from',
        'period_to',
        'commission',
        'logistics',
        'storage',
        'advertising',
        'penalties',
        'returns',
        'other',
        'total',
        'gross_revenue',
        'orders_count',
        'returns_count',
        'currency',
        'total_uzs',
        'synced_at',
        'sync_status',
        'sync_error',
    ];

    protected $casts = [
        'period_from' => 'date',
        'period_to' => 'date',
        'commission' => 'decimal:2',
        'logistics' => 'decimal:2',
        'storage' => 'decimal:2',
        'advertising' => 'decimal:2',
        'penalties' => 'decimal:2',
        'returns' => 'decimal:2',
        'other' => 'decimal:2',
        'total' => 'decimal:2',
        'gross_revenue' => 'decimal:2',
        'total_uzs' => 'decimal:2',
        'orders_count' => 'integer',
        'returns_count' => 'integer',
        'synced_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function marketplaceAccount(): BelongsTo
    {
        return $this->belongsTo(MarketplaceAccount::class);
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeForMarketplace($query, string $marketplace)
    {
        return $query->where('marketplace', $marketplace);
    }

    public function scopeForPeriod($query, string $periodType)
    {
        return $query->where('period_type', $periodType);
    }

    public function scopeSuccessful($query)
    {
        return $query->where('sync_status', 'success');
    }

    public function scopeStale($query, int $hours = 4)
    {
        return $query->where(function ($q) use ($hours) {
            $q->whereNull('synced_at')
              ->orWhere('synced_at', '<', now()->subHours($hours));
        });
    }

    public function isStale(int $hours = 4): bool
    {
        if (!$this->synced_at) {
            return true;
        }

        return $this->synced_at->lt(now()->subHours($hours));
    }

    public function markSyncing(): void
    {
        $this->update([
            'sync_status' => 'syncing',
            'sync_error' => null,
        ]);
    }

    public function markSuccess(array $data): void
    {
        $this->update(array_merge($data, [
            'synced_at' => now(),
            'sync_status' => 'success',
            'sync_error' => null,
        ]));
    }

    public function markError(string $error): void
    {
        $this->update([
            'sync_status' => 'error',
            'sync_error' => $error,
        ]);
    }
}
