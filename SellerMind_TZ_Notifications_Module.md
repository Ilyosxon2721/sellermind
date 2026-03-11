# ТЕХНИЧЕСКОЕ ЗАДАНИЕ
## Модуль уведомлений маркетплейсов — Система SellerMind

**Webhook & Polling интеграция с Ozon, Yandex Market, Wildberries, Uzum Market**

| Параметр | Значение |
|----------|----------|
| Версия | 1.0 |
| Дата | 11 марта 2026 |
| Автор | SellerMind Team |
| Проект | SellerMind v2 |
| Статус | Черновик |

---

## 1. Введение

### 1.1. Цель документа

Настоящее техническое задание описывает архитектуру, требования и план реализации модуля уведомлений маркетплейсов для системы SellerMind. Модуль обеспечит получение событий (новые заказы, изменения статусов, сообщения, возвраты) от четырёх маркетплейсов в реальном времени или с минимальной задержкой.

### 1.2. Область применения

Модуль является частью системы SellerMind — платформы управления продажами на маркетплейсах для предпринимателей Узбекистана. Модуль интегрируется со следующими площадками:

- **Ozon** — через Webhook (Push Notifications API)
- **Yandex Market** — через Webhook (API-уведомления)
- **Wildberries** — через Polling (периодический опрос REST API)
- **Uzum Market** — через Polling (внутренний API / реверс-инжиниринг)

### 1.3. Определения и сокращения

| Термин | Описание |
|--------|----------|
| Webhook | HTTP POST-запрос, отправляемый сервером маркетплейса на URL продавца при наступлении события |
| Polling | Периодический опрос API маркетплейса для проверки новых данных |
| Push Notification | Уведомление, инициированное маркетплейсом (синоним Webhook) |
| Event | Единичное событие от маркетплейса (новый заказ, смена статуса и т.д.) |
| Handler | Обработчик события, выполняющий бизнес-логику |
| Job / Queue | Фоновая задача Laravel, обрабатываемая через очереди (Redis/Database) |
| FBO | Fulfillment By Operator — хранение и отправка со склада маркетплейса |
| FBS | Fulfillment By Seller — хранение у продавца, доставка через маркетплейс |
| DBS | Delivery By Seller — полная доставка силами продавца |

### 1.4. Текущее состояние интеграций

| Маркетплейс | Webhook | Polling | Типы событий | Документация |
|-------------|---------|---------|---------------|--------------|
| **Ozon** | ✅ Да (Push API) | Не требуется | Заказы, Чаты, Статусы | docs.ozon.ru |
| **Yandex Market** | ✅ Да (API-уведомления) | Не требуется | Заказы, Возвраты, Отмены, Чаты | yandex.ru/dev/market |
| **Wildberries** | ❌ Нет | ✅ Да (REST API v3) | Заказы, Остатки, Продажи | dev.wildberries.ru |
| **Uzum Market** | ❌ Нет | ⚠️ Да (внутр. API) | Заказы, Остатки | Нет публичной |

---

## 2. Функциональные требования

### 2.1. Общие требования

1. Единый интерфейс получения событий от всех маркетплейсов (абстракция `MarketplaceEvent`)
2. Поддержка двух транспортов: Webhook (push) и Polling (pull) с единым выходным форматом
3. Гарантия обработки каждого события ровно один раз (idempotency)
4. Хранение лога всех полученных событий с возможностью повторной обработки (replay)
5. Real-time уведомление пользователей через WebSocket/Pusher и Telegram-бот
6. Мониторинг работоспособности каждого канала с алертами при сбоях
7. Конфигурация polling-интервалов и webhook-URL через панель администратора

### 2.2. Требования к Ozon Webhook

- Приём POST-запросов от Ozon на выделенный endpoint
- Валидация запроса по IP-адресам Ozon (whitelist)
- Обработка типов push-уведомлений:
  - `TYPE_NEW_POSTING` — новое отправление (FBS)
  - `TYPE_POSTING_CANCELLED` — отмена отправления
  - `TYPE_NEW_MESSAGE` — новое сообщение покупателя
  - `TYPE_UPDATE_MESSAGE` — обновление сообщения
  - `TYPE_MESSAGE_READ` — сообщение прочитано
  - `TYPE_CHAT_CLOSED` — чат закрыт
- Ответ `200 OK` в течение 3 секунд (асинхронная обработка через Queue)
- Механизм дедупликации по `message_type + posting_number`

### 2.3. Требования к Yandex Market Webhook

- Приём POST-запросов от Yandex Market на выделенный endpoint
- Аутентификация через OAuth-токен в заголовке `Authorization`
- Обработка типов уведомлений:
  - `ORDER_CREATED` — создан новый заказ
  - `ORDER_STATUS_UPDATED` — изменение статуса заказа
  - `ORDER_CANCELLED` — заказ отменён
  - `ORDER_UPDATED` — заказ изменён
  - `ORDER_RETURN_CREATED` — создан невыкуп/возврат
  - `ORDER_RETURN_STATUS_UPDATED` — изменение статуса возврата
  - `ORDER_CANCELLATION_REQUEST` — заявка на отмену (DBS)
  - `CHAT_MESSAGE_CREATED` — новое сообщение в чате
- Ответ `200 OK` в течение 10 секунд (регламент Yandex Market)
- Возврат кода `400` при некорректном уведомлении с описанием ошибки

### 2.4. Требования к Wildberries Polling

- Периодический опрос endpoint `GET /api/v3/orders/new` каждые 30 секунд
- Отслеживание `cursor/lastId` для получения только новых заказов
- Дополнительный polling для статусов: `GET /api/v3/orders/stickers` каждые 60 секунд
- Polling продаж: `GET /api/v1/supplier/sales` каждые 5 минут
- Обработка rate limits (`429 Too Many Requests`) с exponential backoff
- Хранение `last_poll_timestamp` для каждого магазина/endpoint
- Автоматическое определение дельты (новые записи) через сравнение с БД

### 2.5. Требования к Uzum Market Polling

- Polling заказов через внутренний API seller-кабинета каждые 60 секунд
- Авторизация через session token (cookie-based auth)
- Отслеживание новых заказов по дате создания + ID
- Механизм re-auth при истечении сессии (автоматическое обновление токена)
- Fallback: парсинг seller.uzum.uz при недоступности API
- **Внимание:** данная интеграция может быть нестабильной из-за отсутствия публичного API

---

## 3. Архитектура модуля

### 3.1. Высокоуровневая архитектура

Модуль построен на принципах Event-Driven Architecture и использует паттерн Strategy для абстракции транспорта (Webhook vs Polling). Все события приводятся к единому формату `MarketplaceEvent` перед передачей в обработчики.

