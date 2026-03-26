<?php

declare(strict_types=1);

namespace Tests\Feature\Kpi;

use App\Models\Company;
use App\Models\Finance\Employee;
use App\Models\Kpi\BonusScale;
use App\Models\Kpi\BonusScaleTier;
use App\Models\Kpi\KpiPlan;
use App\Models\Kpi\SalesSphere;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class KpiPlanApiTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private User $user;

    private Employee $employee;

    private SalesSphere $sphere;

    private BonusScale $scale;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->user = User::factory()->create(['company_id' => $this->company->id]);
        $this->employee = Employee::factory()->create(['company_id' => $this->company->id]);
        $this->sphere = SalesSphere::factory()->create(['company_id' => $this->company->id]);
        $this->scale = BonusScale::factory()->create(['company_id' => $this->company->id]);
    }

    /**
     * Создание плана требует аутентификации
     */
    public function test_store_plan_requires_authentication(): void
    {
        $response = $this->postJson('/api/kpi/plans', [
            'employee_id' => $this->employee->id,
            'kpi_sales_sphere_id' => $this->sphere->id,
            'kpi_bonus_scale_id' => $this->scale->id,
            'period_year' => 2026,
            'period_month' => 3,
            'target_revenue' => 5000000,
            'target_margin' => 1000000,
            'target_orders' => 100,
            'weight_revenue' => 40,
            'weight_margin' => 30,
            'weight_orders' => 30,
        ]);

        $response->assertUnauthorized();
    }

    /**
     * Сумма весов должна быть ровно 100
     */
    public function test_store_plan_validates_weight_sum_100(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/kpi/plans', [
                'employee_id' => $this->employee->id,
                'kpi_sales_sphere_id' => $this->sphere->id,
                'kpi_bonus_scale_id' => $this->scale->id,
                'period_year' => 2026,
                'period_month' => 3,
                'target_revenue' => 5000000,
                'target_margin' => 1000000,
                'target_orders' => 100,
                'weight_revenue' => 50,
                'weight_margin' => 30,
                'weight_orders' => 30, // сумма = 110, не 100
            ]);

        $response->assertStatus(422);
    }

    /**
     * Нельзя создать план с сотрудником другой компании
     */
    public function test_store_plan_rejects_other_company_employee(): void
    {
        $otherCompany = Company::factory()->create();
        $otherEmployee = Employee::factory()->create(['company_id' => $otherCompany->id]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/kpi/plans', [
                'employee_id' => $otherEmployee->id,
                'kpi_sales_sphere_id' => $this->sphere->id,
                'kpi_bonus_scale_id' => $this->scale->id,
                'period_year' => 2026,
                'period_month' => 3,
                'target_revenue' => 5000000,
                'target_margin' => 1000000,
                'target_orders' => 100,
                'weight_revenue' => 40,
                'weight_margin' => 30,
                'weight_orders' => 30,
            ]);

        $response->assertStatus(422);
    }

    /**
     * Нельзя создать дублирующий план (тот же сотрудник, сфера, период)
     */
    public function test_store_plan_prevents_duplicate_period(): void
    {
        // Создаём первый план
        KpiPlan::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'kpi_sales_sphere_id' => $this->sphere->id,
            'kpi_bonus_scale_id' => $this->scale->id,
            'period_year' => 2026,
            'period_month' => 3,
        ]);

        // Пытаемся создать дубль
        $response = $this->actingAs($this->user)
            ->postJson('/api/kpi/plans', [
                'employee_id' => $this->employee->id,
                'kpi_sales_sphere_id' => $this->sphere->id,
                'kpi_bonus_scale_id' => $this->scale->id,
                'period_year' => 2026,
                'period_month' => 3,
                'target_revenue' => 5000000,
                'target_margin' => 1000000,
                'target_orders' => 100,
                'weight_revenue' => 40,
                'weight_margin' => 30,
                'weight_orders' => 30,
            ]);

        $response->assertStatus(422);
        $response->assertJsonPath('errors.0.code', 'duplicate');
    }

    /**
     * Нельзя редактировать утверждённый план
     */
    public function test_update_plan_blocks_approved(): void
    {
        $plan = KpiPlan::factory()->approved()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'kpi_sales_sphere_id' => $this->sphere->id,
            'kpi_bonus_scale_id' => $this->scale->id,
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/kpi/plans/{$plan->id}", [
                'target_revenue' => 9999999,
                'weight_revenue' => 40,
                'weight_margin' => 30,
                'weight_orders' => 30,
            ]);

        $response->assertStatus(422);
        $response->assertJsonPath('errors.0.code', 'invalid_state');
    }

    /**
     * Нельзя удалить утверждённый план
     */
    public function test_delete_plan_blocks_approved(): void
    {
        $plan = KpiPlan::factory()->approved()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'kpi_sales_sphere_id' => $this->sphere->id,
            'kpi_bonus_scale_id' => $this->scale->id,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/kpi/plans/{$plan->id}");

        $response->assertStatus(422);
        $response->assertJsonPath('errors.0.code', 'invalid_state');

        // План не удалён из БД
        $this->assertDatabaseHas('kpi_plans', ['id' => $plan->id]);
    }

    /**
     * Утверждение плана устанавливает статус approved и approved_by
     */
    public function test_approve_plan_sets_status_and_user(): void
    {
        $plan = KpiPlan::factory()->calculated()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'kpi_sales_sphere_id' => $this->sphere->id,
            'kpi_bonus_scale_id' => $this->scale->id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/kpi/plans/{$plan->id}/approve");

        $response->assertOk();

        $plan->refresh();
        $this->assertEquals(KpiPlan::STATUS_APPROVED, $plan->status);
        $this->assertEquals($this->user->id, $plan->approved_by);
        $this->assertNotNull($plan->approved_at);
    }

    /**
     * Массовый расчёт за период обновляет планы
     */
    public function test_calculate_period_updates_plans(): void
    {
        $year = 2026;
        $month = 3;

        // Создаём план без привязки к МП (ручной ввод) с фактическими данными
        $plan = KpiPlan::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'kpi_sales_sphere_id' => $this->sphere->id,
            'kpi_bonus_scale_id' => $this->scale->id,
            'period_year' => $year,
            'period_month' => $month,
            'target_revenue' => 1000000,
            'target_margin' => 500000,
            'target_orders' => 100,
            'actual_revenue' => 800000,
            'actual_margin' => 400000,
            'actual_orders' => 80,
            'weight_revenue' => 40,
            'weight_margin' => 30,
            'weight_orders' => 30,
            'status' => KpiPlan::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($this->user)
            ->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class)
            ->postJson('/api/kpi/plans/calculate', [
                'year' => $year,
                'month' => $month,
            ]);

        $response->assertOk();
        $response->assertJsonPath('data.calculated', 1);

        $plan->refresh();
        $this->assertEquals(KpiPlan::STATUS_CALCULATED, $plan->status);
        $this->assertNotNull($plan->calculated_at);
        $this->assertGreaterThan(0, $plan->achievement_percent);
    }

    /**
     * Дашборд возвращает корректную структуру данных
     */
    public function test_dashboard_returns_correct_structure(): void
    {
        $year = now()->year;
        $month = now()->month;

        KpiPlan::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'kpi_sales_sphere_id' => $this->sphere->id,
            'kpi_bonus_scale_id' => $this->scale->id,
            'period_year' => $year,
            'period_month' => $month,
            'achievement_percent' => 85.0,
            'bonus_amount' => 300000,
            'actual_revenue' => 8000000,
            'target_revenue' => 10000000,
            'status' => KpiPlan::STATUS_CALCULATED,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/kpi/dashboard?year={$year}&month={$month}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'employees',
                'avg_achievement',
                'total_bonus',
                'total_revenue',
                'target_revenue',
                'plans',
            ],
        ]);

        $data = $response->json('data');
        $this->assertEquals(1, $data['employees']);
        $this->assertEquals(85.0, $data['avg_achievement']);
        $this->assertEquals(300000, $data['total_bonus']);
    }

    /**
     * Рейтинг возвращает отсортированный список сотрудников
     */
    public function test_ranking_returns_sorted_employees(): void
    {
        $employee2 = Employee::factory()->create(['company_id' => $this->company->id]);
        $year = now()->year;
        $month = now()->month;

        KpiPlan::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'kpi_sales_sphere_id' => $this->sphere->id,
            'kpi_bonus_scale_id' => $this->scale->id,
            'period_year' => $year,
            'period_month' => $month,
            'achievement_percent' => 70.0,
            'status' => KpiPlan::STATUS_CALCULATED,
        ]);

        KpiPlan::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $employee2->id,
            'kpi_sales_sphere_id' => $this->sphere->id,
            'kpi_bonus_scale_id' => $this->scale->id,
            'period_year' => $year,
            'period_month' => $month,
            'achievement_percent' => 110.0,
            'status' => KpiPlan::STATUS_CALCULATED,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/kpi/ranking?year={$year}&month={$month}");

        $response->assertOk();

        $data = $response->json('data');
        $this->assertCount(2, $data);
        // Первый — employee2 (110%), второй — employee1 (70%)
        $this->assertEquals($employee2->id, $data[0]['employee_id']);
        $this->assertEquals($this->employee->id, $data[1]['employee_id']);
        $this->assertEquals(1, $data[0]['rank']);
        $this->assertEquals(2, $data[1]['rank']);
    }

    /**
     * Прогноз возвращает структуру с данными прогресса
     */
    public function test_forecast_returns_progress_data(): void
    {
        $year = now()->year;
        $month = now()->month;

        KpiPlan::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'kpi_sales_sphere_id' => $this->sphere->id,
            'kpi_bonus_scale_id' => $this->scale->id,
            'period_year' => $year,
            'period_month' => $month,
            'target_revenue' => 10000000,
            'actual_revenue' => 5000000,
            'status' => KpiPlan::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/kpi/forecast?year={$year}&month={$month}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'days_elapsed',
                'total_days',
                'progress_percent',
                'forecast_revenue',
                'forecast_achievement',
                'on_track_count',
                'at_risk_count',
                'plans',
            ],
        ]);

        $data = $response->json('data');
        $this->assertGreaterThanOrEqual(0, $data['days_elapsed']);
        $this->assertGreaterThan(0, $data['total_days']);
    }
}
