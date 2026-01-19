<?php

namespace App\Models\Finance;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanceSettings extends Model
{
    use HasFactory;

    public const TAX_SYSTEM_SIMPLIFIED = 'simplified';
    public const TAX_SYSTEM_GENERAL = 'general';
    public const TAX_SYSTEM_BOTH = 'both';

    protected $fillable = [
        'company_id',
        'base_currency_code',
        'tax_system',
        'vat_rate',
        'income_tax_rate',
        'social_tax_rate',
        'auto_import_marketplace_fees',
    ];

    protected $casts = [
        'vat_rate' => 'float',
        'income_tax_rate' => 'float',
        'social_tax_rate' => 'float',
        'auto_import_marketplace_fees' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public static function getForCompany(int $companyId): self
    {
        return self::firstOrCreate(
            ['company_id' => $companyId],
            [
                'base_currency_code' => 'UZS',
                'tax_system' => self::TAX_SYSTEM_SIMPLIFIED,
                'vat_rate' => 12.00,
                'income_tax_rate' => 15.00,
                'social_tax_rate' => 12.00,
                'auto_import_marketplace_fees' => true,
            ]
        );
    }
}
