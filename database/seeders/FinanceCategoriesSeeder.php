<?php

namespace Database\Seeders;

use App\Models\Finance\FinanceCategory;
use Illuminate\Database\Seeder;

class FinanceCategoriesSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            // === РАСХОДЫ ===

            // Расходы компании
            [
                'code' => 'COMPANY_EXPENSES',
                'name' => 'Расходы компании',
                'type' => 'expense',
                'is_system' => true,
                'sort_order' => 100,
                'children' => [
                    ['code' => 'COMPANY_RENT', 'name' => 'Аренда офиса/склада', 'tax_category' => 'rent'],
                    ['code' => 'COMPANY_UTILITIES', 'name' => 'Коммунальные услуги', 'tax_category' => 'utilities'],
                    ['code' => 'COMPANY_INTERNET', 'name' => 'Интернет и связь', 'tax_category' => 'communication'],
                    ['code' => 'COMPANY_OFFICE', 'name' => 'Офисные расходы', 'tax_category' => 'office'],
                    ['code' => 'COMPANY_EQUIPMENT', 'name' => 'Оборудование', 'tax_category' => 'equipment'],
                    ['code' => 'COMPANY_SOFTWARE', 'name' => 'Программное обеспечение', 'tax_category' => 'software'],
                    ['code' => 'COMPANY_OTHER', 'name' => 'Прочие расходы компании', 'tax_category' => 'other'],
                ],
            ],

            // Логистика
            [
                'code' => 'LOGISTICS',
                'name' => 'Логистика',
                'type' => 'expense',
                'is_system' => true,
                'sort_order' => 200,
                'children' => [
                    ['code' => 'LOGISTICS_DELIVERY', 'name' => 'Доставка', 'tax_category' => 'logistics'],
                    ['code' => 'LOGISTICS_PACKAGING', 'name' => 'Упаковка', 'tax_category' => 'packaging'],
                    ['code' => 'LOGISTICS_CUSTOMS', 'name' => 'Таможенные расходы', 'tax_category' => 'customs'],
                    ['code' => 'LOGISTICS_STORAGE', 'name' => 'Хранение', 'tax_category' => 'storage'],
                ],
            ],

            // Расходы маркетплейсов
            [
                'code' => 'MARKETPLACE_FEES',
                'name' => 'Расходы маркетплейсов',
                'type' => 'expense',
                'is_system' => true,
                'sort_order' => 300,
                'children' => [
                    ['code' => 'MP_COMMISSION', 'name' => 'Комиссия маркетплейса', 'tax_category' => 'mp_commission'],
                    ['code' => 'MP_LOGISTICS', 'name' => 'Логистика маркетплейса', 'tax_category' => 'mp_logistics'],
                    ['code' => 'MP_STORAGE', 'name' => 'Хранение на складе МП', 'tax_category' => 'mp_storage'],
                    ['code' => 'MP_ADS', 'name' => 'Реклама на маркетплейсе', 'tax_category' => 'mp_ads'],
                    ['code' => 'MP_PENALTIES', 'name' => 'Штрафы маркетплейса', 'tax_category' => 'mp_penalties'],
                    ['code' => 'MP_RETURNS', 'name' => 'Возвраты', 'tax_category' => 'mp_returns'],
                    ['code' => 'MP_OTHER', 'name' => 'Прочие расходы МП', 'tax_category' => 'mp_other'],
                ],
            ],

            // Зарплата и кадры
            [
                'code' => 'PAYROLL',
                'name' => 'Зарплата и кадры',
                'type' => 'expense',
                'is_system' => true,
                'sort_order' => 400,
                'children' => [
                    ['code' => 'PAYROLL_SALARY', 'name' => 'Заработная плата', 'tax_category' => 'salary'],
                    ['code' => 'PAYROLL_BONUS', 'name' => 'Премии и бонусы', 'tax_category' => 'bonus'],
                    ['code' => 'PAYROLL_SOCIAL', 'name' => 'Социальные взносы', 'tax_category' => 'social'],
                    ['code' => 'PAYROLL_VACATION', 'name' => 'Отпускные', 'tax_category' => 'vacation'],
                ],
            ],

            // Налоги
            [
                'code' => 'TAXES',
                'name' => 'Налоги',
                'type' => 'expense',
                'is_system' => true,
                'sort_order' => 500,
                'children' => [
                    ['code' => 'TAX_INCOME', 'name' => 'Налог на прибыль', 'tax_category' => 'income_tax'],
                    ['code' => 'TAX_VAT', 'name' => 'НДС', 'tax_category' => 'vat'],
                    ['code' => 'TAX_SOCIAL', 'name' => 'Социальный налог', 'tax_category' => 'social_tax'],
                    ['code' => 'TAX_SIMPLIFIED', 'name' => 'Упрощёнка (единый налог)', 'tax_category' => 'simplified'],
                    ['code' => 'TAX_OTHER', 'name' => 'Прочие налоги', 'tax_category' => 'other_tax'],
                ],
            ],

            // Закупки товаров
            [
                'code' => 'PURCHASES',
                'name' => 'Закупки товаров',
                'type' => 'expense',
                'is_system' => true,
                'sort_order' => 600,
                'children' => [
                    ['code' => 'PURCHASE_GOODS', 'name' => 'Закупка товаров', 'tax_category' => 'goods'],
                    ['code' => 'PURCHASE_MATERIALS', 'name' => 'Материалы', 'tax_category' => 'materials'],
                ],
            ],

            // Финансовые расходы
            [
                'code' => 'FINANCIAL_EXPENSES',
                'name' => 'Финансовые расходы',
                'type' => 'expense',
                'is_system' => true,
                'sort_order' => 700,
                'children' => [
                    ['code' => 'FIN_BANK_FEES', 'name' => 'Банковские комиссии', 'tax_category' => 'bank_fees'],
                    ['code' => 'FIN_INTEREST', 'name' => 'Проценты по кредитам', 'tax_category' => 'interest'],
                    ['code' => 'FIN_CURRENCY_LOSS', 'name' => 'Курсовые убытки', 'tax_category' => 'currency_loss'],
                ],
            ],

            // === ДОХОДЫ ===

            // Продажи
            [
                'code' => 'SALES',
                'name' => 'Продажи',
                'type' => 'income',
                'is_system' => true,
                'sort_order' => 100,
                'children' => [
                    ['code' => 'SALES_MARKETPLACE', 'name' => 'Продажи на маркетплейсах', 'tax_category' => 'sales_mp'],
                    ['code' => 'SALES_DIRECT', 'name' => 'Прямые продажи', 'tax_category' => 'sales_direct'],
                    ['code' => 'SALES_WHOLESALE', 'name' => 'Оптовые продажи', 'tax_category' => 'sales_wholesale'],
                ],
            ],

            // Прочие доходы
            [
                'code' => 'OTHER_INCOME',
                'name' => 'Прочие доходы',
                'type' => 'income',
                'is_system' => true,
                'sort_order' => 200,
                'children' => [
                    ['code' => 'INCOME_INTEREST', 'name' => 'Проценты по депозитам', 'tax_category' => 'interest_income'],
                    ['code' => 'INCOME_CURRENCY_GAIN', 'name' => 'Курсовые доходы', 'tax_category' => 'currency_gain'],
                    ['code' => 'INCOME_COMPENSATION', 'name' => 'Компенсации', 'tax_category' => 'compensation'],
                    ['code' => 'INCOME_OTHER', 'name' => 'Прочие доходы', 'tax_category' => 'other_income'],
                ],
            ],
        ];

        foreach ($categories as $categoryData) {
            $this->createCategory($categoryData);
        }
    }

    private function createCategory(array $data, ?int $parentId = null): void
    {
        $children = $data['children'] ?? [];
        unset($data['children']);

        $category = FinanceCategory::updateOrCreate(
            ['code' => $data['code'], 'company_id' => null],
            array_merge($data, [
                'company_id' => null,
                'parent_id' => $parentId,
                'is_system' => true,
                'is_active' => true,
            ])
        );

        $sortOrder = 1;
        foreach ($children as $childData) {
            $childData['type'] = $data['type'];
            $childData['is_system'] = true;
            $childData['sort_order'] = $sortOrder++;
            $this->createCategory($childData, $category->id);
        }
    }
}
