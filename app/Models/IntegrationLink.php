<?php

namespace App\Models;

use App\Models\Risment\RismentClient;
use App\Models\Warehouse\Warehouse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntegrationLink extends Model
{
    protected $fillable = [
        'user_id',
        'company_id',
        'risment_client_id',
        'external_system',
        'external_user_id',
        'link_token',
        'warehouse_id',
        'is_active',
        'linked_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'linked_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function rismentClient(): BelongsTo
    {
        return $this->belongsTo(RismentClient::class, 'risment_client_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForSystem($query, string $system)
    {
        return $query->where('external_system', $system);
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Find active RISMENT link for a company (первый найденный)
     */
    public static function rismentForCompany(int $companyId): ?self
    {
        return static::where('company_id', $companyId)
            ->where('external_system', 'risment')
            ->where('is_active', true)
            ->first();
    }

    /**
     * Найти активную RISMENT связку для конкретного клиента
     */
    public static function rismentForClient(int $rismentClientId): ?self
    {
        return static::where('risment_client_id', $rismentClientId)
            ->where('external_system', 'risment')
            ->where('is_active', true)
            ->first();
    }

    /**
     * Все активные RISMENT связки для компании
     */
    public static function rismentLinksForCompany(int $companyId): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('company_id', $companyId)
            ->where('external_system', 'risment')
            ->where('is_active', true)
            ->get();
    }
}
