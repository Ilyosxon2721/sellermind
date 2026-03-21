# ТЗ для Claude Code: Доработки складского модуля SellerMind

**Проект:** SellerMind (sellermind.uz)
**Путь на сервере:** /home/forge/sellermind.uz/current

---

## Задача 1: Фильтр по типам в "Журнале движений"

**Файл:** `resources/views/warehouse/ledger.blade.php`

### Что нужно сделать:

1. В JavaScript объект `filters` (строка ~180) добавить поле:
```javascript
source_type: '',
```

2. В HTML секцию фильтров (после поля "SKU / штрихкод", строка ~49) добавить select:
```html
<div>
    <label class="block text-sm font-medium text-gray-700 mb-2">Тип</label>
    <select class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" x-model="filters.source_type">
        <option value="">Все типы</option>
        <option value="marketplace_order_reserve">Резерв</option>
        <option value="marketplace_order_cancel">Отмена резерва</option>
        <option value="WB_ORDER">WB заказ</option>
        <option value="WB_ORDER_CANCEL">WB отмена</option>
        <option value="UZUM_ORDER">Uzum заказ</option>
        <option value="OZON_ORDER">Ozon заказ</option>
        <option value="OZON_ORDER_CANCEL">Ozon отмена</option>
        <option value="offline_sale">Продажа</option>
        <option value="offline_sale_return">Возврат</option>
        <option value="stock_adjustment">Корректировка</option>
        <option value="initial_stock">Начальный остаток</option>
    </select>
</div>
```

3. Изменить grid с `md:grid-cols-4` на `md:grid-cols-5` чтобы влез новый фильтр (или сделать 2 ряда фильтров)

4. В PWA секции (строка ~306) тоже добавить аналогичный select для фильтра по типу

---

## Задача 2: Фильтр по категориям в "Журнале движений"

**Файл:** `resources/views/warehouse/ledger.blade.php`

### Что нужно сделать:

1. В контроллере (найти через route) передать категории в view:
```php
$categories = ProductCategory::where('company_id', $user->company_id)->orderBy('name')->get();
return view('warehouse.ledger', compact('warehouses', 'selectedWarehouseId', 'categories'));
```

2. В JavaScript объект `filters` добавить:
```javascript
category_id: '',
```

3. В HTML секцию фильтров добавить select для категорий:
```html
<div>
    <label class="block text-sm font-medium text-gray-700 mb-2">Категория</label>
    <select class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" x-model="filters.category_id">
        <option value="">Все категории</option>
        @foreach($categories as $cat)
            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
        @endforeach
    </select>
</div>
```

4. Найти контроллер/route:
```bash
grep -rn "ledger" routes/web.php
grep -rn "ledger" app/Http/Controllers --include="*.php"
```

---

## Задача 3: Фильтр по категориям в "Остатках"

**Файл:** `resources/views/warehouse/balance.blade.php`

### Что нужно сделать:

1. В контроллере передать категории в view (аналогично задаче 2)

2. В JavaScript объект `balancePage()` добавить в начало:
```javascript
categoryId: '',
```

3. В HTML секцию фильтров (после select склада) добавить:
```html
<div>
    <label class="block text-sm font-medium text-gray-700 mb-2">Категория</label>
    <select class="w-full border border-gray-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" x-model="categoryId">
        <option value="">Все категории</option>
        @foreach($categories as $cat)
            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
        @endforeach
    </select>
</div>
```

4. В функции `load()` добавить category_id в params:
```javascript
const params = new URLSearchParams({
    warehouse_id: this.warehouseId,
    category_id: this.categoryId,  // добавить
    query: this.query,
    page: this.pagination.current_page,
    per_page: this.pagination.per_page
});
```

5. Найти контроллер:
```bash
grep -rn "balance" routes/web.php
grep -rn "balance" app/Http/Controllers --include="*.php"
```

---

## Задача 4: Исправить данные в "Журнале движений"

**Проблема:** Страница показывает неправильные данные. Поле "Документ" часто пустое.

### Что нужно изучить:

1. Найти API endpoint для журнала:
```bash
grep -rn "stock/ledger" routes/api.php
grep -rn "ledger" app/Http/Controllers/Api --include="*.php"
```

2. Найти модель StockLedger:
```bash
cat app/Models/Warehouse/StockLedger.php
```

3. Проверить структуру таблицы:
```bash
php artisan tinker --execute="print_r(Schema::getColumnListing('stock_ledger'));"
```

4. Проверить связи:
```bash
php artisan tinker --execute="App\Models\Warehouse\StockLedger::with(['sku', 'warehouse', 'document'])->latest()->first();"
```

5. Проверить откуда берётся order_number, marketplace, shop_name — эти данные должны подтягиваться из связанных таблиц

### Возможные проблемы:
- Поле document_id не заполняется при создании записей
- Связь с заказами неправильно настроена
- API не загружает нужные связи (with)

---

## Задача 5: Проверить API endpoints

Убедиться что API поддерживает новые фильтры:

**Файл:** Найти через `grep -rn "stock/ledger" app/Http/Controllers`

### Добавить поддержку фильтров:

```php
// В методе который возвращает данные для журнала
$query = StockLedger::query()
    ->where('company_id', $companyId)
    ->when($request->warehouse_id, fn($q, $v) => $q->where('warehouse_id', $v))
    ->when($request->source_type, fn($q, $v) => $q->where('source_type', $v))  // добавить
    ->when($request->category_id, fn($q, $v) => $q->whereHas('sku.product', fn($q) => $q->where('category_id', $v)))  // добавить
    ->when($request->query, fn($q, $v) => $q->whereHas('sku', fn($q) => $q->where('sku_code', 'like', "%{$v}%")))
    ->when($request->from, fn($q, $v) => $q->whereDate('occurred_at', '>=', $v))
    ->when($request->to, fn($q, $v) => $q->whereDate('occurred_at', '<=', $v));
```

Аналогично для balance endpoint — добавить фильтр по category_id.

---

## Порядок выполнения:

1. **Задача 1** — Фильтр по типам (Журнал) — просто добавить select в blade
2. **Задача 5** — Проверить/обновить API чтобы поддерживал source_type
3. **Задача 2** — Фильтр по категориям (Журнал)
4. **Задача 3** — Фильтр по категориям (Остатки)
5. **Задача 4** — Изучить и исправить данные

---

## Команды для начала:

```bash
# Найти routes
grep -rn "ledger\|balance" routes/web.php routes/api.php

# Найти контроллеры
grep -rn "ledger\|balance" app/Http/Controllers --include="*.php" | head -20

# Структура StockLedger
cat app/Models/Warehouse/StockLedger.php
```
