<?php

return [
    'title' => 'KPI',
    'subtitle' => 'Управление планами и бонусами сотрудников',

    // Табы
    'tabs' => [
        'dashboard' => 'Дашборд',
        'plans' => 'Планы',
        'spheres' => 'Сферы продаж',
        'scales' => 'Шкалы бонусов',
    ],

    // Дашборд
    'dashboard' => [
        'employees' => 'Сотрудников',
        'avg_achievement' => 'Средний KPI',
        'total_bonus' => 'Сумма бонусов',
        'revenue' => 'Оборот',
        'calculate' => 'Рассчитать KPI',
        'calculating' => 'Расчёт...',
    ],

    // Планы
    'plans' => [
        'title' => 'KPI-планы',
        'new' => 'Новый план',
        'edit' => 'Редактировать план',
        'employee' => 'Сотрудник',
        'sphere' => 'Сфера продаж',
        'scale' => 'Шкала бонусов',
        'period' => 'Период',
        'target' => 'План',
        'actual' => 'Факт',
        'achievement' => 'KPI %',
        'bonus' => 'Бонус',
        'status' => 'Статус',
        'revenue' => 'Оборот',
        'margin' => 'Маржа',
        'orders' => 'Заказы',
        'weight' => 'Вес',
        'approve' => 'Утвердить',
        'delete' => 'Удалить',
        'actuals' => 'Фактические данные',
        'notes' => 'Заметки',
    ],

    // Сферы
    'spheres' => [
        'title' => 'Сферы продаж',
        'new' => 'Новая сфера',
        'edit' => 'Редактировать сферу',
        'name' => 'Название',
        'description' => 'Описание',
        'color' => 'Цвет',
        'icon' => 'Иконка',
        'marketplace' => 'Маркетплейс',
        'active' => 'Активна',
        'no_marketplace' => 'Без привязки (ручной ввод)',
    ],

    // Шкалы
    'scales' => [
        'title' => 'Шкалы бонусов',
        'new' => 'Новая шкала',
        'edit' => 'Редактировать шкалу',
        'name' => 'Название',
        'default' => 'По умолчанию',
        'tiers' => 'Ступени',
        'add_tier' => 'Добавить ступень',
        'min_percent' => 'От %',
        'max_percent' => 'До %',
        'bonus_type' => 'Тип бонуса',
        'bonus_value' => 'Значение',
        'type_fixed' => 'Фиксированная сумма',
        'type_percent_revenue' => '% от оборота',
        'type_percent_margin' => '% от маржи',
    ],

    // Статусы
    'statuses' => [
        'active' => 'Активный',
        'calculated' => 'Рассчитан',
        'approved' => 'Утверждён',
        'cancelled' => 'Отменён',
    ],

    // Общее
    'save' => 'Сохранить',
    'cancel' => 'Отмена',
    'close' => 'Закрыть',
    'empty' => 'Нет данных',
    'empty_plans' => 'Нет KPI-планов за этот период',
    'empty_spheres' => 'Создайте первую сферу продаж',
    'empty_scales' => 'Создайте первую шкалу бонусов',
    'sum_currency' => 'сум',
];
