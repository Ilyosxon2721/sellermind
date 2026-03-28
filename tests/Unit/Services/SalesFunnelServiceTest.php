<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\SalesFunnelService;
use PHPUnit\Framework\TestCase;

/**
 * Тесты ручного расчёта воронки (без БД)
 */
class SalesFunnelServiceTest extends TestCase
{
    private SalesFunnelService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SalesFunnelService();
    }

    /**
     * Тест: ручной расчёт по примеру с фото 1
     */
    public function test_manual_calculation_photo_example_1(): void
    {
        $result = $this->service->calculateManual([
            'views' => 120000000,
            'inquiry_rate' => 0.1,
            'meeting_rate' => 20,
            'sale_rate' => 10,
            'average_check' => 100,
            'profit_margin' => 5,
            'bonus_rate' => 30,
            'currency' => 'UZS',
        ]);

        $this->assertCount(8, $result);

        // Views
        $this->assertEquals(120000000, $result[0]['value']);
        // Inquiries: 120000000 * 0.1% = 120000
        $this->assertEquals(120000, $result[1]['value']);
        // Meetings: 120000 * 20% = 24000
        $this->assertEquals(24000, $result[2]['value']);
        // Sales: 24000 * 10% = 2400
        $this->assertEquals(2400, $result[3]['value']);
        // Average check
        $this->assertEquals(100.0, $result[4]['value']);
        // Revenue: 2400 * 100 = 240000
        $this->assertEquals(240000.0, $result[5]['value']);
        // Net profit: 240000 * 5% = 12000
        $this->assertEquals(12000.0, $result[6]['value']);
        // Bonus: 12000 * 30% = 3600
        $this->assertEquals(3600.0, $result[7]['value']);
    }

    /**
     * Тест: ручной расчёт по примеру с фото 2
     */
    public function test_manual_calculation_photo_example_2(): void
    {
        $result = $this->service->calculateManual([
            'views' => 200000,
            'inquiry_rate' => 1,
            'meeting_rate' => 30,
            'sale_rate' => 20,
            'average_check' => 500,
            'profit_margin' => 20,
            'bonus_rate' => 50,
            'currency' => 'UZS',
        ]);

        $this->assertEquals(200000, $result[0]['value']);  // Views
        $this->assertEquals(2000, $result[1]['value']);     // Inquiries
        $this->assertEquals(600, $result[2]['value']);      // Meetings
        $this->assertEquals(120, $result[3]['value']);      // Sales
        $this->assertEquals(500.0, $result[4]['value']);    // Average check
        $this->assertEquals(60000.0, $result[5]['value']); // Revenue
        $this->assertEquals(12000.0, $result[6]['value']); // Net profit
        $this->assertEquals(6000.0, $result[7]['value']);   // Bonus
    }

    /**
     * Тест: нулевые параметры
     */
    public function test_manual_calculation_with_zeros(): void
    {
        $result = $this->service->calculateManual([
            'views' => 0,
            'inquiry_rate' => 0,
            'meeting_rate' => 0,
            'sale_rate' => 0,
            'average_check' => 0,
            'profit_margin' => 0,
            'bonus_rate' => 0,
        ]);

        foreach ($result as $stage) {
            if (in_array($stage['stage'], ['views', 'inquiries', 'meetings', 'sales'])) {
                $this->assertEquals(0, $stage['value']);
            } else {
                $this->assertEquals(0.0, $stage['value']);
            }
        }
    }

    /**
     * Тест: правильные лейблы этапов
     */
    public function test_manual_calculation_returns_correct_stage_labels(): void
    {
        $result = $this->service->calculateManual([
            'views' => 1000,
            'inquiry_rate' => 10,
            'meeting_rate' => 50,
            'sale_rate' => 50,
            'average_check' => 100,
            'profit_margin' => 10,
            'bonus_rate' => 5,
        ]);

        $stages = array_column($result, 'stage');
        $this->assertEquals([
            'views', 'inquiries', 'meetings', 'sales',
            'average_check', 'revenue', 'net_profit', 'bonus',
        ], $stages);
    }

    /**
     * Тест: правильные ставки (rate) в результатах
     */
    public function test_manual_calculation_preserves_rates(): void
    {
        $result = $this->service->calculateManual([
            'views' => 10000,
            'inquiry_rate' => 5.5,
            'meeting_rate' => 33.3,
            'sale_rate' => 12.7,
            'average_check' => 250,
            'profit_margin' => 18.5,
            'bonus_rate' => 25,
        ]);

        $this->assertNull($result[0]['rate']);    // views — без ставки
        $this->assertEquals(5.5, $result[1]['rate']);    // inquiry_rate
        $this->assertEquals(33.3, $result[2]['rate']);   // meeting_rate
        $this->assertEquals(12.7, $result[3]['rate']);   // sale_rate
        $this->assertNull($result[4]['rate']);    // average_check — без ставки
        $this->assertNull($result[5]['rate']);    // revenue — без ставки
        $this->assertEquals(18.5, $result[6]['rate']);   // profit_margin
        $this->assertEquals(25.0, $result[7]['rate']);   // bonus_rate
    }

    /**
     * Тест: отсутствующие параметры заменяются нулями
     */
    public function test_manual_calculation_with_missing_params(): void
    {
        $result = $this->service->calculateManual([]);

        $this->assertEquals(0, $result[0]['value']);  // Views = 0
        $this->assertEquals(0, $result[1]['value']);  // Inquiries = 0
        $this->assertEquals(0.0, $result[5]['value']); // Revenue = 0
    }

    /**
     * Тест: валюта по умолчанию UZS
     */
    public function test_manual_calculation_default_currency(): void
    {
        $result = $this->service->calculateManual([
            'views' => 100,
            'inquiry_rate' => 10,
            'meeting_rate' => 10,
            'sale_rate' => 10,
            'average_check' => 100,
            'profit_margin' => 10,
            'bonus_rate' => 10,
        ]);

        $this->assertEquals('UZS', $result[4]['unit']); // average_check unit
        $this->assertEquals('UZS', $result[5]['unit']); // revenue unit
    }

    /**
     * Тест: можно передать другую валюту
     */
    public function test_manual_calculation_custom_currency(): void
    {
        $result = $this->service->calculateManual([
            'views' => 100,
            'inquiry_rate' => 10,
            'meeting_rate' => 10,
            'sale_rate' => 10,
            'average_check' => 100,
            'profit_margin' => 10,
            'bonus_rate' => 10,
            'currency' => 'RUB',
        ]);

        $this->assertEquals('RUB', $result[4]['unit']);
        $this->assertEquals('RUB', $result[5]['unit']);
        $this->assertEquals('RUB', $result[6]['unit']);
        $this->assertEquals('RUB', $result[7]['unit']);
    }
}
