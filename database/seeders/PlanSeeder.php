<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Тарифные планы SellerMind AI
     *
     * Ценообразование основано на анализе рынка CIS/Узбекистан:
     * - MPStats: 4,990-19,990 RUB/мес
     * - Sellboard: 1,490-9,990 RUB/мес
     * - JetSeller: 1,500-14,990 RUB/мес
     * - RetailCRM: 0-1,500/user RUB/мес
     * - Средний SaaS в Узбекистане: 50,000-500,000 UZS/мес
     *
     * SellerMind — комплексная платформа (операции + аналитика + AI),
     * что оправдывает позиционирование в среднем/высшем ценовом сегменте.
     */
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Старт',
                'slug' => 'start',
                'description' => 'Для начинающих селлеров. Идеально для работы с одним маркетплейсом и освоения платформы.',
                'price' => 290000,
                'currency' => 'UZS',
                'billing_period' => 'monthly',
                'max_marketplace_accounts' => 1,
                'max_products' => 100,
                'max_orders_per_month' => 300,
                'max_users' => 1,
                'max_warehouses' => 1,
                'max_ai_requests' => 30,
                'data_retention_days' => 30,
                'has_api_access' => false,
                'has_priority_support' => false,
                'has_telegram_notifications' => true,
                'has_auto_pricing' => false,
                'has_analytics' => false,
                'allowed_marketplaces' => ['uzum'],
                'features' => [
                    'Синхронизация заказов и остатков',
                    'Управление товарами',
                    'Складской учёт (1 склад)',
                    'Telegram уведомления',
                    '30 AI-запросов в месяц',
                    'Email поддержка',
                ],
                'sort_order' => 1,
                'is_active' => true,
                'is_popular' => false,
            ],
            [
                'name' => 'Бизнес',
                'slug' => 'business',
                'description' => 'Для активных продавцов. Несколько маркетплейсов, аналитика и автоценообразование.',
                'price' => 890000,
                'currency' => 'UZS',
                'billing_period' => 'monthly',
                'max_marketplace_accounts' => 3,
                'max_products' => 1000,
                'max_orders_per_month' => 3000,
                'max_users' => 5,
                'max_warehouses' => 3,
                'max_ai_requests' => 200,
                'data_retention_days' => 90,
                'has_api_access' => true,
                'has_priority_support' => false,
                'has_telegram_notifications' => true,
                'has_auto_pricing' => true,
                'has_analytics' => true,
                'allowed_marketplaces' => ['uzum', 'wb', 'ozon'],
                'features' => [
                    'Всё из тарифа «Старт»',
                    '3 маркетплейса (Uzum, WB, Ozon)',
                    'Расширенная аналитика продаж',
                    'Автоценообразование',
                    'Smart-акции и промо',
                    'API доступ',
                    '200 AI-запросов в месяц',
                    'Массовые операции с товарами',
                    'AI-ответы на отзывы',
                ],
                'sort_order' => 2,
                'is_active' => true,
                'is_popular' => true,
            ],
            [
                'name' => 'Про',
                'slug' => 'pro',
                'description' => 'Для крупных продавцов. Все маркетплейсы, неограниченная аналитика и приоритетная поддержка.',
                'price' => 1990000,
                'currency' => 'UZS',
                'billing_period' => 'monthly',
                'max_marketplace_accounts' => 10,
                'max_products' => 10000,
                'max_orders_per_month' => 30000,
                'max_users' => 15,
                'max_warehouses' => 10,
                'max_ai_requests' => 1000,
                'data_retention_days' => 365,
                'has_api_access' => true,
                'has_priority_support' => true,
                'has_telegram_notifications' => true,
                'has_auto_pricing' => true,
                'has_analytics' => true,
                'allowed_marketplaces' => ['uzum', 'wb', 'ozon', 'yandex'],
                'features' => [
                    'Всё из тарифа «Бизнес»',
                    '4 маркетплейса включая Yandex Market',
                    'Мониторинг цен конкурентов (Uzum Analytics)',
                    'Приоритетная поддержка 24/7',
                    '1 000 AI-запросов в месяц',
                    'Годовое хранение данных',
                    'KPI и мотивация сотрудников',
                    'Расширенные отчёты и экспорт',
                    'Webhook-интеграции',
                ],
                'sort_order' => 3,
                'is_active' => true,
                'is_popular' => false,
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'description' => 'Для крупного бизнеса. Безлимитные возможности, персональный менеджер и SLA.',
                'price' => 4990000,
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
                    'Всё из тарифа «Про»',
                    'Безлимитные возможности',
                    'Персональный менеджер',
                    'Кастомные интеграции (1С, ERP)',
                    'SLA гарантии 99.9%',
                    'Обучение команды',
                    'Приоритетные обновления',
                    'Выделенная инфраструктура',
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
