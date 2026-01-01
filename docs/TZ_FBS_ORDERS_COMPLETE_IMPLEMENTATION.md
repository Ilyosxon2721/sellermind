# ТЕХНИЧЕСКОЕ ЗАДАНИЕ
## Полная реализация работы с заказами FBS Wildberries

**Дата создания:** 06.12.2025
**Версия:** 1.0
**Статус:** К разработке

---

## 1. ОБЗОР ПРОЕКТА

### 1.1. Цель
Реализация полного цикла работы с заказами FBS (Fulfillment by Seller) Wildberries согласно официальной документации API, включая все этапы от получения заказов до передачи в доставку.

### 1.2. Область применения
Система управления заказами маркетплейсов (SellerMind AI) - модуль интеграции с Wildberries FBS.

---

## 2. АНАЛИЗ ТЕКУЩЕЙ РЕАЛИЗАЦИИ

### 2.1. Что уже реализовано

#### Backend:
✅ **Базовая работа с заказами:**
- `MarketplaceOrder` модель с основными полями
- `MarketplaceOrderController` - CRUD операции
- `WildberriesOrderService` - базовая синхронизация заказов
- Синхронизация заказов через `GET /api/v3/orders`

✅ **Работа с поставками:**
- `Supply` модель с полями: name, status, external_supply_id, barcode_path
- `SupplyController` - создание, получение, удаление поставок
- Автоматическое создание поставок в WB при создании локально
- Добавление/удаление заказов в поставку
- Закрытие поставки с автоматической загрузкой баркода
- Скачивание баркода поставки

✅ **Статусы:**
- Базовые статусы поставок: draft, in_assembly, ready, sent, delivered, cancelled
- Хранение wb_status, wb_supplier_status в заказах

✅ **Frontend:**
- Отображение заказов с фильтрацией
- Отображение поставок в виде аккордеона
- Добавление заказов в поставки через UI
- Создание поставок

### 2.2. Что отсутствует (критичные функции)

#### 🔴 Критичные пробелы:

**1. Получение новых заказов**
- ❌ Не реализован `GET /api/v3/orders/new`
- ❌ Нет автоматического получения новых сборочных заданий

**2. Работа с метаданными**
- ❌ Не реализованы эндпоинты для метаданных (SGTIN, UIN, IMEI, GTIN, срок годности)
- ❌ Нет UI для ввода метаданных
- ❌ Не сохраняются requiredMeta и optionalMeta из заказов

**3. Работа со стикерами**
- ❌ Не реализовано получение стикеров заказов `POST /api/v3/orders/stickers`
- ❌ Отсутствует функция печати стикеров
- ❌ Нет поддержки cross-border стикеров

**4. Управление поставками**
- ❌ Отсутствует передача поставки в доставку `PATCH /api/v3/supplies/{supplyId}/deliver`
- ❌ Нет работы с коробами (trbx)
- ❌ Нет стикеров для коробов

**5. Статусы и история**
- ❌ Не реализован `POST /api/v3/orders/status` для получения актуальных статусов
- ❌ Отсутствует история изменения статусов
- ❌ Нет обработки всех статусов wbStatus

**6. Отмена заказов**
- ❌ Не реализована отмена заказов через `PATCH /api/v3/orders/{orderId}/cancel`

**7. Повторная отгрузка**
- ❌ Нет функционала reshipment `GET /api/v3/supplies/orders/reshipment`

**8. Пропуска на склады**
- ❌ Не реализована система пропусков `POST /api/v3/passes`
- ❌ Отсутствует проверка требований офисов

---

## 3. ДЕТАЛЬНОЕ ТЕХНИЧЕСКОЕ ЗАДАНИЕ

### 3.1. Backend: База данных

#### 3.1.1. Расширение таблицы `marketplace_orders`

