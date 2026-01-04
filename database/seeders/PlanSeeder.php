<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Старт',
                'slug' => 'start',
                'description' => 'Для начинающих селлеров. Идеально для тестирования платформы и работы с одним маркетплейсом.',
                'price' => 500000,
                'currency' => 'UZS',
                'billing_period' => 'monthly',
                'max_marketplace_accounts' => 1,
                'max_products' => 200,
                'max_orders_per_month' => 500,
                'max_users' => 2,
                'max_warehouses' => 1,
                'max_ai_requests' => 50,
                'data_retention_days' => 30,
                'has_api_access' => false,
                'has_priority_support' => false,
                'has_telegram_notifications' => true,
                'has_auto_pricing' => false,
                'has_analytics' => true,
                'allowed_marketplaces' => ['uzum'],
                'features' => [
                    'Базовая синхронизация заказов',
                    'Управление товарами',
                    'Складской учёт',
                    'Email поддержка',
                ],
                'sort_order' => 1,
                'is_active' => true,
                'is_popular' => false,
            ],
            [
                'name' => 'Бизнес',
                'slug' => 'business',
                'description' => 'Для активных продавцов. Расширенные возможности и несколько маркетплейсов.',
                'price' => 1500000,
                'currency' => 'UZS',
                'billing_period' => 'monthly',
                'max_marketplace_accounts' => 5,
                'max_products' => 2000,
                'max_orders_per_month' => 5000,
                'max_users' => 10,
                'max_warehouses' => 5,
                'max_ai_requests' => 300,
                'data_retention_days' => 90,
                'has_api_access' => true,
                'has_priority_support' => false,
                'has_telegram_notifications' => true,
                'has_auto_pricing' => true,
                'has_analytics' => true,
                'allowed_marketplaces' => ['uzum', 'wb', 'ozon'],
                'features' => [
                    'Всё из тарифа "Старт"',
                    '3 маркетплейса (Uzum, WB, Ozon)',
                    'Автоценообразование',
                    'API доступ',
                    'Расширенная аналитика',
                    'Telegram уведомления',
                ],
                'sort_order' => 2,
                'is_active' => true,
                'is_popular' => true,
            ],
            [
                'name' => 'Про',
                'slug' => 'pro',
                'description' => 'Для крупных продавцов. Все маркетплейсы и премиум возможности.',
                'price' => 3500000,
                'currency' => 'UZS',
                'billing_period' => 'monthly',
                'max_marketplace_accounts' => 15,
                'max_products' => 20000,
                'max_orders_per_month' => 50000,
                'max_users' => 30,
                'max_warehouses' => 20,
                'max_ai_requests' => 1000,
                'data_retention_days' => 365,
                'has_api_access' => true,
                'has_priority_support' => true,
                'has_telegram_notifications' => true,
                'has_auto_pricing' => true,
                'has_analytics' => true,
                'allowed_marketplaces' => ['uzum', 'wb', 'ozon', 'yandex'],
                'features' => [
                    'Всё из тарифа "Бизнес"',
                    '4 маркетплейса включая Yandex Market',
                    'Приоритетная поддержка 24/7',
                    'AI-помощник без ограничений',
                    'Годовое хранение данных',
                    'Индивидуальные отчёты',
                ],
                'sort_order' => 3,
                'is_active' => true,
                'is_popular' => false,
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'description' => 'Для крупного бизнеса. Индивидуальные условия и выделенная поддержка.',
                'price' => 7000000,
                'currency' => 'UZS',
                'billing_period' => 'monthly',
                'max_marketplace_accounts' => 999,
                'max_products' => 999999,
                'max_orders_per_month' => 999999,
                'max_users' => 999,
                'max_warehouses' => 999,
                'max_ai_requests' => 99999,
                'data_retention_days' => 9999,
                'has_api_access' => true,
                'has_priority_support' => true,
                'has_telegram_notifications' => true,
                'has_auto_pricing' => true,
                'has_analytics' => true,
                'allowed_marketplaces' => ['uzum', 'wb', 'ozon', 'yandex'],
                'features' => [
                    'Всё из тарифа "Про"',
                    'Безлимитные возможности',
                    'Личный менеджер',
                    'Кастомные интеграции',
                    'SLA гарантии',
                    'Обучение команды',
                    'Приоритетные обновления',
                ],
                'sort_order' => 4,
                'is_active' => true,
                'is_popular' => false,
            ],
        ];

        foreach ($plans as $planData) {
            Plan::updateOrCreate(
                ['slug' => $planData['slug']],
                $planData
            );
        }
    }
}