```
┌─────────────────────────────────────────────────────────────────┐
│                     МАРКЕТПЛЕЙСЫ                                │
│  ┌──────────┐  ┌──────────────┐  ┌─────────────┐  ┌──────────┐ │
│  │   Ozon   │  │ Yandex Market│  │ Wildberries  │  │   Uzum   │ │
│  │ (webhook)│  │  (webhook)   │  │  (polling)   │  │(polling) │ │
│  └────┬─────┘  └──────┬───────┘  └──────┬───────┘  └────┬─────┘ │
└───────┼───────────────┼────────────────┼───────────────┼────────┘
        │               │                │               │
        ▼               ▼                ▼               ▼
┌─────────────────────────────────────────────────────────────────┐
│                   TRANSPORT LAYER                               │
│  ┌────────────────────────┐  ┌──────────────────────────────┐   │
│  │   Webhook Receiver     │  │      Polling Engine           │   │
│  │  (HTTP Controllers)    │  │   (Laravel Scheduler Jobs)    │   │
│  │  - OzonWebhookCtrl     │  │  - PollWildberriesOrdersJob   │   │
│  │  - YandexWebhookCtrl   │  │  - PollWildberriesSalesJob    │   │
│  │                        │  │  - PollUzumOrdersJob          │   │
│  └───────────┬────────────┘  └──────────────┬───────────────┘   │
└──────────────┼──────────────────────────────┼───────────────────┘
               │                              │
               ▼                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                   PROCESSING LAYER                              │
│                                                                 │
│  ┌──────────────────┐   ┌──────────────────────────────────┐    │
│  │ Deduplication     │──▶│         Event Store              │    │
│  │ Service           │   │   (marketplace_events table)     │    │
│  └──────────────────┘   └──────────────┬───────────────────┘    │
│                                        │                        │
│  ┌──────────────────┐   ┌──────────────▼───────────────────┐    │
│  │ Event Normalizer  │──▶│      Event Dispatcher            │    │
│  │ (per marketplace) │   │   (Queue: marketplace-events)    │    │
│  └──────────────────┘   └──────────────┬───────────────────┘    │
│                                        │                        │
│                         ┌──────────────▼───────────────────┐    │
│                         │          Handlers                 │    │
│                         │  - OrderCreatedHandler            │    │
│                         │  - OrderStatusChangedHandler      │    │
│                         │  - OrderCancelledHandler          │    │
│                         │  - ReturnCreatedHandler           │    │
│                         │  - ChatMessageHandler             │    │
│                         └──────────────┬───────────────────┘    │
└────────────────────────────────────────┼────────────────────────┘
                                         │
                                         ▼
┌─────────────────────────────────────────────────────────────────┐
│                 NOTIFICATION LAYER                               │
│  ┌───────────┐ ┌──────────┐ ┌─────────┐ ┌───────┐ ┌────────┐  │
│  │ WebSocket │ │ Telegram │ │PWA Push │ │ Email │ │ In-App │  │
│  │ (Soketi)  │ │   Bot    │ │  (FCM)  │ │(SMTP) │ │  (DB)  │  │
│  └───────────┘ └──────────┘ └─────────┘ └───────┘ └────────┘  │
└─────────────────────────────────────────────────────────────────┘
```

Основные компоненты:

1. **Webhook Receiver** — HTTP контроллеры для приёма push-уведомлений (Ozon, Yandex)
2. **Polling Engine** — Laravel Scheduler задачи для периодического опроса (WB, Uzum)
3. **Event Normalizer** — преобразование сырых данных в единый `MarketplaceEvent`
4. **Event Store** — хранение всех событий в таблице `marketplace_events`
5. **Event Dispatcher** — маршрутизация событий к соответствующим Handler-ам
6. **Notification Service** — доставка уведомлений пользователю (WebSocket, Telegram, Email)
7. **Health Monitor** — мониторинг состояния каналов и алерты

### 3.2. Структура базы данных

#### Таблица `marketplace_events` — лог всех полученных событий

| Поле | Тип | NULL | Описание |
|------|-----|------|----------|
| `id` | BIGINT PK | NO | Auto-increment ID |
| `uuid` | UUID UNIQUE | NO | Уникальный идентификатор события |
| `store_id` | BIGINT FK | NO | Ссылка на магазин продавца |
| `marketplace` | ENUM | NO | `ozon`, `yandex`, `wildberries`, `uzum` |
| `event_type` | VARCHAR(100) | NO | Тип события (`order_created`, `status_changed` и т.д.) |
| `external_id` | VARCHAR(255) | YES | ID события от маркетплейса (для дедупликации) |
| `entity_type` | VARCHAR(50) | NO | `order`, `return`, `chat`, `posting` |
| `entity_id` | VARCHAR(255) | NO | ID сущности (номер заказа/чата) |
| `payload` | JSON | NO | Сырые данные от маркетплейса |
| `normalized_data` | JSON | YES | Нормализованные данные MarketplaceEvent |
| `status` | ENUM | NO | `received`, `processing`, `processed`, `failed`, `skipped` |
| `attempts` | TINYINT | NO | Количество попыток обработки (default: 0) |
| `error_message` | TEXT | YES | Текст ошибки при неуспешной обработке |
| `processed_at` | TIMESTAMP | YES | Время завершения обработки |
| `created_at` | TIMESTAMP | NO | Время получения события |
| `updated_at` | TIMESTAMP | NO | Время последнего обновления |

**Индексы:**
```sql
UNIQUE INDEX idx_dedup (marketplace, external_id)
INDEX idx_store_type (store_id, event_type, created_at)
INDEX idx_status (status, attempts)
INDEX idx_entity (marketplace, entity_type, entity_id)
```

#### Таблица `marketplace_webhook_configs` — настройки webhook-каналов

| Поле | Тип | NULL | Описание |
|------|-----|------|----------|
| `id` | BIGINT PK | NO | Auto-increment ID |
| `store_id` | BIGINT FK | NO | Ссылка на магазин |
| `marketplace` | ENUM | NO | `ozon`, `yandex` |
| `webhook_url` | VARCHAR(500) | NO | Сгенерированный URL для приёма webhook |
| `secret_key` | VARCHAR(255) | NO | Секретный ключ для верификации |
| `is_active` | BOOLEAN | NO | Активен ли канал |
| `last_received_at` | TIMESTAMP | YES | Время последнего полученного события |
| `events_count` | INT | NO | Счётчик полученных событий |
| `created_at` | TIMESTAMP | NO | |
| `updated_at` | TIMESTAMP | NO | |

#### Таблица `marketplace_polling_states` — состояние polling-каналов

| Поле | Тип | NULL | Описание |
|------|-----|------|----------|
| `id` | BIGINT PK | NO | Auto-increment ID |
| `store_id` | BIGINT FK | NO | Ссылка на магазин |
| `marketplace` | ENUM | NO | `wildberries`, `uzum` |
| `endpoint` | VARCHAR(255) | NO | Polling endpoint (`orders_new`, `sales`, etc.) |
| `last_cursor` | VARCHAR(255) | YES | Последний cursor/offset/ID |
| `last_poll_at` | TIMESTAMP | YES | Время последнего успешного опроса |
| `poll_interval_sec` | INT | NO | Интервал опроса в секундах |
| `consecutive_errors` | INT | NO | Счётчик последовательных ошибок |
| `is_active` | BOOLEAN | NO | Активен ли polling |
| `is_locked` | BOOLEAN | NO | Блокировка при текущем выполнении |
| `locked_at` | TIMESTAMP | YES | Время установки блокировки |
| `created_at` | TIMESTAMP | NO | |
| `updated_at` | TIMESTAMP | NO | |

### 3.3. Laravel миграции

