<?php

return [
    // Settings page
    'settings' => [
        'title' => 'Настройки',
        'subtitle' => 'Управление аккаунтом и уведомлениями',
        'tabs' => [
            'profile' => 'Профиль',
            'telegram' => 'Telegram Уведомления',
            'security' => 'Безопасность',
            'sync' => 'Синхронизация',
            'currency' => 'Валюты',
            'language' => 'Язык',
        ],
        'profile' => [
            'title' => 'Информация о профиле',
            'name' => 'Имя',
            'email' => 'Email',
            'language' => 'Язык интерфейса',
            'save' => 'Сохранить изменения',
            'not_specified' => 'Не указано',
        ],
        'security' => [
            'title' => 'Изменить пароль',
            'current_password' => 'Текущий пароль',
            'new_password' => 'Новый пароль',
            'confirm_password' => 'Подтвердите пароль',
            'change_password' => 'Изменить пароль',
            'update_password' => 'Обновите ваш пароль',
        ],
        'currency' => [
            'title' => 'Курсы валют',
            'description' => 'Установите текущие курсы валют для расчёта себестоимости и отчётов. Эти курсы используются во всех разделах системы.',
            'usd' => 'Доллар США (USD → UZS)',
            'rub' => 'Рубль (RUB → UZS)',
            'eur' => 'Евро (EUR → UZS)',
            'last_updated' => 'Последнее обновление',
        ],
        'sync' => [
            'title' => 'Настройки синхронизации остатков',
            'description' => 'Управляйте автоматической синхронизацией остатков с маркетплейсами',
            'stock_sync_enabled' => 'Синхронизация остатков',
            'stock_sync_description' => 'Автоматически обновлять остатки на маркетплейсах',
            'auto_sync_on_link' => 'Автосинхронизация при привязке',
            'auto_sync_on_link_description' => 'Синхронизировать остатки сразу после привязки товара',
            'auto_sync_on_change' => 'Автосинхронизация при изменении',
            'auto_sync_on_change_description' => 'Синхронизировать остатки при изменении на складе',
        ],
        'telegram' => [
            'connect' => 'Подключить',
            'configure' => 'Настроить',
            'description' => 'Подключите Telegram для уведомлений',
        ],
        'company' => [
            'title' => 'КОМПАНИЯ',
            'current' => 'Текущая компания',
            'not_selected' => 'Не выбрана',
        ],
        'notifications' => [
            'title' => 'УВЕДОМЛЕНИЯ',
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
        'save' => 'Сохранить',
        'cancel' => 'Отмена',
        'logout' => 'Выйти',
        'logout_confirm' => 'Вы уверены, что хотите выйти?',
        'close' => 'Закрыть',
        'edit' => 'Редактировать',
        'delete' => 'Удалить',
        'confirm' => 'Подтвердить',
    ],
    
    // Messages
    'messages' => [
        'profile_updated' => 'Профиль обновлен',
        'password_changed' => 'Пароль изменен',
        'password_mismatch' => 'Пароли не совпадают',
        'password_too_short' => 'Пароль должен быть не менее 8 символов',
        'currency_updated' => 'Курсы валют обновлены',
        'settings_saved' => 'Настройки сохранены',
        'error' => 'Ошибка',
        'save_error' => 'Ошибка сохранения',
        'loading' => 'Загрузка...',
        'saving' => 'Сохранение...',
    ],
    
    // Navigation
    'nav' => [
        'dashboard' => 'Главная',
        'products' => 'Товары',
        'orders' => 'Заказы',
        'warehouse' => 'Склад',
        'analytics' => 'Аналитика',
        'marketplace' => 'Маркетплейсы',
        'settings' => 'Настройки',
        'finance' => 'Финансы',
        'sales' => 'Продажи',
    ],
    
    // User
    'user' => [
        'profile' => 'Профиль',
        'settings' => 'Настройки',
        'logout' => 'Выйти',
    ],
    
    // App info
    'app' => [
        'name' => 'SellerMind',
        'version' => 'v1.0.0',
        'copyright' => '© 2024 SellerMind',
    ],
];
