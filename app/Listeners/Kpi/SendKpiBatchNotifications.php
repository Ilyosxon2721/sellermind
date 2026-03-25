<?php

declare(strict_types=1);

namespace App\Listeners\Kpi;

use App\Events\Kpi\KpiBatchCalculated;
use App\Models\Company;
use App\Models\Kpi\KpiPlan;
use App\Models\User;
use App\Notifications\Kpi\KpiAtRiskNotification;
use App\Notifications\Kpi\KpiCalculationCompletedNotification;
use Illuminate\Support\Facades\Log;

/**
 * Слушатель: массовый расчёт KPI завершён
 *
 * Отправляет уведомления руководителям и алерты по сотрудникам под угрозой
 */
final class SendKpiBatchNotifications
{
    public function handle(KpiBatchCalculated $event): void
    {
        try {
            $company = Company::find($event->companyId);
            if (! $company) {
                return;
            }

            // Средний KPI за период
            $avgAchievement = KpiPlan::byCompany($event->companyId)
                ->forPeriod($event->year, $event->month)
                ->where('status', '!=', KpiPlan::STATUS_CANCELLED)
                ->avg('achievement_percent') ?? 0;

            // Уведомить владельца компании о завершении расчёта
            $owner = User::where('company_id', $event->companyId)
                ->where('is_owner', true)
                ->first();

            if ($owner) {
                $owner->notify(new KpiCalculationCompletedNotification(
                    $company->name,
                    $event->year,
                    $event->month,
                    $event->plansCount,
                    round($avgAchievement, 1),
                    $event->errorCount,
                ));
            }

            // Алерты: сотрудники под угрозой (KPI < 50%)
            $this->sendAtRiskAlerts($event->companyId, $event->year, $event->month, $owner);

        } catch (\Exception $e) {
            Log::error('KPI batch notification failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Отправить алерты по сотрудникам с KPI < 50%
     */
    private function sendAtRiskAlerts(int $companyId, int $year, int $month, ?User $manager): void
    {
        if (! $manager) {
            return;
        }

        $atRiskPlans = KpiPlan::byCompany($companyId)
            ->forPeriod($year, $month)
            ->where('status', KpiPlan::STATUS_CALCULATED)
            ->where('achievement_percent', '<', 50)
            ->with(['employee', 'salesSphere'])
            ->get();

        foreach ($atRiskPlans as $plan) {
            $employeeName = $plan->employee?->full_name ?? 'Сотрудник #' . $plan->employee_id;
            $sphereName = $plan->salesSphere?->name ?? 'Неизвестная сфера';

            $manager->notify(new KpiAtRiskNotification(
                $employeeName,
                $sphereName,
                $plan->achievement_percent,
                $plan->achievement_percent, // текущий = прогноз (на момент расчёта)
                $plan->period_label,
            ));
        }
    }
}
