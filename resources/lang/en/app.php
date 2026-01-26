<?php

return [
    // Settings page
    'settings' => [
        'title' => 'Settings',
        'subtitle' => 'Manage your account and notifications',
        'tabs' => [
            'profile' => 'Profile',
            'telegram' => 'Telegram Notifications',
            'security' => 'Security',
            'sync' => 'Synchronization',
            'currency' => 'Currency',
            'language' => 'Language',
            'navigation' => 'Navigation',
        ],
        'profile' => [
            'title' => 'Profile Information',
            'name' => 'Name',
            'email' => 'Email',
            'language' => 'Interface Language',
            'save' => 'Save Changes',
            'not_specified' => 'Not specified',
        ],
        'security' => [
            'title' => 'Change Password',
            'current_password' => 'Current Password',
            'new_password' => 'New Password',
            'confirm_password' => 'Confirm Password',
            'change_password' => 'Change Password',
            'update_password' => 'Update your password',
        ],
        'currency' => [
            'title' => 'Currency Rates',
            'description' => 'Set current exchange rates for cost calculations and reports. These rates are used across all sections of the system.',
            'display_currency' => 'Main Currency',
            'display_currency_description' => 'Select the currency for displaying amounts in reports and dashboard',
            'currencies' => [
                'UZS' => 'Uzbekistani Som',
                'RUB' => 'Russian Ruble',
                'USD' => 'US Dollar',
                'EUR' => 'Euro',
                'KZT' => 'Kazakhstani Tenge',
            ],
            'usd' => 'US Dollar (USD → UZS)',
            'rub' => 'Ruble (RUB → UZS)',
            'eur' => 'Euro (EUR → UZS)',
            'last_updated' => 'Last updated',
            'exchange_rates' => 'Exchange Rates',
        ],
        'sync' => [
            'title' => 'Stock Synchronization Settings',
            'description' => 'Manage automatic stock synchronization with marketplaces',
            'stock_sync_enabled' => 'Stock Synchronization',
            'stock_sync_description' => 'Automatically update stock on marketplaces',
            'auto_sync_on_link' => 'Auto-sync on Link',
            'auto_sync_on_link_description' => 'Sync stock immediately after linking a product',
            'auto_sync_on_change' => 'Auto-sync on Change',
            'auto_sync_on_change_description' => 'Sync stock when warehouse quantities change',
        ],
        'navigation' => [
            'title' => 'Navigation',
            'description' => 'Customize navigation bar position and appearance',
            'position' => 'Position',
            'position_left' => 'Left',
            'position_right' => 'Right',
            'position_top' => 'Top',
            'position_bottom' => 'Bottom',
            'collapse' => 'Collapse sidebar',
            'collapse_description' => 'Show only icons in sidebar',
            'more' => 'More',
        ],
        'telegram' => [
            'connect' => 'Connect',
            'configure' => 'Configure',
            'description' => 'Connect Telegram for notifications',
        ],
        'company' => [
            'title' => 'COMPANY',
            'current' => 'Current company',
            'not_selected' => 'Not selected',
        ],
        'notifications' => [
            'title' => 'NOTIFICATIONS',
        ],
    ],
    
    // Languages
    'languages' => [
        'ru' => 'Русский',
        'uz' => 'O\'zbekcha',
        'en' => 'English',
    ],
    
    // Common actions
    'actions' => [
        'save' => 'Save',
        'cancel' => 'Cancel',
        'logout' => 'Log Out',
        'logout_confirm' => 'Are you sure you want to log out?',
        'close' => 'Close',
        'edit' => 'Edit',
        'delete' => 'Delete',
        'confirm' => 'Confirm',
    ],
    
    // Messages
    'messages' => [
        'profile_updated' => 'Profile updated',
        'password_changed' => 'Password changed',
        'password_mismatch' => 'Passwords do not match',
        'password_too_short' => 'Password must be at least 8 characters',
        'currency_updated' => 'Currency rates updated',
        'settings_saved' => 'Settings saved',
        'error' => 'Error',
        'save_error' => 'Save error',
        'loading' => 'Loading...',
        'saving' => 'Saving...',
    ],
    
    // Navigation
    'nav' => [
        'dashboard' => 'Dashboard',
        'products' => 'Products',
        'orders' => 'Orders',
        'warehouse' => 'Warehouse',
        'analytics' => 'Analytics',
        'marketplace' => 'Marketplaces',
        'settings' => 'Settings',
        'finance' => 'Finance',
        'sales' => 'Sales',
    ],
    
    // User
    'user' => [
        'profile' => 'Profile',
        'settings' => 'Settings',
        'logout' => 'Log Out',
    ],
    
    // App info
    'app' => [
        'name' => 'SellerMind',
        'version' => 'v1.0.0',
        'copyright' => '© 2024 SellerMind',
    ],
];