```sql
ALTER TABLE marketplace_orders ADD COLUMN IF NOT EXISTS:
    -- Метаданные
    required_meta JSON COMMENT 'Обязательные метаданные из requiredMeta',
    optional_meta JSON COMMENT 'Опциональные метаданные из optionalMeta',
    meta_sgtin JSON COMMENT 'Коды маркировки (Честный знак)',
    meta_uin VARCHAR(255) COMMENT 'Уникальный номер товара',
    meta_imei VARCHAR(255) COMMENT 'IMEI для электроники',
    meta_gtin VARCHAR(255) COMMENT 'GTIN для товаров в Беларуси',
    meta_expiration_date DATE COMMENT 'Срок годности',

    -- Статусы и история
    supplier_status VARCHAR(50) COMMENT 'Статус продавца: new, confirm, complete, cancel',
    wb_status VARCHAR(50) COMMENT 'Статус WB системы',
    wb_status_group VARCHAR(50) COMMENT 'Группа статуса: new, assembling, delivering, done, cancelled',
    status_history JSON COMMENT 'История изменения статусов',

    -- Тип груза и ограничения
    cargo_type VARCHAR(50) COMMENT 'Габаритный тип: монопаллета, суперсейф и т.д.',

    -- Стикеры
    sticker_path VARCHAR(255) COMMENT 'Путь к файлу стикера',
    sticker_generated_at TIMESTAMP COMMENT 'Когда сгенерирован стикер',

    -- Даты
    cancel_dt TIMESTAMP COMMENT 'Дата отмены заказа',

    -- Адрес доставки
    delivery_address_full TEXT COMMENT 'Полный адрес доставки',
    delivery_province VARCHAR(255) COMMENT 'Область',
    delivery_area VARCHAR(255) COMMENT 'Район',
    delivery_city VARCHAR(255) COMMENT 'Город',
    delivery_street VARCHAR(255) COMMENT 'Улица',
    delivery_home VARCHAR(50) COMMENT 'Дом',
    delivery_flat VARCHAR(50) COMMENT 'Квартира',
    delivery_entrance VARCHAR(50) COMMENT 'Подъезд',
    delivery_longitude DECIMAL(10, 7) COMMENT 'Долгота',
    delivery_latitude DECIMAL(10, 7) COMMENT 'Широта',

    INDEX idx_supplier_status (supplier_status),
    INDEX idx_cargo_type (cargo_type),
    INDEX idx_cancel_dt (cancel_dt);
```

#### 3.1.2. Новая таблица `supply_boxes` (короба)

