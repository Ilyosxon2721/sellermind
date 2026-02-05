<?php

return [
    // Navigation Groups
    'nav_groups' => [
        'users' => 'Users',
        'billing' => 'Billing',
        'finance' => 'Finance',
        'catalog' => 'Catalog',
        'warehouse' => 'Warehouse',
        'marketplaces' => 'Marketplaces',
        'ai_content' => 'AI & Content',
        'system' => 'System',
    ],

    // Resources
    'resources' => [
        // Users group
        'user' => [
            'label' => 'User',
            'plural' => 'Users',
        ],
        'company' => [
            'label' => 'Company',
            'plural' => 'Companies',
        ],

        // Billing group
        'plan' => [
            'label' => 'Plan',
            'plural' => 'Plans',
        ],
        'subscription' => [
            'label' => 'Subscription',
            'plural' => 'Subscriptions',
        ],

        // Finance group
        'sale' => [
            'label' => 'Sale',
            'plural' => 'Sales',
        ],
        'marketplace_payout' => [
            'label' => 'Payout',
            'plural' => 'Marketplace Payouts',
        ],

        // Catalog group
        'product' => [
            'label' => 'Product',
            'plural' => 'All Products',
        ],
        'product_category' => [
            'label' => 'Category',
            'plural' => 'Categories',
        ],

        // Warehouse group
        'warehouse' => [
            'label' => 'Warehouse',
            'plural' => 'Warehouses',
        ],
        'inventory' => [
            'label' => 'Inventory Count',
            'plural' => 'Inventory Counts',
        ],

        // Marketplaces group
        'marketplace_account' => [
            'label' => 'Marketplace Account',
            'plural' => 'Marketplace Accounts',
        ],
        'marketplace_sync_log' => [
            'label' => 'Sync Log',
            'plural' => 'Sync Logs',
        ],
        'marketplace_account_issue' => [
            'label' => 'Account Issue',
            'plural' => 'Account Issues',
        ],
        'marketplace_automation_rule' => [
            'label' => 'Automation Rule',
            'plural' => 'Automation Rules',
        ],

        // AI & Content group
        'agent' => [
            'label' => 'Agent',
            'plural' => 'AI Agents',
        ],
        'agent_task' => [
            'label' => 'Agent Task',
            'plural' => 'Agent Tasks',
        ],
        'generation_task' => [
            'label' => 'Generation Task',
            'plural' => 'Generation Tasks',
        ],
        'ai_usage_log' => [
            'label' => 'AI Log',
            'plural' => 'AI Usage Logs',
        ],

        // System group
        'global_option' => [
            'label' => 'Global Setting',
            'plural' => 'System Settings',
        ],
        'vpc_session' => [
            'label' => 'VPC Session',
            'plural' => 'VPC Sessions',
        ],
    ],

    // Form Sections
    'sections' => [
        'basic_info' => 'Basic Information',
        'address_contacts' => 'Address & Contacts',
        'system_settings' => 'System Settings',
        'subject' => 'Subject',
        'task_context' => 'Task Context',
        'status_control' => 'Status & Control',
        'timestamps' => 'Timestamps',
        'personal_data' => 'Personal Data',
        'settings_access' => 'Settings & Access',
        'description' => 'Description',
        'characteristics' => 'Characteristics',
        'package_dimensions' => 'Package Dimensions',
        'status' => 'Status',
        'order_details' => 'Order Details',
        'amount_payment' => 'Amount & Payment',
        'status_dates' => 'Status & Dates',
        'additional' => 'Additional',
        'session_metadata' => 'Session Metadata',
        'time_range' => 'Time Range',
        'result_data' => 'Result & Data',
        'general_info' => 'General Information',
        'period' => 'Period',
        'financial_metrics' => 'Financial Metrics',
        'raw_data' => 'Raw Data',
        'rule_logic' => 'Rule Logic',
        'params_conditions' => 'Parameters & Conditions',
        'inventory_params' => 'Inventory Parameters',
        'status_type' => 'Status & Type',
        'totals_calculated' => 'Totals (Calculated)',
        'apply_results' => 'Apply Results',
        'issue_object' => 'Issue Object',
        'error_details' => 'Error Details',
        'technical_data' => 'Technical Data',
        'status_frequency' => 'Status & Frequency',
        'data_progress' => 'Data & Progress',
        'setting' => 'Setting',
        'display' => 'Display',
        'task_description' => 'Task Description',
        'execution_data' => 'Execution Data',
        'behavior_config' => 'Behavior Configuration',
        'resource_consumption' => 'Resource Consumption',
    ],

    // Stats Widget
    'stats' => [
        'sales' => 'Sales',
        'users' => 'Users',
        'active_subscriptions' => 'Active Subscriptions',
        'connections' => 'Connections',
        'total_revenue' => 'Total Revenue',
        'total_in_system' => 'Total in System',
        'paid_accounts' => 'Paid Accounts',
        'marketplaces' => 'Marketplaces',
    ],
];
