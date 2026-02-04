<?php

namespace App\Helpers;

class CurrencyHelper
{
    /**
     * ISO 4217 Currency Codes mapping
     * https://www.iso.org/iso-4217-currency-codes.html
     */
    private const CURRENCY_CODES = [
        '643' => ['code' => 'RUB', 'name' => 'Российский рубль', 'symbol' => '₽'],
        '933' => ['code' => 'BYN', 'name' => 'Белорусский рубль', 'symbol' => 'Br'],
        '860' => ['code' => 'UZS', 'name' => 'Узбекский сум', 'symbol' => 'сўм'],
        '398' => ['code' => 'KZT', 'name' => 'Казахстанский тенге', 'symbol' => '₸'],
        '417' => ['code' => 'KGS', 'name' => 'Киргизский сом', 'symbol' => 'с'],
        '051' => ['code' => 'AMD', 'name' => 'Армянский драм', 'symbol' => '֏'],
        '944' => ['code' => 'AZN', 'name' => 'Азербайджанский манат', 'symbol' => '₼'],
        '840' => ['code' => 'USD', 'name' => 'Доллар США', 'symbol' => '$'],
        '978' => ['code' => 'EUR', 'name' => 'Евро', 'symbol' => '€'],
    ];

    /**
     * Get currency info by ISO numeric code
     *
     * @param  string|int|null  $numericCode
     */
    public static function getCurrencyByCode($numericCode): array
    {
        if (empty($numericCode)) {
            return [
                'code' => 'RUB',
                'name' => 'Российский рубль',
                'symbol' => '₽',
            ];
        }

        $code = (string) $numericCode;

        return self::CURRENCY_CODES[$code] ?? [
            'code' => 'RUB',
            'name' => 'Российский рубль',
            'symbol' => '₽',
        ];
    }

    /**
     * Get currency name by ISO numeric code
     *
     * @param  string|int|null  $numericCode
     */
    public static function getCurrencyName($numericCode): string
    {
        return self::getCurrencyByCode($numericCode)['name'];
    }

    /**
     * Get currency code (3-letter) by ISO numeric code
     *
     * @param  string|int|null  $numericCode
     */
    public static function getCurrencyCode($numericCode): string
    {
        return self::getCurrencyByCode($numericCode)['code'];
    }

    /**
     * Get currency symbol by ISO numeric code
     *
     * @param  string|int|null  $numericCode
     */
    public static function getCurrencySymbol($numericCode): string
    {
        return self::getCurrencyByCode($numericCode)['symbol'];
    }

    /**
     * Format price with currency
     *
     * @param  float|int|string|null  $amount
     * @param  string|int|null  $currencyCode  ISO numeric code
     * @param  bool  $withSymbol  Include currency symbol
     */
    public static function formatPrice($amount, $currencyCode = null, bool $withSymbol = true): string
    {
        if ($amount === null) {
            return 'N/A';
        }

        $amount = (float) $amount;
        $currency = self::getCurrencyByCode($currencyCode);

        $formatted = number_format($amount, 2, '.', ' ');

        if ($withSymbol) {
            return $formatted.' '.$currency['symbol'];
        }

        return $formatted.' '.$currency['code'];
    }

    /**
     * Convert price from kopecks to currency units
     */
    public static function fromKopecks(?int $kopecks): float
    {
        if ($kopecks === null) {
            return 0;
        }

        return $kopecks / 100;
    }
}
