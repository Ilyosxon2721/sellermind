<?php

declare(strict_types=1);

namespace Tests\Unit\Promotions;

use App\Models\Promotion;
use App\Models\PromotionProduct;
use App\Services\PromotionService;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Unit-тесты для PromotionService и связанных моделей.
 */
class PromotionServiceTest extends TestCase
{
    protected PromotionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PromotionService;
    }

    // ==========================================
    // Тесты для PromotionService::calculateRecommendedDiscount
    // ==========================================

    #[DataProvider('recommendedDiscountProvider')]
    public function test_calculate_recommended_discount(int $days, float $turnover, int $expected): void
    {
        $method = new ReflectionMethod(PromotionService::class, 'calculateRecommendedDiscount');

        $result = $method->invoke($this->service, $days, $turnover);

        $this->assertEquals($expected, $result);
    }

    public static function recommendedDiscountProvider(): array
    {
        return [
            'very_slow_180_days' => [180, 0.05, 50],
            'quite_slow_90_days' => [90, 0.05, 35],
            'moderately_slow_60_days' => [60, 0.05, 25],
            'slightly_slow_30_days' => [30, 0.05, 15],
            'minimum_discount_below_30' => [20, 0.05, 10],
            'edge_case_exactly_180' => [180, 0.0, 50],
            'edge_case_exactly_90' => [90, 0.0, 35],
            'edge_case_exactly_60' => [60, 0.0, 25],
            'edge_case_exactly_30' => [30, 0.0, 15],
            'edge_case_29_days' => [29, 0.0, 10],
        ];
    }

    // ==========================================
    // Тесты для Promotion::calculateDiscountedPrice
    // ==========================================

    public function test_calculate_discounted_price_percentage(): void
    {
        $promotion = new Promotion([
            'type' => 'percentage',
            'discount_value' => 20,
        ]);

        $result = $promotion->calculateDiscountedPrice(1000);

        $this->assertEquals(800, $result);
    }

    public function test_calculate_discounted_price_percentage_50(): void
    {
        $promotion = new Promotion([
            'type' => 'percentage',
            'discount_value' => 50,
        ]);

        $result = $promotion->calculateDiscountedPrice(200);

        $this->assertEquals(100, $result);
    }

    public function test_calculate_discounted_price_fixed(): void
    {
        $promotion = new Promotion([
            'type' => 'fixed',
            'discount_value' => 150,
        ]);

        $result = $promotion->calculateDiscountedPrice(1000);

        $this->assertEquals(850, $result);
    }

    public function test_calculate_discounted_price_fixed_exceeds_price(): void
    {
        $promotion = new Promotion([
            'type' => 'fixed',
            'discount_value' => 200,
        ]);

        $result = $promotion->calculateDiscountedPrice(100);

        $this->assertEquals(0, $result);
    }

    // ==========================================
    // Тесты для Promotion::calculateDiscountAmount
    // ==========================================

    public function test_calculate_discount_amount_percentage(): void
    {
        $promotion = new Promotion([
            'type' => 'percentage',
            'discount_value' => 25,
        ]);

        $result = $promotion->calculateDiscountAmount(400);

        $this->assertEquals(100, $result);
    }

    public function test_calculate_discount_amount_fixed(): void
    {
        $promotion = new Promotion([
            'type' => 'fixed',
            'discount_value' => 50,
        ]);

        $result = $promotion->calculateDiscountAmount(200);

        $this->assertEquals(50, $result);
    }

    // ==========================================
    // Тесты для Promotion::isCurrentlyActive
    // ==========================================

    public function test_is_currently_active_returns_true(): void
    {
        $promotion = new Promotion([
            'is_active' => true,
            'start_date' => Carbon::now()->subDays(5),
            'end_date' => Carbon::now()->addDays(5),
        ]);

        $this->assertTrue($promotion->isCurrentlyActive());
    }

    public function test_is_currently_active_returns_false_when_inactive(): void
    {
        $promotion = new Promotion([
            'is_active' => false,
            'start_date' => Carbon::now()->subDays(5),
            'end_date' => Carbon::now()->addDays(5),
        ]);

        $this->assertFalse($promotion->isCurrentlyActive());
    }

    public function test_is_currently_active_returns_false_when_not_started(): void
    {
        $promotion = new Promotion([
            'is_active' => true,
            'start_date' => Carbon::now()->addDays(5),
            'end_date' => Carbon::now()->addDays(10),
        ]);

        $this->assertFalse($promotion->isCurrentlyActive());
    }

    public function test_is_currently_active_returns_false_when_expired(): void
    {
        $promotion = new Promotion([
            'is_active' => true,
            'start_date' => Carbon::now()->subDays(10),
            'end_date' => Carbon::now()->subDays(5),
        ]);

        $this->assertFalse($promotion->isCurrentlyActive());
    }

    // ==========================================
    // Тесты для Promotion::hasExpired
    // ==========================================

    public function test_has_expired_returns_true(): void
    {
        $promotion = new Promotion([
            'end_date' => Carbon::now()->subDays(1),
        ]);

        $this->assertTrue($promotion->hasExpired());
    }

    public function test_has_expired_returns_false(): void
    {
        $promotion = new Promotion([
            'end_date' => Carbon::now()->addDays(1),
        ]);

        $this->assertFalse($promotion->hasExpired());
    }

    // ==========================================
    // Тесты для Promotion::getDaysUntilExpiration
    // ==========================================

    public function test_get_days_until_expiration_positive(): void
    {
        $promotion = new Promotion([
            'end_date' => Carbon::now()->startOfDay()->addDays(10),
        ]);

        $result = $promotion->getDaysUntilExpiration();

        // Из-за округления Carbon может быть 9 или 10 в зависимости от времени суток
        $this->assertGreaterThanOrEqual(9, $result);
        $this->assertLessThanOrEqual(10, $result);
    }

    public function test_get_days_until_expiration_negative(): void
    {
        $promotion = new Promotion([
            'end_date' => Carbon::now()->subDays(5),
        ]);

        $result = $promotion->getDaysUntilExpiration();

        $this->assertEquals(-5, $result);
    }

    // ==========================================
    // Тесты для Promotion::isExpiringSoon
    // ==========================================

    public function test_is_expiring_soon_returns_true(): void
    {
        $promotion = new Promotion([
            'end_date' => Carbon::now()->addDays(2),
            'notify_days_before' => 3,
        ]);

        $this->assertTrue($promotion->isExpiringSoon());
    }

    public function test_is_expiring_soon_returns_false_too_far(): void
    {
        $promotion = new Promotion([
            'end_date' => Carbon::now()->addDays(10),
            'notify_days_before' => 3,
        ]);

        $this->assertFalse($promotion->isExpiringSoon());
    }

    public function test_is_expiring_soon_returns_false_already_expired(): void
    {
        $promotion = new Promotion([
            'end_date' => Carbon::now()->subDays(1),
            'notify_days_before' => 3,
        ]);

        $this->assertFalse($promotion->isExpiringSoon());
    }

    public function test_is_expiring_soon_with_custom_days(): void
    {
        $promotion = new Promotion([
            'end_date' => Carbon::now()->addDays(5),
            'notify_days_before' => 3,
        ]);

        $this->assertFalse($promotion->isExpiringSoon(3));
        $this->assertTrue($promotion->isExpiringSoon(7));
    }

    // ==========================================
    // Тесты для PromotionProduct::calculateROI
    // ==========================================

    public function test_calculate_roi_positive(): void
    {
        $pp = new PromotionProduct([
            'discount_amount' => 100,
            'units_sold' => 10,
            'revenue_generated' => 5000,
        ]);

        $result = $pp->calculateROI();

        // ROI = (5000 / (100 * 10)) * 100 = 500%
        $this->assertEquals(500, $result);
    }

    public function test_calculate_roi_zero_discount(): void
    {
        $pp = new PromotionProduct([
            'discount_amount' => 0,
            'units_sold' => 10,
            'revenue_generated' => 5000,
        ]);

        $result = $pp->calculateROI();

        $this->assertEquals(0, $result);
    }

    public function test_calculate_roi_zero_units_sold(): void
    {
        $pp = new PromotionProduct([
            'discount_amount' => 100,
            'units_sold' => 0,
            'revenue_generated' => 0,
        ]);

        $result = $pp->calculateROI();

        $this->assertEquals(0, $result);
    }

    // ==========================================
    // Тесты для PromotionProduct::isPerformingWell
    // ==========================================

    public function test_is_performing_well_returns_true(): void
    {
        $pp = new PromotionProduct([
            'discount_amount' => 50,
            'units_sold' => 10,
            'revenue_generated' => 1000,
        ]);

        // ROI = (1000 / 500) * 100 = 200%
        $this->assertTrue($pp->isPerformingWell());
    }

    public function test_is_performing_well_returns_false_low_units(): void
    {
        $pp = new PromotionProduct([
            'discount_amount' => 50,
            'units_sold' => 4,
            'revenue_generated' => 1000,
        ]);

        $this->assertFalse($pp->isPerformingWell());
    }

    public function test_is_performing_well_returns_false_low_roi(): void
    {
        $pp = new PromotionProduct([
            'discount_amount' => 100,
            'units_sold' => 10,
            'revenue_generated' => 1000,
        ]);

        // ROI = (1000 / 1000) * 100 = 100%
        $this->assertFalse($pp->isPerformingWell());
    }
}
