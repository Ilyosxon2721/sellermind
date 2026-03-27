<?php

declare(strict_types=1);

namespace App\Events\Kpi;

use App\Models\Kpi\KpiPlan;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Событие: один KPI-план рассчитан
 */
final class KpiPlanCalculated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly KpiPlan $plan,
    ) {}
}