```php
// database/migrations/2026_03_11_000001_create_marketplace_events_table.php

Schema::create('marketplace_events', function (Blueprint $table) {
    $table->id();
    $table->uuid('uuid')->unique();
    $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
    $table->enum('marketplace', ['ozon', 'yandex', 'wildberries', 'uzum']);
    $table->string('event_type', 100);
    $table->string('external_id', 255)->nullable();
    $table->string('entity_type', 50);
    $table->string('entity_id', 255);
    $table->json('payload');
    $table->json('normalized_data')->nullable();
    $table->enum('status', ['received', 'processing', 'processed', 'failed', 'skipped'])
          ->default('received');
    $table->tinyInteger('attempts')->default(0);
    $table->text('error_message')->nullable();
    $table->timestamp('processed_at')->nullable();
    $table->timestamps();

    $table->unique(['marketplace', 'external_id'], 'idx_dedup');
    $table->index(['store_id', 'event_type', 'created_at'], 'idx_store_type');
    $table->index(['status', 'attempts'], 'idx_status');
    $table->index(['marketplace', 'entity_type', 'entity_id'], 'idx_entity');
});

// database/migrations/2026_03_11_000002_create_marketplace_webhook_configs_table.php

Schema::create('marketplace_webhook_configs', function (Blueprint $table) {
    $table->id();
    $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
    $table->enum('marketplace', ['ozon', 'yandex']);
    $table->string('webhook_url', 500);
    $table->string('secret_key', 255);
    $table->boolean('is_active')->default(true);
    $table->timestamp('last_received_at')->nullable();
    $table->unsignedInteger('events_count')->default(0);
    $table->timestamps();

    $table->unique(['store_id', 'marketplace']);
});

// database/migrations/2026_03_11_000003_create_marketplace_polling_states_table.php

Schema::create('marketplace_polling_states', function (Blueprint $table) {
    $table->id();
    $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
    $table->enum('marketplace', ['wildberries', 'uzum']);
    $table->string('endpoint', 255);
    $table->string('last_cursor', 255)->nullable();
    $table->timestamp('last_poll_at')->nullable();
    $table->unsignedInteger('poll_interval_sec')->default(30);
    $table->unsignedInteger('consecutive_errors')->default(0);
    $table->boolean('is_active')->default(true);
    $table->boolean('is_locked')->default(false);
    $table->timestamp('locked_at')->nullable();
    $table->timestamps();

    $table->unique(['store_id', 'marketplace', 'endpoint']);
});
```

### 3.4. Структура каталогов Laravel

```
app/
  Modules/
    MarketplaceNotifications/
      Contracts/
        MarketplaceEventInterface.php
        EventNormalizerInterface.php
        NotificationTransportInterface.php
      DTO/
        MarketplaceEvent.php
        NormalizedOrder.php
        NormalizedReturn.php
        NormalizedChatMessage.php
      Enums/
        MarketplaceType.php
        EventType.php
        EventStatus.php
        EntityType.php
      Models/
        MarketplaceEventLog.php
        WebhookConfig.php
        PollingState.php
      Webhook/
        Controllers/
          OzonWebhookController.php
          YandexMarketWebhookController.php
        Middleware/
          VerifyOzonIP.php
          VerifyYandexSignature.php
        Routes/
          webhook.php
      Polling/
        Jobs/
          PollWildberriesOrdersJob.php
          PollWildberriesSalesJob.php
          PollUzumOrdersJob.php
        Engine/
          PollingScheduler.php
          PollingLockManager.php
      Normalizers/
        OzonEventNormalizer.php
        YandexEventNormalizer.php
        WildberriesEventNormalizer.php
        UzumEventNormalizer.php
      Handlers/
        OrderCreatedHandler.php
        OrderStatusChangedHandler.php
        OrderCancelledHandler.php
        ReturnCreatedHandler.php
        ChatMessageHandler.php
      Notifications/
        Channels/
          WebSocketChannel.php
          TelegramChannel.php
        NewOrderNotification.php
        OrderStatusNotification.php
        ReturnNotification.php
      Monitor/
        HealthCheckService.php
        AlertService.php
      Services/
        EventDispatcherService.php
        DeduplicationService.php
        EventReplayService.php
      Providers/
        MarketplaceNotificationsServiceProvider.php
```

---

## 4. Проектирование API

### 4.1. Webhook endpoints

| Метод | Маркетплейс | URL | Описание |
|-------|-------------|-----|----------|
| POST | Ozon | `/api/webhook/ozon/{store_uuid}` | Приём push-уведомлений от Ozon |
| POST | Yandex | `/api/webhook/yandex/{store_uuid}` | Приём API-уведомлений от Yandex |
| GET | Все | `/api/webhook/health` | Health check для всех webhook каналов |

### 4.2. Internal REST API (для фронтенда SellerMind)

| Метод | URL | Описание |
|-------|-----|----------|
| GET | `/api/v1/events` | Список событий с фильтрацией (marketplace, type, status, date) |
| GET | `/api/v1/events/{uuid}` | Детали события с payload и normalized_data |
| POST | `/api/v1/events/{uuid}/replay` | Повторная обработка события |
| GET | `/api/v1/events/stats` | Статистика событий (кол-во по типам, маркетплейсам) |
| GET | `/api/v1/webhook-configs` | Список webhook-конфигураций |
| PUT | `/api/v1/webhook-configs/{id}` | Обновление webhook-конфигурации |
| POST | `/api/v1/webhook-configs/{id}/regenerate-secret` | Перегенерация секретного ключа |
| GET | `/api/v1/polling-states` | Список polling-каналов с их состоянием |
| PUT | `/api/v1/polling-states/{id}` | Обновление настроек polling (интервал, активность) |
| POST | `/api/v1/polling-states/{id}/force-poll` | Принудительный запуск polling |
| GET | `/api/v1/notifications/health` | Общий статус модуля уведомлений |

### 4.3. DTO MarketplaceEvent

Единый формат события, к которому приводятся данные от всех маркетплейсов:

```php
class MarketplaceEvent
{
    public function __construct(
        public readonly string $uuid,
        public readonly MarketplaceType $marketplace,   // ozon|yandex|wildberries|uzum
        public readonly EventType $eventType,            // order_created|status_changed|...
        public readonly EntityType $entityType,          // order|return|chat
        public readonly string $entityId,                // ID сущности на маркетплейсе
        public readonly int $storeId,                    // ID магазина в SellerMind
        public readonly Carbon $occurredAt,              // Время события на маркетплейсе
        public readonly Carbon $receivedAt,              // Время получения в SellerMind
        public readonly array $rawPayload,               // Сырые данные от API
        public readonly ?NormalizedData $normalized,     // Нормализованные данные
        public readonly array $metadata = [],            // Доп. мета (IP, headers и т.д.)
    ) {}
}
```

### 4.4. Enum EventType

```php
enum EventType: string
{
    // Orders
    case ORDER_CREATED = 'order_created';
    case ORDER_STATUS_CHANGED = 'order_status_changed';
    case ORDER_CANCELLED = 'order_cancelled';
    case ORDER_UPDATED = 'order_updated';

    // Returns
    case RETURN_CREATED = 'return_created';
    case RETURN_STATUS_CHANGED = 'return_status_changed';

    // Chat
    case CHAT_MESSAGE_CREATED = 'chat_message_created';
    case CHAT_MESSAGE_UPDATED = 'chat_message_updated';
    case CHAT_MESSAGE_READ = 'chat_message_read';
    case CHAT_CLOSED = 'chat_closed';

    // Inventory
    case STOCK_UPDATED = 'stock_updated';

    // Sales
    case SALE_RECORDED = 'sale_recorded';
}
```

### 4.5. Enum MarketplaceType

```php
enum MarketplaceType: string
{
    case OZON = 'ozon';
    case YANDEX = 'yandex';
    case WILDBERRIES = 'wildberries';
    case UZUM = 'uzum';

    public function supportsWebhook(): bool
    {
        return match ($this) {
            self::OZON, self::YANDEX => true,
            self::WILDBERRIES, self::UZUM => false,
        };
    }

    public function supportsPolling(): bool
    {
        return match ($this) {
            self::WILDBERRIES, self::UZUM => true,
            self::OZON, self::YANDEX => false,
        };
    }
}
```

