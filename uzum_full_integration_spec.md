
# ТЗ: Полная интеграция Uzum Market Seller API в SellerMindAi
## (с UI по аналогии с Wildberries)

Проект: SellerMindAi  
Стек: Laravel (PHP 8+), MySQL, Blade/SPA, фоновые задачи (Jobs / Queues).  
Канал: `uzum` (код канала, аналог `wildberries`).

> ВАЖНО: все реальные пути (`/v1/orders`, `/v1/products` и т.п.) и структура запросов/ответов должны браться ИЗ ОФИЦИАЛЬНОГО OpenAPI Uzum Seller (`Uzum market seller openapi`), который у нас уже есть или будет добавлен в проект отдельно.  
> В этом ТЗ пути / поля для Uzum указаны как ПРИМЕР и перед реализацией должны быть проверены по OpenAPI.

---

## 1. Цель интеграции

### 1.1. Что есть сейчас

В SellerMindAi уже реализован канал Wildberries с:

- подключением аккаунта;
- синхронизацией заказов и базовой информацией;
- страницей заказов с табами по статусам и детальной карточкой заказа.

### 1.2. Что нужно сделать для Uzum

Нужно реализовать полноценный канал `Uzum` с возможностями:

- подключение Uzum-аккаунта (API-ключи/токены);
- синхронизация:
  - товаров (каталог, карточки, офферы);
  - остатков;
  - цен;
  - заказов;
  - статусов/отмен;
  - (опционально) отчётов/аналитики;
- управление ценами и остатками с пушем изменений обратно в Uzum.

UI для Uzum должен:

- быть выполнен в том же стиле, что и UI для Wildberries;
- иметь аналогичную структуру страниц:
  - «Настройки»;
  - «Товары»;
  - «Цены»;
  - «Остатки»;
  - «Заказы»;
  - «Аналитика» (опционально).

---

## 2. Настройки и хранение доступа к Uzum

### 2.1. Модель и миграции

Используем существующую модель аккаунта маркетплейса, например `MarketplaceAccount`.

**Задача:**

1. В миграции для аккаунтов маркетплейсов добавить поля под Uzum:

```php
$table->string('uzum_client_id')->nullable();
$table->string('uzum_client_secret')->nullable();
$table->string('uzum_api_key')->nullable();         // если Uzum работает по API-ключу
$table->string('uzum_refresh_token')->nullable();   // если используется OAuth2
$table->string('uzum_access_token')->nullable();
$table->dateTime('uzum_token_expires_at')->nullable();

$table->json('uzum_settings')->nullable();          // склад, типы логистики и прочее
```

2. В модели `MarketplaceAccount`:

- добавить новые поля в `$fillable`/`$casts`;
- добавить helper-методы:

```php
public function isUzumConfigured(): bool;
public function getUzumAuthHeaders(): array;
public function getUzumSettings(string $key, $default = null);
```

### 2.2. Страница настроек Uzum

Маршруты:

```php
GET  /marketplaces/uzum/settings   -> SettingsController@uzumSettings
POST /marketplaces/uzum/settings   -> SettingsController@saveUzumSettings
```

Форма настроек должна содержать:

- Блок «Доступ к API»:
  - `uzum_client_id` / `uzum_client_secret` / `uzum_api_key` (в зависимости от схемы аутентификации);
- Блок «Логистика и склады»:
  - основной склад Uzum (ID склада из API);
  - флаг «использовать единый склад для всех товаров»;
  - выбор режима работы: FBO / FBS / DBS (если Uzum это разделяет);
- Блок «Синхронизация»:
  - чекбоксы:
    - «Синхронизировать товары»;
    - «Синхронизировать остатки»;
    - «Синхронизировать цены»;
    - «Синхронизировать заказы».

Дизайн:

- использовать те же компоненты и стили, что на странице настроек Wildberries;
- отличия только в логотипе/иконках и текстах.

---

## 3. HTTP-клиент Uzum

Создать сервис:

```php
namespace App\Services\Marketplaces;

use App\Models\MarketplaceAccount;
use Illuminate\Support\Facades\Http;

class UzumClient
{
    public function __construct(
        private MarketplaceAccount $account
    ) {}

    // базовый метод для запросов
}
```

