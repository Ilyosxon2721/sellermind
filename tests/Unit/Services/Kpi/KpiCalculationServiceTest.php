<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Kpi;

use App\Models\Company;
use App\Models\Finance\Employee;
use App\Models\Kpi\BonusScale;
use App\Models\Kpi\BonusScaleTier;
use App\Models\Kpi\KpiPlan;
use App\Models\Kpi\SalesSphere;
use App\Services\Kpi\KpiCalculationService;
use App\Services\Kpi\KpiMarginCalculator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class KpiCalculationServiceTest extends TestCase
{
    use RefreshDatabase;

    private KpiCalculationService $service;

    private Company $company;

    private Employee $employee;

    private SalesSphere $sphere;

    private BonusScale $scale;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new KpiCalculationService(
            new KpiMarginCalculator(),
        );

        $this->company = Company::factory()->create();
        $this->employee = Employee::factory()->create(['company_id' => $this->company->id]);
        $this->sphere = SalesSphere::factory()->create(['company_id' => $this->company->id]);
        $this->scale = BonusScale::factory()->create(['company_id' => $this->company->id]);
    }

    /**
     * Все метрики выполнены на 100% при равных весах — результат 100%
     */
    public function test_calculate_achievement_with_equal_weights(): void
    {
        $plan = KpiPlan::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'kpi_sales_sphere_id' => $this->sphere->id,
            'kpi_bonus_scale_id' => $this->scale->id,
            'target_revenue' => 1000000,
            'target_margin' => 500000,
            'target_orders' => 100,
            'actual_revenue' => 1000000,
            'actual_margin' => 500000,
            'actual_orders' => 100,
            'weight_revenue' => 34,
            'weight_margin' => 33,
            'weight_orders' => 33,
        ]);

        $achievement = $plan->calculateAchievement();

        $this->assertEquals(100.0, $achievement);
    }

    /**
     * Частичное выполнение — achievement пропорционален с учётом весов
     */
    public function test_calculate_achievement_with_partial_completion(): void
    {
        $plan = KpiPlan::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'kpi_sales_sphere_id' => $this->sphere->id,
            'kpi_bonus_scale_id' => $this->scale->id,
            'target_revenue' => 1000000,
            'target_margin' => 500000,
            'target_orders' => 100,
            'actual_revenue' => 500000,   // 50%
            'actual_margin' => 250000,    // 50%
            'actual_orders' => 50,        // 50%
            'weight_revenue' => 40,
            'weight_margin' => 30,
            'weight_orders' => 30,
        ]);

        $achievement = $plan->calculateAchievement();

        // (50*40 + 50*30 + 50*30) / 100 = 50.0
        $this->assertEquals(50.0, $achievement);
    }

    /**
     * Каждая метрика ограничена 200%, итоговый achievement тоже не более 200%
     */
    public function test_calculate_achievement_capped_at_200(): void
    {
        $plan = KpiPlan::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'kpi_sales_sphere_id' => $this->sphere->id,
            'kpi_bonus_scale_id' => $this->scale->id,
            'target_revenue' => 1000000,
            'target_margin' => 500000,
            'target_orders' => 100,
            'actual_revenue' => 5000000,  // 500% -> cap 200%
            'actual_margin' => 2500000,   // 500% -> cap 200%
            'actual_orders' => 500,       // 500% -> cap 200%
            'weight_revenue' => 40,
            'weight_margin' => 30,
            'weight_orders' => 30,
        ]);

        $achievement = $plan->calculateAchievement();

        // (200*40 + 200*30 + 200*30) / 100 = 200.0
        $this->assertEquals(200.0, $achievement);
    }

    /**
     * При нулевых целях деление на ноль не происходит — возвращается 0
     */
    public function test_calculate_achievement_with_zero_targets(): void
    {
        $plan = KpiPlan::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'kpi_sales_sphere_id' => $this->sphere->id,
            'kpi_bonus_scale_id' => $this->scale->id,
            'target_revenue' => 0,
            'target_margin' => 0,
            'target_orders' => 0,
            'actual_revenue' => 1000000,
            'actual_margin' => 500000,
            'actual_orders' => 100,
            'weight_revenue' => 40,
            'weight_margin' => 30,
            'weight_orders' => 30,
        ]);

        $achievement = $plan->calculateAchievement();

        // Все процентные значения = 0, итого 0
        $this->assertEquals(0.0, $achievement);
    }

    /**
     * Бонус с фиксированной суммой — возвращается bonus_value ступени
     */
    public function test_calculate_bonus_with_fixed_tier(): void
    {
        $tier = BonusScaleTier::factory()->create([
            'kpi_bonus_scale_id' => $this->scale->id,
            'min_percent' => 80,
            'max_percent' => null,
            'bonus_type' => BonusScaleTier::TYPE_FIXED,
            'bonus_value' => 500000,
        ]);

        $plan = KpiPlan::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'kpi_sales_sphere_id' => $this->sphere->id,
            'kpi_bonus_scale_id' => $this->scale->id,
            'achievement_percent' => 95.0,
            'actual_revenue' => 2000000,
            'actual_margin' => 800000,
        ]);

        $bonus = $plan->calculateBonus();

        $this->assertEquals(500000.0, $bonus);
    }

    /**
     * Бонус как процент от оборота — bonus_value% * actual_revenue
     */
    public function test_calculate_bonus_with_percent_revenue(): void
    {
        $tier = BonusScaleTier::factory()->create([
            'kpi_bonus_scale_id' => $this->scale->id,
            'min_percent' => 80,
            'max_percent' => null,
            'bonus_type' => BonusScaleTier::TYPE_PERCENT_REVENUE,
            'bonus_value' => 5.0, // 5%
        ]);

        $plan = KpiPlan::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'kpi_sales_sphere_id' => $this->sphere->id,
            'kpi_bonus_scale_id' => $this->scale->id,
            'achievement_percent' => 100.0,
            'actual_revenue' => 2000000,
            'actual_margin' => 800000,
        ]);

        $bonus = $plan->calculateBonus();

        // 2000000 * 5 / 100 = 100000
        $this->assertEquals(100000.0, $bonus);
    }

    /**
     * Бонус как процент от маржи — bonus_value% * actual_margin
     */
    public function test_calculate_bonus_with_percent_margin(): void
    {
        $tier = BonusScaleTier::factory()->create([
            'kpi_bonus_scale_id' => $this->scale->id,
            'min_percent' => 80,
            'max_percent' => null,
            'bonus_type' => BonusScaleTier::TYPE_PERCENT_MARGIN,
            'bonus_value' => 10.0, // 10%
        ]);

        $plan = KpiPlan::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'kpi_sales_sphere_id' => $this->sphere->id,
            'kpi_bonus_scale_id' => $this->scale->id,
            'achievement_percent' => 100.0,
            'actual_revenue' => 2000000,
            'actual_margin' => 800000,
        ]);

        $bonus = $plan->calculateBonus();

        // 800000 * 10 / 100 = 80000
        $this->assertEquals(80000.0, $bonus);
    }

    /**
     * Если achievement ниже минимального порога — бонус 0
     */
    public function test_calculate_bonus_no_matching_tier(): void
    {
        BonusScaleTier::factory()->create([
            'kpi_bonus_scale_id' => $this->scale->id,
            'min_percent' => 80,
            'max_percent' => null,
            'bonus_type' => BonusScaleTier::TYPE_FIXED,
            'bonus_value' => 500000,
        ]);

        $plan = KpiPlan::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'kpi_sales_sphere_id' => $this->sphere->id,
            'kpi_bonus_scale_id' => $this->scale->id,
            'achievement_percent' => 50.0, // ниже минимума 80%
            'actual_revenue' => 500000,
            'actual_margin' => 200000,
        ]);

        $bonus = $plan->calculateBonus();

        $this->assertEquals(0.0, $bonus);
    }

    /**
     * Рейтинг сотрудников отсортирован по убыванию avg_achievement
     */
    public function test_get_employee_ranking_sorted_desc(): void
    {
        $employee2 = Employee::factory()->create(['company_id' => $this->company->id]);
        $employee3 = Employee::factory()->create(['company_id' => $this->company->id]);

        $year = now()->year;
        $month = now()->month;

        // Сотрудник 1: achievement = 60%
        KpiPlan::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'kpi_sales_sphere_id' => $this->sphere->id,
            'kpi_bonus_scale_id' => $this->scale->id,
            'period_year' => $year,
            'period_month' => $month,
            'achievement_percent' => 60.0,
            'bonus_amount' => 100000,
            'status' => KpiPlan::STATUS_CALCULATED,
        ]);

        // Сотрудник 2: achievement = 120%
        KpiPlan::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $employee2->id,
            'kpi_sales_sphere_id' => $this->sphere->id,
            'kpi_bonus_scale_id' => $this->scale->id,
            'period_year' => $year,
            'period_month' => $month,
            'achievement_percent' => 120.0,
            'bonus_amount' => 300000,
            'status' => KpiPlan::STATUS_CALCULATED,
        ]);

        // Сотрудник 3: achievement = 90%
        KpiPlan::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $employee3->id,
            'kpi_sales_sphere_id' => $this->sphere->id,
            'kpi_bonus_scale_id' => $this->scale->id,
            'period_year' => $year,
            'period_month' => $month,
            'achievement_percent' => 90.0,
            'bonus_amount' => 200000,
            'status' => KpiPlan::STATUS_CALCULATED,
        ]);

        $ranking = $this->service->getEmployeeRanking($this->company->id, $year, $month);

        $this->assertCount(3, $ranking);
        // Первый — employee2 (120%), потом employee3 (90%), потом employee1 (60%)
        $this->assertEquals($employee2->id, $ranking[0]['employee_id']);
        $this->assertEquals($employee3->id, $ranking[1]['employee_id']);
        $this->assertEquals($this->employee->id, $ranking[2]['employee_id']);
        // Ранги присвоены корректно
        $this->assertEquals(1, $ranking[0]['rank']);
        $this->assertEquals(2, $ranking[1]['rank']);
        $this->assertEquals(3, $ranking[2]['rank']);
    }

    /**
     * Прогноз в середине месяца: при progressRatio > 0.1 прогноз рассчитывается
     */
    public function test_get_forecast_mid_month(): void
    {
        $year = now()->year;
        $month = now()->month;

        // Замораживаем время на 15 число текущего месяца
        Carbon::setTestNow(Carbon::create($year, $month, 15, 12, 0, 0));

        $plan = KpiPlan::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'kpi_sales_sphere_id' => $this->sphere->id,
            'kpi_bonus_scale_id' => $this->scale->id,
            'period_year' => $year,
            'period_month' => $month,
            'target_revenue' => 10000000,
            'target_margin' => 2000000,
            'target_orders' => 100,
            'actual_revenue' => 5000000,
            'actual_margin' => 1000000,
            'actual_orders' => 50,
            'weight_revenue' => 40,
            'weight_margin' => 30,
            'weight_orders' => 30,
            'status' => KpiPlan::STATUS_ACTIVE,
        ]);

        $forecast = $this->service->getForecast($this->company->id, $year, $month);

        $this->assertEquals(15, $forecast['days_elapsed']);
        $this->assertGreaterThan(0, $forecast['forecast_revenue']);
        $this->assertArrayHasKey('on_track_count', $forecast);
        $this->assertArrayHasKey('at_risk_count', $forecast);
        $this->assertArrayHasKey('plans', $forecast);
        $this->assertNotEmpty($forecast['plans']);

        // Прогнозная выручка должна быть больше текущей (экстраполяция)
        $this->assertGreaterThanOrEqual($plan->actual_revenue, $forecast['forecast_revenue']);

        Carbon::setTestNow(); // Сброс замороженного времени
    }

    /**
     * Прогноз в начале месяца (< 10% дней): forecast_achievement = 0
     */
    public function test_get_forecast_early_month(): void
    {
        $year = now()->year;
        $month = now()->month;

        // Замораживаем время на 1 число (1/30 ~ 3.3%, что < 10%)
        Carbon::setTestNow(Carbon::create($year, $month, 1, 12, 0, 0));

        KpiPlan::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'kpi_sales_sphere_id' => $this->sphere->id,
            'kpi_bonus_scale_id' => $this->scale->id,
            'period_year' => $year,
            'period_month' => $month,
            'target_revenue' => 10000000,
            'target_margin' => 2000000,
            'target_orders' => 100,
            'actual_revenue' => 100000,
            'actual_margin' => 20000,
            'actual_orders' => 2,
            'weight_revenue' => 40,
            'weight_margin' => 30,
            'weight_orders' => 30,
            'status' => KpiPlan::STATUS_ACTIVE,
        ]);

        $forecast = $this->service->getForecast($this->company->id, $year, $month);

        // При progressRatio <= 0.1 прогноз achievement = 0
        $this->assertEquals(0.0, $forecast['forecast_achievement']);
        // Прогнозная выручка тоже 0
        $this->assertEquals(0.0, $forecast['forecast_revenue']);

        Carbon::setTestNow();
    }
}
