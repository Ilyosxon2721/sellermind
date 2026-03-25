<?php

declare(strict_types=1);

namespace Database\Factories\Kpi;

use App\Models\Company;
use App\Models\Finance\Employee;
use App\Models\Kpi\BonusScale;
use App\Models\Kpi\KpiPlan;
use App\Models\Kpi\SalesSphere;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Фабрика для модели KpiPlan
 *
 * @extends Factory<KpiPlan>
 */
final class KpiPlanFactory extends Factory
{
    protected $model = KpiPlan::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'employee_id' => Employee::factory(),
            'kpi_sales_sphere_id' => SalesSphere::factory(),
            'kpi_bonus_scale_id' => BonusScale::factory(),
            'period_year' => now()->year,
            'period_month' => now()->month,
            'target_revenue' => fake()->numberBetween(1000000, 50000000),
            'target_margin' => fake()->numberBetween(100000, 5000000),
            'target_orders' => fake()->numberBetween(10, 500),
            'weight_revenue' => 40,
            'weight_margin' => 30,
            'weight_orders' => 30,
            'actual_revenue' => 0,
            'actual_margin' => 0,
            'actual_orders' => 0,
            'achievement_percent' => 0,
            'bonus_amount' => 0,
            'status' => KpiPlan::STATUS_ACTIVE,
        ];
    }

    /**
     * План со статусом "рассчитан"
     */
    public function calculated(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => KpiPlan::STATUS_CALCULATED,
            'calculated_at' => now(),
        ]);
    }

    /**
     * План со статусом "утверждён"
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => KpiPlan::STATUS_APPROVED,
            'calculated_at' => now(),
            'approved_at' => now(),
            'approved_by' => \App\Models\User::factory(),
        ]);
    }

    /**
     * План со статусом "отменён"
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => KpiPlan::STATUS_CANCELLED,
        ]);
    }

    /**
     * Установить фактические данные
     */
    public function withActuals(float $revenue, float $margin, int $orders): static
    {
        return $this->state(fn (array $attributes) => [
            'actual_revenue' => $revenue,
            'actual_margin' => $margin,
            'actual_orders' => $orders,
        ]);
    }

    /**
     * Установить целевые данные
     */
    public function withTargets(float $revenue, float $margin, int $orders): static
    {
        return $this->state(fn (array $attributes) => [
            'target_revenue' => $revenue,
            'target_margin' => $margin,
            'target_orders' => $orders,
        ]);
    }

    /**
     * Установить веса
     */
    public function withWeights(int $revenue, int $margin, int $orders): static
    {
        return $this->state(fn (array $attributes) => [
            'weight_revenue' => $revenue,
            'weight_margin' => $margin,
            'weight_orders' => $orders,
        ]);
    }

    /**
     * Установить период
     */
    public function forPeriod(int $year, int $month): static
    {
        return $this->state(fn (array $attributes) => [
            'period_year' => $year,
            'period_month' => $month,
        ]);
    }
}