```sql
CREATE TABLE IF NOT EXISTS supply_boxes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    supply_id BIGINT UNSIGNED NOT NULL COMMENT 'ID поставки',
    box_number VARCHAR(100) NOT NULL COMMENT 'Номер короба',
    sticker_path VARCHAR(255) COMMENT 'Путь к стикеру короба',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (supply_id) REFERENCES supplies(id) ON DELETE CASCADE,
    INDEX idx_supply_id (supply_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 3.1.3. Новая таблица `warehouse_passes` (пропуска)

```sql
CREATE TABLE IF NOT EXISTS warehouse_passes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    marketplace_account_id BIGINT UNSIGNED NOT NULL,
    external_pass_id VARCHAR(255) COMMENT 'ID пропуска в WB',
    office_id VARCHAR(255) NOT NULL COMMENT 'ID офиса/склада WB',
    supply_id BIGINT UNSIGNED COMMENT 'ID поставки',
    car_number VARCHAR(50) NOT NULL COMMENT 'Номер автомобиля',
    car_model VARCHAR(100) COMMENT 'Модель автомобиля',
    driver_name VARCHAR(255) NOT NULL COMMENT 'ФИО водителя',
    phone VARCHAR(50) NOT NULL COMMENT 'Телефон',
    planned_date DATE NOT NULL COMMENT 'Планируемая дата прибытия',
    status VARCHAR(50) DEFAULT 'active' COMMENT 'Статус: active, used, cancelled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (marketplace_account_id) REFERENCES marketplace_accounts(id) ON DELETE CASCADE,
    FOREIGN KEY (supply_id) REFERENCES supplies(id) ON DELETE SET NULL,
    INDEX idx_office_id (office_id),
    INDEX idx_planned_date (planned_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 3.1.4. Расширение таблицы `supplies`

```sql
ALTER TABLE supplies ADD COLUMN IF NOT EXISTS:
    -- Дополнительные поля
    cargo_type VARCHAR(50) COMMENT 'Габаритный тип поставки',
    boxes_count INT DEFAULT 0 COMMENT 'Количество коробов',
    delivered_at TIMESTAMP COMMENT 'Дата передачи в доставку',
    delivery_started_at TIMESTAMP COMMENT 'Фактическое начало доставки',

    INDEX idx_cargo_type (cargo_type),
    INDEX idx_delivered_at (delivered_at);
```

---

### 3.2. Backend: Сервисы и контроллеры

#### 3.2.1. WildberriesOrderService - Новые методы

**Файл:** `app/Services/Marketplaces/Wildberries/WildberriesOrderService.php`

```php
/**
 * Получить новые сборочные задания
 * GET /api/v3/orders/new
 */
public function getNewOrders(MarketplaceAccount $account): array

/**
 * Получить статусы заказов
 * POST /api/v3/orders/status
 */
public function getOrdersStatus(MarketplaceAccount $account, array $orderIds): array

/**
 * Получить историю статусов (cross-border)
 * POST /api/v3/orders/status/history
 */
public function getOrdersStatusHistory(MarketplaceAccount $account, array $orderIds): array

/**
 * Отменить заказ
 * PATCH /api/v3/orders/{orderId}/cancel
 */
public function cancelOrder(MarketplaceAccount $account, string $orderId): array

/**
 * Получить стикеры заказов
 * POST /api/v3/orders/stickers
 * @param array $orderIds - до 100 ID заказов
 * @param string $type - svg|zplv|zplh|png
 * @param int $width - 58|40
 * @param int $height - 40|30
 */
public function getOrderStickers(
    MarketplaceAccount $account,
    array $orderIds,
    string $type = 'png',
    int $width = 58,
    int $height = 40
): array

/**
 * Получить cross-border стикеры
 * POST /api/v3/orders/stickers/cross-border
 */
public function getCrossBorderStickers(MarketplaceAccount $account, array $orderIds): array

/**
 * Передать поставку в доставку
 * PATCH /api/v3/supplies/{supplyId}/deliver
 */
public function deliverSupply(MarketplaceAccount $account, string $supplyId): array

/**
 * Получить заказы для повторной отгрузки
 * GET /api/v3/supplies/orders/reshipment
 */
public function getReshipmentOrders(MarketplaceAccount $account): array

/**
 * Добавить короба в поставку
 * POST /api/v3/supplies/{supplyId}/trbx
 */
public function addBoxes(MarketplaceAccount $account, string $supplyId, int $count): array

/**
 * Получить список коробов
 * GET /api/v3/supplies/{supplyId}/trbx
 */
public function getBoxes(MarketplaceAccount $account, string $supplyId): array

/**
 * Получить стикеры коробов
 * POST /api/v3/supplies/{supplyId}/trbx/stickers
 */
public function getBoxStickers(MarketplaceAccount $account, string $supplyId): array

/**
 * Удалить короба
 * DELETE /api/v3/supplies/{supplyId}/trbx
 */
public function deleteBoxes(MarketplaceAccount $account, string $supplyId): array
```

#### 3.2.2. WildberriesOrderMetaService - Сервис метаданных

**Файл:** `app/Services/Marketplaces/Wildberries/WildberriesOrderMetaService.php`

```php
/**
 * Получить метаданные заказов
 * POST /api/marketplace/v3/orders/meta
 */
public function getOrdersMeta(MarketplaceAccount $account, array $orderIds): array

/**
 * Обновить SGTIN (коды маркировки)
 * PUT /api/v3/orders/{orderId}/meta/sgtin
 */
public function updateSgtin(MarketplaceAccount $account, string $orderId, array $sgtins): array

/**
 * Обновить UIN
 * PUT /api/v3/orders/{orderId}/meta/uin
 */
public function updateUin(MarketplaceAccount $account, string $orderId, string $uin): array

/**
 * Обновить IMEI
 * PUT /api/v3/orders/{orderId}/meta/imei
 */
public function updateImei(MarketplaceAccount $account, string $orderId, string $imei): array

/**
 * Обновить GTIN
 * PUT /api/v3/orders/{orderId}/meta/gtin
 */
public function updateGtin(MarketplaceAccount $account, string $orderId, string $gtin): array

/**
 * Обновить срок годности
 * PUT /api/v3/orders/{orderId}/meta/expiration
 */
public function updateExpiration(MarketplaceAccount $account, string $orderId, string $date): array

/**
 * Удалить метаданные
 * DELETE /api/v3/orders/{orderId}/meta?key={metaType}
 */
public function deleteMeta(MarketplaceAccount $account, string $orderId, string $metaType): array
```

#### 3.2.3. WildberriesPassService - Сервис пропусков

**Новый файл:** `app/Services/Marketplaces/Wildberries/WildberriesPassService.php`

```php
/**
 * Получить требования офисов
 * GET /api/v3/passes/offices
 */
public function getOfficeRequirements(MarketplaceAccount $account): array

/**
 * Создать пропуск
 * POST /api/v3/passes
 */
public function createPass(
    MarketplaceAccount $account,
    string $officeId,
    string $carNumber,
    string $driverName,
    string $phone,
    string $plannedDate
): array

/**
 * Обновить пропуск
 * PUT /api/v3/passes/{passId}
 */
public function updatePass(
    MarketplaceAccount $account,
    string $passId,
    array $data
): array
```

#### 3.2.4. Контроллеры

**MarketplaceOrderController - Новые методы:**

```php
// GET /api/marketplace/orders/new - получить новые заказы
public function getNew(Request $request): JsonResponse

// POST /api/marketplace/orders/{order}/cancel - отменить заказ
public function cancel(Request $request, MarketplaceOrder $order): JsonResponse

// POST /api/marketplace/orders/stickers - получить стикеры
public function getStickers(Request $request): JsonResponse

// POST /api/marketplace/orders/{order}/meta - обновить метаданные
public function updateMeta(Request $request, MarketplaceOrder $order): JsonResponse

// GET /api/marketplace/orders/{order}/meta - получить метаданные
public function getMeta(Request $request, MarketplaceOrder $order): JsonResponse
```

**SupplyController - Новые методы:**

```php
// POST /api/marketplace/supplies/{supply}/deliver - передать в доставку
public function deliver(Request $request, Supply $supply): JsonResponse

// POST /api/marketplace/supplies/{supply}/boxes - добавить короба
public function addBoxes(Request $request, Supply $supply): JsonResponse

// GET /api/marketplace/supplies/{supply}/boxes - список коробов
public function getBoxes(Request $request, Supply $supply): JsonResponse

// POST /api/marketplace/supplies/{supply}/boxes/stickers - стикеры коробов
public function getBoxStickers(Request $request, Supply $supply): JsonResponse

// DELETE /api/marketplace/supplies/{supply}/boxes - удалить короба
public function deleteBoxes(Request $request, Supply $supply): JsonResponse
```

**Новый контроллер: WarehousePassController**

```php
// GET /api/warehouse/passes - список пропусков
public function index(Request $request): JsonResponse

// POST /api/warehouse/passes - создать пропуск
public function store(Request $request): JsonResponse

// PUT /api/warehouse/passes/{pass} - обновить пропуск
public function update(Request $request, WarehousePass $pass): JsonResponse

// GET /api/warehouse/offices - требования офисов
public function getOffices(Request $request): JsonResponse
```

---

### 3.3. Backend: Модели

#### 3.3.1. MarketplaceOrder - Расширение

```php
// Добавить в fillable:
protected $fillable = [
    // ... существующие поля
    'required_meta',
    'optional_meta',
    'meta_sgtin',
    'meta_uin',
    'meta_imei',
    'meta_gtin',
    'meta_expiration_date',
    'supplier_status',
    'wb_status',
    'wb_status_group',
    'status_history',
    'cargo_type',
    'sticker_path',
    'sticker_generated_at',
    'cancel_dt',
    'delivery_address_full',
    'delivery_province',
    'delivery_area',
    'delivery_city',
    'delivery_street',
    'delivery_home',
    'delivery_flat',
    'delivery_entrance',
    'delivery_longitude',
    'delivery_latitude',
];

// Добавить casts:
protected $casts = [
    // ... существующие casts
    'required_meta' => 'array',
    'optional_meta' => 'array',
    'meta_sgtin' => 'array',
    'status_history' => 'array',
    'sticker_generated_at' => 'datetime',
    'cancel_dt' => 'datetime',
];

// Новые методы:
public function hasRequiredMeta(): bool
public function hasSgtin(): bool
public function canCancel(): bool
public function needsSticker(): bool
public function getSupplierStatusLabel(): string
public function getWbStatusLabel(): string
```

#### 3.3.2. Новые модели

**SupplyBox:**
```php
class SupplyBox extends Model
{
    protected $fillable = ['supply_id', 'box_number', 'sticker_path'];

    public function supply(): BelongsTo
    {
        return $this->belongsTo(Supply::class);
    }
}
```

**WarehousePass:**
```php
class WarehousePass extends Model
{
    protected $fillable = [
        'marketplace_account_id',
        'external_pass_id',
        'office_id',
        'supply_id',
        'car_number',
        'car_model',
        'driver_name',
        'phone',
        'planned_date',
        'status',
    ];

    protected $casts = [
        'planned_date' => 'date',
    ];

    public function account(): BelongsTo
    public function supply(): BelongsTo
    public function isActive(): bool
}
```

---

### 3.4. Frontend: UI/UX

#### 3.4.1. Страница заказов - Расширение функционала

**Файл:** `resources/views/pages/marketplace/orders.blade.php`

**Новые функции:**

1. **Фильтры по статусам:**
   - По supplierStatus: new, confirm, complete, cancel
   - По wbStatus: waiting, sorted, sold, canceled, ready_for_pickup, etc.
   - По cargo_type

2. **Действия с заказами:**
   - Кнопка "Отменить заказ" для статусов new/confirm
   - Кнопка "Печать стикера" для каждого заказа
   - Массовая печать стикеров (до 100 заказов)
   - Кнопка "Метаданные" для заказов с requiredMeta

3. **Модальное окно метаданных:**
   ```html
   <div id="metaModal">
       - Поле SGTIN (textarea для нескольких кодов)
       - Поле UIN
       - Поле IMEI
       - Поле GTIN
       - Поле "Срок годности" (datepicker)
       - Кнопка "Сохранить"
   </div>
   ```

4. **Индикаторы:**
   - Иконка "Требуются метаданные" если requiredMeta не пустой
   - Иконка "Стикер сгенерирован" если sticker_path существует
   - Цветовая индикация cargo_type

#### 3.4.2. Страница поставок - Расширение

**Файл:** `resources/views/pages/marketplace/supplies.blade.php` или в составе orders

**Новые функции для каждой поставки:**

1. **В аккордеоне поставки добавить:**
   - Отображение cargo_type
   - Количество коробов
   - Кнопка "Добавить короба"
   - Кнопка "Печать стикеров коробов"
   - Кнопка "Передать в доставку" (для статуса ready)
   - Кнопка "Скачать QR код" (уже есть, улучшить)

2. **Модальное окно "Добавить короба":**
   ```html
   <div id="addBoxesModal">
       <input type="number" name="boxes_count" placeholder="Количество коробов">
       <button>Добавить</button>
   </div>
   ```

3. **Модальное окно "Передать в доставку":**
   ```html
   <div id="deliverSupplyModal">
       <p>Вы уверены, что хотите передать поставку в доставку?</p>
       <p>После этого статус заказов изменится на "complete"</p>
       <button class="confirm">Да, передать</button>
       <button class="cancel">Отмена</button>
   </div>
   ```

#### 3.4.3. Новая страница: Пропуска на склады

**Новый файл:** `resources/views/pages/warehouse/passes.blade.php`

```html
<div class="passes-page">
    <!-- Кнопка создания пропуска -->
    <button @click="showCreatePassModal">Создать пропуск</button>

    <!-- Таблица пропусков -->
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Офис/Склад</th>
                <th>Номер авто</th>
                <th>Водитель</th>
                <th>Дата</th>
                <th>Статус</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            <!-- Список пропусков -->
        </tbody>
    </table>

    <!-- Модальное окно создания -->
    <div id="createPassModal">
        <select name="office_id"><!-- Офисы --></select>
        <input name="car_number" placeholder="Номер авто">
        <input name="car_model" placeholder="Модель">
        <input name="driver_name" placeholder="ФИО водителя">
        <input name="phone" placeholder="Телефон">
        <input type="date" name="planned_date">
        <select name="supply_id"><!-- Поставки --></select>
        <button>Создать</button>
    </div>
</div>
```

---

### 3.5. Автоматизация и фоновые задачи

#### 3.5.1. Job: SyncNewOrders

**Файл:** `app/Jobs/Marketplace/SyncNewOrdersJob.php`

```php
/**
 * Регулярная синхронизация новых заказов
 * Выполнение: каждые 5 минут
 */
class SyncNewOrdersJob implements ShouldQueue
{
    public function handle()
    {
        // Для каждого аккаунта WB
        // - Вызвать getNewOrders()
        // - Сохранить новые заказы с requiredMeta/optionalMeta
        // - Отправить уведомление о новых заказах
    }
}
```

#### 3.5.2. Job: UpdateOrdersStatus

**Файл:** `app/Jobs/Marketplace/UpdateOrdersStatusJob.php`

```php
/**
 * Обновление статусов активных заказов
 * Выполнение: каждые 10 минут
 */
class UpdateOrdersStatusJob implements ShouldQueue
{
    public function handle()
    {
        // Получить заказы со статусами new, confirm, complete
        // - Вызвать getOrdersStatus() партиями по 1000
        // - Обновить supplier_status, wb_status
        // - Записать историю изменений в status_history
        // - Отправить уведомления при изменении статуса
    }
}
```

#### 3.5.3. Scheduler

**Файл:** `app/Console/Kernel.php`

```php
protected function schedule(Schedule $schedule)
{
    // Новые задачи
    $schedule->job(new SyncNewOrdersJob)->everyFiveMinutes();
    $schedule->job(new UpdateOrdersStatusJob)->everyTenMinutes();
}
```

---

### 3.6. Rate Limiting и обработка ошибок

#### 3.6.1. Rate Limiter для WB API

**Файл:** `app/Services/Marketplaces/Wildberries/WildberriesRateLimiter.php`

```php
class WildberriesRateLimiter
{
    // 300 requests/min с burst 20 requests
    const STANDARD_LIMIT = 300; // per minute
    const BURST_LIMIT = 20;
    const BURST_INTERVAL = 200; // milliseconds

    // 100 requests/min для отмены
    const CANCEL_LIMIT = 100;

    // 1000 requests/min для метаданных
    const META_LIMIT = 1000;

    public function allowRequest(string $endpoint): bool
    public function waitIfNeeded(string $endpoint): void
    public function handle409Error(): void // 409 = 10 requests
}
```

#### 3.6.2. Обработка ошибок API

```php
try {
    $response = $this->httpClient->get($url);
} catch (RequestException $e) {
    if ($e->getCode() === 409) {
        // Conflict - учитывается как 10 запросов
        $this->rateLimiter->handle409Error();
    }

    if ($e->getCode() === 429) {
        // Too Many Requests - превышен лимит
        Log::warning('WB API rate limit exceeded', [
            'endpoint' => $url,
            'account_id' => $account->id,
        ]);

        // Повторить через минуту
        throw new RateLimitException('Rate limit exceeded');
    }

    throw $e;
}
```

---

## 4. ПРИОРИТИЗАЦИЯ ЗАДАЧ

### Фаза 1: Критичные функции (2-3 недели)
1. ✅ Миграции БД (1 день)
2. ✅ Получение новых заказов `GET /api/v3/orders/new` (2 дня)
3. ✅ Обновление статусов `POST /api/v3/orders/status` (2 дня)
4. ✅ Стикеры заказов `POST /api/v3/orders/stickers` (3 дня)
5. ✅ Передача в доставку `PATCH /api/v3/supplies/{supplyId}/deliver` (2 дня)
6. ✅ UI для печати стикеров (2 дня)
7. ✅ Фоновые задачи синхронизации (3 дня)

### Фаза 2: Метаданные (1-2 недели)
1. ✅ Сервис метаданных (3 дня)
2. ✅ UI для ввода метаданных (2 дня)
3. ✅ Индикаторы requiredMeta (1 день)

### Фаза 3: Короба и пропуска (1-2 недели)
1. ✅ Работа с коробами (4 дня)
2. ✅ Стикеры коробов (2 дня)
3. ✅ Система пропусков (5 дней)

### Фаза 4: Дополнительные функции (1 неделя)
1. ✅ Отмена заказов (2 дня)
2. ✅ Повторная отгрузка (2 дня)
3. ✅ Cross-border стикеры (1 день)
4. ✅ Оптимизация и тестирование (2 дня)

---

## 5. ROUTES (API эндпоинты)

**Файл:** `routes/api.php`

```php
// Orders
Route::get('marketplace/orders/new', [MarketplaceOrderController::class, 'getNew']);
Route::post('marketplace/orders/{order}/cancel', [MarketplaceOrderController::class, 'cancel']);
Route::post('marketplace/orders/stickers', [MarketplaceOrderController::class, 'getStickers']);
Route::get('marketplace/orders/{order}/meta', [MarketplaceOrderController::class, 'getMeta']);
Route::post('marketplace/orders/{order}/meta', [MarketplaceOrderController::class, 'updateMeta']);

// Supplies
Route::post('marketplace/supplies/{supply}/deliver', [SupplyController::class, 'deliver']);
Route::post('marketplace/supplies/{supply}/boxes', [SupplyController::class, 'addBoxes']);
Route::get('marketplace/supplies/{supply}/boxes', [SupplyController::class, 'getBoxes']);
Route::post('marketplace/supplies/{supply}/boxes/stickers', [SupplyController::class, 'getBoxStickers']);
Route::delete('marketplace/supplies/{supply}/boxes', [SupplyController::class, 'deleteBoxes']);

// Warehouse Passes
Route::get('warehouse/passes', [WarehousePassController::class, 'index']);
Route::post('warehouse/passes', [WarehousePassController::class, 'store']);
Route::put('warehouse/passes/{pass}', [WarehousePassController::class, 'update']);
Route::get('warehouse/offices', [WarehousePassController::class, 'getOffices']);
```

---

## 6. ТЕСТИРОВАНИЕ

### 6.1. Unit тесты

```php
// tests/Unit/Services/WildberriesOrderServiceTest.php
- testGetNewOrders()
- testGetOrdersStatus()
- testCancelOrder()
- testGetOrderStickers()
- testDeliverSupply()

// tests/Unit/Services/WildberriesOrderMetaServiceTest.php
- testGetOrdersMeta()
- testUpdateSgtin()
- testUpdateUin()
- testUpdateImei()

// tests/Unit/Services/WildberriesPassServiceTest.php
- testCreatePass()
- testGetOfficeRequirements()
```

### 6.2. Feature тесты

```php
// tests/Feature/Orders/OrdersWorkflowTest.php
- testCompleteOrderWorkflow() // От получения до передачи в доставку
- testOrderWithMetadata()
- testOrderCancellation()
- testStickerGeneration()

// tests/Feature/Supplies/SupplyWorkflowTest.php
- testCreateSupplyWithOrders()
- testAddBoxesToSupply()
- testDeliverSupply()
```

---

## 7. ДОКУМЕНТАЦИЯ

### 7.1. README файлы

- `docs/FBS_ORDERS_GUIDE.md` - Руководство пользователя
- `docs/API_INTEGRATION.md` - Документация API интеграции
- `docs/WORKFLOWS.md` - Описание бизнес-процессов

### 7.2. Комментарии в коде

- PHPDoc для всех публичных методов
- Описание алгоритмов в сложных местах
- TODO комментарии для будущих улучшений

---

## 8. МОНИТОРИНГ И ЛОГИРОВАНИЕ

### 8.1. Логи

```php
Log::channel('wb_api')->info('New orders fetched', [
    'account_id' => $account->id,
    'count' => count($orders),
    'timestamp' => now(),
]);

Log::channel('wb_api')->error('Failed to deliver supply', [
    'supply_id' => $supply->id,
    'error' => $e->getMessage(),
    'trace' => $e->getTraceAsString(),
]);
```

### 8.2. Метрики

- Количество полученных заказов за период
- Скорость обработки заказов
- Количество ошибок API
- Rate limit статистика

---

## 9. БЕЗОПАСНОСТЬ

### 9.1. Валидация данных

```php
$request->validate([
    'order_ids' => 'required|array|max:100',
    'order_ids.*' => 'required|integer|exists:marketplace_orders,id',
    'sticker_type' => 'required|in:svg,zplv,zplh,png',
]);
```

### 9.2. Авторизация

```php
// Policy для заказов
public function cancel(User $user, MarketplaceOrder $order): bool
{
    return $user->hasCompanyAccess($order->account->company_id)
        && $order->canCancel();
}
```

---

## 10. PERFORMANCE ОПТИМИЗАЦИЯ

### 10.1. Кеширование

```php
// Кеш офисов на 24 часа
Cache::remember('wb_offices_' . $accountId, 86400, function() {
    return $this->getOfficeRequirements($account);
});
```

### 10.2. Batch обработка

```php
// Обновление статусов партиями по 1000
$orders->chunk(1000, function($chunk) {
    $orderIds = $chunk->pluck('external_order_id')->toArray();
    $statuses = $this->getOrdersStatus($account, $orderIds);
    // Update statuses
});
```

### 10.3. Оптимизация запросов

```php
// Eager loading для избежания N+1
$orders = MarketplaceOrder::with(['account', 'supply'])
    ->where('supplier_status', 'new')
    ->get();
```

---

## 11. ЗАКЛЮЧЕНИЕ

Данное ТЗ охватывает все аспекты работы с FBS заказами Wildberries согласно официальной документации. Реализация всех пунктов позволит создать полноценную систему управления заказами с автоматизацией всех процессов от получения заказа до передачи в доставку.

### Ключевые метрики успеха:
- ✅ 100% покрытие API эндпоинтов из документации WB
- ✅ Автоматическая синхронизация новых заказов
- ✅ Удобный UI для всех операций
- ✅ Соблюдение rate limits
- ✅ Обработка всех edge cases
- ✅ Полное логирование операций

**Следующий шаг:** Утверждение ТЗ и начало реализации с Фазы 1.
