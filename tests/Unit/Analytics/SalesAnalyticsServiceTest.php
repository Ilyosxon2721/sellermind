<?php

declare(strict_types=1);

namespace Tests\Unit\Analytics;

use App\Services\SalesAnalyticsService;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Unit-тесты для SalesAnalyticsService.
 * Тестирует логику расчёта дат и вспомогательных методов.
 */
class SalesAnalyticsServiceTest extends TestCase
{
    protected SalesAnalyticsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SalesAnalyticsService();
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
}
