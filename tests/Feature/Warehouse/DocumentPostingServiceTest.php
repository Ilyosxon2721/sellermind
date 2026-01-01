<?php

namespace Tests\Feature\Warehouse;

use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\Product;
use App\Models\Warehouse\InventoryDocument;
use App\Models\Warehouse\InventoryDocumentLine;
use App\Models\Warehouse\Sku;
use App\Models\Warehouse\Unit;
use App\Models\Warehouse\Warehouse;
use App\Services\Warehouse\DocumentPostingService;
use App\Services\Warehouse\StockBalanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentPostingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // ensure base unit
        Unit::create(['code' => 'pcs', 'name' => 'pcs']);
    }

    public function test_in_document_increases_on_hand(): void
    {
        $company = Company::create(['name' => 'Test Co']);
        CompanySetting::create(['company_id' => $company->id]);
        $warehouse = Warehouse::create(['company_id' => $company->id, 'name' => 'Main', 'is_default' => true]);
        $product = Product::create([
            'company_id' => $company->id,
            'name' => 'Test product',
            'article' => 'ART-1',
            'is_active' => true,
            'is_archived' => false,
        ]);
        $sku = Sku::create([
            'product_id' => $product->id,
            'company_id' => $company->id,
            'sku_code' => 'SKU-1',
            'is_active' => true,
        ]);

        $doc = InventoryDocument::create([
            'company_id' => $company->id,
            'doc_no' => 'IN-1',
            'type' => InventoryDocument::TYPE_IN,
            'status' => InventoryDocument::STATUS_DRAFT,
            'warehouse_id' => $warehouse->id,
        ]);

        InventoryDocumentLine::create([
            'document_id' => $doc->id,
            'sku_id' => $sku->id,
            'qty' => 5,
            'unit_id' => Unit::where('code', 'pcs')->first()->id,
        ]);

        app(DocumentPostingService::class)->post($doc->id, $company->id, null);

        $doc->refresh();
        $this->assertSame(InventoryDocument::STATUS_POSTED, $doc->status);

        $balance = app(StockBalanceService::class)->balance($company->id, $warehouse->id, $sku->id);
        $this->assertEquals(5, $balance['on_hand']);
        $this->assertEquals(5, $balance['available']);
    }

    public function test_out_document_blocks_negative_stock_when_disallowed(): void
    {
        $this->expectException(\RuntimeException::class);

        $company = Company::create(['name' => 'Test Co']);
        CompanySetting::create(['company_id' => $company->id, 'allow_negative_stock' => false]);
        $warehouse = Warehouse::create(['company_id' => $company->id, 'name' => 'Main', 'is_default' => true]);
        $product = Product::create([
            'company_id' => $company->id,
            'name' => 'Test product',
            'article' => 'ART-2',
            'is_active' => true,
            'is_archived' => false,
        ]);
        $sku = Sku::create([
            'product_id' => $product->id,
            'company_id' => $company->id,
            'sku_code' => 'SKU-NEG',
            'is_active' => true,
        ]);

        $doc = InventoryDocument::create([
            'company_id' => $company->id,
            'doc_no' => 'OUT-1',
            'type' => InventoryDocument::TYPE_OUT,
            'status' => InventoryDocument::STATUS_DRAFT,
            'warehouse_id' => $warehouse->id,
        ]);

        InventoryDocumentLine::create([
            'document_id' => $doc->id,
            'sku_id' => $sku->id,
            'qty' => 2,
            'unit_id' => Unit::where('code', 'pcs')->first()->id,
        ]);

        app(DocumentPostingService::class)->post($doc->id, $company->id, null);
    }
}
