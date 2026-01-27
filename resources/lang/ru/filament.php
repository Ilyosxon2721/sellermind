<?php

return [
    // Navigation Groups
    'nav_groups' => [
        'users' => 'Пользователи',
        'billing' => 'Биллинг',
        'finance' => 'Финансы',
        'catalog' => 'Каталог',
        'warehouse' => 'Склад',
        'marketplaces' => 'Маркетплейсы',
        'ai_content' => 'ИИ и Контент',
        'system' => 'Система',
    ],

    // Resources
    'resources' => [
        // Users group
        'user' => [
            'label' => 'Пользователь',
            'plural' => 'Пользователи',
        ],
        'company' => [
            'label' => 'Компания',
            'plural' => 'Компании',
        ],
        
        // Billing group
        'plan' => [
            'label' => 'Тариф',
            'plural' => 'Тарифы',
        ],
        'subscription' => [
            'label' => 'Подписка',
            'plural' => 'Подписки',
        ],
        
        // Finance group
        'sale' => [
            'label' => 'Продажа',
            'plural' => 'Продажи',
        ],
        'marketplace_payout' => [
            'label' => 'Выплата',
            'plural' => 'Выплаты маркетплейсов',
        ],
        
        // Catalog group
        'product' => [
            'label' => 'Товар',
            'plural' => 'Все товары',
        ],
        'product_category' => [
            'label' => 'Категория',
            'plural' => 'Категории',
        ],
        
        // Warehouse group
        'warehouse' => [
            'label' => 'Склад',
            'plural' => 'Склады',
        ],
        'inventory' => [
            'label' => 'Инвентаризация',
            'plural' => 'Инвентаризация',
        ],
        'stock_reservation' => [
            'label' => 'Резерв',
            'plural' => 'Резервы',
        ],
        'inventory_document' => [
            'label' => 'Складской документ',
            'plural' => 'Складские документы',
        ],

        // Marketplaces group
        'marketplace_account' => [
            'label' => 'Аккаунт маркетплейса',
            'plural' => 'Аккаунты маркетплейсов',
        ],
        'marketplace_sync_log' => [
            'label' => 'Лог синхронизации',
            'plural' => 'Логи синхронизации',
        ],
        'marketplace_account_issue' => [
            'label' => 'Проблема аккаунта',
            'plural' => 'Проблемы аккаунтов',
        ],
        'marketplace_automation_rule' => [
            'label' => 'Правило автоматизации',
            'plural' => 'Автоматизация',
        ],
        
        // AI & Content group
        'agent' => [
            'label' => 'Агент',
            'plural' => 'ИИ-Агенты',
        ],
        'agent_task' => [
            'label' => 'Задача агента',
            'plural' => 'Задачи агентов',
        ],
        'generation_task' => [
            'label' => 'Задача генерации',
            'plural' => 'Задачи генерации',
        ],
        'ai_usage_log' => [
            'label' => 'Лог ИИ',
            'plural' => 'Использование ИИ',
        ],
        
        // System group
        'global_option' => [
            'label' => 'Глобальная настройка',
            'plural' => 'Настройки системы',
        ],
        'vpc_session' => [
            'label' => 'VPC сессия',
            'plural' => 'VPC сессии',
        ],
    ],

    // Form Sections
    'sections' => [
        'basic_info' => 'Основная информация',
        'address_contacts' => 'Адрес и Контакты',
        'system_settings' => 'Системные настройки',
        'subject' => 'Субъект',
        'task_context' => 'Контекст задачи',
        'status_control' => 'Статус и Управление',
        'timestamps' => 'Временные метки',
        'personal_data' => 'Личные данные',
        'settings_access' => 'Настройки и доступ',
        'description' => 'Описание',
        'characteristics' => 'Характеристики',
        'package_dimensions' => 'Габариты упаковки',
        'status' => 'Статус',
        'order_details' => 'Детали заказа',
        'amount_payment' => 'Сумма и Оплата',
        'status_dates' => 'Статус и Даты',
        'additional' => 'Дополнительно',
        'session_metadata' => 'Метаданные сессии',
        'time_range' => 'Временные рамки',
        'result_data' => 'Результат и Данные',
        'general_info' => 'Общая информация',
        'period' => 'Период',
        'financial_metrics' => 'Финансовые показатели',
        'raw_data' => 'Сырые данные',
        'rule_logic' => 'Логика правила',
        'params_conditions' => 'Параметры и Условия',
        'inventory_params' => 'Параметры инвентаризации',
        'status_type' => 'Статус и Тип',
        'totals_calculated' => 'Итоги (расчетные данные)',
        'apply_results' => 'Применение результатов',
        'issue_object' => 'Объект проблемы',
        'error_details' => 'Детали ошибки',
        'technical_data' => 'Технические данные',
        'status_frequency' => 'Статус и Частота',
        'data_progress' => 'Данные и прогресс',
        'setting' => 'Настройка',
        'display' => 'Отображение',
        'task_description' => 'Описание задачи',
        'execution_data' => 'Данные исполнения',
        'behavior_config' => 'Конфигурация поведения',
        'resource_consumption' => 'Потребление ресурсов',
    ],

    // Stats Widget
    'stats' => [
        'sales' => 'Продажи',
        'users' => 'Пользователи',
        'active_subscriptions' => 'Активные подписки',
        'connections' => 'Подключения',
        'total_revenue' => 'Общая выручка',
        'total_in_system' => 'Всего в системе',
        'paid_accounts' => 'Платные аккаунты',
        'marketplaces' => 'Маркетплейсы',
    ],

    // Pages
    'pages' => [
        'warehouse_dashboard' => [
            'label' => 'Дашборд склада',
            'title' => 'Дашборд склада',
            'description' => 'Обзор складских остатков, движений и резервов',
        ],
    ],
];
