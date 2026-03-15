# SellerMind — Интеграция с Яндекс Маркетом

## Два механизма

### 1. Авто-перемещение заказов (Новые → Сборка)

**Как работает:**
- Scheduler (`ym:process-orders`) запускается каждые 5 минут
- Получает заказы в статусе `PROCESSING / STARTED` через API
- Переводит в `PROCESSING / READY_TO_SHIP` (готов к отгрузке)
- Настраиваемая задержка (по умолчанию 30 сек) — защита от перевода слишком свежих заказов
- Логирует каждую операцию в `ym_order_auto_logs`
- Поддержка FBS, DBS, Экспресс

**Альтернативный режим через webhook:**
- Маркет отправляет POST на `/api/ym/notifications` при новом заказе
- Job `ProcessYmNewOrder` запускается с настраиваемой задержкой
- Работает мгновенно, не нужен polling

**API-методы Яндекс Маркета:**
- `GET /v2/campaigns/{campaignId}/orders?status=PROCESSING&substatus=STARTED`
- `PUT /v2/campaigns/{campaignId}/orders/{orderId}/status`
- `POST /v2/campaigns/{campaignId}/orders/status-update` (массовый)

---

### 2. ИИ-автоответы на отзывы

**Логика:**
- Scheduler (`ym:auto-reviews`) запускается каждые 15 минут
- Получает отзывы с `reactionStatus: NEED_REACTION`
- Генерирует ответ через Claude API или OpenAI API (настраивается)
- **4-5 звёзд** → автоматическая публикация
- **1-3 звезды** → черновик для проверки продавцом
- Продавец может: одобрить, отредактировать, перегенерировать, пропустить

**API для управления черновиками:**
```
GET    /api/ym/reviews              — список отзывов
GET    /api/ym/reviews/stats        — статистика
GET    /api/ym/reviews/{id}         — детали
POST   /api/ym/reviews/{id}/approve — одобрить (с возможностью редактирования)
POST   /api/ym/reviews/{id}/skip    — пропустить
POST   /api/ym/reviews/{id}/regenerate — перегенерировать ответ
```

**API-методы Яндекс Маркета:**
- `POST /v2/businesses/{businessId}/goods-feedback` (получение отзывов)
- `POST /v2/businesses/{businessId}/goods-feedback/comments/update` (ответ)
- `POST /v2/businesses/{businessId}/goods-feedback/skip-reaction` (пропуск)

---

## Установка

### 1. Скопируйте файлы в проект SellerMind

```
app/
├── Console/Commands/
│   ├── YmProcessNewOrders.php
│   └── YmAutoRespondReviews.php
├── Http/Controllers/Api/
│   ├── YmNotificationController.php
│   └── YmReviewController.php
├── Jobs/
│   ├── ProcessYmNewOrder.php
│   └── ProcessYmNewReview.php
├── Models/
│   ├── YmOrderAutoLog.php
│   ├── YmReviewResponse.php
│   └── YmIntegrationSetting.php
└── Services/
    ├── AI/
    │   └── ReviewAIService.php
    └── YandexMarket/
        ├── YandexMarketApiService.php
        ├── OrderAutoProcessingService.php
        └── ReviewAutoResponderService.php

config/yandex-market.php
database/migrations/2026_03_12_000001_create_yandex_market_tables.php
routes/ym.php
routes/ym-schedule.php
```

### 2. Настройте .env

```bash
cp .env.example .env
# Заполните ключи API
```

### 3. Запустите миграцию

```bash
php artisan migrate
```

### 4. Подключите маршруты

В `routes/api.php`:
```php
require __DIR__.'/ym.php';
```

### 5. Подключите расписание

В `routes/console.php` (Laravel 11+):
```php
require __DIR__.'/ym-schedule.php';
```

### 6. Запустите scheduler

```bash
php artisan schedule:work
# или в cron: * * * * * php artisan schedule:run
```

---

## Artisan-команды

```bash
# Обработать заказы вручную
php artisan ym:process-orders
php artisan ym:process-orders --user=1
php artisan ym:process-orders --dry-run

# Обработать отзывы вручную
php artisan ym:auto-reviews
php artisan ym:auto-reviews --user=1
php artisan ym:auto-reviews --dry-run
```

---

## Архитектура

```
┌─────────────────┐     ┌──────────────────┐
│  Яндекс Маркет  │────▶│  Webhook (POST)  │──▶ Job Queue
│    Push API     │     │  /api/ym/notif.  │
└─────────────────┘     └──────────────────┘
        │
        │ polling
        ▼
┌─────────────────┐     ┌──────────────────┐     ┌────────────┐
│   Scheduler     │────▶│  OrderAutoProc.  │────▶│ YM API     │
│  (cron 5 min)   │     │  Service         │     │ PUT status │
└─────────────────┘     └──────────────────┘     └────────────┘
        │
        │ cron 15 min
        ▼
┌─────────────────┐     ┌──────────────────┐     ┌────────────┐
│ ReviewAutoResp. │────▶│  ReviewAIService │────▶│ Claude API │
│    Service      │     │  (generate)      │     │ OpenAI API │
└─────────────────┘     └──────────────────┘     └────────────┘
        │                                               │
        │ 4-5★ auto                                     │
        │ 1-3★ draft                                    ▼
        ▼                                        ┌────────────┐
┌─────────────────┐                              │ YM API     │
│  ym_review_     │ ◀── approve/edit ◀── UI     │ POST reply │
│  responses DB   │                              └────────────┘
└─────────────────┘
```

## Мультитенантность

Настройки хранятся в `ym_integration_settings` — каждый пользователь SellerMind имеет свои:
- API-ключ Яндекс Маркета
- business_id / campaign_id
- Настройки авто-заказов и авто-отзывов
- Выбор AI-провайдера
- Кастомный промпт для ИИ
