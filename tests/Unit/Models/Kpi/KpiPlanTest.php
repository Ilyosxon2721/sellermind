<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Kpi;

use App\Models\Company;
use App\Models\Finance\Employee;
use App\Models\Kpi\BonusScale;
use App\Models\Kpi\BonusScaleTier;
use App\Models\Kpi\KpiPlan;
use App\Models\Kpi\SalesSphere;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class KpiPlanTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private Employee $employee;

    private SalesSphere $sphere;

    private BonusScale $scale;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->employee = Employee::factory()->create(['company_id' => $this->company->id]);
        $this->sphere = SalesSphere::factory()->create(['company_id' => $this->company->id]);
        $this->scale = BonusScale::factory()->create(['company_id' => $this->company->id]);
    }

    /**
     * calculateAchievement возвращает взвешенный процент выполнения
     */
    public function test_calculate_achievement_returns_weighted_percent(): void
    {
        $plan = KpiPlan::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'kpi_sales_sphere_id' => $this->sphere->id,
            'kpi_bonus_scale_id' => $this->scale->id,
            'target_revenue' => 1000000,
            'target_margin' => 500000,
            'target_orders' => 100,
            'actual_revenue' => 800000,   // 80%
            'actual_margin' => 400000,    // 80%
            'actual_orders' => 60,        // 60%
            'weight_revenue' => 50,
            'weight_margin' => 30,
            'weight_orders' => 20,
        ]);

        $achievement = $plan->calculateAchievement();

        // (80*50 + 80*30 + 60*20) / 100 = (4000 + 2400 + 1200) / 100 = 76.0
        $this->assertEquals(76.0, $achievement);
    }

    /**
     * calculateBonus находит нужную ступень и считает бонус
     */
    public function test_calculate_bonus_uses_correct_tier(): void
    {
        // Ступень 80-99%: фиксированный бонус 200000
        BonusScaleTier::factory()->create([
            'kpi_bonus_scale_id' => $this->scale->id,
            'min_percent' => 80,
            'max_percent' => 100,
            'bonus_type' => BonusScaleTier::TYPE_FIXED,
            'bonus_value' => 200000,
        ]);

        // Ступень 100%+: 5% от оборота
        BonusScaleTier::factory()->create([
            'kpi_bonus_scale_id' => $this->scale->id,
            'min_percent' => 100,
            'max_percent' => null,
            'bonus_type' => BonusScaleTier::TYPE_PERCENT_REVENUE,
            'bonus_value' => 5.0,
        ]);

        // План с achievement 90% — попадает в первую ступень
        $plan = KpiPlan::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'kpi_sales_sphere_id' => $this->sphere->id,
            'kpi_bonus_scale_id' => $this->scale->id,
            'achievement_percent' => 90.0,
            'actual_revenue' => 1000000,
            'actual_margin' => 400000,
        ]);

        $bonus = $plan->calculateBonus();

        $this->assertEquals(200000.0, $bonus);

        // План с achievement 110% — попадает во вторую ступень
        $plan2 = KpiPlan::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'kpi_sales_sphere_id' => $this->sphere->id,
            'kpi_bonus_scale_id' => $this->scale->id,
            'achievement_percent' => 110.0,
            'actual_revenue' => 2000000,
            'actual_margin' => 800000,
            'period_month' => now()->month === 12 ? 11 : now()->month + 1,
        ]);

        $bonus2 = $plan2->calculateBonus();

        // 2000000 * 5 / 100 = 100000
        $this->assertEquals(100000.0, $bonus2);
    }

    /**
     * Атрибут period_label возвращает русское название месяца и год
     */
    public function test_period_label_attribute_russian_month(): void
    {
        $plan = KpiPlan::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'kpi_sales_sphere_id' => $this->sphere->id,
            'kpi_bonus_scale_id' => $this->scale->id,
            'period_year' => 2026,
            'period_month' => 3,
        ]);

        $this->assertEquals('Март 2026', $plan->period_label);

        $plan2 = KpiPlan::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'kpi_sales_sphere_id' => $this->sphere->id,
            'kpi_bonus_scale_id' => $this->scale->id,
            'period_year' => 2025,
            'period_month' => 12,
        ]);

        $this->assertEquals('Декабрь 2025', $plan2->period_label);
    }

    /**
     * Scope forPeriod фильтрует по году и месяцу
     */
    public function test_scope_for_period_filters_correctly(): void
    {
        // Январь 2026
        KpiPlan::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'kpi_sales_sphere_id' => $this->sphere->id,
            'kpi_bonus_scale_id' => $this->scale->id,
            'period_year' => 2026,
            'period_month' => 1,
        ]);

        // Февраль 2026
        KpiPlan::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'kpi_sales_sphere_id' => $this->sphere->id,
            'kpi_bonus_scale_id' => $this->scale->id,
            'period_year' => 2026,
            'period_month' => 2,
        ]);

        // Январь 2025
        KpiPlan::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'kpi_sales_sphere_id' => $this->sphere->id,
            'kpi_bonus_scale_id' => $this->scale->id,
            'period_year' => 2025,
            'period_month' => 1,
        ]);

        $plans = KpiPlan::forPeriod(2026, 1)->get();

        $this->assertCount(1, $plans);
        $this->assertEquals(2026, $plans->first()->period_year);
        $this->assertEquals(1, $plans->first()->period_month);
    }

    /**
     * Scope active исключает отменённые планы
     */
    public function test_scope_active_excludes_cancelled(): void
    {
        // Активный план
        KpiPlan::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'kpi_sales_sphere_id' => $this->sphere->id,
            'kpi_bonus_scale_id' => $this->scale->id,
            'status' => KpiPlan::STATUS_ACTIVE,
        ]);

        // Рассчитанный план
        KpiPlan::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'kpi_sales_sphere_id' => $this->sphere->id,
            'kpi_bonus_scale_id' => $this->scale->id,
            'status' => KpiPlan::STATUS_CALCULATED,
            'period_month' => now()->month === 12 ? 11 : now()->month + 1,
        ]);

        // Отменённый план
        KpiPlan::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'kpi_sales_sphere_id' => $this->sphere->id,
            'kpi_bonus_scale_id' => $this->scale->id,
            'status' => KpiPlan::STATUS_CANCELLED,
            'period_year' => now()->year - 1,
        ]);

        // Утверждённый план
        KpiPlan::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'kpi_sales_sphere_id' => $this->sphere->id,
            'kpi_bonus_scale_id' => $this->scale->id,
            'status' => KpiPlan::STATUS_APPROVED,
            'period_year' => now()->year - 1,
            'period_month' => 6,
        ]);

        $activePlans = KpiPlan::active()->get();

        // active() включает STATUS_ACTIVE и STATUS_CALCULATED
        $this->assertCount(2, $activePlans);

        $statuses = $activePlans->pluck('status')->toArray();
        $this->assertContains(KpiPlan::STATUS_ACTIVE, $statuses);
        $this->assertContains(KpiPlan::STATUS_CALCULATED, $statuses);
        $this->assertNotContains(KpiPlan::STATUS_CANCELLED, $statuses);
        $this->assertNotContains(KpiPlan::STATUS_APPROVED, $statuses);
    }
}
