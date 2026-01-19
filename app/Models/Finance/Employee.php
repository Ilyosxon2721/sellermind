<?php

namespace App\Models\Finance;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    use HasFactory;

    public const SALARY_TYPE_FIXED = 'fixed';
    public const SALARY_TYPE_HOURLY = 'hourly';
    public const SALARY_TYPE_COMMISSION = 'commission';

    protected $fillable = [
        'company_id',
        'user_id',
        'first_name',
        'last_name',
        'middle_name',
        'phone',
        'email',
        'position',
        'department',
        'hire_date',
        'termination_date',
        'salary_type',
        'base_salary',
        'currency_code',
        'bank_name',
        'bank_account',
        'inn',
        'is_active',
    ];

    protected $casts = [
        'hire_date' => 'date',
        'termination_date' => 'date',
        'base_salary' => 'float',
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function salaryItems(): HasMany
    {
        return $this->hasMany(SalaryItem::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(FinanceTransaction::class);
    }

    public function debts(): HasMany
    {
        return $this->hasMany(FinanceDebt::class);
    }

    public function getFullNameAttribute(): string
    {
        $parts = array_filter([$this->last_name, $this->first_name, $this->middle_name]);
        return implode(' ', $parts);
    }

    public function getShortNameAttribute(): string
    {
        $initials = '';
        if ($this->first_name) {
            $initials .= mb_substr($this->first_name, 0, 1) . '.';
        }
        if ($this->middle_name) {
            $initials .= mb_substr($this->middle_name, 0, 1) . '.';
        }
        return trim($this->last_name . ' ' . $initials);
    }

    public function scopeByCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