### 4.6. NormalizedOrder DTO

```php
class NormalizedOrder
{
    public function __construct(
        public readonly string $orderId,           // ID заказа на маркетплейсе
        public readonly string $orderNumber,       // Номер заказа (человекочитаемый)
        public readonly string $status,            // Нормализованный статус
        public readonly string $fulfillmentType,   // fbo|fbs|dbs
        public readonly array $items,              // Товары в заказе
        public readonly float $totalAmount,        // Сумма заказа
        public readonly string $currency,          // Валюта (UZS, RUB)
        public readonly ?Carbon $shipBy,           // Крайний срок отгрузки
        public readonly ?string $customerName,     // Имя покупателя (если доступно)
        public readonly ?string $deliveryAddress,  // Адрес доставки (если доступно)
        public readonly array $extra = [],         // Дополнительные поля
    ) {}
}
```

---

## 5. Алгоритмы обработки

### 5.1. Webhook Flow (Ozon, Yandex Market)

```
Маркетплейс ──POST──▶ Nginx ──▶ Laravel Router ──▶ Middleware (Auth)
                                                         │
                                                    ┌────▼─────┐
                                                    │Controller │
                                                    └────┬─────┘
                                                         │
                        ┌────────────────────────────────┼────────────────┐
                        │                                │                │
                   1. Save raw payload            2. Return 200 OK   3. Dispatch Job
                   to marketplace_events          (immediately)       to Queue
                   status: 'received'                                     │
                        │                                                 │
                        │                                           ┌─────▼──────┐
                        │                                           │ Queue Worker│
                        │                                           └─────┬──────┘
                        │                                                 │
                        │                              ┌──────────────────┼──────────────┐
                        │                              │                  │              │
                        │                         4. Dedup           5. Normalize    6. Handle
                        │                         check              payload         event
                        │                              │                  │              │
                        │                              │                  │         ┌────▼─────┐
                        │                              │                  │         │Notify    │
                        │                              │                  │         │User      │
                        │                              │                  │         └──────────┘
                        │                              │                  │
                        └──────────────────────────────┴──────────────────┘
                                                    Update status: 'processed'
```

Шаги:

1. Маркетплейс отправляет POST на `/api/webhook/{marketplace}/{store_uuid}`
2. Middleware проверяет аутентификацию (IP whitelist для Ozon, OAuth для Yandex)
3. Controller извлекает raw payload и **немедленно** сохраняет в `marketplace_events` со статусом `received`
4. Controller отправляет ответ **200 OK** (до начала обработки)
5. Dispatch Job `ProcessMarketplaceEventJob` в очередь `marketplace-events`
6. Job: `DeduplicationService` проверяет уникальность (`external_id + marketplace`)
7. Job: `EventNormalizer` преобразует payload в `MarketplaceEvent`
8. Job: `EventDispatcher` вызывает соответствующий Handler
9. Job: `NotificationService` отправляет уведомление пользователю
10. Job: Обновляет статус события в `marketplace_events` на `processed`

### 5.2. Polling Flow (Wildberries, Uzum)

```
Laravel Scheduler (cron)
         │
    ┌────▼─────┐
    │ Check    │──── is_locked? ──── YES ──▶ Skip
    │ Lock     │
    └────┬─────┘
         │ NO
    ┌────▼─────┐
    │ Set Lock │
    │ Read     │
    │ cursor   │
    └────┬─────┘
         │
    ┌────▼──────────────┐
    │ API Request       │
    │ (with cursor)     │──── Error? ──▶ increment consecutive_errors
    └────┬──────────────┘               release lock, backoff
         │ OK
    ┌────▼──────────────┐
    │ For each record:  │
    │ - Dedup check     │
    │ - Save to events  │
    │ - Dispatch job    │
    └────┬──────────────┘
         │
    ┌────▼──────────────┐
    │ Update cursor     │
    │ Update last_poll  │
    │ Reset errors = 0  │
    │ Release lock      │
    └───────────────────┘
```

Шаги:

1. Laravel Scheduler запускает `PollMarketplaceJob` каждые N секунд
2. `PollingLockManager` проверяет блокировку (`is_locked`) для предотвращения параллельного выполнения
3. Job устанавливает блокировку и считывает `last_cursor` из `polling_states`
4. Job выполняет API-запрос к маркетплейсу с cursor/timestamp
5. Для каждой новой записи: `DeduplicationService` сравнивает с существующими событиями
6. Новые записи сохраняются в `marketplace_events` со статусом `received`
7. Для каждого нового события: dispatch `ProcessMarketplaceEventJob`
8. Обновляет `last_cursor` и `last_poll_at` в `polling_states`
9. Снимает блокировку. При ошибке — инкрементирует `consecutive_errors`

### 5.3. Обработка ошибок и retry

- Максимум **5 попыток** обработки события с exponential backoff: `10s, 30s, 90s, 270s, 810s`
- После 5 неуспешных попыток — статус `failed`, алерт в Telegram
- При 3+ `consecutive_errors` в polling — автоматическое увеличение интервала в 2 раза
- При 10+ `consecutive_errors` — деактивация polling, алерт администратору
- Dead Letter Queue (DLQ) для событий, которые не удалось обработать
- Ручной replay через API `/api/v1/events/{uuid}/replay`

```php
// ProcessMarketplaceEventJob.php

public int $tries = 5;

public function backoff(): array
{
    return [10, 30, 90, 270, 810];
}

public function failed(\Throwable $exception): void
{
    $this->event->update([
        'status' => EventStatus::FAILED,
        'error_message' => $exception->getMessage(),
    ]);

    AlertService::sendTelegram(
        "❌ Event processing failed after {$this->attempts()} attempts\n"
        . "Event: {$this->event->uuid}\n"
        . "Type: {$this->event->event_type}\n"
        . "Error: {$exception->getMessage()}"
    );
}
```

### 5.4. Дедупликация

Механизм предотвращения повторной обработки одного и того же события:

| Маркетплейс | Ключ дедупликации | Метод |
|-------------|-------------------|-------|
| Ozon | `message_type + posting_number + timestamp` (окно 5 мин) | Webhook может отправлять дубликаты |
| Yandex | `event_type + order_id + updatedAt` | По документации возможны повторы |
| Wildberries | `order_id + status` | Хранение последнего известного состояния |
| Uzum | `order_id + дата модификации` | Сравнение с БД |

Реализация: составной unique index по `(marketplace, external_id)` + Redis cache с TTL 10 минут для быстрой проверки.

```php
class DeduplicationService
{
    public function isDuplicate(MarketplaceType $marketplace, string $externalId): bool
    {
        $cacheKey = "dedup:{$marketplace->value}:{$externalId}";

        // Fast check: Redis cache
        if (Cache::has($cacheKey)) {
            return true;
        }

        // Slow check: Database
        $exists = MarketplaceEventLog::where('marketplace', $marketplace)
            ->where('external_id', $externalId)
            ->exists();

        if ($exists) {
            Cache::put($cacheKey, true, now()->addMinutes(10));
            return true;
        }

        return false;
    }

    public function markProcessed(MarketplaceType $marketplace, string $externalId): void
    {
        Cache::put("dedup:{$marketplace->value}:{$externalId}", true, now()->addMinutes(10));
    }
}
```

---

## 6. Детальная реализация по маркетплейсам

### 6.1. Ozon Webhook Controller

