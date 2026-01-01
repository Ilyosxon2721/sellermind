<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Counterparty extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'type',
        'name',
        'short_name',
        'inn',
        'kpp',
        'ogrn',
        'okpo',
        'phone',
        'email',
        'website',
        'legal_address',
        'actual_address',
        'bank_name',
        'bank_bik',
        'bank_account',
        'bank_corr_account',
        'contact_person',
        'contact_phone',
        'contact_position',
        'is_active',
        'is_supplier',
        'is_customer',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_supplier' => 'boolean',
        'is_customer' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(CounterpartyContract::class);
    }

    public function activeContract(): ?CounterpartyContract
    {
        return $this->contracts()
            ->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('valid_until')
                  ->orWhere('valid_until', '>=', now());
            })
            ->orderByDesc('date')
            ->first();
    }

    public function getCommissionPercent(): float
    {
        $contract = $this->activeContract();
        return $contract ? (float) $contract->commission_percent : 0;
    }

    public function calculateCommission(float $amount, string $type = 'sales'): float
    {
        $contract = $this->activeContract();
        if (!$contract || $contract->commission_percent <= 0) {
            return 0;
        }

        if ($contract->commission_type === $type) {
            return $amount * ($contract->commission_percent / 100);
        }

        return 0;
    }

    public function getTypeLabel(): string
    {
        return $this->type === 'legal' ? 'Юр. лицо' : 'Физ. лицо';
    }

    public function getDisplayName(): string
    {
        return $this->short_name ?: $this->name;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeCustomers($query)
    {
        return $query->where('is_customer', true);
    }

    public function scopeSuppliers($query)
    {
        return $query->where('is_supplier', true);
    }

    public function scopeByCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeSearch($query, ?string $search)
    {
        if (!$search) return $query;
        
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('short_name', 'like', "%{$search}%")
              ->orWhere('inn', 'like', "%{$search}%")
              ->orWhere('phone', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%");
        });
    }
}
