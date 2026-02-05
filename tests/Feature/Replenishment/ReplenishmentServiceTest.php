<?php

namespace Tests\Feature\Replenishment;

use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\Product;
use App\Models\Warehouse\InventoryDocument;
use App\Models\Warehouse\InventoryDocumentLine;
use App\Models\Warehouse\Sku;
use App\Models\Warehouse\Unit;
use App\Models\Warehouse\Warehouse;
use App\Services\Replenishment\DemandService;
use App\Services\Replenishment\ReplenishmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReplenishmentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected Warehouse $warehouse;

    protected Sku $sku;

    protected function setUp(): void
    {
        parent::setUp();
        Unit::create(['code' => 'pcs', 'name' => 'pcs']);
        $this->company = Company::create(['name' => 'TestCo']);
        CompanySetting::create(['company_id' => $this->company->id]);
        $this->warehouse = Warehouse::create(['company_id' => $this->company->id, 'name' => 'Main', 'is_default' => true]);
        $product = Product::create([
            'company_id' => $this->company->id,
            'name' => 'P',
            'article' => 'A',
            'is_active' => true,
            'is_archived' => false,
        ]);
        $this->sku = Sku::create([
            'product_id' => $product->id,
            'company_id' => $this->company->id,
            'sku_code' => 'SKU1',
            'is_active' => true,
        ]);
    }

    public function test_avg_daily_demand_from_out(): void
    {
        $this->createOut(10);
        $avg = app(DemandService::class)->avgDailyDemand($this->company->id, $this->warehouse->id, $this->sku->id, 10);
        $this->assertEquals(1.0, $avg);
    }

    public function test_rop_reorder_positive(): void
    {
        $this->createOut(5);
        $setting = \App\Models\Replenishment\ReplenishmentSetting::create([
            'company_id' => $this->company->id,
            'warehouse_id' => $this->warehouse->id,
            'sku_id' => $this->sku->id,
            'policy' => 'ROP',
            'lead_time_days' => 5,
            'safety_stock' => 2,
            'demand_window_days' => 5,
        ]);

        $result = app(ReplenishmentService::class)->calculate($this->company->id, $this->warehouse->id, $this->sku->id);
        $this->assertNotNull($result);
        $this->assertTrue($result['reorder_qty'] > 0);
    }

    public function test_min_max(): void
    {
        $setting = \App\Models\Replenishment\ReplenishmentSetting::create([
            'company_id' => $this->company->id,
            'warehouse_id' => $this->warehouse->id,
            'sku_id' => $this->sku->id,
            'policy' => 'MIN_MAX',
            'min_qty' => 10,
            'max_qty' => 20,
        ]);
        $result = app(ReplenishmentService::class)->calculateFromSetting($setting, [
            'on_hand' => 8,
            'reserved' => 0,
            'available' => 8,
        ], 0);
        $this->assertEquals(12, $result['reorder_qty']);
    }

    public function test_rounding_step(): void
    {
        $setting = \App\Models\Replenishment\ReplenishmentSetting::create([
            'company_id' => $this->company->id,
            'warehouse_id' => $this->warehouse->id,
            'sku_id' => $this->sku->id,
            'policy' => 'ROP',
            'lead_time_days' => 1,
            'safety_stock' => 0,
            'rounding_step' => 5,
        ]);
        $result = app(ReplenishmentService::class)->calculateFromSetting($setting, [
            'on_hand' => 0,
            'reserved' => 0,
            'available' => 0,
        ], 3); // target 3
        $this->assertEquals(5, $result['reorder_qty']);
    }

    public function test_risk_high_when_cover_lt_leadtime(): void
    {
        $setting = \App\Models\Replenishment\ReplenishmentSetting::create([
            'company_id' => $this->company->id,
            'warehouse_id' => $this->warehouse->id,
            'sku_id' => $this->sku->id,
            'policy' => 'ROP',
            'lead_time_days' => 5,
        ]);
        $result = app(ReplenishmentService::class)->calculateFromSetting($setting, [
            'on_hand' => 0,
            'reserved' => 0,
            'available' => 2,
        ], 1); // days cover =2
        $this->assertEquals('HIGH', $result['risk_level']);
    }

    protected function createOut(float $qty): void
    {
        $doc = InventoryDocument::create([
            'company_id' => $this->company->id,
            'doc_no' => 'OUT-'.uniqid(),
            'type' => InventoryDocument::TYPE_OUT,
            'status' => InventoryDocument::STATUS_DRAFT,
            'warehouse_id' => $this->warehouse->id,
        ]);
        InventoryDocumentLine::create([
            'document_id' => $doc->id,
            'sku_id' => $this->sku->id,
            'qty' => $qty,
            'unit_id' => Unit::where('code', 'pcs')->first()->id,
        ]);
        // ledger entry negative
        \App\Models\Warehouse\StockLedger::create([
            'company_id' => $this->company->id,
            'occurred_at' => now()->subDays(1),
            'warehouse_id' => $this->warehouse->id,
            'sku_id' => $this->sku->id,
            'qty_delta' => -$qty,
            'cost_delta' => 0,
            'document_id' => $doc->id,
        ]);
    }
}
