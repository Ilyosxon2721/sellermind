<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Company;
use App\Services\BusinessAnalyticsService;
use App\Services\CurrencyConversionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Пересчёт бизнес-аналитики (ABC, ABCXYZ) для компании.
 * Запускается по расписанию, результаты кэшируются.
 */
final class CalculateBusinessAnalyticsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 300;

    public function __construct(
        private readonly int $companyId,
        private readonly string $period = '30days',
    ) {}

    public function handle(BusinessAnalyticsService $service, CurrencyConversionService $currencyService): void
    {
        $company = Company::find($this->companyId);
        if (!$company) {
            return;
        }

        $currencyService->forCompany($company);

        try {
            // ABC-анализ
            $abcData = $service->getAbcAnalysis($this->companyId, $this->period);
            Cache::put(
                "business_abc_{$this->companyId}_{$this->period}",
                $abcData,
                now()->addHours(6)
            );

            Log::info("BusinessAnalytics: ABC рассчитан для компании {$this->companyId}", [
                'total_products' => $abcData['summary']['total_products'],
                'period' => $this->period,
            ]);

            // ABCXYZ-анализ
            $abcxyzData = $service->getAbcxyzAnalysis($this->companyId, '90days');
            Cache::put(
                "business_abcxyz_{$this->companyId}_90days",
                $abcxyzData,
                now()->addHours(6)
            );

            Log::info("BusinessAnalytics: ABCXYZ рассчитан для компании {$this->companyId}", [
                'total_customers' => $abcxyzData['summary']['total_customers'],
            ]);
        } catch (\Exception $e) {
            Log::error("BusinessAnalytics: Ошибка расчёта для компании {$this->companyId}: " . $e->getMessage());
            throw $e;
        }
    }
}
