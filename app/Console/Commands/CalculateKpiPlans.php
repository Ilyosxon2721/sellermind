<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\CalculateKpiPlansJob;
use App\Models\Company;
use App\Services\Kpi\KpiCalculationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Команда расчёта KPI планов по расписанию
 */
final class CalculateKpiPlans extends Command
{
    protected $signature = 'kpi:calculate
        {--company= : ID компании (если не указан — все компании)}
        {--year= : Год периода (по умолчанию — текущий)}
        {--month= : Месяц периода (по умолчанию — текущий)}
        {--queue : Отправить задачи в очередь вместо синхронного выполнения}';

    protected $description = 'Расчёт KPI планов для компаний за указанный период';

    public function __construct(
        private readonly KpiCalculationService $calculationService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $year = (int) ($this->option('year') ?: now()->year);
        $month = (int) ($this->option('month') ?: now()->month);
        $companyId = $this->option('company') ? (int) $this->option('company') : null;
        $useQueue = (bool) $this->option('queue');

        $this->info("Расчёт KPI планов за {$month}/{$year}");

        $companyIds = $this->resolveCompanyIds($companyId);

        if ($companyIds->isEmpty()) {
            $this->warn('Компании для расчёта не найдены.');

            return self::SUCCESS;
        }

        $this->info("Найдено компаний: {$companyIds->count()}");

        $successCount = 0;
        $errorCount = 0;

        foreach ($companyIds as $id) {
            if ($useQueue) {
                CalculateKpiPlansJob::dispatch($id, $year, $month);
                $this->info("  Компания #{$id}: задача отправлена в очередь");
                $successCount++;

                continue;
            }

            try {
                $plans = $this->calculationService->calculatePeriod($id, $year, $month);
                $this->info("  Компания #{$id}: рассчитано планов — {$plans->count()}");
                $successCount++;
            } catch (\Throwable $e) {
                $errorCount++;
                $this->error("  Компания #{$id}: ошибка — {$e->getMessage()}");
                Log::error("KPI расчёт: ошибка для компании #{$id}", [
                    'company_id' => $id,
                    'year' => $year,
                    'month' => $month,
                    'exception' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        $this->newLine();
        $this->info("Готово. Успешно: {$successCount}, Ошибки: {$errorCount}");

        return $errorCount > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Получить список ID компаний для расчёта
     *
     * @return \Illuminate\Support\Collection<int, int>
     */
    private function resolveCompanyIds(?int $companyId): \Illuminate\Support\Collection
    {
        if ($companyId !== null) {
            $exists = Company::where('id', $companyId)->exists();

            if (! $exists) {
                $this->error("Компания #{$companyId} не найдена.");

                return collect();
            }

            return collect([$companyId]);
        }

        return Company::pluck('id');
    }
}
