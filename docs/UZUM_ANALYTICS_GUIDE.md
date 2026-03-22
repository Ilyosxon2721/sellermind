# Uzum Analytics — Руководство модуля

> Версия: 1.0 | Дата: 2026-03-23

Модуль мониторинга цен конкурентов на Uzum Market. Собирает публичные данные через анонимный API uzum.uz, хранит снепшоты цен и отправляет алерты в Telegram при изменениях.

---

## Архитектура

```
app/Modules/UzumAnalytics/
├── Console/
│   └── CheckApiStructureCommand.php   # php artisan uzum:check-api-structure
├── Http/Controllers/
│   └── AnalyticsController.php        # REST API эндпоинты
├── Jobs/
│   ├── RefreshTokenPoolJob.php        # каждые 5 мин — обновление пула JWT
│   ├── SyncCategoriesJob.php          # ежедневно 03:00 — дерево категорий
│   ├── CrawlCategoryJob.php           # GraphQL — список товаров категории
│   └── CrawlProductJob.php            # GET /v2/product/{id} — снепшот
├── Models/
│   ├── UzumToken.php                  # таблица: uzum_token_pool
│   ├── UzumCategory.php               # таблица: uzum_categories
│   ├── UzumProductSnapshot.php        # таблица: uzum_products_snapshots
│   └── UzumTrackedProduct.php         # таблица: uzum_tracked_products
├── Repositories/
│   └── AnalyticsRepository.php        # запросы + Redis кэш 30 мин
├── Services/
│   ├── CircuitBreaker.php             # 5 ошибок → пауза 60 мин
│   ├── RateLimiter.php                # Redis rate limiting + jitter
│   ├── TokenRefreshService.php        # пул JWT, round-robin ротация
│   └── UzumAnalyticsApiClient.php     # HTTP клиент, retry, backoff
└── routes/
    └── api.php                        # /api/analytics/uzum/*
```

---

## Переменные окружения

```env
# API Uzum (подтверждено через DevTools браузера)
UZUM_REST_API_URL=https://api.uzum.uz/api
UZUM_GRAPHQL_URL=https://graphql.uzum.uz
UZUM_AUTH_SERVER=https://id.uzum.uz
UZUM_CRAWLER_TOKEN_URL=https://api.uzum.uz/api/auth/token

# Таймаут запросов
UZUM_API_TIMEOUT=30

# Включить модуль (по умолчанию выключен)
UZUM_ANALYTICS_ENABLED=true
UZUM_BETA_ONLY=false

# Лимиты по тарифам
UZUM_LIMIT_PRO=20
UZUM_LIMIT_BUSINESS=50

# Telegram чат для алертов краулера (отдельный от основного)
UZUM_CRAWLER_TELEGRAM_CHAT_ID=your_chat_id
UZUM_DEV_CHAT_ID=dev_chat_id  # алерты разработчику (ошибки структуры API)

# Снепшоты в сутки (4 = 00:00/06:00/12:00/18:00 UTC+5)
UZUM_SNAPSHOTS_PER_DAY=4
```

---

## API эндпоинты

Все маршруты требуют авторизации (`auth:sanctum`).

| Метод | URL | Описание |
|-------|-----|----------|
| GET | `/api/analytics/uzum/categories` | Список категорий из БД |
| GET | `/api/analytics/uzum/category/{id}/products` | Статистика категории + топ товары |
| GET | `/api/analytics/uzum/competitor/{slug}` | Данные магазина конкурента |
| GET | `/api/analytics/uzum/price-history/{productId}` | История цен (параметр `?days=30`) |
| GET | `/api/analytics/uzum/market-overview` | Сводка по отслеживаемым товарам |
| GET | `/api/analytics/uzum/tracked` | Список отслеживаемых товаров |
| POST | `/api/analytics/uzum/tracked` | Добавить товар в отслеживание |
| DELETE | `/api/analytics/uzum/tracked/{productId}` | Удалить из отслеживания |
| GET | `/api/analytics/uzum/export` | Скачать CSV (`?type=tracked\|snapshots&days=30`) |
| GET | `/api/analytics/uzum/health` | Healthcheck краулера |

### POST /api/analytics/uzum/tracked

```json
{
  "product_id": 12345678,
  "alert_enabled": true,
  "alert_threshold_pct": 5
}
```

### GET /api/analytics/uzum/health — пример ответа

```json
{
  "status": "ok",
  "tokens": { "active": 3, "total": 5 },
  "circuit_breaker": {
    "status": "closed",
    "consecutive_failures": 0,
    "failures_in_window": 0,
    "pause_until": null
  },
  "last_snapshot": "2026-03-23T18:00:00+05:00",
  "snapshots_today": 4,
  "tracked_products": 12,
  "feature_enabled": true
}
```

---

