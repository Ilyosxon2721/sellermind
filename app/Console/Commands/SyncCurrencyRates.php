<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Finance\FinanceSettings;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Синхронизация курсов валют из ЦБ Узбекистана (CBU)
 *
 * API: https://cbu.uz/oz/arkhiv-kursov-valyut/json/
 * Курсы обновляются ежедневно.
 */
final class SyncCurrencyRates extends Command
{
    protected $signature = 'finance:sync-currency-rates {--force : Обновить даже если уже обновлялись сегодня}';

    protected $description = 'Синхронизировать курсы валют из ЦБ Узбекистана';

    protected const CBU_API_URL = 'https://cbu.uz/oz/arkhiv-kursov-valyut/json/';

    /**
     * Маппинг кодов CBU → наши поля в FinanceSettings
     */
    protected const CURRENCY_MAP = [
        'USD' => 'usd_rate',
        'RUB' => 'rub_rate',
        'EUR' => 'eur_rate',
    ];

    public function handle(): int
    {
        $this->info('Получение курсов валют из ЦБ Узбекистана...');

        $rates = $this->fetchRatesFromCbu();

        if (empty($rates)) {
            $this->error('Не удалось получить курсы валют');

            return self::FAILURE;
        }

        $this->info('Полученные курсы:');
        foreach ($rates as $currency => $rate) {
            $this->line("  {$currency}: {$rate} UZS");
        }

        // Обновляем FinanceSettings для всех компаний
        $updated = $this->updateFinanceSettings($rates);
        $this->info("Обновлено настроек: {$updated}");

        // Обновляем кэш CurrencyConversionService
        $this->updateCache($rates);

        Log::info('Курсы валют обновлены из CBU', $rates);

        return self::SUCCESS;
    }

    /**
     * Получить курсы из API ЦБ Узбекистана
     */
    protected function fetchRatesFromCbu(): array
    {
        try {
            $response = Http::timeout(10)
                ->retry(3, 1000)
                ->get(self::CBU_API_URL);

            if (! $response->successful()) {
                Log::error('CBU API вернул ошибку', ['status' => $response->status()]);

                return [];
            }

            $data = $response->json();
            if (! is_array($data)) {
                return [];
            }

            $rates = [];
            foreach ($data as $item) {
                $code = $item['Ccy'] ?? null;
                $rate = (float) ($item['Rate'] ?? 0);
                $nominal = (int) ($item['Nominal'] ?? 1);

                if ($code && $rate > 0 && isset(self::CURRENCY_MAP[$code])) {
                    // CBU возвращает курс за Nominal единиц, нормализуем к 1 единице
                    $rates[$code] = round($rate / $nominal, 2);
                }
            }

            return $rates;
        } catch (\Exception $e) {
            Log::error('Ошибка при получении курсов из CBU', ['error' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * Обновить FinanceSettings для всех компаний
     */
    protected function updateFinanceSettings(array $rates): int
    {
        $count = 0;

        $settings = FinanceSettings::all();

        foreach ($settings as $setting) {
            $changed = false;

            foreach ($rates as $currency => $rate) {
                $field = self::CURRENCY_MAP[$currency] ?? null;
                if ($field && abs($setting->{$field} - $rate) > 0.01) {
                    $setting->{$field} = $rate;
                    $changed = true;
                }
            }

            if ($changed) {
                $setting->rates_updated_at = now();
                $setting->save();
                $count++;
            }
        }

        return $count;
    }

    /**
     * Обновить кэш для CurrencyConversionService
     */
    protected function updateCache(array $rates): void
    {
        foreach ($rates as $currency => $rate) {
            // Кэшируем как X_UZS (1 единица валюты = N UZS)
            Cache::put("exchange_rate:{$currency}_UZS", $rate, 86400);
            // И обратный курс
            if ($rate > 0) {
                Cache::put("exchange_rate:UZS_{$currency}", 1.0 / $rate, 86400);
            }
        }
    }
}
