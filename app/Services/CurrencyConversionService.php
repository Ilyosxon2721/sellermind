<?php

namespace App\Services;

use App\Models\Company;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Service for currency conversion.
 *
 * Handles conversion between currencies, primarily RUB to UZS for analytics.
 * Exchange rates can be set manually in company settings or fetched from API.
 */
class CurrencyConversionService
{
    /**
     * Default exchange rates (fallback values)
     * Rates to UZS as base (approximate values, should be updated via API or settings)
     */
    protected const DEFAULT_RATES = [
        // RUB conversions
        'RUB_UZS' => 140.0,   // 1 RUB ≈ 140 UZS
        'RUB_KZT' => 5.2,     // 1 RUB ≈ 5.2 KZT

        // BYN (Belarusian Ruble) conversions
        'BYN_UZS' => 3900.0,  // 1 BYN ≈ 3900 UZS
        'BYN_RUB' => 28.0,    // 1 BYN ≈ 28 RUB

        // KZT (Kazakh Tenge) conversions
        'KZT_UZS' => 27.0,    // 1 KZT ≈ 27 UZS

        // USD conversions
        'USD_UZS' => 12800.0, // 1 USD ≈ 12800 UZS
        'USD_RUB' => 92.0,    // 1 USD ≈ 92 RUB

        // EUR conversions
        'EUR_UZS' => 13800.0, // 1 EUR ≈ 13800 UZS
        'EUR_RUB' => 100.0,   // 1 EUR ≈ 100 RUB

        // KGS (Kyrgyz Som) conversions
        'KGS_UZS' => 145.0,   // 1 KGS ≈ 145 UZS
    ];

    /**
     * ISO 4217 numeric codes to currency codes mapping
     */
    protected const ISO_NUMERIC_TO_CODE = [
        '643' => 'RUB', // Russian Ruble
        '933' => 'BYN', // Belarusian Ruble
        '860' => 'UZS', // Uzbek Sum
        '398' => 'KZT', // Kazakh Tenge
        '417' => 'KGS', // Kyrgyz Som
        '051' => 'AMD', // Armenian Dram
        '944' => 'AZN', // Azerbaijani Manat
        '840' => 'USD', // US Dollar
        '978' => 'EUR', // Euro
    ];

    /**
     * Cache TTL for exchange rates (1 hour)
     */
    protected const RATE_CACHE_TTL = 3600;

    protected ?Company $company = null;

    public function __construct(?Company $company = null)
    {
        $this->company = $company;
    }

    /**
     * Set company context
     */
    public function forCompany(Company $company): self
    {
        $this->company = $company;
        return $this;
    }

    /**
     * Get the display currency for the company
     * Default is UZS for Uzbekistan-based companies
     */
    public function getDisplayCurrency(): string
    {
        if (!$this->company) {
            return 'UZS'; // Default to UZS for sellermind.uz
        }

        return $this->company->getSetting('display_currency', 'UZS');
    }

    /**
     * Get currency symbol
     */
    public function getCurrencySymbol(?string $currency = null): string
    {
        $currency = $currency ?? $this->getDisplayCurrency();

        return match ($currency) {
            'RUB' => '₽',
            'UZS' => 'сўм',
            'KZT' => '₸',
            'KGS' => 'с',
            'USD' => '$',
            'EUR' => '€',
            'BYN' => 'Br',
            default => $currency,
        };
    }

    /**
     * Get exchange rate from source to target currency
     */
    public function getRate(string $from, string $to): float
    {
        if ($from === $to) {
            return 1.0;
        }

        $rateKey = "{$from}_{$to}";
        $inverseKey = "{$to}_{$from}";

        // Check company settings first
        if ($this->company) {
            $customRates = $this->company->getSetting('exchange_rates', []);
            if (isset($customRates[$rateKey])) {
                return (float) $customRates[$rateKey];
            }
            if (isset($customRates[$inverseKey])) {
                return 1.0 / (float) $customRates[$inverseKey];
            }
        }

        // Check cached rates
        $cachedRate = Cache::get("exchange_rate:{$rateKey}");
        if ($cachedRate !== null) {
            return (float) $cachedRate;
        }

        // Check default rates
        if (isset(self::DEFAULT_RATES[$rateKey])) {
            return self::DEFAULT_RATES[$rateKey];
        }
        if (isset(self::DEFAULT_RATES[$inverseKey])) {
            return 1.0 / self::DEFAULT_RATES[$inverseKey];
        }

        // Try to fetch from API
        $fetchedRate = $this->fetchRateFromApi($from, $to);
        if ($fetchedRate !== null) {
            Cache::put("exchange_rate:{$rateKey}", $fetchedRate, self::RATE_CACHE_TTL);
            return $fetchedRate;
        }

        // Try cross-rate via RUB (common base currency for CIS)
        if ($from !== 'RUB' && $to !== 'RUB') {
            $fromToRub = $this->getRate($from, 'RUB');
            $rubToTarget = $this->getRate('RUB', $to);
            if ($fromToRub !== 1.0 || $rubToTarget !== 1.0) {
                $crossRate = $fromToRub * $rubToTarget;
                Cache::put("exchange_rate:{$rateKey}", $crossRate, self::RATE_CACHE_TTL);
                return $crossRate;
            }
        }

        Log::warning("Exchange rate not found", ['from' => $from, 'to' => $to]);
        return 1.0;
    }

    /**
     * Convert amount from one currency to another
     */
    public function convert(float $amount, string $from, string $to): float
    {
        if ($from === $to) {
            return $amount;
        }

        $rate = $this->getRate($from, $to);
        return $amount * $rate;
    }

