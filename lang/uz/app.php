<?php

return [
    // Settings page
    'settings' => [
        'title' => 'Sozlamalar',
        'subtitle' => 'Hisob va bildirishnomalarni boshqarish',
        'tabs' => [
            'profile' => 'Profil',
            'telegram' => 'Telegram Bildirishnomalar',
            'security' => 'Xavfsizlik',
            'sync' => 'Sinxronizatsiya',
            'currency' => 'Valyutalar',
            'language' => 'Til',
        ],
        'profile' => [
            'title' => 'Profil ma\'lumotlari',
            'name' => 'Ism',
            'email' => 'Email',
            'language' => 'Interfeys tili',
            'save' => 'O\'zgarishlarni saqlash',
            'not_specified' => 'Ko\'rsatilmagan',
        ],
        'security' => [
            'title' => 'Parolni o\'zgartirish',
            'current_password' => 'Joriy parol',
            'new_password' => 'Yangi parol',
            'confirm_password' => 'Parolni tasdiqlang',
            'change_password' => 'Parolni o\'zgartirish',
            'update_password' => 'Parolingizni yangilang',
        ],
        'currency' => [
            'title' => 'Valyuta kurslari',
            'description' => 'Tannarx va hisobotlarni hisoblash uchun joriy valyuta kurslarini o\'rnating. Bu kurslar tizimning barcha bo\'limlarida ishlatiladi.',
            'usd' => 'AQSh Dollari (USD → UZS)',
            'rub' => 'Rubl (RUB → UZS)',
            'eur' => 'Yevro (EUR → UZS)',
            'last_updated' => 'Oxirgi yangilanish',
        ],
        'sync' => [
            'title' => 'Qoldiqlarni sinxronizatsiya sozlamalari',
            'description' => 'Marketplace\'lar bilan qoldiqlarni avtomatik sinxronizatsiyani boshqaring',
            'stock_sync_enabled' => 'Qoldiqlarni sinxronizatsiya',
            'stock_sync_description' => 'Marketplace\'larda qoldiqlarni avtomatik yangilash',
            'auto_sync_on_link' => 'Bog\'lashda avtosinxronizatsiya',
            'auto_sync_on_link_description' => 'Mahsulotni bog\'lagandan so\'ng qoldiqlarni darhol sinxronlash',
            'auto_sync_on_change' => 'O\'zgartirishda avtosinxronizatsiya',
            'auto_sync_on_change_description' => 'Omborda o\'zgarish bo\'lganda qoldiqlarni sinxronlash',
        ],
        'telegram' => [
            'connect' => 'Ulash',
            'configure' => 'Sozlash',
            'description' => 'Bildirishnomalar uchun Telegram\'ni ulang',
        ],
        'company' => [
            'title' => 'KOMPANIYA',
            'current' => 'Joriy kompaniya',
            'not_selected' => 'Tanlanmagan',
        ],
        'notifications' => [
            'title' => 'BILDIRISHNOMALAR',
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
        'save' => 'Saqlash',
        'cancel' => 'Bekor qilish',
        'logout' => 'Chiqish',
        'logout_confirm' => 'Haqiqatan ham chiqishni xohlaysizmi?',
        'close' => 'Yopish',
        'edit' => 'Tahrirlash',
        'delete' => 'O\'chirish',
        'confirm' => 'Tasdiqlash',
    ],
    
    // Messages
    'messages' => [
        'profile_updated' => 'Profil yangilandi',
        'password_changed' => 'Parol o\'zgartirildi',
        'password_mismatch' => 'Parollar mos kelmaydi',
        'password_too_short' => 'Parol kamida 8 ta belgidan iborat bo\'lishi kerak',
        'currency_updated' => 'Valyuta kurslari yangilandi',
        'settings_saved' => 'Sozlamalar saqlandi',
        'error' => 'Xatolik',
        'save_error' => 'Saqlashda xatolik',
        'loading' => 'Yuklanmoqda...',
        'saving' => 'Saqlanmoqda...',
    ],
    
    // Navigation
    'nav' => [
        'dashboard' => 'Bosh sahifa',
        'products' => 'Mahsulotlar',
        'orders' => 'Buyurtmalar',
        'warehouse' => 'Ombor',
        'analytics' => 'Tahlil',
        'marketplace' => 'Marketplace\'lar',
        'settings' => 'Sozlamalar',
        'finance' => 'Moliya',
        'sales' => 'Sotuvlar',
    ],
    
    // User
    'user' => [
        'profile' => 'Profil',
        'settings' => 'Sozlamalar',
        'logout' => 'Chiqish',
    ],
    
    // App info
    'app' => [
        'name' => 'SellerMind',
        'version' => 'v1.0.0',
        'copyright' => '© 2024 SellerMind',
    ],
];
