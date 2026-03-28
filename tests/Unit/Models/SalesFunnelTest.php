<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\SalesFunnel;
use PHPUnit\Framework\TestCase;

/**
 * Тесты расчётов воронки продаж
 */
class SalesFunnelTest extends TestCase
{
    /**
     * Тест: расчёт воронки по примеру с фото 1
     * Views=120000000, inquiry=0.1%, meeting=20%, sale=10%, check=100, margin=5%, bonus=30%
     */
    public function test_calculates_funnel_from_photo_example_1(): void
    {
        $funnel = new SalesFunnel();
        $funnel->views = 120000000;
        $funnel->inquiry_rate = 0.1;
        $funnel->meeting_rate = 20;
        $funnel->sale_rate = 10;
        $funnel->average_check = 100;
        $funnel->profit_margin = 5;
        $funnel->bonus_rate = 30;
        $funnel->currency = 'UZS';

        $this->assertEquals(120000, $funnel->getInquiries());
        $this->assertEquals(24000, $funnel->getMeetings());
        $this->assertEquals(2400, $funnel->getSalesCount());
        $this->assertEquals(240000.0, $funnel->getRevenue());
        $this->assertEquals(12000.0, $funnel->getNetProfit());
        $this->assertEquals(3600.0, $funnel->getBonus());
    }

    /**
     * Тест: расчёт воронки по примеру с фото 2
     * Views=200000, inquiry=1%, meeting=30%, sale=20%, check=500, margin=20%, bonus=50%
     */
    public function test_calculates_funnel_from_photo_example_2(): void
    {
        $funnel = new SalesFunnel();
        $funnel->views = 200000;
        $funnel->inquiry_rate = 1;
        $funnel->meeting_rate = 30;
        $funnel->sale_rate = 20;
        $funnel->average_check = 500;
        $funnel->profit_margin = 20;
        $funnel->bonus_rate = 50;
        $funnel->currency = 'UZS';

        $this->assertEquals(2000, $funnel->getInquiries());
        $this->assertEquals(600, $funnel->getMeetings());
        $this->assertEquals(120, $funnel->getSalesCount());
        $this->assertEquals(60000.0, $funnel->getRevenue());
        $this->assertEquals(12000.0, $funnel->getNetProfit());
        $this->assertEquals(6000.0, $funnel->getBonus());
    }

    /**
     * Тест: нулевые просмотры = нулевая воронка
     */
    public function test_zero_views_produces_zero_funnel(): void
    {
        $funnel = new SalesFunnel();
        $funnel->views = 0;
        $funnel->inquiry_rate = 10;
        $funnel->meeting_rate = 20;
        $funnel->sale_rate = 30;
        $funnel->average_check = 1000;
        $funnel->profit_margin = 50;
        $funnel->bonus_rate = 10;

        $this->assertEquals(0, $funnel->getInquiries());
        $this->assertEquals(0, $funnel->getMeetings());
        $this->assertEquals(0, $funnel->getSalesCount());
        $this->assertEquals(0.0, $funnel->getRevenue());
        $this->assertEquals(0.0, $funnel->getNetProfit());
        $this->assertEquals(0.0, $funnel->getBonus());
    }

    /**
     * Тест: 100% конверсия на каждом этапе
     */
    public function test_full_conversion_rate(): void
    {
        $funnel = new SalesFunnel();
        $funnel->views = 1000;
        $funnel->inquiry_rate = 100;
        $funnel->meeting_rate = 100;
        $funnel->sale_rate = 100;
        $funnel->average_check = 200;
        $funnel->profit_margin = 100;
        $funnel->bonus_rate = 100;

        $this->assertEquals(1000, $funnel->getInquiries());
        $this->assertEquals(1000, $funnel->getMeetings());
        $this->assertEquals(1000, $funnel->getSalesCount());
        $this->assertEquals(200000.0, $funnel->getRevenue());
        $this->assertEquals(200000.0, $funnel->getNetProfit());
        $this->assertEquals(200000.0, $funnel->getBonus());
    }

    /**
     * Тест: calculateFunnel возвращает все 8 этапов
     */
    public function test_calculate_funnel_returns_8_stages(): void
    {
        $funnel = new SalesFunnel();
        $funnel->views = 100000;
        $funnel->inquiry_rate = 5;
        $funnel->meeting_rate = 25;
        $funnel->sale_rate = 10;
        $funnel->average_check = 300;
        $funnel->profit_margin = 15;
        $funnel->bonus_rate = 20;
        $funnel->currency = 'UZS';

        $result = $funnel->calculateFunnel();

        $this->assertCount(8, $result);

        $stages = array_column($result, 'stage');
        $this->assertEquals([
            'views', 'inquiries', 'meetings', 'sales',
            'average_check', 'revenue', 'net_profit', 'bonus',
        ], $stages);

        // Проверяем значения
        $this->assertEquals(100000, $result[0]['value']); // views
        $this->assertEquals(5000, $result[1]['value']);    // inquiries = 100000 * 5%
        $this->assertEquals(1250, $result[2]['value']);    // meetings = 5000 * 25%
        $this->assertEquals(125, $result[3]['value']);     // sales = 1250 * 10%
        $this->assertEquals(300.0, $result[4]['value']);   // avg check
        $this->assertEquals(37500.0, $result[5]['value']); // revenue = 125 * 300
        $this->assertEquals(5625.0, $result[6]['value']);  // profit = 37500 * 15%
        $this->assertEquals(1125.0, $result[7]['value']);  // bonus = 5625 * 20%
    }

    /**
     * Тест: список доступных источников
     */
    public function test_sources_list_includes_all_channels(): void
    {
        $sources = SalesFunnel::SOURCES;

        $this->assertArrayHasKey('wb', $sources);
        $this->assertArrayHasKey('ozon', $sources);
        $this->assertArrayHasKey('uzum', $sources);
        $this->assertArrayHasKey('ym', $sources);
        $this->assertArrayHasKey('manual', $sources);
        $this->assertArrayHasKey('retail', $sources);
        $this->assertArrayHasKey('wholesale', $sources);
        $this->assertArrayHasKey('direct', $sources);
        $this->assertCount(8, $sources);
    }
}
