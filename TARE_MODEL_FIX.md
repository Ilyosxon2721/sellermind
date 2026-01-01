# Исправление моделей для работы с коробами (Tares)

## Дата: 2025-12-15

## Проблема

При загрузке списка коробов (`GET /api/marketplace/supplies/250/tares`) возникала ошибка 500:

```
include(/Applications/MAMP/htdocs/sellermind-ai/vendor/composer/../../app/Models/MarketplaceOrder.php):
Failed to open stream: No such file or directory
```

## Причина

После миграции с `marketplace_orders` на `wb_orders` модель `Tare` продолжала ссылаться на несуществующую модель `MarketplaceOrder`.

## Внесённые изменения

### 1. Обновлена модель Tare

**Файл**: `app/Models/Tare.php`

**Было**:
```php
public function orders(): HasMany
{
    return $this->hasMany(MarketplaceOrder::class, 'tare_id');
}
```

**Стало**:
```php
public function orders(): HasMany
{
    return $this->hasMany(WbOrder::class, 'tare_id');
}
```

### 2. Добавлено поле tare_id в таблицу wb_orders

**Миграция**: `database/migrations/2025_12_15_054624_add_tare_id_to_wb_orders_table.php`

```php
public function up(): void
{
    if (Schema::hasTable('wb_orders')) {
        Schema::table('wb_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('wb_orders', 'tare_id')) {
                $table->unsignedBigInteger('tare_id')->nullable()->after('supply_id');
                $table->foreign('tare_id')->references('id')->on('tares')->onDelete('set null');
            }
        });
    }
}
```

**Запущена миграция**:
```bash
php artisan migrate
```

### 3. Обновлена модель WbOrder

**Файл**: `app/Models/WbOrder.php`

**Добавлено в $fillable**:
```php
protected $fillable = [
    // ...
    'supply_id',
    'tare_id',  // ← Добавлено
    'office',
    // ...
];
```

**Добавлен relationship**:
```php
public function tare(): BelongsTo
{
    return $this->belongsTo(Tare::class);
}
```

### 4. Обновлён TareController

**Файл**: `app/Http/Controllers/Api/TareController.php`

#### 4.1. Обновлён импорт модели

**Было**:
```php
use App\Models\MarketplaceOrder;
```

**Стало**:
```php
use App\Models\WbOrder;
```

#### 4.2. Обновлён метод index()

**Было**:
```php
$tares = $supply->tares()
    ->withCount('orders')
    ->with('orders:id,external_order_id,tare_id,wb_article,wb_nm_id,wb_final_price')
    ->get();
```

**Стало**:
```php
$tares = $supply->tares()
    ->withCount('orders')
    ->with('orders:id,external_order_id,tare_id,article,nm_id,total_amount')
    ->get();
```

#### 4.3. Обновлён метод show()

**Было**:
```php
'orders' => function ($query) {
    $query->select(
        'id', 'tare_id', 'external_order_id', 'supply_id',
        'wb_article', 'wb_nm_id', 'wb_final_price',
        'wb_status', 'wb_supplier_status', 'wb_status_group'
    );
}
```

**Стало**:
```php
'orders' => function ($query) {
    $query->select(
        'id', 'tare_id', 'external_order_id', 'supply_id',
        'article', 'nm_id', 'total_amount',
        'status', 'wb_supplier_status', 'wb_status_group'
    );
}
```

#### 4.4. Обновлён метод addOrder()

**Было**:
```php
$validated = $request->validate([
    'order_id' => ['required', 'exists:marketplace_orders,id'],
]);

$order = MarketplaceOrder::findOrFail($validated['order_id']);
```

**Стало**:
```php
$validated = $request->validate([
    'order_id' => ['required', 'exists:wb_orders,id'],
]);

$order = WbOrder::findOrFail($validated['order_id']);
```

#### 4.5. Обновлён метод removeOrder()

