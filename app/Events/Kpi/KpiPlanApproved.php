<?php

declare(strict_types=1);

namespace App\Events\Kpi;

use App\Models\Kpi\KpiPlan;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Событие: KPI-план утверждён руководителем
 */
final class KpiPlanApproved
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly KpiPlan $plan,
        public readonly User $approvedBy,
    ) {}
}
