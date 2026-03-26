<?php

declare(strict_types=1);

namespace App\Listeners\Kpi;

use App\Events\Kpi\KpiPlanApproved;
use App\Notifications\Kpi\KpiPlanApprovedNotification;
use Illuminate\Support\Facades\Log;

/**
 * Слушатель: KPI-план утверждён — уведомить сотрудника
 */
final class SendKpiApprovedNotification
{
    public function handle(KpiPlanApproved $event): void
    {
        try {
            $plan = $event->plan;
            $plan->load(['employee.user', 'salesSphere']);

            // Уведомить пользователя сотрудника (если привязан)
            $user = $plan->employee?->user;
            if (! $user) {
                return;
            }

            $user->notify(new KpiPlanApprovedNotification(
                $plan->period_label,
                $plan->salesSphere?->name ?? 'Неизвестная сфера',
                $plan->achievement_percent,
                $plan->bonus_amount,
                $event->approvedBy->name ?? $event->approvedBy->email,
            ));
        } catch (\Exception $e) {
            Log::error('KPI approved notification failed', ['error' => $e->getMessage()]);
        }
    }
}