## UI дашборд

Страница: `/analytics/uzum` (ссылка в sidebar — оранжевый цвет)

**Табы:**
- **Обзор рынка** — таблица отслеживаемых товаров с трендом цены за 7 дней, кнопка добавления
- **Отслеживаемые** — детальный список с настройками алертов
- **Категории** — дерево категорий из БД (синхронизируется ежедневно)

**Модальное окно** — добавить товар по ID (найти в URL uzum.uz/product/{ID})

**График** — Chart.js линейный график истории цен при клике на товар

---

## Rate Limits

| Тип запроса | Лимит | Задержка |
|-------------|-------|----------|
| Карточка товара | 10/мин | 6 сек |
| Категория (GraphQL) | 5/мин | 12 сек |
| Магазин | 8/мин | 8 сек |
| Токен | 2/мин | 30 сек |

К каждой задержке добавляется случайный jitter ×0.5 для снижения паттерна.

---

## Пул токенов

Uzum использует анонимный JWT, выдаваемый при первом открытии сайта. Стратегия:

1. **Пул минимум 3 токена** — всегда готов к работе
2. **Round-robin ротация** — наименьший `requests_count` используется первым
3. **TTL = 12 минут** — обновляем за 2 мин до истечения
4. **Max 8 запросов на токен** — затем деактивируется
5. **RefreshTokenPoolJob** — запускается каждые 5 минут

Метод получения токена: `POST https://api.uzum.uz/api/auth/token`

---

## Circuit Breaker

| Параметр | Значение |
|----------|----------|
| Порог ошибок | 5 за 10 минут |
| Пауза при срабатывании | 60 минут |
| Алерт в Telegram | при 3+ последовательных ошибках |

Состояния: `closed` (норма) → `open` (пауза) → автосброс через 60 мин.

---

## Тарифные лимиты

| Тариф | Макс. отслеживаемых | Снепшотов в сутки |
|-------|--------------------|--------------------|
| Free | 0 | 0 |
| Pro | 20 | 4 |
| Business | 50 | 4 |

---

## Команды

```bash
# Проверить структуру ответа Uzum API (запускается ежедневно в 09:00)
php artisan uzum:check-api-structure

# Проверить с конкретным товаром
php artisan uzum:check-api-structure --product-id=12345678

# Принудительно запустить снепшот для отдельного товара
php artisan tinker
>>> App\Modules\UzumAnalytics\Jobs\CrawlProductJob::dispatch(12345678, $companyId)->onQueue('uzum-crawler');

# Синхронизировать категории вручную
php artisan tinker
>>> App\Modules\UzumAnalytics\Jobs\SyncCategoriesJob::dispatch();
```

---

## Расписание (Scheduler)

| Задача | Расписание | Описание |
|--------|-----------|----------|
| `RefreshTokenPoolJob` | каждые 5 мин | Обновление пула JWT |
| `SyncCategoriesJob` | 03:00 ежедневно | Дерево категорий |
| `CrawlProductJob` × все товары | 00:00, 06:00, 12:00, 18:00 | Снепшоты цен |
| `uzum:check-api-structure` | 09:00 ежедневно | Мониторинг структуры API |

---

## Troubleshooting

### Краулер заблокирован (Circuit Breaker открыт)

```bash
# Проверить статус
curl -H "Authorization: Bearer {token}" https://sellermind.uz/api/analytics/uzum/health

# Принудительный сброс через tinker
php artisan tinker
>>> app(App\Modules\UzumAnalytics\Services\CircuitBreaker::class)->reset();
```

### Нет токенов в пуле

```bash
# Посмотреть токены
php artisan tinker
>>> App\Modules\UzumAnalytics\Models\UzumToken::all(['id','is_active','expires_at','requests_count']);

# Принудительно обновить пул
>>> app(App\Modules\UzumAnalytics\Services\TokenRefreshService::class)->refreshPool();
```

### API Uzum изменил структуру ответа

При алерте от `uzum:check-api-structure` нужно обновить:
1. `AnalyticsRepository::saveProductSnapshot()` — маппинг полей
2. `CheckApiStructureCommand::REQUIRED_PRODUCT_FIELDS` — список проверяемых полей
3. `CrawlCategoryJob` — парсинг GraphQL ответа

### Логи краулера

```bash
tail -f storage/logs/uzum-analytics.log
```

---

## Безопасность и юридическое

> **Внимание:** Модуль использует публичный API uzum.uz без официального партнёрства. Перед запуском в продакшн необходимо:
> - Получить юридическую консультацию по законодательству РУз (задача #060)
> - Настроить выделенный IP или прокси-ротацию (задача #061), чтобы блокировка краулера не затронула основной сервер

Модуль использует только публично доступные данные (те же данные, что видит любой посетитель uzum.uz).
