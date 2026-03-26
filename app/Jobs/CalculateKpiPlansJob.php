<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\Kpi\KpiCalculationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Фоновая задача расчёта KPI планов для компании за период
 */
final class CalculateKpiPlansJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Количество попыток выполнения задачи
     */
    public int $tries = 3;

    /**
     * Задержка между повторными попытками (в секундах)
     */
    public int $backoff = 60;

    public function __construct(
        private readonly int $companyId,
        private readonly int $year,
        private readonly int $month,
    ) {}

    /**
     * Выполнить расчёт KPI планов
     */
    public function handle(KpiCalculationService $calculationService): void
    {
        Log::info("KPI расчёт: начало для компании #{$this->companyId}", [
            'company_id' => $this->companyId,
            'year' => $this->year,
            'month' => $this->month,
        ]);

        $plans = $calculationService->calculatePeriod(
            $this->companyId,
            $this->year,
            $this->month,
        );

        Log::info("KPI расчёт: завершено для компании #{$this->companyId}", [
            'company_id' => $this->companyId,
            'plans_count' => $plans->count(),
        ]);
    }

    /**
     * Обработка ошибки после исчерпания попыток
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("KPI расчёт: финальная ошибка для компании #{$this->companyId}", [
            'company_id' => $this->companyId,
            'year' => $this->year,
            'month' => $this->month,
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }

    /**
     * Теги для Horizon
     *
     * @return array<int, string>
     */
    public function tags(): array
    {
        return [
            'kpi-calculation',
            "company:{$this->companyId}",
            "period:{$this->year}-{$this->month}",
        ];
    }
}
