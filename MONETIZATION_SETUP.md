# Настройка системы монетизации SellerMind

Полное руководство по настройке и запуску системы подписок и платежей.

## 📋 Содержание
1. [Обзор системы](#обзор-системы)
2. [Настройка базы данных](#настройка-базы-данных)
3. [Конфигурация платежных систем](#конфигурация-платежных-систем)
4. [Настройка уведомлений](#настройка-уведомлений)
5. [Планировщик задач](#планировщик-задач)
6. [Тестирование](#тестирование)

---

## 🎯 Обзор системы

Система монетизации включает:
- ✅ **4 тарифных плана**: Старт, Бизнес, Про, Enterprise
- ✅ **Проверка лимитов**: Автоматическая при создании товаров, складов, маркетплейсов, пользователей
- ✅ **Уведомления**: Email, Telegram, Database за 7/3/1 день до истечения
- ✅ **Платежи**: Click, Payme (Узбекистан)
- ✅ **API**: Полное управление подписками через REST API

---

## 🗄️ Настройка базы данных

### 1. Запуск миграций

```bash
# На production сервере
php artisan migrate --force
```

Это создаст таблицы:
- `plans` - тарифные планы
- `subscriptions` - подписки компаний

### 2. Заполнение тарифов

```bash
php artisan db:seed --class=PlanSeeder
```

Создаст 4 тарифа:
- **Старт**: 500,000 UZS/мес
- **Бизнес**: 1,500,000 UZS/мес (популярный)
- **Про**: 3,500,000 UZS/мес
- **Enterprise**: 7,000,000 UZS/мес

---

## 💳 Конфигурация платежных систем

### 1. Click

Добавьте в `.env`:

```env
CLICK_MERCHANT_ID=your_merchant_id
CLICK_SERVICE_ID=your_service_id
CLICK_SECRET_KEY=your_secret_key
CLICK_MERCHANT_USER_ID=your_user_id
```

**Регистрация webhook URLs в Click:**
- Prepare: `https://sellermind.uz/webhooks/click/prepare`
- Complete: `https://sellermind.uz/webhooks/click/complete`

### 2. Payme (Paycom)

Добавьте в `.env`:

```env
PAYME_MERCHANT_ID=your_merchant_id
PAYME_SECRET_KEY=your_secret_key
PAYME_ENDPOINT=https://checkout.paycom.uz
```

**Регистрация webhook URL в Payme:**
- Endpoint: `https://sellermind.uz/webhooks/payme`
- Method: JSON-RPC 2.0
- Authorization: HTTP Basic Auth (Paycom:{SECRET_KEY})

---

## 🔔 Настройка уведомлений

### 1. Email уведомления

Уже настроены в `config/mail.php`. Проверьте настройки SMTP:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@sellermind.uz
MAIL_FROM_NAME="SellerMind AI"
```

### 2. Telegram уведомления

Уже интегрированы. Пользователи подключают Telegram в настройках профиля:
- Бот: `@your_bot_username`
- Команда: `/link CODE`

### 3. Database уведомления

Автоматически сохраняются в таблицу `notifications`. Доступны через:
```php
$user->notifications
$user->unreadNotifications
```

---

## ⏰ Планировщик задач

### 1. Настройка Cron

Добавьте в crontab на сервере:

```bash
* * * * * cd /path-to-sellermind && php artisan schedule:run >> /dev/null 2>&1
```

### 2. Проверка задач

Список запланированных задач:
```bash
php artisan schedule:list
```

### 3. Ручной запуск проверки подписок

```bash
# Проверить истекающие подписки (за 7, 3, 1 день)
php artisan subscriptions:check-expiring

# С пометкой истекших
php artisan subscriptions:check-expiring --mark-expired

# Настроить дни уведомлений
php artisan subscriptions:check-expiring --notify-days=14,7,3,1
```

### 4. Логи

Проверяйте логи:
```bash
tail -f storage/logs/subscriptions.log
tail -f storage/logs/laravel.log
```

---

## 🧪 Тестирование

### 1. Тестирование API

#### Получить тарифы
```bash
curl https://sellermind.uz/api/plans
```

#### Статус подписки
```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
     https://sellermind.uz/api/subscription/status
```

#### Создать подписку
```bash
curl -X POST \
     -H "Authorization: Bearer YOUR_TOKEN" \
     -H "Content-Type: application/json" \
     -d '{"plan_id": 2, "billing_period": "monthly"}' \
     https://sellermind.uz/api/subscription/subscribe
```

### 2. Тестирование платежей

#### Click (Sandbox)
1. Перейдите на `/payment/subscription/{id}`
2. Выберите Click
3. Используйте тестовые карты Click

#### Payme (Sandbox)
1. Перейдите на `/payment/subscription/{id}`
2. Выберите Payme
3. Используйте тестовые карты Payme

### 3. Тестирование уведомлений

```bash
# Создать тестовую подписку, истекающую завтра
php artisan tinker

$subscription = Subscription::create([
    'company_id' => 1,
    'plan_id' => 2,
    'status' => 'active',
    'starts_at' => now(),
    'ends_at' => now()->addDay(),
]);

# Запустить проверку
php artisan subscriptions:check-expiring --notify-days=1
```

---

## 🔧 Middleware и проверки лимитов

### Применение middleware

Middleware автоматически применяются к:
- `POST /api/products` → проверяет лимит товаров
- `POST /api/marketplace/accounts` → проверяет лимит маркетплейсов
- `POST /api/companies/{id}/members` → проверяет лимит пользователей
- `POST /api/warehouses` → проверяет лимит складов

### Добавление проверки лимитов к новым маршрутам

```php
Route::post('/some-entity', [Controller::class, 'store'])
    ->middleware('plan.limits:entity_type,count');

// Примеры:
->middleware('plan.limits:products,1')      // Проверить лимит товаров
->middleware('plan.limits:users,5')         // Проверить лимит на 5 пользователей
->middleware('plan.limits:marketplace_accounts,1')
```

---

## 📊 Мониторинг

### Проверка подписок

```sql
-- Активные подписки
SELECT c.name, p.name, s.ends_at, s.status
FROM subscriptions s
JOIN companies c ON s.company_id = c.id
JOIN plans p ON s.plan_id = p.id
WHERE s.status = 'active';

-- Истекающие в ближайшие 7 дней
SELECT c.name, p.name, DATEDIFF(s.ends_at, NOW()) as days_left
FROM subscriptions s
JOIN companies c ON s.company_id = c.id
JOIN plans p ON s.plan_id = p.id
WHERE s.status = 'active'
  AND s.ends_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY);

-- Использование лимитов
SELECT
    c.name,
    s.current_products_count,
    p.max_products,
    ROUND(s.current_products_count / p.max_products * 100, 1) as usage_pct
FROM subscriptions s
JOIN companies c ON s.company_id = c.id
JOIN plans p ON s.plan_id = p.id
WHERE s.status = 'active';
```

---

## 🚀 Деплой checklist

- [ ] Запущены миграции (`php artisan migrate --force`)
- [ ] Загружены тарифы (`php artisan db:seed --class=PlanSeeder`)
- [ ] Настроены переменные окружения Click/Payme
- [ ] Зарегистрированы webhook URLs в Click
- [ ] Зарегистрирован webhook URL в Payme
- [ ] Настроен cron для планировщика
- [ ] Проверены логи уведомлений
- [ ] Протестированы платежи в sandbox
- [ ] Проверена работа middleware проверки лимитов

---

## 📞 Поддержка

При возникновении проблем:
1. Проверьте логи: `storage/logs/subscriptions.log`
2. Проверьте Laravel log: `storage/logs/laravel.log`
3. Проверьте cron: `grep CRON /var/log/syslog`
4. Проверьте webhook логи в Click/Payme панелях

---

## 🔗 Полезные ссылки

- **Click документация**: https://docs.click.uz/
- **Payme документация**: https://developer.help.paycom.uz/
- **Laravel Scheduler**: https://laravel.com/docs/scheduling
- **Laravel Notifications**: https://laravel.com/docs/notifications
