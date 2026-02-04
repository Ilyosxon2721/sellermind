<?php

namespace Database\Seeders;

use App\Models\AP\Supplier;
use App\Models\AP\SupplierInvoice;
use App\Models\AP\SupplierPayment;
use App\Models\Company;
use App\Models\Pricing\PricingScenario;
use App\Models\Product;
use App\Models\ProductDescription;
use App\Models\User;
use App\Models\UserCompanyRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create demo user
        $user = User::firstOrCreate(
            ['email' => 'demo@sellermind.ai'],
            [
                'name' => 'Demo User',
                'password' => Hash::make('password'),
                'locale' => 'ru',
            ]
        );

        // Create demo company
        $company = Company::firstOrCreate(
            ['slug' => 'demo-store'],
            ['name' => 'Demo Store']
        );

        // Assign user as owner
        UserCompanyRole::firstOrCreate(
            ['user_id' => $user->id, 'company_id' => $company->id],
            ['role' => 'owner']
        );

        // Create sample products
        $products = [
            ['name' => 'Халат мужской махровый', 'article' => 'HAL-001', 'brand_name' => 'HomeStyle'],
            ['name' => 'Органайзер для хранения 66L', 'article' => 'ORG-002', 'brand_name' => 'StoreMaster'],
            ['name' => 'Набор полотенец хлопок', 'article' => 'TOW-003', 'brand_name' => 'SoftTouch'],
        ];

        foreach ($products as $productData) {
            $product = Product::firstOrCreate(
                ['company_id' => $company->id, 'article' => $productData['article']],
                [
                    'name' => $productData['name'],
                    'brand_name' => $productData['brand_name'],
                    'is_active' => true,
                    'is_archived' => false,
                ]
            );

            ProductDescription::firstOrCreate(
                ['product_id' => $product->id, 'marketplace' => 'universal', 'language' => 'ru'],
                [
                    'title' => $productData['name'].' — качество и комфорт',
                    'short_description' => 'Отличное качество по доступной цене',
                    'full_description' => 'Подробное описание товара будет сгенерировано ИИ.',
                    'bullets' => ['Высокое качество', 'Доступная цена', 'Быстрая доставка'],
                    'attributes' => ['Материал' => 'Хлопок', 'Страна' => 'Узбекистан'],
                    'keywords' => ['качество', 'комфорт', 'дом'],
                    'version' => 1,
                ]
            );
        }

        $this->command->info('Demo data created successfully!');
        $this->command->info('Login: demo@sellermind.ai / password');

        // Warehouse core defaults (units, channels, main warehouse)
        $this->call(WarehouseCoreSeeder::class);

        // Plans and pricing tiers
        $this->call(PlanSeeder::class);

        // AP demo supplier + invoice + payment draft
        $supplier = Supplier::create([
            'company_id' => $company->id,
            'name' => 'Demo Supplier',
            'currency_code' => 'USD',
        ]);

        $invoice = SupplierInvoice::create([
            'company_id' => $company->id,
            'supplier_id' => $supplier->id,
            'invoice_no' => 'INV-001',
            'status' => SupplierInvoice::STATUS_DRAFT,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'amount_total' => 1000,
            'amount_subtotal' => 1000,
            'amount_outstanding' => 1000,
        ]);

        SupplierPayment::create([
            'company_id' => $company->id,
            'supplier_id' => $supplier->id,
            'payment_no' => 'PAY-001',
            'status' => SupplierPayment::STATUS_DRAFT,
            'paid_at' => now(),
            'amount_total' => 500,
            'currency_code' => 'USD',
            'method' => 'BANK',
        ]);

        // Pricing scenario demo
        PricingScenario::create([
            'company_id' => $company->id,
            'name' => 'Base',
            'description' => 'Базовый сценарий 30% маржа, 5% промо',
            'target_margin_percent' => 0.30,
            'promo_reserve_percent' => 0.05,
            'tax_mode' => 'VAT_INCLUDED',
            'vat_percent' => 12,
            'rounding_mode' => 'UP',
            'rounding_step' => 1000,
            'is_default' => true,
        ]);

        // Autopricing demo policy + rules
        $policyId = \App\Models\Autopricing\AutopricingPolicy::create([
            'company_id' => $company->id,
            'name' => 'Base Uzum',
            'channel_code' => 'UZUM',
            'scenario_id' => 1,
            'mode' => 'SUGGEST_ONLY',
            'max_delta_percent' => 0.10,
            'max_changes_per_day' => 50,
        ])->id;

        \App\Models\Autopricing\AutopricingRule::insert([
            [
                'company_id' => $company->id,
                'policy_id' => $policyId,
                'scope_type' => 'GLOBAL',
                'rule_type' => 'TARGET_MARGIN',
                'params_json' => json_encode(['use' => 'RECOMMENDED']),
                'priority' => 100,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'company_id' => $company->id,
                'policy_id' => $policyId,
                'scope_type' => 'GLOBAL',
                'rule_type' => 'STOCK_SCARCITY_UP',
                'params_json' => json_encode(['available_lt' => 10, 'increase_percent' => 0.05]),
                'priority' => 110,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'company_id' => $company->id,
                'policy_id' => $policyId,
                'scope_type' => 'GLOBAL',
                'rule_type' => 'STOCK_EXCESS_DOWN',
                'params_json' => json_encode(['available_gt' => 300, 'decrease_percent' => 0.05, 'floor' => 'MIN_PRICE']),
                'priority' => 120,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
