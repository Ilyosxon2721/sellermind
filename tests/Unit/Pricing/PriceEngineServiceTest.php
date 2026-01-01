<?php

namespace Tests\Unit\Pricing;

use App\Models\Company;
use App\Models\Pricing\PricingScenario;
use App\Models\Pricing\PricingSkuOverride;
use App\Models\Warehouse\Sku;
use App\Services\Pricing\ChannelCostRuleService;
use App\Services\Pricing\PriceCostService;
use App\Services\Pricing\PriceEngineService;
use App\Services\Pricing\PricingOverridesService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PriceEngineServiceTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected PricingScenario $scenario;
    protected Sku $sku;

    protected function setUp(): void
    {
        parent::setUp();
        $this->company = Company::create(['name' => 'Co']);
        $productId = \App\Models\Product::create([
            'company_id' => $this->company->id,
            'name' => 'Test product',
            'article' => 'ART',
            'is_active' => true,
            'is_archived' => false,
        ])->id;
        $this->sku = Sku::create([
            'product_id' => $productId,
            'company_id' => $this->company->id,
            'sku_code' => 'SKU',
            'is_active' => true,
        ]);
        $this->scenario = PricingScenario::create([
            'company_id' => $this->company->id,
            'name' => 'Base',
            'target_margin_percent' => 0.2,
            'rounding_mode' => 'UP',
            'rounding_step' => 1,
        ]);
    }

    protected function engine(): PriceEngineService
    {
        return new PriceEngineService(
            new class extends PriceCostService {
                public function unitCost(int $companyId, int $skuId, ?string $date = null): array
                {
                    return ['cost' => 100, 'confidence' => 'HIGH', 'source' => 'test'];
                }
            },
            new PricingOverridesService(),
            new class extends ChannelCostRuleService {
                public function costs(string $channelCode, int $companyId): array
                {
                    return [
                        'commission_percent' => 0.1,
                        'commission_fixed' => 0,
                        'logistics_fixed' => 10,
                        'payment_fee_percent' => 0,
                        'other_percent' => 0,
                        'other_fixed' => 0,
                    ];
                }
            }
        );
    }

    public function test_linear_price_calculation(): void
    {
        $calc = $this->engine()->calculate($this->company->id, $this->scenario->id, 'UZUM', $this->sku->id);
        $this->assertNotNull($calc);
        $this->assertTrue($calc['recommended_price'] > $calc['min_price']);
    }

    public function test_rounding_up(): void
    {
        $scenario = $this->scenario;
        $scenario->rounding_step = 5;
        $scenario->save();
        $calc = $this->engine()->calculate($this->company->id, $scenario->id, 'UZUM', $this->sku->id);
        $this->assertEquals(0, fmod($calc['recommended_price'], 5));
    }

    public function test_sku_override_excludes(): void
    {
        PricingSkuOverride::create([
            'company_id' => $this->company->id,
            'scenario_id' => $this->scenario->id,
            'sku_id' => $this->sku->id,
            'is_excluded' => true,
        ]);
        $calc = $this->engine()->calculate($this->company->id, $this->scenario->id, 'UZUM', $this->sku->id);
        $this->assertNull($calc);
    }
}
