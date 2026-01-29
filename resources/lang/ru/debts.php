<?php

return [
    'title' => 'Долги и предоплаты',
    'subtitle' => 'Учет долгов контрагентов, предоплат и авансов',

    // Назначение
    'purpose_debt' => 'Долг',
    'purpose_prepayment' => 'Предоплата',
    'purpose_advance' => 'Аванс',
    'purpose_loan' => 'Заём',
    'purpose_other' => 'Прочее',

    // Тип
    'type_receivable' => 'Нам должны',
    'type_payable' => 'Мы должны',

    // Статус
    'status_active' => 'Активный',
    'status_partially_paid' => 'Частично',
    'status_paid' => 'Оплачен',
    'status_written_off' => 'Списан',

    // Вкладки
    'tab_all' => 'Все долги',
    'tab_by_counterparty' => 'По контрагентам',
    'tab_by_employee' => 'По сотрудникам',

    // Сводки
    'total_receivable' => 'Дебиторка',
    'total_payable' => 'Кредиторка',
    'overdue' => 'Просрочено',
    'net_balance' => 'Чистый баланс',

    // Действия
    'create_debt' => 'Новый долг',
    'add_payment' => 'Погасить',
    'write_off' => 'Списать',
    'write_off_reason' => 'Причина списания',
    'payment_history' => 'История погашений',
    'view_details' => 'Подробно',
    'back_to_list' => 'Назад к списку',

    // Форма
    'description' => 'Описание',
    'type' => 'Тип',
    'purpose' => 'Назначение',
    'counterparty' => 'Контрагент',
    'employee' => 'Сотрудник',
    'amount' => 'Сумма',
    'currency' => 'Валюта',
    'debt_date' => 'Дата долга',
    'due_date' => 'Срок оплаты',
    'cash_account' => 'Счёт / Касса',
    'reference' => 'Ссылка / Документ',
    'notes' => 'Примечания',
    'outstanding' => 'Остаток',
    'paid' => 'Оплачено',

    // Платежи
    'payment_amount' => 'Сумма платежа',
    'payment_date' => 'Дата платежа',
    'payment_method' => 'Способ оплаты',
    'method_cash' => 'Наличные',
    'method_bank' => 'Банк',
    'method_card' => 'Карта',

    // Сообщения
    'no_debts' => 'Долги не найдены',
    'debt_created' => 'Долг создан',
    'payment_recorded' => 'Платёж записан',
    'debt_written_off' => 'Долг списан',
    'confirm_write_off' => 'Вы уверены, что хотите списать этот долг?',

    // Поиск
    'search_counterparty' => 'Поиск контрагента...',
    'search_employee' => 'Поиск сотрудника...',
    'search_placeholder' => 'Поиск по описанию, ссылке...',

    // Таблица по контрагентам
    'counterparty_name' => 'Контрагент',
    'receivable_total' => 'Дебиторка',
    'payable_total' => 'Кредиторка',
    'balance' => 'Баланс',
    'debt_count' => 'Долгов',

    // Таблица по сотрудникам
    'employee_name' => 'Сотрудник',
    'employee_position' => 'Должность',

    // Детали
    'created_by' => 'Создал',
    'created_at' => 'Дата создания',
    'written_off_by' => 'Списал',
    'written_off_at' => 'Дата списания',
    'written_off_reason_label' => 'Причина списания',

    // Авто-долги из продаж / счетов
    'auto_from_sale' => 'Автоматически из продажи',
    'auto_from_invoice' => 'Автоматически из счёта поставщика',
    'source_sale' => 'Продажа',
    'source_invoice' => 'Счёт поставщика',
    'view_sale' => 'Посмотреть продажу',

    // Касса
    'select_cash_account' => 'Выберите счёт / кассу',
    'cash_income' => 'Приход на кассу',
    'cash_expense' => 'Расход с кассы',
    'no_cash_accounts' => 'Нет доступных счетов',
];