### 3.1. Базовый URL и аутентификация

Фактический `base_url` и схема аутентификации берутся из OpenAPI Uzum.  
Пример (НО НЕ ХАРДКОДИТЬ БЕЗ ПРОВЕРКИ):

- Base URL: `https://api-seller.uzum.uz/api/seller-openapi`
- Версия: `/v1/...`
- Auth:
  - либо `Authorization: Bearer <access_token>`,
  - либо `X-API-Key: <uzum_api_key>`,
  - либо другая схема из OpenAPI.

**Задача:**

Реализовать приватный метод:

```php
protected function request(
    string $method,
    string $path,
    array $query = [],
    array $body = []
): array;
```

Требования:

- Собрать полный URL: `base_url . $path`.
- Сформировать заголовки:
  - аутентификация (на основе полей аккаунта);
  - `Accept: application/json`;
  - для POST/PUT/PATCH — `Content-Type: application/json`.
- Выполнить запрос через `Http::` (Laravel).
- Логировать:
  - URL, метод, query;
  - статус и первые N символов тела ответа.
- Если статус не 2xx:
  - бросить `UzumApiException` с кодом и телом ответа.

---

## 4. Товары и карточки Uzum

### 4.1. Клиентские методы

Используя OpenAPI Uzum, реализовать в `UzumClient` методы:

```php
public function fetchProducts(array $filters = [], int $page = 1, int $pageSize = 100): array;
public function fetchProductById(string $productId): array; // если есть отдельный endpoint
public function pushProducts(array $products): array;       // создание/обновление товаров
```

Точные пути (например, `/v1/products`) и параметры брать из OpenAPI.

### 4.2. Хранение в БД

Использовать таблицу `marketplace_products` или отдельную `uzum_products`.  
Пример для `marketplace_products`:

```php
$table->string('channel')->default('uzum');          // канал
$table->string('uzum_product_id')->nullable()->index();
$table->string('uzum_sku')->nullable();
$table->string('uzum_offer_id')->nullable();         // если есть понятие offer
$table->json('uzum_attributes')->nullable();         // атрибуты/характеристики
$table->string('uzum_status')->nullable();           // статус товара Uzum (активен, скрыт и т.д.)
$table->json('uzum_raw_payload')->nullable();        // полный JSON
```

В модели добавить `casts` для JSON-полей.

### 4.3. Сервис синхронизации товаров

В `MarketplaceSyncService` реализовать:

```php
public function syncUzumProducts(MarketplaceAccount $account): void;
```

Логика:

1. Считать настройки аккаунта (фильтры по складам/статусу, если есть).
2. Через `UzumClient::fetchProducts()` получить все товары с пагинацией.
3. Для каждого товара:
   - взять внешние ID (productId/sku/offerId);
   - сохранить/обновить запись в `marketplace_products` (upsert);
   - привязать к внутреннему товару по артикулу/SKU/баркоду, если реализовано.
4. Сохранить `uzum_raw_payload` для отладки.

### 4.4. UI: страница «Товары Uzum»

Маршрут:

```php
GET /marketplaces/uzum/products
```

Функционал:

- Фильтры:
  - поиск по названию, артикулу, SKU;
  - статус товара (активен, скрыт и т.д.);
  - наличие привязки к внутреннему товару.
- Таблица:
  - название;
  - артикул / SKU;
  - статус;
  - цена (если доступна);
  - остаток (если можем подтянуть из таблицы остатков).
- Клик по строке → модал/сайдбар с деталями товара (атрибуты, категории, сырые данные `uzum_raw_payload`).

Дизайн:

- использовать те же компоненты, что на странице «Товары Wildberries», адаптировав под Uzum.

---

## 5. Остатки Uzum

### 5.1. Клиентские методы

В `UzumClient` реализовать:

```php
public function fetchStocks(array $filters = [], int $page = 1, int $pageSize = 100): array;
public function pushStocks(array $stocks): array;
```

Точные endpoints (например, `/v1/stock`) и формат тела — из OpenAPI.

### 5.2. Хранение в БД

