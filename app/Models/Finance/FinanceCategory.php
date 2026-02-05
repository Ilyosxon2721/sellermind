<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinanceCategory extends Model
{
    use HasFactory;

    public const TYPE_INCOME = 'income';

    public const TYPE_EXPENSE = 'expense';

    public const TYPE_BOTH = 'both';

    // Системные коды категорий
    public const CODE_COMPANY_RENT = 'COMPANY_RENT';

    public const CODE_COMPANY_UTILITIES = 'COMPANY_UTILITIES';

    public const CODE_COMPANY_INTERNET = 'COMPANY_INTERNET';

    public const CODE_COMPANY_OFFICE = 'COMPANY_OFFICE';

    public const CODE_COMPANY_EQUIPMENT = 'COMPANY_EQUIPMENT';

    public const CODE_LOGISTICS_DELIVERY = 'LOGISTICS_DELIVERY';

    public const CODE_LOGISTICS_PACKAGING = 'LOGISTICS_PACKAGING';

    public const CODE_LOGISTICS_CUSTOMS = 'LOGISTICS_CUSTOMS';

    public const CODE_LOGISTICS_STORAGE = 'LOGISTICS_STORAGE';

    public const CODE_MP_COMMISSION = 'MP_COMMISSION';

    public const CODE_MP_LOGISTICS = 'MP_LOGISTICS';

    public const CODE_MP_STORAGE = 'MP_STORAGE';

    public const CODE_MP_ADS = 'MP_ADS';

    public const CODE_MP_PENALTIES = 'MP_PENALTIES';

    public const CODE_MP_RETURNS = 'MP_RETURNS';

    public const CODE_PAYROLL_SALARY = 'PAYROLL_SALARY';

    public const CODE_PAYROLL_BONUS = 'PAYROLL_BONUS';

    public const CODE_PAYROLL_SOCIAL = 'PAYROLL_SOCIAL';

    public const CODE_TAX_INCOME = 'TAX_INCOME';

    public const CODE_TAX_VAT = 'TAX_VAT';

    public const CODE_TAX_SOCIAL = 'TAX_SOCIAL';

    public const CODE_SALES_MARKETPLACE = 'SALES_MARKETPLACE';

    public const CODE_SALES_DIRECT = 'SALES_DIRECT';

    public const CODE_SALES_WHOLESALE = 'SALES_WHOLESALE';

    public const CODE_OTHER_INCOME = 'OTHER_INCOME';

    public const CODE_OTHER_EXPENSE = 'OTHER_EXPENSE';

    protected $fillable = [
        'company_id',
        'parent_id',
        'type',
        'code',
        'name',
        'is_system',
        'is_active',
        'tax_category',
        'sort_order',
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(FinanceCategory::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(FinanceCategory::class, 'parent_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(FinanceTransaction::class, 'category_id');
    }

    public function scopeByCompany($query, ?int $companyId)
    {
        return $query->where(function ($q) use ($companyId) {
            $q->whereNull('company_id') // системные
                ->orWhere('company_id', $companyId);
        });
    }

    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeExpense($query)
    {
        return $query->whereIn('type', [self::TYPE_EXPENSE, self::TYPE_BOTH]);
    }

    public function scopeIncome($query)
    {
        return $query->whereIn('type', [self::TYPE_INCOME, self::TYPE_BOTH]);
    }

    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id');
    }
}