```php
// app/Modules/MarketplaceNotifications/Webhook/Controllers/OzonWebhookController.php

class OzonWebhookController extends Controller
{
    public function __construct(
        private EventStoreService $eventStore,
        private DeduplicationService $dedup,
    ) {}

    public function handle(Request $request, string $storeUuid): JsonResponse
    {
        // 1. Find store by UUID
        $config = WebhookConfig::where('webhook_url', 'LIKE', "%{$storeUuid}")
            ->where('marketplace', MarketplaceType::OZON)
            ->where('is_active', true)
            ->firstOrFail();

        // 2. Parse Ozon push payload
        $payload = $request->all();
        $messageType = $payload['message_type'] ?? 'unknown';
        $postingNumber = $payload['posting_number'] ?? null;

        // 3. Generate external_id for deduplication
        $externalId = md5($messageType . $postingNumber . ($payload['changed_at'] ?? ''));

        // 4. Check duplicate
        if ($this->dedup->isDuplicate(MarketplaceType::OZON, $externalId)) {
            return response()->json(['status' => 'duplicate'], 200);
        }

        // 5. Map Ozon message_type to internal EventType
        $eventType = $this->mapEventType($messageType);
        $entityType = $this->mapEntityType($messageType);

        // 6. Store event immediately
        $event = $this->eventStore->create([
            'store_id' => $config->store_id,
            'marketplace' => MarketplaceType::OZON,
            'event_type' => $eventType,
            'external_id' => $externalId,
            'entity_type' => $entityType,
            'entity_id' => $postingNumber ?? $payload['chat_id'] ?? 'unknown',
            'payload' => $payload,
            'status' => EventStatus::RECEIVED,
            'metadata' => ['ip' => $request->ip(), 'headers' => $request->headers->all()],
        ]);

        // 7. Dispatch async processing
        ProcessMarketplaceEventJob::dispatch($event)
            ->onQueue('marketplace-events');

        // 8. Update webhook stats
        $config->increment('events_count');
        $config->update(['last_received_at' => now()]);

        // 9. Return 200 immediately
        return response()->json(['status' => 'ok'], 200);
    }

    private function mapEventType(string $ozonType): EventType
    {
        return match ($ozonType) {
            'TYPE_NEW_POSTING'      => EventType::ORDER_CREATED,
            'TYPE_POSTING_CANCELLED'=> EventType::ORDER_CANCELLED,
            'TYPE_NEW_MESSAGE'      => EventType::CHAT_MESSAGE_CREATED,
            'TYPE_UPDATE_MESSAGE'   => EventType::CHAT_MESSAGE_UPDATED,
            'TYPE_MESSAGE_READ'     => EventType::CHAT_MESSAGE_READ,
            'TYPE_CHAT_CLOSED'      => EventType::CHAT_CLOSED,
            default                 => EventType::ORDER_UPDATED,
        };
    }

    private function mapEntityType(string $ozonType): EntityType
    {
        return match (true) {
            str_contains($ozonType, 'MESSAGE'),
            str_contains($ozonType, 'CHAT') => EntityType::CHAT,
            default                         => EntityType::ORDER,
        };
    }
}
```

### 6.2. Ozon IP Verification Middleware

```php
// app/Modules/MarketplaceNotifications/Webhook/Middleware/VerifyOzonIP.php

class VerifyOzonIP
{
    private array $allowedRanges;

    public function __construct()
    {
        $this->allowedRanges = explode(',', config('marketplace.ozon.webhook_ip_whitelist'));
    }

    public function handle(Request $request, Closure $next): Response
    {
        $clientIP = $request->ip();

        foreach ($this->allowedRanges as $range) {
            if ($this->ipInRange($clientIP, trim($range))) {
                return $next($request);
            }
        }

        Log::warning("Ozon webhook: rejected IP {$clientIP}");
        abort(403, 'IP not in Ozon whitelist');
    }

    private function ipInRange(string $ip, string $range): bool
    {
        if (!str_contains($range, '/')) {
            return $ip === $range;
        }

        [$subnet, $mask] = explode('/', $range, 2);
        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);
        $maskLong = -1 << (32 - (int)$mask);

        return ($ipLong & $maskLong) === ($subnetLong & $maskLong);
    }
}
```

### 6.3. Wildberries Polling Job

```php
// app/Modules/MarketplaceNotifications/Polling/Jobs/PollWildberriesOrdersJob.php

class PollWildberriesOrdersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 30;

    public function handle(
        PollingLockManager $lockManager,
        DeduplicationService $dedup,
        EventStoreService $eventStore,
        WildberriesApiClient $api,
    ): void {
        // Get all active WB stores with polling enabled
        $pollingStates = PollingState::where('marketplace', MarketplaceType::WILDBERRIES)
            ->where('endpoint', 'orders_new')
            ->where('is_active', true)
            ->get();

        foreach ($pollingStates as $state) {
            // Check lock
            if (!$lockManager->acquire($state)) {
                continue;
            }

            try {
                $store = $state->store;
                $apiKey = decrypt($store->wb_api_key);

                // API request: GET /api/v3/orders/new
                $response = $api->getNewOrders($apiKey);

                if (empty($response['orders'])) {
                    $state->update(['last_poll_at' => now(), 'consecutive_errors' => 0]);
                    continue;
                }

                foreach ($response['orders'] as $order) {
                    $externalId = "wb_order_{$order['id']}_{$order['status']}";

                    if ($dedup->isDuplicate(MarketplaceType::WILDBERRIES, $externalId)) {
                        continue;
                    }

                    $event = $eventStore->create([
                        'store_id' => $store->id,
                        'marketplace' => MarketplaceType::WILDBERRIES,
                        'event_type' => EventType::ORDER_CREATED,
                        'external_id' => $externalId,
                        'entity_type' => EntityType::ORDER,
                        'entity_id' => (string)$order['id'],
                        'payload' => $order,
                        'status' => EventStatus::RECEIVED,
                    ]);

                    ProcessMarketplaceEventJob::dispatch($event)
                        ->onQueue('marketplace-events');

                    $dedup->markProcessed(MarketplaceType::WILDBERRIES, $externalId);
                }

                // Update cursor (last order ID)
                $lastOrder = end($response['orders']);
                $state->update([
                    'last_cursor' => (string)$lastOrder['id'],
                    'last_poll_at' => now(),
                    'consecutive_errors' => 0,
                ]);

            } catch (\Throwable $e) {
                $state->increment('consecutive_errors');
                Log::error("WB Polling error for store {$state->store_id}: {$e->getMessage()}");

                // Auto-deactivate after 10 consecutive errors
                if ($state->consecutive_errors >= 10) {
                    $state->update(['is_active' => false]);
                    AlertService::sendTelegram(
                        "⚠️ WB Polling deactivated for store {$state->store_id}\n"
                        . "Errors: {$state->consecutive_errors}\n"
                        . "Last: {$e->getMessage()}"
                    );
                }
            } finally {
                $lockManager->release($state);
            }
        }
    }
}
```

### 6.4. Yandex Market Webhook Controller