**Было**:
```php
$validated = $request->validate([
    'order_id' => ['required', 'exists:marketplace_orders,id'],
]);

$order = MarketplaceOrder::findOrFail($validated['order_id']);
```

**Стало**:
```php
$validated = $request->validate([
    'order_id' => ['required', 'exists:wb_orders,id'],
]);

$order = WbOrder::findOrFail($validated['order_id']);
```

## Структура базы данных

### Таблица `wb_orders` (обновлена)

```sql
ALTER TABLE wb_orders
ADD COLUMN tare_id BIGINT UNSIGNED NULL AFTER supply_id,
ADD FOREIGN KEY (tare_id) REFERENCES tares(id) ON DELETE SET NULL;
```

### Связи между таблицами

```
supplies (1) ──┬──> (n) wb_orders
               │
               └──> (n) tares (1) ──> (n) wb_orders
```

**Relationships**:
- `Supply` hasMany `Tare`
- `Supply` hasMany `WbOrder`
- `Tare` belongsTo `Supply`
- `Tare` hasMany `WbOrder`
- `WbOrder` belongsTo `Supply`
- `WbOrder` belongsTo `Tare`

## Workflow работы с коробами

### 1. Создание короба
```
POST /api/marketplace/supplies/{supply_id}/tares
→ TareController::store()
→ WB API: POST /api/v3/supplies/{supplyId}/trbx
→ Save to DB: tares table
```

### 2. Получение списка коробов
```
GET /api/marketplace/supplies/{supply_id}/tares
→ TareController::index()
→ Return: tares with orders count
```

### 3. Добавление заказа в короб
```
POST /api/marketplace/tares/{tare_id}/orders
Body: { "order_id": 123 }
→ TareController::addOrder()
→ WB API: добавление заказа в короб
→ Update: wb_orders.tare_id = tare_id
```

### 4. Удаление заказа из короба
```
DELETE /api/marketplace/tares/{tare_id}/orders
Body: { "order_id": 123 }
→ TareController::removeOrder()
→ Update: wb_orders.tare_id = NULL
```

### 5. Удаление короба
```
DELETE /api/marketplace/tares/{tare_id}
→ TareController::destroy()
→ Update all orders: tare_id = NULL
→ Delete from tares table
```

## Тестирование

После исправлений все операции с коробами работают корректно:

✅ **Создание короба**:
```bash
POST /api/marketplace/supplies/250/tares
→ 201 Created
```

✅ **Получение списка коробов**:
```bash
GET /api/marketplace/supplies/250/tares
→ 200 OK
{
  "tares": [
    {
      "id": 1,
      "supply_id": 250,
      "external_tare_id": "WB-MP-28388090",
      "barcode": "WB-MP-28388090",
      "orders_count": 0,
      "orders": []
    }
  ]
}
```

✅ **Добавление заказа в короб**:
```bash
POST /api/marketplace/tares/1/orders
Body: { "order_id": 2833 }
→ 200 OK
```

✅ **Удаление заказа из короба**:
```bash
DELETE /api/marketplace/tares/1/orders
Body: { "order_id": 2833 }
→ 200 OK
```

## Очистка кэша

После всех изменений выполнено:
```bash
php artisan migrate
php artisan cache:clear
php artisan config:clear
```

## Статус

✅ **Все исправления завершены**:
1. ✅ Модель `Tare` обновлена для использования `WbOrder`
2. ✅ Добавлено поле `tare_id` в таблицу `wb_orders`
3. ✅ Модель `WbOrder` обновлена (fillable + relationship)
4. ✅ `TareController` полностью обновлён для работы с `WbOrder`
5. ✅ Все названия полей обновлены с `wb_*` на новые
6. ✅ Миграция выполнена успешно

## Дополнительная информация

Подробное руководство:
- [STICKER_PRINTING_GUIDE.md](STICKER_PRINTING_GUIDE.md) - работа со стикерами
- [TARE_FIX_SUMMARY.md](TARE_FIX_SUMMARY.md) - исправление маршрутов
