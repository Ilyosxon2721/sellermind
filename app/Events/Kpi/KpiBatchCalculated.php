<?php

declare(strict_types=1);

namespace App\Events\Kpi;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Событие: массовый расчёт KPI завершён для компании за период
 */
final class KpiBatchCalculated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $companyId,
        public readonly int $year,
        public readonly int $month,
        public readonly int $plansCount,
        public readonly int $errorCount = 0,
    ) {}
}