Таблица `marketplace_stocks` (для всех маркетплейсов) или отдельная для Uzum.  
Пример полей:

```php
$table->string('channel')->default('uzum');
$table->string('uzum_product_id')->index();
$table->string('uzum_sku')->nullable();
$table->string('uzum_warehouse_id')->nullable();
$table->integer('stock')->default(0);
$table->json('uzum_raw_payload')->nullable();
```

### 5.3. Сервис синхронизации остатков

В `MarketplaceSyncService`:

```php
public function syncUzumStocks(MarketplaceAccount $account): void;
```

Логика:

1. Получить список товаров/офферов, по которым нужно вытянуть остатки.
2. Через `UzumClient::fetchStocks()` забрать остатки по складам.
3. Upsert в `marketplace_stocks`.
4. Опционально — рассчитать разницу с внутренними остатками и сохранить.

### 5.4. UI: страница «Остатки Uzum»

Маршрут:

```php
GET /marketplaces/uzum/stocks
```

Функционал:

- Фильтры:
  - склад;
  - категория;
  - поиск по SKU/названию.
- Таблица:
  - товар;
  - склад;
  - остаток на Uzum;
  - внутренний остаток (если есть);
  - разница.
- Массовые операции:
  - выбор нескольких строк и кнопка «Синхронизировать остатки с Uzum» (пуш наружу).

---

## 6. Цены Uzum

### 6.1. Клиентские методы

В `UzumClient` реализовать:

```php
public function fetchPrices(array $filters = [], int $page = 1, int $pageSize = 100): array;
public function pushPrices(array $prices): array;
```

Endpoints (например, `/v1/prices`) и формат тела — из OpenAPI.

### 6.2. Хранение в БД

Таблица `marketplace_prices`:

```php
$table->string('channel')->default('uzum');
$table->string('uzum_product_id')->index();
$table->decimal('price', 15, 2)->nullable();
$table->decimal('old_price', 15, 2)->nullable();
$table->decimal('promo_price', 15, 2)->nullable();
$table->string('currency', 10)->nullable();
$table->json('uzum_raw_payload')->nullable();
```

### 6.3. Сервис синхронизации цен

В `MarketplaceSyncService`:

```php
public function syncUzumPrices(MarketplaceAccount $account): void;
```

Логика:

1. Вызвать `UzumClient::fetchPrices()` и получить актуальные цены.
2. Upsert в `marketplace_prices`.
3. Связать с внутренними товарами (через productId/SKU).

### 6.4. UI: страница «Цены Uzum»

Маршрут:

```php
GET /marketplaces/uzum/prices
```

Функционал:

- Фильтры:
  - категория;
  - наличие промо-цен;
  - диапазон цены.
- Таблица:
  - товар;
  - текущая цена Uzum;
  - промо-цена (если есть);
  - рекомендуемая цена (на основе логики SellerMindAi / FORRIS Price);
  - маржа (если можно посчитать).
- Массовые операции:
  - изменение цены на %;
  - установка цены по формуле;
  - кнопка «Отправить изменения на Uzum».

Дизайн:

- использовать тот же компонент таблицы цен, что и для Wildberries, с параметризацией по каналу.

---

## 7. Заказы Uzum

### 7.1. Модель и статусы

**БД:**

Расширить `marketplace_orders` или создать отдельную таблицу. Пример:

```php
$table->string('channel')->default('uzum');
$table->string('uzum_order_id')->index();
$table->string('uzum_order_number')->nullable();
$table->string('uzum_order_status')->nullable();        // сырой статус Uzum
$table->string('uzum_status_group')->nullable()->index(); // new / assembling / shipping / archive / canceled

$table->string('uzum_customer_name')->nullable();
$table->string('uzum_customer_phone')->nullable();
$table->json('uzum_address')->nullable();

$table->string('uzum_delivery_type')->nullable();   // FBO/FBS/DBS/самовывоз
$table->string('uzum_warehouse_id')->nullable();
$table->string('uzum_pickup_point_id')->nullable();

$table->decimal('uzum_order_amount', 15, 2)->nullable();
$table->decimal('uzum_items_amount', 15, 2)->nullable();
$table->decimal('uzum_delivery_price', 15, 2)->nullable();
$table->decimal('uzum_commission_amount', 15, 2)->nullable();

$table->dateTimeTz('uzum_created_at_utc')->nullable();
$table->dateTimeTz('uzum_updated_at_utc')->nullable();
$table->dateTimeTz('uzum_delivered_at_utc')->nullable();
$table->dateTimeTz('uzum_canceled_at_utc')->nullable();

$table->json('uzum_items')->nullable();        // массив позиций заказа
$table->json('uzum_raw_payload')->nullable();  // полный ответ API
```