```php
// app/Modules/MarketplaceNotifications/Webhook/Controllers/YandexMarketWebhookController.php

class YandexMarketWebhookController extends Controller
{
    public function handle(Request $request, string $storeUuid): JsonResponse
    {
        $config = WebhookConfig::where('webhook_url', 'LIKE', "%{$storeUuid}")
            ->where('marketplace', MarketplaceType::YANDEX)
            ->where('is_active', true)
            ->firstOrFail();

        $payload = $request->all();
        $eventType = $payload['type'] ?? 'unknown';
        $orderId = $payload['order']['id'] ?? $payload['return']['id'] ?? null;
        $updatedAt = $payload['updatedAt'] ?? $payload['createdAt'] ?? now()->toISOString();

        $externalId = md5($eventType . $orderId . $updatedAt);

        if ($this->dedup->isDuplicate(MarketplaceType::YANDEX, $externalId)) {
            return response()->json(['status' => 'ok'], 200);
        }

        $internalType = match ($eventType) {
            'ORDER_CREATED'                 => EventType::ORDER_CREATED,
            'ORDER_STATUS_UPDATED'          => EventType::ORDER_STATUS_CHANGED,
            'ORDER_CANCELLED'               => EventType::ORDER_CANCELLED,
            'ORDER_UPDATED'                 => EventType::ORDER_UPDATED,
            'ORDER_RETURN_CREATED'          => EventType::RETURN_CREATED,
            'ORDER_RETURN_STATUS_UPDATED'   => EventType::RETURN_STATUS_CHANGED,
            'ORDER_CANCELLATION_REQUEST'    => EventType::ORDER_CANCELLED,
            'CHAT_MESSAGE_CREATED'          => EventType::CHAT_MESSAGE_CREATED,
            default                         => EventType::ORDER_UPDATED,
        };

        $entityType = str_contains($eventType, 'RETURN')
            ? EntityType::RETURN
            : (str_contains($eventType, 'CHAT') ? EntityType::CHAT : EntityType::ORDER);

        $event = $this->eventStore->create([
            'store_id' => $config->store_id,
            'marketplace' => MarketplaceType::YANDEX,
            'event_type' => $internalType,
            'external_id' => $externalId,
            'entity_type' => $entityType,
            'entity_id' => (string)$orderId,
            'payload' => $payload,
            'status' => EventStatus::RECEIVED,
        ]);

        ProcessMarketplaceEventJob::dispatch($event)->onQueue('marketplace-events');

        $config->increment('events_count');
        $config->update(['last_received_at' => now()]);

        return response()->json(['status' => 'ok'], 200);
    }
}
```

---

## 7. Доставка уведомлений пользователю

### 7.1. Каналы доставки

| Канал | Технология | Задержка | Приоритет |
|-------|-----------|----------|-----------|
| **WebSocket** | Laravel Broadcasting + Pusher/Soketi | < 1 сек | Высокий (primary) |
| **Telegram Bot** | FORRIS Support Bot (PHP) | 1-3 сек | Высокий (primary) |
| **Push (PWA)** | Web Push API / Firebase FCM | 2-5 сек | Средний |
| **Email** | Laravel Mail (SMTP) | 30-60 сек | Низкий (digest) |
| **In-App** | Таблица `user_notifications` | Мгновенно | Фоновый |

### 7.2. Настройки пользователя

Каждый пользователь может настроить для каждого типа события:

- Какие каналы использовать (WebSocket, Telegram, Push, Email)
- Фильтры: по маркетплейсу, по магазину, по типу события
- Quiet Hours: временные рамки, когда не отправлять уведомления (кроме срочных)
- Группировка: получать дайджест каждые N минут вместо мгновенных уведомлений
- Формат Telegram-сообщений: краткий / подробный

### 7.3. Формат Telegram-уведомления

```
✅ НОВЫЙ ЗАКАЗ

📦 Маркетплейс: Ozon
🏪 Магазин: FORRIS HOME
🔢 Заказ: #39268230-0002
📋 Товар: Фитнес-браслет (x1)
💰 Сумма: 79 990 сум
📦 Схема: FBS
🕐 Время: 11.03.2026 14:32

⏰ Собрать до: 12.03.2026 16:00
```

```
⚠️ СТАТУС ЗАКАЗА ИЗМЕНЁН

📦 Маркетплейс: Wildberries
🔢 Заказ: #WB-445566
📊 Статус: В доставке → Доставлен
🕐 Время: 11.03.2026 18:45
```

```
❌ ВОЗВРАТ

📦 Маркетплейс: Yandex Market
🔢 Заказ: #YM-778899
📋 Товар: Чехол для телефона (x2)
💰 Сумма возврата: 15 000 сум
📝 Причина: Не подошёл размер
```

### 7.4. WebSocket Event Broadcasting

```php
// Broadcasting event for real-time UI updates
class MarketplaceEventReceived implements ShouldBroadcast
{
    public function __construct(
        public MarketplaceEvent $event,
        public int $userId,
    ) {}

    public function broadcastOn(): Channel
    {
        return new PrivateChannel("user.{$this->userId}.marketplace-events");
    }

    public function broadcastAs(): string
    {
        return 'marketplace.event';
    }

    public function broadcastWith(): array
    {
        return [
            'uuid' => $this->event->uuid,
            'marketplace' => $this->event->marketplace->value,
            'event_type' => $this->event->eventType->value,
            'entity_type' => $this->event->entityType->value,
            'entity_id' => $this->event->entityId,
            'summary' => $this->event->normalized?->toSummary(),
            'occurred_at' => $this->event->occurredAt->toISOString(),
        ];
    }
}
```

---

## 8. Безопасность

### 8.1. Аутентификация webhook-запросов

**Ozon:**
- Whitelist IP-адресов Ozon: `185.71.76.0/27`, `185.71.77.0/27`, `77.75.153.0/25`, `77.75.154.128/25`
- `store_uuid` в URL — непубличный UUID v4, привязанный к магазину
- Rate limiting: максимум 100 запросов в секунду на endpoint

**Yandex Market:**
- Проверка OAuth-токена в заголовке `Authorization`
- HMAC-SHA256 подпись payload (при наличии)
- `store_uuid` в URL для идентификации магазина

### 8.2. Защита polling-каналов

- API-ключи маркетплейсов хранятся в зашифрованном виде (AES-256-CBC)
- Ключи шифрования берутся из `APP_KEY` (Laravel encryption)
- Логирование всех API-вызовов без sensitive data (ключи маскируются)
- Separate database credentials для модуля уведомлений

### 8.3. Webhook URL Security

- UUID v4 в URL — практически невозможно угадать (122 бита энтропии)
- HTTPS обязателен для всех webhook endpoints
- Rate limiting: 100 req/sec per store, 1000 req/sec global
- Payload size limit: максимум 1 MB
- Request timeout: 5 секунд для чтения body

---

## 9. Мониторинг и наблюдаемость

### 9.1. Метрики

| Метрика | Описание | Алерт |
|---------|----------|-------|
| `events_received_total` | Общее число полученных событий | N/A |
| `events_processed_total` | Обработанные события | Если 0 за 10 мин — Warning |
| `events_failed_total` | Неуспешные обработки | Если > 5 за 5 мин — Critical |
| `webhook_response_time_ms` | Время ответа webhook endpoint | Если > 3000ms — Warning |
| `polling_last_success_ago_s` | Секунд с последнего успешного poll | Если > 300 — Critical |
| `polling_consecutive_errors` | Последовательные ошибки polling | Если > 5 — Warning |
| `queue_depth` | Размер очереди marketplace-events | Если > 100 — Warning |
| `dedup_hits_total` | Отброшенные дубликаты | Если > 50% — Investigate |

### 9.2. Health Check Dashboard

Страница `/admin/notifications/health` в панели администратора SellerMind:

- Статус каждого webhook-канала (last received, events count, errors)
- Статус каждого polling-канала (last poll, interval, cursor, errors)
- График количества событий за последние 24 часа по маркетплейсам
- Таблица последних 50 событий с возможностью фильтрации
- Кнопка **Force Poll** для каждого polling-канала
- Кнопка **Replay** для повторной обработки failed-событий

