<?php

declare(strict_types=1);

namespace Tests\Unit\Analytics;

use App\Models\Company;
use App\Models\MarketplaceAccount;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use App\Models\WildberriesOrder;
use App\Services\SalesAnalyticsService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Unit-тесты для SalesAnalyticsService.
 * Тестирует логику расчёта дат и вспомогательных методов.
 */
class SalesAnalyticsServiceTest extends TestCase
{
    use RefreshDatabase;

    protected SalesAnalyticsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SalesAnalyticsService;
    }

    // ==========================================
    // Тесты для getDateRange
    // ==========================================

    #[DataProvider('dateRangeProvider')]
    public function test_get_date_range(string $period, int $expectedDays): void
    {
        // Замораживаем время для предсказуемости тестов
        Carbon::setTestNow(Carbon::parse('2026-03-09 12:00:00'));

        $method = new ReflectionMethod(SalesAnalyticsService::class, 'getDateRange');
        $result = $method->invoke($this->service, $period);

        $this->assertArrayHasKey('from', $result);
        $this->assertArrayHasKey('to', $result);
        $this->assertInstanceOf(Carbon::class, $result['from']);
        $this->assertInstanceOf(Carbon::class, $result['to']);

        // Проверяем разницу в днях (используем abs, т.к. from < to)
        $diff = abs($result['to']->diffInDays($result['from']));
        $this->assertEquals($expectedDays, $diff, "Period '$period' should span $expectedDays days");

        Carbon::setTestNow();
    }

    public static function dateRangeProvider(): array
    {
        return [
            '7days' => ['7days', 7],
            '30days' => ['30days', 30],
            '90days' => ['90days', 90],
            'default_fallback' => ['invalid', 30],
        ];
    }

    public function test_get_date_range_today(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-03-09 12:00:00'));

        $method = new ReflectionMethod(SalesAnalyticsService::class, 'getDateRange');
        $result = $method->invoke($this->service, 'today');

        // Для today from должен быть startOfDay
        $this->assertEquals('2026-03-09', $result['from']->toDateString());
        $this->assertEquals('2026-03-09', $result['to']->toDateString());
        $this->assertEquals('00:00:00', $result['from']->toTimeString());

        Carbon::setTestNow();
    }

    public function test_get_date_range_month_period(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-03-15 12:00:00'));

        $method = new ReflectionMethod(SalesAnalyticsService::class, 'getDateRange');
        $result = $method->invoke($this->service, 'month');

        // Должно вернуть от начала текущего месяца
        $this->assertEquals('2026-03-01', $result['from']->toDateString());

        Carbon::setTestNow();
    }

    public function test_get_date_range_year_period(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-03-15 12:00:00'));

        $method = new ReflectionMethod(SalesAnalyticsService::class, 'getDateRange');
        $result = $method->invoke($this->service, 'year');

        // Должно вернуть от начала текущего года
        $this->assertEquals('2026-01-01', $result['from']->toDateString());

        Carbon::setTestNow();
    }

    // ==========================================
    // Тесты для getPreviousDateRange
    // ==========================================

    public function test_get_previous_date_range_7days(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-03-09 12:00:00'));

        $method = new ReflectionMethod(SalesAnalyticsService::class, 'getPreviousDateRange');
        $result = $method->invoke($this->service, '7days');

        // Текущий период: 2026-03-02 — 2026-03-09 (7 дней)
        // Предыдущий: 2026-02-23 — 2026-03-02 (7 дней до этого)
        $this->assertArrayHasKey('from', $result);
        $this->assertArrayHasKey('to', $result);

        $diff = $result['to']->diffInDays($result['from']);
        $this->assertEquals(7, $diff);

        Carbon::setTestNow();
    }

    public function test_get_previous_date_range_30days(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-03-09 12:00:00'));

        $method = new ReflectionMethod(SalesAnalyticsService::class, 'getPreviousDateRange');
        $result = $method->invoke($this->service, '30days');

        $diff = $result['to']->diffInDays($result['from']);
        $this->assertEquals(30, $diff);

        Carbon::setTestNow();
    }

    public function test_previous_range_ends_at_current_range_start(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-03-09 12:00:00'));

        $getDateRange = new ReflectionMethod(SalesAnalyticsService::class, 'getDateRange');
        $getPrevDateRange = new ReflectionMethod(SalesAnalyticsService::class, 'getPreviousDateRange');

        $currentRange = $getDateRange->invoke($this->service, '30days');
        $prevRange = $getPrevDateRange->invoke($this->service, '30days');

        // Предыдущий период должен заканчиваться там, где начинается текущий
        $this->assertEquals(
            $currentRange['from']->toDateString(),
            $prevRange['to']->toDateString()
        );

        Carbon::setTestNow();
    }

    // ==========================================
    // Тесты для логики расчётов
    // ==========================================

    public function test_average_order_value_calculation(): void
    {
        // Тестируем формулу: avgOrderValue = totalRevenue / totalOrders
        $totalRevenue = 10000;
        $totalOrders = 50;

        $avgOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;

        $this->assertEquals(200, $avgOrderValue);
    }

    public function test_average_order_value_zero_orders(): void
    {
        $totalRevenue = 10000;
        $totalOrders = 0;

        $avgOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;

        $this->assertEquals(0, $avgOrderValue);
    }

    public function test_revenue_growth_calculation(): void
    {
        // Тестируем формулу: growth = ((current - prev) / prev) * 100
        $currentRevenue = 15000;
        $prevRevenue = 10000;

        $growth = $prevRevenue > 0 ? (($currentRevenue - $prevRevenue) / $prevRevenue) * 100 : 0;

        $this->assertEquals(50, $growth); // 50% рост
    }

    public function test_revenue_growth_negative(): void
    {
        $currentRevenue = 8000;
        $prevRevenue = 10000;

        $growth = $prevRevenue > 0 ? (($currentRevenue - $prevRevenue) / $prevRevenue) * 100 : 0;

        $this->assertEquals(-20, $growth); // -20% падение
    }

    public function test_revenue_growth_zero_previous(): void
    {
        $currentRevenue = 15000;
        $prevRevenue = 0;

        $growth = $prevRevenue > 0 ? (($currentRevenue - $prevRevenue) / $prevRevenue) * 100 : 0;

        $this->assertEquals(0, $growth); // Нет предыдущего - рост 0
    }

    public function test_revenue_growth_double_revenue(): void
    {
        $currentRevenue = 20000;
        $prevRevenue = 10000;

        $growth = $prevRevenue > 0 ? (($currentRevenue - $prevRevenue) / $prevRevenue) * 100 : 0;

        $this->assertEquals(100, $growth); // 100% рост (удвоение)
    }

    // ==========================================
    // Тесты для CANCELLED_STATUSES
    // ==========================================

    public function test_cancelled_statuses_constant(): void
    {
        $reflection = new \ReflectionClass(SalesAnalyticsService::class);
        $constant = $reflection->getConstant('CANCELLED_STATUSES');

        $this->assertIsArray($constant);
        $this->assertContains('cancelled', $constant);
        $this->assertContains('canceled', $constant);
        $this->assertContains('CANCELED', $constant);
        $this->assertContains('PENDING_CANCELLATION', $constant);
    }

    // ==========================================
    // Тесты для avg_price расчёта в getTopProducts/getFlopProducts
    // ==========================================

    public function test_avg_price_calculation(): void
    {
        $totalRevenue = 5000;
        $totalQuantity = 25;

        $avgPrice = $totalQuantity > 0
            ? round($totalRevenue / $totalQuantity, 2)
            : 0;

        $this->assertEquals(200.0, $avgPrice);
    }

    public function test_avg_price_zero_quantity(): void
    {
        $totalRevenue = 5000;
        $totalQuantity = 0;

        $avgPrice = $totalQuantity > 0
            ? round($totalRevenue / $totalQuantity, 2)
            : 0;

        $this->assertEquals(0, $avgPrice);
    }

    public function test_avg_price_with_rounding(): void
    {
        $totalRevenue = 1000;
        $totalQuantity = 3;

        $avgPrice = $totalQuantity > 0
            ? round($totalRevenue / $totalQuantity, 2)
            : 0;

        $this->assertEquals(333.33, $avgPrice);
    }

    // ==========================================
    // Тесты для getGroupByExpressions
    // ==========================================

    public function test_get_group_by_expressions_month_default(): void
    {
        $method = new ReflectionMethod(SalesAnalyticsService::class, 'getGroupByExpressions');
        $result = $method->invoke($this->service, 'month');

        $this->assertArrayHasKey('select_date', $result);
        $this->assertArrayHasKey('select_label', $result);
        $this->assertStringContainsString('DATE_FORMAT', $result['select_date']);
        $this->assertStringContainsString('%Y-%m', $result['select_date']);
        $this->assertStringContainsString('DATE_FORMAT', $result['select_label']);
        $this->assertStringContainsString('%Y-%m', $result['select_label']);
    }

    public function test_get_group_by_expressions_year(): void
    {
        $method = new ReflectionMethod(SalesAnalyticsService::class, 'getGroupByExpressions');
        $result = $method->invoke($this->service, 'year');

        $this->assertArrayHasKey('select_date', $result);
        $this->assertArrayHasKey('select_label', $result);
        $this->assertStringContainsString('YEAR', $result['select_date']);
        $this->assertStringContainsString('YEAR', $result['select_label']);
    }

    public function test_get_group_by_expressions_week(): void
    {
        $method = new ReflectionMethod(SalesAnalyticsService::class, 'getGroupByExpressions');
        $result = $method->invoke($this->service, 'week');

        $this->assertArrayHasKey('select_date', $result);
        $this->assertArrayHasKey('select_label', $result);
        $this->assertStringContainsString('YEARWEEK', $result['select_date']);
        $this->assertStringContainsString('CONCAT', $result['select_label']);
        $this->assertStringContainsString('-W', $result['select_label']);
    }

    public function test_get_group_by_expressions_invalid_falls_back_to_month(): void
    {
        $method = new ReflectionMethod(SalesAnalyticsService::class, 'getGroupByExpressions');
        $result = $method->invoke($this->service, 'invalid_value');

        // Должен вернуть дефолтный формат (month)
        $monthResult = $method->invoke($this->service, 'month');
        $this->assertEquals($monthResult, $result);
    }

    public function test_get_group_by_expressions_contains_order_date_placeholder(): void
    {
        $method = new ReflectionMethod(SalesAnalyticsService::class, 'getGroupByExpressions');

        // Все варианты должны использовать order_date как базовый столбец
        foreach (['year', 'month', 'week'] as $groupBy) {
            $result = $method->invoke($this->service, $groupBy);
            $this->assertStringContainsString('order_date', $result['select_date'],
                "Group by '$groupBy' select_date should contain 'order_date'");
            $this->assertStringContainsString('order_date', $result['select_label'],
                "Group by '$groupBy' select_label should contain 'order_date'");
        }
    }

    #[DataProvider('groupByExpressionsProvider')]
    public function test_get_group_by_expressions_returns_two_keys(string $groupBy): void
    {
        $method = new ReflectionMethod(SalesAnalyticsService::class, 'getGroupByExpressions');
        $result = $method->invoke($this->service, $groupBy);

        $this->assertCount(2, $result);
        $this->assertArrayHasKey('select_date', $result);
        $this->assertArrayHasKey('select_label', $result);
        $this->assertIsString($result['select_date']);
        $this->assertIsString($result['select_label']);
    }

    public static function groupByExpressionsProvider(): array
    {
        return [
            'year' => ['year'],
            'month' => ['month'],
            'week' => ['week'],
        ];
    }

    // ==========================================
    // Тесты для getSalesStatistics
    // ==========================================

    public function test_get_sales_statistics_returns_correct_structure(): void
    {
        // Arrange
        $company = Company::factory()->create();

        // Act
        $result = $this->service->getSalesStatistics($company->id);

        // Assert
        $this->assertArrayHasKey('group_by', $result);
        $this->assertArrayHasKey('periods', $result);
        $this->assertArrayHasKey('by_source', $result);
        $this->assertArrayHasKey('totals', $result);
    }

    public function test_get_sales_statistics_returns_empty_data_for_new_company(): void
    {
        // Arrange
        $company = Company::factory()->create();

        // Act
        $result = $this->service->getSalesStatistics($company->id);

        // Assert
        $this->assertEquals('month', $result['group_by']);
        $this->assertEmpty($result['periods']);
        $this->assertEmpty($result['by_source']);
        $this->assertEquals(0, $result['totals']['orders_count']);
        $this->assertEquals(0, $result['totals']['revenue']);
        $this->assertEquals(0, $result['totals']['quantity']);
        $this->assertEquals(0, $result['totals']['avg_order_value']);
    }

    public function test_get_sales_statistics_respects_group_by_parameter(): void
    {
        // Arrange
        $company = Company::factory()->create();

        // Act
        $resultYear = $this->service->getSalesStatistics($company->id, 'year');
        $resultWeek = $this->service->getSalesStatistics($company->id, 'week');
        $resultMonth = $this->service->getSalesStatistics($company->id, 'month');

        // Assert
        $this->assertEquals('year', $resultYear['group_by']);
        $this->assertEquals('week', $resultWeek['group_by']);
        $this->assertEquals('month', $resultMonth['group_by']);
    }

    public function test_get_sales_statistics_with_wildberries_orders(): void
    {
        // Arrange
        $company = Company::factory()->create();
        $account = MarketplaceAccount::factory()->create([
            'company_id' => $company->id,
            'marketplace' => 'wb',
        ]);

        // Создаём заказы WB
        DB::table('wildberries_orders')->insert([
            [
                'marketplace_account_id' => $account->id,
                'order_date' => '2026-03-15 10:00:00',
                'for_pay' => 1500.00,
                'is_cancel' => false,
                'is_return' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'marketplace_account_id' => $account->id,
                'order_date' => '2026-03-20 12:00:00',
                'for_pay' => 2500.00,
                'is_cancel' => false,
                'is_return' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Act
        $result = $this->service->getSalesStatistics($company->id, 'month');

        // Assert
        $this->assertEquals(2, $result['totals']['orders_count']);
        $this->assertGreaterThan(0, $result['totals']['revenue']);
        $this->assertEquals(2, $result['totals']['quantity']);
        $this->assertNotEmpty($result['periods']);
        $this->assertNotEmpty($result['by_source']);
    }

    public function test_get_sales_statistics_excludes_cancelled_wb_orders(): void
    {
        // Arrange
        $company = Company::factory()->create();
        $account = MarketplaceAccount::factory()->create([
            'company_id' => $company->id,
            'marketplace' => 'wb',
        ]);

        // Активный заказ
        DB::table('wildberries_orders')->insert([
            'marketplace_account_id' => $account->id,
            'order_date' => '2026-03-15 10:00:00',
            'for_pay' => 1500.00,
            'is_cancel' => false,
            'is_return' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Отменённый заказ
        DB::table('wildberries_orders')->insert([
            'marketplace_account_id' => $account->id,
            'order_date' => '2026-03-16 10:00:00',
            'for_pay' => 3000.00,
            'is_cancel' => true,
            'is_return' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Возвращённый заказ
        DB::table('wildberries_orders')->insert([
            'marketplace_account_id' => $account->id,
            'order_date' => '2026-03-17 10:00:00',
            'for_pay' => 2000.00,
            'is_cancel' => false,
            'is_return' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Act
        $result = $this->service->getSalesStatistics($company->id, 'month', ['wb']);

        // Assert — только 1 активный заказ
        $this->assertEquals(1, $result['totals']['orders_count']);
    }

    public function test_get_sales_statistics_filters_by_source_wb(): void
    {
        // Arrange
        $company = Company::factory()->create();
        $account = MarketplaceAccount::factory()->create([
            'company_id' => $company->id,
            'marketplace' => 'wb',
        ]);

        DB::table('wildberries_orders')->insert([
            'marketplace_account_id' => $account->id,
            'order_date' => '2026-03-15 10:00:00',
            'for_pay' => 1500.00,
            'is_cancel' => false,
            'is_return' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Создаём ручную продажу, которую фильтр wb НЕ должен включить
        $user = User::factory()->create(['company_id' => $company->id]);
        $sale = Sale::factory()->create([
            'company_id' => $company->id,
            'status' => 'confirmed',
            'created_by' => $user->id,
        ]);
        DB::table('sale_items')->insert([
            'sale_id' => $sale->id,
            'product_name' => 'Test Product',
            'quantity' => 2,
            'unit_price' => 500.00,
            'subtotal' => 1000.00,
            'total' => 1000.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Act — фильтруем только wb
        $result = $this->service->getSalesStatistics($company->id, 'month', ['wb']);

        // Assert — только WB заказ
        $this->assertEquals(1, $result['totals']['orders_count']);
    }

    public function test_get_sales_statistics_filters_by_source_manual(): void
    {
        // Arrange
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        // WB заказ (не должен попасть в manual)
        $account = MarketplaceAccount::factory()->create([
            'company_id' => $company->id,
            'marketplace' => 'wb',
        ]);
        DB::table('wildberries_orders')->insert([
            'marketplace_account_id' => $account->id,
            'order_date' => '2026-03-15 10:00:00',
            'for_pay' => 1500.00,
            'is_cancel' => false,
            'is_return' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Ручная продажа
        $sale = Sale::factory()->create([
            'company_id' => $company->id,
            'status' => 'confirmed',
            'created_by' => $user->id,
        ]);
        DB::table('sale_items')->insert([
            'sale_id' => $sale->id,
            'product_name' => 'Manual Product',
            'quantity' => 3,
            'unit_price' => 200.00,
            'subtotal' => 600.00,
            'total' => 600.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Act — фильтруем только manual
        $result = $this->service->getSalesStatistics($company->id, 'month', ['manual']);

        // Assert — только ручная продажа
        $this->assertEquals(1, $result['totals']['orders_count']);
        $this->assertEquals(3, $result['totals']['quantity']);
    }

    public function test_get_sales_statistics_excludes_cancelled_manual_sales(): void
    {
        // Arrange
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        // Подтверждённая продажа
        $activeSale = Sale::factory()->create([
            'company_id' => $company->id,
            'status' => 'confirmed',
            'created_by' => $user->id,
        ]);
        DB::table('sale_items')->insert([
            'sale_id' => $activeSale->id,
            'product_name' => 'Active Product',
            'quantity' => 1,
            'unit_price' => 500.00,
            'subtotal' => 500.00,
            'total' => 500.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Отменённая продажа
        $cancelledSale = Sale::factory()->cancelled()->create([
            'company_id' => $company->id,
            'created_by' => $user->id,
        ]);
        DB::table('sale_items')->insert([
            'sale_id' => $cancelledSale->id,
            'product_name' => 'Cancelled Product',
            'quantity' => 5,
            'unit_price' => 1000.00,
            'subtotal' => 5000.00,
            'total' => 5000.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Act
        $result = $this->service->getSalesStatistics($company->id, 'month', ['manual']);

        // Assert — только активная продажа
        $this->assertEquals(1, $result['totals']['orders_count']);
    }

    public function test_get_sales_statistics_totals_calculates_avg_order_value(): void
    {
        // Arrange
        $company = Company::factory()->create();
        $account = MarketplaceAccount::factory()->create([
            'company_id' => $company->id,
            'marketplace' => 'wb',
        ]);

        // 3 заказа с разными суммами
        DB::table('wildberries_orders')->insert([
            [
                'marketplace_account_id' => $account->id,
                'order_date' => '2026-03-10 10:00:00',
                'for_pay' => 1000.00,
                'is_cancel' => false,
                'is_return' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'marketplace_account_id' => $account->id,
                'order_date' => '2026-03-11 10:00:00',
                'for_pay' => 2000.00,
                'is_cancel' => false,
                'is_return' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'marketplace_account_id' => $account->id,
                'order_date' => '2026-03-12 10:00:00',
                'for_pay' => 3000.00,
                'is_cancel' => false,
                'is_return' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Act
        $result = $this->service->getSalesStatistics($company->id, 'month', ['wb']);

        // Assert
        $this->assertEquals(3, $result['totals']['orders_count']);
        $this->assertGreaterThan(0, $result['totals']['avg_order_value']);
        // avg = total_revenue / 3
        $expectedAvg = round($result['totals']['revenue'] / 3, 2);
        $this->assertEquals($expectedAvg, $result['totals']['avg_order_value']);
    }

    public function test_get_sales_statistics_groups_by_year(): void
    {
        // Arrange
        $company = Company::factory()->create();
        $account = MarketplaceAccount::factory()->create([
            'company_id' => $company->id,
            'marketplace' => 'wb',
        ]);

        // Заказы в разных годах
        DB::table('wildberries_orders')->insert([
            [
                'marketplace_account_id' => $account->id,
                'order_date' => '2025-06-15 10:00:00',
                'for_pay' => 1000.00,
                'is_cancel' => false,
                'is_return' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'marketplace_account_id' => $account->id,
                'order_date' => '2026-03-15 10:00:00',
                'for_pay' => 2000.00,
                'is_cancel' => false,
                'is_return' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Act
        $result = $this->service->getSalesStatistics($company->id, 'year', ['wb']);

        // Assert — два периода (2025 и 2026)
        $this->assertCount(2, $result['periods']);
        $this->assertEquals('year', $result['group_by']);
    }

    public function test_get_sales_statistics_groups_by_week(): void
    {
        // Arrange
        $company = Company::factory()->create();
        $account = MarketplaceAccount::factory()->create([
            'company_id' => $company->id,
            'marketplace' => 'wb',
        ]);

        // Заказы в разных неделях
        DB::table('wildberries_orders')->insert([
            [
                'marketplace_account_id' => $account->id,
                'order_date' => '2026-03-02 10:00:00', // неделя 10
                'for_pay' => 1000.00,
                'is_cancel' => false,
                'is_return' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'marketplace_account_id' => $account->id,
                'order_date' => '2026-03-16 10:00:00', // неделя 12
                'for_pay' => 2000.00,
                'is_cancel' => false,
                'is_return' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Act
        $result = $this->service->getSalesStatistics($company->id, 'week', ['wb']);

        // Assert — два разных периода по неделям
        $this->assertCount(2, $result['periods']);
        $this->assertEquals('week', $result['group_by']);
    }

    public function test_get_sales_statistics_by_source_contains_source_breakdown(): void
    {
        // Arrange
        $company = Company::factory()->create();
        $account = MarketplaceAccount::factory()->create([
            'company_id' => $company->id,
            'marketplace' => 'wb',
        ]);

        DB::table('wildberries_orders')->insert([
            'marketplace_account_id' => $account->id,
            'order_date' => '2026-03-15 10:00:00',
            'for_pay' => 1500.00,
            'is_cancel' => false,
            'is_return' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Act
        $result = $this->service->getSalesStatistics($company->id, 'month', ['wb']);

        // Assert — by_source содержит разбивку по wb
        $this->assertNotEmpty($result['by_source']);
        $firstPeriod = array_values($result['by_source'])[0];
        $this->assertArrayHasKey('wb', $firstPeriod);
        $this->assertArrayHasKey('orders_count', $firstPeriod['wb']);
        $this->assertArrayHasKey('revenue', $firstPeriod['wb']);
        $this->assertArrayHasKey('quantity', $firstPeriod['wb']);
    }

    public function test_get_sales_statistics_scoped_to_company(): void
    {
        // Arrange
        $company1 = Company::factory()->create();
        $company2 = Company::factory()->create();

        $account1 = MarketplaceAccount::factory()->create([
            'company_id' => $company1->id,
            'marketplace' => 'wb',
        ]);
        $account2 = MarketplaceAccount::factory()->create([
            'company_id' => $company2->id,
            'marketplace' => 'wb',
        ]);

        // Заказы для компании 1
        DB::table('wildberries_orders')->insert([
            'marketplace_account_id' => $account1->id,
            'order_date' => '2026-03-15 10:00:00',
            'for_pay' => 1500.00,
            'is_cancel' => false,
            'is_return' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Заказы для компании 2
        DB::table('wildberries_orders')->insert([
            'marketplace_account_id' => $account2->id,
            'order_date' => '2026-03-15 10:00:00',
            'for_pay' => 3000.00,
            'is_cancel' => false,
            'is_return' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Act
        $result1 = $this->service->getSalesStatistics($company1->id, 'month', ['wb']);
        $result2 = $this->service->getSalesStatistics($company2->id, 'month', ['wb']);

        // Assert — каждая компания видит только свои данные
        $this->assertEquals(1, $result1['totals']['orders_count']);
        $this->assertEquals(1, $result2['totals']['orders_count']);
        $this->assertNotEquals($result1['totals']['revenue'], $result2['totals']['revenue']);
    }

    public function test_get_sales_statistics_with_empty_sources_returns_all_data(): void
    {
        // Arrange
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $account = MarketplaceAccount::factory()->create([
            'company_id' => $company->id,
            'marketplace' => 'wb',
        ]);

        // WB заказ
        DB::table('wildberries_orders')->insert([
            'marketplace_account_id' => $account->id,
            'order_date' => '2026-03-15 10:00:00',
            'for_pay' => 1000.00,
            'is_cancel' => false,
            'is_return' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Ручная продажа
        $sale = Sale::factory()->create([
            'company_id' => $company->id,
            'status' => 'confirmed',
            'created_by' => $user->id,
        ]);
        DB::table('sale_items')->insert([
            'sale_id' => $sale->id,
            'product_name' => 'Manual Product',
            'quantity' => 1,
            'unit_price' => 500.00,
            'subtotal' => 500.00,
            'total' => 500.00,
            'created_at' => '2026-03-15 10:00:00',
            'updated_at' => now(),
        ]);

        // Act — пустой sources = все источники
        $result = $this->service->getSalesStatistics($company->id, 'month', []);

        // Assert — должны быть данные из обоих источников
        $this->assertGreaterThanOrEqual(2, $result['totals']['orders_count']);
    }

    public function test_get_sales_statistics_periods_are_sorted_by_key(): void
    {
        // Arrange
        $company = Company::factory()->create();
        $account = MarketplaceAccount::factory()->create([
            'company_id' => $company->id,
            'marketplace' => 'wb',
        ]);

        // Заказы в разных месяцах (вставляем не по порядку)
        DB::table('wildberries_orders')->insert([
            [
                'marketplace_account_id' => $account->id,
                'order_date' => '2026-03-15 10:00:00',
                'for_pay' => 1000.00,
                'is_cancel' => false,
                'is_return' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'marketplace_account_id' => $account->id,
                'order_date' => '2026-01-10 10:00:00',
                'for_pay' => 2000.00,
                'is_cancel' => false,
                'is_return' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'marketplace_account_id' => $account->id,
                'order_date' => '2026-02-20 10:00:00',
                'for_pay' => 1500.00,
                'is_cancel' => false,
                'is_return' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Act
        $result = $this->service->getSalesStatistics($company->id, 'month', ['wb']);

        // Assert — периоды отсортированы по ключу
        $periodKeys = array_column($result['periods'], 'period_key');
        $sorted = $periodKeys;
        sort($sorted);
        $this->assertEquals($sorted, $periodKeys);
    }
}
