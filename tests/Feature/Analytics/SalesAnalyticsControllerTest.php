<?php

declare(strict_types=1);

namespace Tests\Feature\Analytics;

use App\Models\Company;
use App\Models\MarketplaceAccount;
use App\Models\Sale;
use App\Models\User;
use App\Services\SalesAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

/**
 * Feature-тесты для endpoint GET /api/analytics/sales-statistics.
 * Тестирует контроллер SalesAnalyticsController::salesStatistics.
 */
final class SalesAnalyticsControllerTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->user = User::factory()->create(['company_id' => $this->company->id]);
    }

    // ==========================================
    // Тесты аутентификации
    // ==========================================

    public function test_sales_statistics_requires_authentication(): void
    {
        $response = $this->getJson('/api/analytics/sales-statistics');

        $response->assertUnauthorized();
    }

    // ==========================================
    // Тесты базового ответа
    // ==========================================

    public function test_sales_statistics_returns_ok_for_authenticated_user(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/analytics/sales-statistics');

        $response->assertOk();
    }

    public function test_sales_statistics_returns_correct_json_structure(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/analytics/sales-statistics');

        $response->assertOk()
            ->assertJsonStructure([
                'group_by',
                'periods',
                'by_source',
                'totals' => [
                    'orders_count',
                    'revenue',
                    'quantity',
                    'avg_order_value',
                ],
            ]);
    }

    // ==========================================
    // Тесты параметра group_by
    // ==========================================

    public function test_sales_statistics_defaults_to_month_group_by(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/analytics/sales-statistics');

        $response->assertOk()
            ->assertJsonPath('group_by', 'month');
    }

    public function test_sales_statistics_accepts_year_group_by(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/analytics/sales-statistics?group_by=year');

        $response->assertOk()
            ->assertJsonPath('group_by', 'year');
    }

    public function test_sales_statistics_accepts_week_group_by(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/analytics/sales-statistics?group_by=week');

        $response->assertOk()
            ->assertJsonPath('group_by', 'week');
    }

    public function test_sales_statistics_accepts_month_group_by(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/analytics/sales-statistics?group_by=month');

        $response->assertOk()
            ->assertJsonPath('group_by', 'month');
    }

    // ==========================================
    // Тесты параметра sources
    // ==========================================

    public function test_sales_statistics_accepts_comma_separated_sources(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/analytics/sales-statistics?sources=wb,ozon');

        $response->assertOk()
            ->assertJsonStructure([
                'group_by',
                'periods',
                'by_source',
                'totals',
            ]);
    }

    public function test_sales_statistics_accepts_single_source(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/analytics/sales-statistics?sources=manual');

        $response->assertOk();
    }

    public function test_sales_statistics_with_empty_sources_returns_all(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/analytics/sales-statistics?sources=');

        $response->assertOk();
    }

    // ==========================================
    // Тесты кэширования
    // ==========================================

    public function test_sales_statistics_caches_response(): void
    {
        Cache::flush();

        $this->actingAs($this->user)
            ->getJson('/api/analytics/sales-statistics?group_by=month');

        $cacheKey = "sales_statistics_{$this->company->id}_month_all";
        $this->assertTrue(Cache::has($cacheKey));
    }

    public function test_sales_statistics_cache_key_varies_by_group_by(): void
    {
        Cache::flush();

        $this->actingAs($this->user)
            ->getJson('/api/analytics/sales-statistics?group_by=year');

        $yearKey = "sales_statistics_{$this->company->id}_year_all";
        $monthKey = "sales_statistics_{$this->company->id}_month_all";

        $this->assertTrue(Cache::has($yearKey));
        $this->assertFalse(Cache::has($monthKey));
    }

    public function test_sales_statistics_cache_key_varies_by_sources(): void
    {
        Cache::flush();

        $this->actingAs($this->user)
            ->getJson('/api/analytics/sales-statistics?group_by=month&sources=wb,ozon');

        $cacheKey = "sales_statistics_{$this->company->id}_month_wb_ozon";
        $this->assertTrue(Cache::has($cacheKey));
    }

    // ==========================================
    // Тесты totals при пустых данных
    // ==========================================

    public function test_sales_statistics_returns_zero_totals_when_no_data(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/analytics/sales-statistics');

        $response->assertOk()
            ->assertJsonPath('totals.orders_count', 0)
            ->assertJsonPath('totals.revenue', 0)
            ->assertJsonPath('totals.quantity', 0)
            ->assertJsonPath('totals.avg_order_value', 0);
    }

    public function test_sales_statistics_returns_empty_periods_when_no_data(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/analytics/sales-statistics');

        $response->assertOk()
            ->assertJsonPath('periods', []);
    }

    public function test_sales_statistics_returns_empty_by_source_when_no_data(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/analytics/sales-statistics');

        $response->assertOk()
            ->assertJsonPath('by_source', []);
    }

    // ==========================================
    // Тесты комбинации параметров
    // ==========================================

    public function test_sales_statistics_with_all_parameters(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/analytics/sales-statistics?group_by=week&sources=wb,ozon,manual');

        $response->assertOk()
            ->assertJsonPath('group_by', 'week')
            ->assertJsonStructure([
                'group_by',
                'periods',
                'by_source',
                'totals' => [
                    'orders_count',
                    'revenue',
                    'quantity',
                    'avg_order_value',
                ],
            ]);
    }

    // ==========================================
    // Тесты изоляции данных между компаниями
    // ==========================================

    public function test_sales_statistics_scoped_to_user_company(): void
    {
        // Arrange: создаём другую компанию с данными
        $otherCompany = Company::factory()->create();
        $otherUser = User::factory()->create(['company_id' => $otherCompany->id]);

        // Act: запрос от текущего пользователя должен вернуть только данные его компании
        $response = $this->actingAs($this->user)
            ->getJson('/api/analytics/sales-statistics');

        $response->assertOk()
            ->assertJsonPath('totals.orders_count', 0);
    }

    // ==========================================
    // Тесты с реальными данными
    // ==========================================

    public function test_sales_statistics_returns_data_from_wildberries_orders(): void
    {
        // Arrange
        $account = MarketplaceAccount::factory()->create([
            'company_id' => $this->company->id,
            'marketplace' => 'wb',
        ]);

        \Illuminate\Support\Facades\DB::table('wildberries_orders')->insert([
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
        $response = $this->actingAs($this->user)
            ->getJson('/api/analytics/sales-statistics?group_by=month&sources=wb');

        // Assert
        $response->assertOk()
            ->assertJsonPath('totals.orders_count', 2)
            ->assertJsonPath('totals.quantity', 2);

        $this->assertGreaterThan(0, $response->json('totals.revenue'));
        $this->assertGreaterThan(0, $response->json('totals.avg_order_value'));
    }

    public function test_sales_statistics_excludes_cancelled_orders_from_totals(): void
    {
        // Arrange
        $account = MarketplaceAccount::factory()->create([
            'company_id' => $this->company->id,
            'marketplace' => 'wb',
        ]);

        \Illuminate\Support\Facades\DB::table('wildberries_orders')->insert([
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
                'order_date' => '2026-03-16 10:00:00',
                'for_pay' => 5000.00,
                'is_cancel' => true,
                'is_return' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Act
        $response = $this->actingAs($this->user)
            ->getJson('/api/analytics/sales-statistics?sources=wb');

        // Assert — только 1 неотменённый заказ
        $response->assertOk()
            ->assertJsonPath('totals.orders_count', 1);
    }

    public function test_sales_statistics_with_manual_sales_data(): void
    {
        // Arrange
        $sale = Sale::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'confirmed',
            'created_by' => $this->user->id,
        ]);

        \Illuminate\Support\Facades\DB::table('sale_items')->insert([
            'sale_id' => $sale->id,
            'product_name' => 'Test Product',
            'quantity' => 5,
            'unit_price' => 300.00,
            'subtotal' => 1500.00,
            'total' => 1500.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Act
        $response = $this->actingAs($this->user)
            ->getJson('/api/analytics/sales-statistics?sources=manual');

        // Assert
        $response->assertOk()
            ->assertJsonPath('totals.orders_count', 1)
            ->assertJsonPath('totals.quantity', 5);
    }

    public function test_sales_statistics_isolates_data_between_companies(): void
    {
        // Arrange: данные для другой компании
        $otherCompany = Company::factory()->create();
        $otherAccount = MarketplaceAccount::factory()->create([
            'company_id' => $otherCompany->id,
            'marketplace' => 'wb',
        ]);

        \Illuminate\Support\Facades\DB::table('wildberries_orders')->insert([
            'marketplace_account_id' => $otherAccount->id,
            'order_date' => '2026-03-15 10:00:00',
            'for_pay' => 10000.00,
            'is_cancel' => false,
            'is_return' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Act: запрос от текущего пользователя
        $response = $this->actingAs($this->user)
            ->getJson('/api/analytics/sales-statistics?sources=wb');

        // Assert — не видит данные другой компании
        $response->assertOk()
            ->assertJsonPath('totals.orders_count', 0)
            ->assertJsonPath('totals.revenue', 0);
    }

    public function test_sales_statistics_periods_structure_with_data(): void
    {
        // Arrange
        $account = MarketplaceAccount::factory()->create([
            'company_id' => $this->company->id,
            'marketplace' => 'wb',
        ]);

        \Illuminate\Support\Facades\DB::table('wildberries_orders')->insert([
            'marketplace_account_id' => $account->id,
            'order_date' => '2026-03-15 10:00:00',
            'for_pay' => 1500.00,
            'is_cancel' => false,
            'is_return' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Act
        $response = $this->actingAs($this->user)
            ->getJson('/api/analytics/sales-statistics?group_by=month&sources=wb');

        // Assert — проверяем структуру каждого периода
        $response->assertOk()
            ->assertJsonStructure([
                'periods' => [
                    [
                        'period_key',
                        'period_label',
                        'orders_count',
                        'revenue',
                        'quantity',
                    ],
                ],
            ]);
    }

    public function test_sales_statistics_by_source_structure_with_data(): void
    {
        // Arrange
        $account = MarketplaceAccount::factory()->create([
            'company_id' => $this->company->id,
            'marketplace' => 'wb',
        ]);

        \Illuminate\Support\Facades\DB::table('wildberries_orders')->insert([
            'marketplace_account_id' => $account->id,
            'order_date' => '2026-03-15 10:00:00',
            'for_pay' => 1500.00,
            'is_cancel' => false,
            'is_return' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Act
        $response = $this->actingAs($this->user)
            ->getJson('/api/analytics/sales-statistics?sources=wb');

        // Assert — by_source содержит wb с правильной структурой
        $bySource = $response->json('by_source');
        $this->assertNotEmpty($bySource);

        $firstPeriod = array_values($bySource)[0];
        $this->assertArrayHasKey('wb', $firstPeriod);
        $this->assertArrayHasKey('orders_count', $firstPeriod['wb']);
        $this->assertArrayHasKey('revenue', $firstPeriod['wb']);
        $this->assertArrayHasKey('quantity', $firstPeriod['wb']);
    }

    public function test_sales_statistics_cache_is_invalidated_by_different_params(): void
    {
        Cache::flush();

        // Первый запрос с wb
        $this->actingAs($this->user)
            ->getJson('/api/analytics/sales-statistics?group_by=month&sources=wb');

        // Второй запрос с ozon
        $this->actingAs($this->user)
            ->getJson('/api/analytics/sales-statistics?group_by=month&sources=ozon');

        $wbKey = "sales_statistics_{$this->company->id}_month_wb";
        $ozonKey = "sales_statistics_{$this->company->id}_month_ozon";

        $this->assertTrue(Cache::has($wbKey));
        $this->assertTrue(Cache::has($ozonKey));
    }
}