### 7.2. Маппинг статусов в группы

В зависимости от документации Uzum (типичные статусы: `new`, `processing`, `shipped`, `delivered`, `canceled`, `returned` и т.п.):

Создать helper:

```php
public static function mapUzumStatusGroup(string $uzumOrderStatus): string;
```

Маппинг примерно:

- `uzum_status_group = 'new'` — для статусов «новый / ожидает подтверждения»;
- `uzum_status_group = 'assembling'` — для «подтверждён / комплектуется»;
- `uzum_status_group = 'shipping'` — для «передан в доставку / в пути»;
- `uzum_status_group = 'archive'` — для «доставлен / завершён»;
- `uzum_status_group = 'canceled'` — для «отменён / возврат`.

Сами соответствия статусов и групп вынести в `config/uzum.php['status_map']`.

### 7.3. Клиентские методы для заказов

В `UzumClient` реализовать:

```php
public function fetchOrders(array $filters = [], int $page = 1, int $pageSize = 100): array;
public function fetchOrderById(string $orderId): array;      // если есть отдельный endpoint
public function fetchOrderStatuses(array $orderIds): array;  // опционально, если есть отдельный endpoint статуса
```

Фильтры:

- период по дате создания/обновления;
- статус;
- тип доставки.

### 7.4. Сервис синхронизации заказов

В `MarketplaceSyncService`:

```php
public function syncUzumOrders(MarketplaceAccount $account): void;
```

Логика:

1. Определить временное окно (например, последние 30 дней или от `last_synced_at`).
2. Через `UzumClient::fetchOrders()` забрать заказы с пагинацией.
3. Для каждого заказа:
   - определить `uzum_status_group` через helper;
   - сохранить/обновить запись в `marketplace_orders`;
   - сохранить `uzum_items` и `uzum_raw_payload`.
4. Обновить `last_synced_at` для аккаунта Uzum.

### 7.5. UI: страница «Заказы Uzum»

Маршрут:

```php
GET /marketplaces/uzum/orders
```

Дизайн — такой же, как страница заказов Wildberries:

- Табы:

  - «Новые» → `uzum_status_group = 'new'`
  - «На сборке» → `assembling`
  - «В доставке» → `shipping`
  - «Архив» → `archive`
  - «Отменённые» → `canceled`

- Фильтры:

  - период дат;
  - тип доставки;
  - поиск по номеру заказа, телефону, артикулу/sku.

- Таблица:

  - № заказа Uzum;
  - дата;
  - клиент;
  - сумма;
  - тип доставки / склад / ПВЗ;
  - статус-группа (чип с цветом).

- Детали заказа (сайдбар/модал):

  - общая информация (статусы, даты);
  - клиент (имя, телефон, адрес);
  - логистика (склад, ПВЗ, тип доставки);
  - финансы (сумма заказа, доставка, комиссии, если есть);
  - товары (список позиций с количеством и ценами);
  - блок «Сырой JSON Uzum» (`uzum_raw_payload`) с pretty-print.

---

## 8. Возвраты и отмены Uzum (опционально)

Если в OpenAPI Uzum есть отдельные endpoints для возвратов/отмен:

### 8.1. Клиентские методы

В `UzumClient`:

```php
public function fetchReturns(array $filters = [], int $page = 1, int $pageSize = 100): array;
public function fetchCancellations(array $filters = [], int $page = 1, int $pageSize = 100): array;
```

### 8.2. Хранение

Либо:

- доп. поля в `marketplace_orders` (флаг/дата возврата, причина, суммы),

либо:

- отдельная таблица `marketplace_returns`:

```php
$table->string('channel')->default('uzum');
$table->string('uzum_order_id')->index();
$table->string('type');       // return / cancellation
$table->string('reason')->nullable();
$table->decimal('amount', 15, 2)->nullable();
$table->dateTimeTz('created_at_utc')->nullable();
$table->json('uzum_raw_payload')->nullable();
```

### 8.3. UI

В карточке заказа Uzum добавить блок «Возвраты / отмены» с деталями (тип, причина, сумма, дата).

---

## 9. Аналитика / отчёты Uzum (опционально)

Если OpenAPI Uzum предоставляет отчёты по обороту, комиссиям и т.д.:

### 9.1. Сервис отчётов

Создать `UzumReportsService`, который:

- через Uzum API получает отчёты;
- сохраняет агрегированные данные в таблицу `marketplace_reports`;
- предоставляет методы для построения графиков/таблиц.

### 9.2. UI: страница «Аналитика Uzum»

Маршрут:

```php
GET /marketplaces/uzum/analytics
```

Функционал:

- графики оборота по дням/неделям;
- комиссии/расходы;
- маржинальность;
- топ-товары.

Дизайн:

- использовать общие компоненты дашборда, принятые в SellerMindAi (как для WB).

---

## 10. Общий UX и дизайн

### 10.1. Структура меню

В разделе «Маркетплейсы» основного меню:

- Wildberries
- Uzum
- (далее другие каналы)

При выборе Uzum — внутреннее меню (слева/сверху, как у WB):

- Настройки
- Товары
- Цены
- Остатки
- Заказы
- Аналитика (если реализовано)

### 10.2. Повторение дизайна WB

Требования:

- использовать те же компоненты (Blade/SPA-компоненты), что для Wildberries:
  - карточки;
  - таблицы;
  - фильтры;
  - табы;
  - сайдбар деталей.
- различия только в:
  - тексте;
  - логотипе Uzum;
  - возможных специфичных флагах/полях.

Где возможно — вынести общий UI в переиспользуемые компоненты с параметром `channel`.

---

## 11. Логи, ошибки и rate limiting

### 11.1. Логирование

- Все запросы к Uzum логировать на уровне `debug`:
  - метод, URL, query, статус, часть тела.
- Ошибки (4xx/5xx) логировать в отдельный канал `marketplaces` с указанием:
  - канал = `uzum`;
  - ID аккаунта.

### 11.2. Обработка ошибок

- При ошибках API:
  - бросать `UzumApiException`;
  - в UI отображать понятное сообщение пользователю.
- Для критичных ошибок синхронизации:
  - записывать запись в таблицу `marketplace_sync_logs` (канал, сущность, статус, описание).

### 11.3. Ограничения по частоте

Если Uzum API имеет лимиты по запросам:

- добавить простую защиту:
  - задержки между запросами;
  - обработку кода «too many requests» с повторной попыткой.

---

## 12. Тестирование

### 12.1. Unit / Feature тесты

Написать тесты для:

- `UzumClient` с mock HTTP:
  - успешный запрос;
  - ошибки 400/401/500;
- `MarketplaceSyncService`:
  - `syncUzumProducts`;
  - `syncUzumStocks`;
  - `syncUzumPrices`;
  - `syncUzumOrders`.

### 12.2. Ручное тестирование

1. Подключить тестовый аккаунт Uzum:
   - заполнить API-ключи/токены;
   - сохранить настройки.
2. Выполнить:
   - синхронизацию товаров → проверить страницу «Товары Uzum»;
   - синхронизацию остатков → «Остатки Uzum»;
   - синхронизацию цен → «Цены Uzum»;
   - синхронизацию заказов → «Заказы Uzum».
3. Сверить данные с личным кабинетом Uzum:
   - выборочно проверить несколько товаров/заказов на совпадение полей и статусов.

---

## 13. Результат

После реализации этого ТЗ канал `Uzum` в SellerMindAi должен:

- полноценно интегрироваться с Uzum Seller API (через OpenAPI);
- предоставлять единый интерфейс для работы с товарами, остатками, ценами и заказами Uzum;
- по UX и дизайну быть максимально близким к реализованному каналу Wildberries.