    /**
     * Convert RUB amount to display currency
     */
    public function convertFromRub(float $amount): float
    {
        return $this->convert($amount, 'RUB', $this->getDisplayCurrency());
    }

    /**
     * Convert amount from any currency to display currency
     *
     * @param float $amount The amount to convert
     * @param string|int|null $fromCurrency Currency code (e.g., 'RUB', 'BYN') or ISO numeric code (e.g., 643, 933)
     * @return float Converted amount in display currency
     */
    public function convertToDisplay(float $amount, $fromCurrency = null): float
    {
        if ($fromCurrency === null) {
            return $amount; // No conversion needed
        }

        // Convert ISO numeric code to currency code if needed
        $from = $this->normalizeCurrencyCode($fromCurrency);
        $to = $this->getDisplayCurrency();

        return $this->convert($amount, $from, $to);
    }

    /**
     * Normalize currency code - convert ISO numeric to 3-letter code if needed
     *
     * @param string|int|null $code Currency code or ISO numeric code
     * @return string 3-letter currency code
     */
    public function normalizeCurrencyCode($code): string
    {
        if ($code === null) {
            return 'RUB'; // Default to RUB for WB orders without currency info
        }

        $code = (string) $code;

        // Check if it's an ISO numeric code
        if (isset(self::ISO_NUMERIC_TO_CODE[$code])) {
            return self::ISO_NUMERIC_TO_CODE[$code];
        }

        // Already a 3-letter code
        if (strlen($code) === 3) {
            return strtoupper($code);
        }

        return 'RUB'; // Default fallback
    }

    /**
     * Get currency code from ISO numeric code
     */
    public function getCurrencyCodeFromNumeric($numericCode): string
    {
        return self::ISO_NUMERIC_TO_CODE[(string) $numericCode] ?? 'RUB';
    }

    /**
     * Format amount in display currency
     */
    public function format(float $amount, ?string $currency = null, bool $withSymbol = true): string
    {
        $currency = $currency ?? $this->getDisplayCurrency();

        // For UZS, use no decimal places (сумы обычно без копеек)
        $decimals = in_array($currency, ['UZS', 'KZT', 'KGS']) ? 0 : 2;

        $formatted = number_format($amount, $decimals, '.', ' ');

        if ($withSymbol) {
            $symbol = $this->getCurrencySymbol($currency);
            return "{$formatted} {$symbol}";
        }

        return $formatted;
    }

    /**
     * Convert from RUB and format in display currency
     */
    public function convertAndFormat(float $amountRub, bool $withSymbol = true): string
    {
        $converted = $this->convertFromRub($amountRub);
        return $this->format($converted, null, $withSymbol);
    }

    /**
     * Convert from any currency and format in display currency
     *
     * @param float $amount Amount to convert
     * @param string|int|null $fromCurrency Source currency code or ISO numeric code
     * @param bool $withSymbol Include currency symbol
     * @return string Formatted amount
     */
    public function convertToDisplayAndFormat(float $amount, $fromCurrency = null, bool $withSymbol = true): string
    {
        $converted = $this->convertToDisplay($amount, $fromCurrency);
        return $this->format($converted, null, $withSymbol);
    }

    /**
     * Fetch exchange rate from external API
     */
    protected function fetchRateFromApi(string $from, string $to): ?float
    {
        try {
            // Use Central Bank of Russia API for RUB rates
            if ($from === 'RUB' || $to === 'RUB') {
                return $this->fetchFromCbrApi($from, $to);
            }

            return null;
        } catch (\Exception $e) {
            Log::warning("Failed to fetch exchange rate from API", [
                'from' => $from,
                'to' => $to,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Fetch rate from Central Bank of Russia API
     */
    protected function fetchFromCbrApi(string $from, string $to): ?float
    {
        try {
            $response = Http::timeout(5)->get('https://www.cbr-xml-daily.ru/daily_json.js');

            if (!$response->successful()) {
                return null;
            }

            $data = $response->json();
            $valutes = $data['Valute'] ?? [];

            // If converting from RUB to another currency
            if ($from === 'RUB' && isset($valutes[$to])) {
                $valute = $valutes[$to];
                // Rate shows how many RUB for Nominal units of foreign currency
                // So to get how many $to for 1 RUB: divide by rate
                return $valute['Nominal'] / $valute['Value'];
            }

            // If converting from another currency to RUB
            if ($to === 'RUB' && isset($valutes[$from])) {
                $valute = $valutes[$from];
                return $valute['Value'] / $valute['Nominal'];
            }

            return null;
        } catch (\Exception $e) {
            Log::warning("CBR API request failed", ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Update exchange rate in company settings
     */
    public function setCompanyRate(string $from, string $to, float $rate): void
    {
        if (!$this->company) {
            throw new \RuntimeException('Company not set');
        }

        $rates = $this->company->getSetting('exchange_rates', []);
        $rates["{$from}_{$to}"] = $rate;

        $this->company->setSetting('exchange_rates', $rates);
        $this->company->save();

        // Clear cache
        Cache::forget("exchange_rate:{$from}_{$to}");
    }

    /**
     * Get all custom rates for company
     */
    public function getCompanyRates(): array
    {
        if (!$this->company) {
            return [];
        }

        return $this->company->getSetting('exchange_rates', []);
    }

    /**
     * Set display currency for company
     */
    public function setDisplayCurrency(string $currency): void
    {
        if (!$this->company) {
            throw new \RuntimeException('Company not set');
        }

        $this->company->setSetting('display_currency', $currency);
        $this->company->save();
    }
}