### 9.3. Health Check Endpoint

```php
// GET /api/v1/notifications/health

{
    "status": "healthy",
    "channels": {
        "ozon_webhook": {
            "status": "active",
            "last_event": "2026-03-11T14:32:00Z",
            "events_24h": 47,
            "errors_24h": 0
        },
        "yandex_webhook": {
            "status": "active",
            "last_event": "2026-03-11T14:28:00Z",
            "events_24h": 23,
            "errors_24h": 1
        },
        "wildberries_polling": {
            "status": "active",
            "last_poll": "2026-03-11T14:32:30Z",
            "interval_sec": 30,
            "consecutive_errors": 0,
            "events_24h": 156
        },
        "uzum_polling": {
            "status": "degraded",
            "last_poll": "2026-03-11T14:30:00Z",
            "interval_sec": 60,
            "consecutive_errors": 3,
            "events_24h": 12
        }
    },
    "queue": {
        "depth": 3,
        "failed": 0,
        "processing": 2
    }
}
```

---

## 10. Конфигурация

### 10.1. Environment Variables

```env
# ──── Webhook ────
WEBHOOK_BASE_URL=https://api.sellermind.uz
WEBHOOK_RATE_LIMIT=100
WEBHOOK_PAYLOAD_MAX_SIZE=1048576

# ──── Ozon ────
OZON_WEBHOOK_IP_WHITELIST=185.71.76.0/27,185.71.77.0/27,77.75.153.0/25,77.75.154.128/25

# ──── Polling Intervals ────
POLLING_WB_ORDERS_INTERVAL=30
POLLING_WB_SALES_INTERVAL=300
POLLING_UZUM_ORDERS_INTERVAL=60
POLLING_MAX_CONSECUTIVE_ERRORS=10
POLLING_BACKOFF_MULTIPLIER=2

# ──── Notifications ────
NOTIFICATION_TELEGRAM_ENABLED=true
NOTIFICATION_WEBSOCKET_ENABLED=true
NOTIFICATION_EMAIL_ENABLED=false

# ──── Queue ────
MARKETPLACE_EVENTS_QUEUE=marketplace-events
MARKETPLACE_EVENTS_RETRY_AFTER=90
MARKETPLACE_EVENTS_MAX_TRIES=5

# ──── Monitoring ────
HEALTH_CHECK_INTERVAL=60
ALERT_TELEGRAM_CHAT_ID=-100xxxxxxxxxx
```

### 10.2. Laravel Scheduler

```php
// app/Console/Kernel.php

protected function schedule(Schedule $schedule): void
{
    // ── Wildberries Polling ──
    $schedule->job(new PollWildberriesOrdersJob)
        ->everyThirtySeconds()
        ->withoutOverlapping()
        ->runInBackground();

    $schedule->job(new PollWildberriesSalesJob)
        ->everyFiveMinutes()
        ->withoutOverlapping()
        ->runInBackground();

    // ── Uzum Polling ──
    $schedule->job(new PollUzumOrdersJob)
        ->everyMinute()
        ->withoutOverlapping()
        ->runInBackground();

    // ── Health Monitoring ──
    $schedule->job(new HealthCheckJob)
        ->everyMinute();

    // ── Cleanup old events (> 90 days) ──
    $schedule->job(new CleanupOldEventsJob)
        ->daily()
        ->at('03:00');

    // ── Stale lock cleanup ──
    $schedule->job(new CleanupStaleLocks)
        ->everyFiveMinutes();
}
```

### 10.3. Supervisor Config

```ini
; /etc/supervisor/conf.d/sellermind-marketplace-events.conf

[program:sellermind-marketplace-events]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/sellermind/artisan queue:work redis --queue=marketplace-events --sleep=1 --tries=5 --timeout=30 --max-jobs=1000 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/sellermind/marketplace-events.log
stopwaitsecs=60
```

### 10.4. Webhook Routes

```php
// routes/webhook.php

Route::prefix('api/webhook')->group(function () {
    Route::post('ozon/{storeUuid}', [OzonWebhookController::class, 'handle'])
        ->middleware(['throttle:webhook', VerifyOzonIP::class])
        ->name('webhook.ozon');

    Route::post('yandex/{storeUuid}', [YandexMarketWebhookController::class, 'handle'])
        ->middleware(['throttle:webhook', VerifyYandexSignature::class])
        ->name('webhook.yandex');

    Route::get('health', [WebhookHealthController::class, 'index'])
        ->name('webhook.health');
});
```

---

## 11. Тестирование

### 11.1. Unit-тесты

- `EventNormalizer` для каждого маркетплейса (входной payload → `MarketplaceEvent`)
- `DeduplicationService` (дубликаты vs уникальные события)
- Каждый Handler (mock `MarketplaceEvent` → ожидаемые side effects)
- Middleware верификации (валидные/невалидные IP, подписи)

### 11.2. Integration-тесты

- Полный Webhook flow: HTTP POST → DB record → Job → Handler → Notification
- Polling flow: Mock API response → DeduplicationService → Event creation
- Error handling: невалидный payload, timeout, 429 rate limit
- Replay: повторная обработка failed-события

### 11.3. E2E тестирование

- **Ozon:** создать тестовый заказ → проверить получение webhook → проверить Telegram
- **Yandex:** использовать тестовые заказы из sandbox → проверить полный flow
- **Wildberries:** создать тестовое FBS-заказ → проверить polling обнаружение
- **Uzum:** использовать staging-среду seller.uzum.uz → проверить polling
- **Load test:** 100 concurrent webhook requests → проверить throughput и latency

### 11.4. Примеры тестов

```php
// tests/Feature/Webhook/OzonWebhookTest.php

class OzonWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_ozon_webhook_creates_event(): void
    {
        $store = Store::factory()->create();
        $config = WebhookConfig::factory()->create([
            'store_id' => $store->id,
            'marketplace' => MarketplaceType::OZON,
            'webhook_url' => "/api/webhook/ozon/{$store->uuid}",
        ]);

        $payload = [
            'message_type' => 'TYPE_NEW_POSTING',
            'posting_number' => '39268230-0002-3',
            'changed_at' => now()->toISOString(),
        ];

        $response = $this->postJson(
            "/api/webhook/ozon/{$store->uuid}",
            $payload,
            ['REMOTE_ADDR' => '185.71.76.1'] // Ozon IP
        );

        $response->assertStatus(200);
        $this->assertDatabaseHas('marketplace_events', [
            'store_id' => $store->id,
            'marketplace' => 'ozon',
            'event_type' => 'order_created',
            'entity_id' => '39268230-0002-3',
            'status' => 'received',
        ]);
    }

    public function test_ozon_webhook_rejects_invalid_ip(): void
    {
        $store = Store::factory()->create();
        WebhookConfig::factory()->create([
            'store_id' => $store->id,
            'marketplace' => MarketplaceType::OZON,
        ]);

        $response = $this->postJson(
            "/api/webhook/ozon/{$store->uuid}",
            ['message_type' => 'TYPE_NEW_POSTING'],
            ['REMOTE_ADDR' => '1.2.3.4'] // Not Ozon IP
        );

        $response->assertStatus(403);
    }

    public function test_ozon_webhook_deduplicates(): void
    {
        $store = Store::factory()->create();
        WebhookConfig::factory()->create([
            'store_id' => $store->id,
            'marketplace' => MarketplaceType::OZON,
        ]);

        $payload = [
            'message_type' => 'TYPE_NEW_POSTING',
            'posting_number' => '39268230-0002-3',
            'changed_at' => '2026-03-11T14:00:00Z',
        ];

        // First request
        $this->postJson("/api/webhook/ozon/{$store->uuid}", $payload);

        // Second request (duplicate)
        $response = $this->postJson("/api/webhook/ozon/{$store->uuid}", $payload);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'duplicate']);

        $this->assertDatabaseCount('marketplace_events', 1);
    }
}
```

