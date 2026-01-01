<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CounterpartyContract extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'counterparty_id',
        'company_id',
        'number',
        'name',
        'date',
        'valid_from',
        'valid_until',
        'commission_percent',
        'commission_type',
        'commission_includes_vat',
        'status',
        'payment_days',
        'credit_limit',
        'currency',
        'file_path',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'valid_from' => 'date',
        'valid_until' => 'date',
        'commission_percent' => 'decimal:2',
        'commission_includes_vat' => 'boolean',
        'credit_limit' => 'decimal:2',
        'payment_days' => 'integer',
    ];

    public function counterparty(): BelongsTo
    {
        return $this->belongsTo(Counterparty::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function isActive(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        if ($this->valid_until && $this->valid_until->lt(now())) {
            return false;
        }

        return true;
    }

    public function isExpired(): bool
    {
        return $this->valid_until && $this->valid_until->lt(now());
    }

    public function getStatusLabel(): string
    {
        return match($this->status) {
            'draft' => 'Черновик',
            'active' => 'Действует',
            'suspended' => 'Приостановлен',
            'terminated' => 'Расторгнут',
            'expired' => 'Истёк',
            default => $this->status,
        };
    }

    public function getCommissionTypeLabel(): string
    {
        return $this->commission_type === 'profit' ? 'От прибыли' : 'От продаж';
    }

    public function calculateCommission(float $amount): float
    {
        if ($this->commission_percent <= 0) {
            return 0;
        }

        return $amount * ($this->commission_percent / 100);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('valid_until')
                  ->orWhere('valid_until', '>=', now());
            });
    }

    public function scopeByCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }
}
