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
        'usd_rate',
        'rub_rate',
        'eur_rate',
        'rates_updated_at',
        'tax_system',
        'vat_rate',
        'income_tax_rate',
        'social_tax_rate',
        'auto_import_marketplace_fees',
    ];

    protected $casts = [
        'usd_rate' => 'float',
        'rub_rate' => 'float',
        'eur_rate' => 'float',
        'rates_updated_at' => 'datetime',
        'vat_rate' => 'float',
        'income_tax_rate' => 'float',
        'social_tax_rate' => 'float',
        'auto_import_marketplace_fees' => 'boolean',
    ];

    /**
     * Конвертировать сумму из указанной валюты в базовую (UZS)
     */
    public function convertToBase(float $amount, string $currency): float
    {
        $currency = strtoupper($currency);

        if ($currency === 'UZS' || $currency === $this->base_currency_code) {
            return $amount;
        }

        return match ($currency) {
            'USD' => $amount * $this->usd_rate,
            'RUB' => $amount * $this->rub_rate,
            'EUR' => $amount * $this->eur_rate,
            default => $amount,
        };
    }

    /**
     * Получить курс для валюты
     */
    public function getRate(string $currency): float
    {
        return match (strtoupper($currency)) {
            'USD' => $this->usd_rate,
            'RUB' => $this->rub_rate,
            'EUR' => $this->eur_rate,
            'UZS' => 1.0,
            default => 1.0,
        };
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get or create finance settings for company.
     * Default rates are for Uzbekistan:
     * - VAT: 12% (standard rate)
     * - Income tax (profit tax): 15% (general rate, 20% for banks/telecom)
     * - Simplified tax (turnover tax): 4% for trade, 25% for other
     * - Social tax (INPS): 12% (employer contribution)
     */
    public static function getForCompany(int $companyId): self
    {
        return self::firstOrCreate(
            ['company_id' => $companyId],
            [
                'base_currency_code' => 'UZS',
                'usd_rate' => 12700.00,        // Курс доллара
                'rub_rate' => 140.00,          // Курс рубля
                'eur_rate' => 13800.00,        // Курс евро
                'tax_system' => self::TAX_SYSTEM_SIMPLIFIED,
                'vat_rate' => 12.00,           // НДС 12%
                'income_tax_rate' => 4.00,     // Упрощёнка 4% от оборота (торговля)
                'social_tax_rate' => 12.00,    // ИНПС 12% (работодатель)
                'auto_import_marketplace_fees' => true,
            ]
        );
    }
}