---

## 12. План реализации

### 12.1. Этапы

| # | Задача | Срок | Приоритет | Зависимости |
|---|--------|------|-----------|-------------|
| 1 | Проектирование БД + миграции | 2 дня | 🔴 Must | — |
| 2 | Базовые модели, DTO, Enums | 1 день | 🔴 Must | Этап 1 |
| 3 | Event Store + DeduplicationService | 2 дня | 🔴 Must | Этап 2 |
| 4 | Ozon Webhook Receiver + Normalizer | 3 дня | 🔴 Must | Этап 3 |
| 5 | Yandex Market Webhook + Normalizer | 3 дня | 🔴 Must | Этап 3 |
| 6 | WB Polling Engine + Normalizer | 3 дня | 🔴 Must | Этап 3 |
| 7 | Uzum Polling Engine + Normalizer | 4 дня | 🟡 Should | Этап 3 |
| 8 | Event Handlers (Order, Return, Chat) | 3 дня | 🔴 Must | Этапы 4-6 |
| 9 | Telegram Bot уведомления | 2 дня | 🔴 Must | Этап 8 |
| 10 | WebSocket уведомления (Pusher/Soketi) | 2 дня | 🟡 Should | Этап 8 |
| 11 | Admin Dashboard (Health, Events log) | 3 дня | 🟡 Should | Этапы 4-7 |
| 12 | User Notification Settings UI | 2 дня | 🔵 Could | Этапы 9-10 |
| 13 | Monitoring + Alerting | 2 дня | 🟡 Should | Этапы 4-7 |
| 14 | Unit + Integration тесты | 3 дня | 🔴 Must | Этапы 4-8 |
| 15 | E2E тестирование + bugfix | 3 дня | 🔴 Must | Все этапы |
| 16 | Документация + деплой | 2 дня | 🔴 Must | Все этапы |

**Общая оценка: 38-40 рабочих дней (~8 недель)**

### 12.2. Приоритизация (MoSCoW)

**🔴 Must Have (MVP):**
- Ozon Webhook + обработка заказов
- Wildberries Polling + обработка заказов
- Telegram уведомления
- Event Store + дедупликация
- Базовые тесты

**🟡 Should Have:**
- Yandex Market Webhook
- WebSocket уведомления
- Admin Dashboard
- Monitoring + Alerting

**🔵 Could Have:**
- Uzum Market Polling
- User notification settings
- Email digest
- Event replay UI

**⚪ Won't Have (v1):**
- Двусторонняя синхронизация (отправка статусов обратно)
- AI-анализ событий (автоклассификация причин возврата)
- Multi-tenant webhook proxy

### 12.3. Roadmap

```
Неделя 1-2: Фундамент
  ├── БД миграции, модели, DTO, Enums
  ├── Event Store + Deduplication
  └── Queue infrastructure (Redis + Supervisor)

Неделя 3-4: Транспорт
  ├── Ozon Webhook (Controller + Middleware + Normalizer)
  ├── Yandex Webhook (Controller + Middleware + Normalizer)
  └── WB Polling (Job + Lock Manager + Normalizer)

Неделя 5-6: Обработка + Уведомления
  ├── Event Handlers (Order, Return, Chat)
  ├── Telegram notifications
  ├── WebSocket broadcasting
  └── Uzum Polling (если время позволяет)

Неделя 7-8: Качество + Деплой
  ├── Admin Dashboard
  ├── Health monitoring + Alerts
  ├── Unit + Integration + E2E тесты
  └── Документация + Production deploy
```

---

## 13. Технологический стек

| Компонент | Технология | Версия / Примечания |
|-----------|-----------|---------------------|
| Backend Framework | Laravel | 11.x (PHP 8.3+) |
| Queue Driver | Redis / Database | Redis предпочтителен для production |
| Cache | Redis | Для дедупликации и rate limiting |
| Database | MySQL / PostgreSQL | Существующая БД SellerMind |
| WebSocket | Soketi / Laravel Reverb | Self-hosted, совместим с Pusher protocol |
| Telegram Bot | PHP (custom / Nutgram) | Существующий FORRIS Support Bot |
| Scheduler | Laravel Scheduler + Supervisor | Для polling jobs |
| Monitoring | Laravel Telescope + custom | Для отладки в staging |
| Testing | PHPUnit + Pest | Unit + Integration |
| Deploy | Laravel Forge + GitHub | Существующая CI/CD на VPS |

### 13.1. Требования к инфраструктуре

- **VPS:** минимум 2 vCPU, 4 GB RAM (текущий сервер SellerMind)
- **Redis:** отдельный инстанс или на том же сервере с выделением 512 MB RAM
- **Supervisor:** минимум 2 воркера для очереди `marketplace-events`
- **SSL:** обязателен для webhook endpoints (Let's Encrypt / Cloudflare)
- **DNS:** поддомен `api.sellermind.uz` для webhook endpoints
- **Cron:** `* * * * * php artisan schedule:run` (уже настроен)

---

## 14. Риски и митигация

| Риск | Вероятность | Влияние | Митигация |
|------|-------------|---------|-----------|
| Uzum Market изменит внутренний API без предупреждения | Высокая | Среднее | Fallback на парсинг, мониторинг, быстрое обновление |
| Wildberries введёт stricter rate limits | Средняя | Высокое | Адаптивный polling interval, exponential backoff |
| Ozon изменит формат push-уведомлений | Низкая | Высокое | Подписка на Telegram @OzonSellerAPI, версионирование normalizer |
| Потеря событий при перезапуске очередей | Средняя | Высокое | Redis persistence (AOF), graceful shutdown, health checks |
| Высокая нагрузка при большом количестве магазинов | Средняя | Среднее | Горизонтальное масштабирование воркеров, приоритизация очередей |

---

## Приложение A: Маппинг статусов заказов

| Статус SellerMind | Ozon | Yandex Market | Wildberries | Uzum |
|-------------------|------|---------------|-------------|------|
| `new` | awaiting_packaging | PROCESSING | 0 (new) | new |
| `assembling` | awaiting_deliver | — | 1 (confirm) | assembling |
| `shipping` | delivering | DELIVERY | 2 (complete) | shipping |
| `delivered` | delivered | DELIVERED | 3 (cancel) | delivered |
| `cancelled` | cancelled | CANCELLED | — | cancelled |
| `returned` | — | RETURNED | — | returned |

---

## Приложение B: Полезные ссылки

- **Ozon Seller API:** https://docs.ozon.ru/api/seller/
- **Ozon Push Notifications:** https://docs.ozon.ru/api/seller/#tag/push_start
- **Yandex Market Partner API:** https://yandex.ru/dev/market/partner-api/doc/ru/
- **Yandex Market API-уведомления:** https://yandex.ru/dev/market/partner-api/doc/ru/push-notifications/
- **Wildberries API:** https://dev.wildberries.ru/
- **Uzum Market Seller:** https://seller.uzum.uz/
- **Laravel Queues:** https://laravel.com/docs/11.x/queues
- **Laravel Broadcasting:** https://laravel.com/docs/11.x/broadcasting
- **Soketi:** https://soketi.app/

---

*Конец документа*

*SellerMind Marketplace Notifications Module — Technical Specification v1.0*
