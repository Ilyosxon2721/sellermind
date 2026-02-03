# Техническое задание: Модуль списания товаров со склада

## 1. Общее описание

### 1.1 Цель модуля
Реализовать функционал списания товаров с внутреннего склада компании с автоматическим обновлением остатков и синхронизацией с маркетплейсами.

### 1.2 Основные функции
- Создание документа списания (WRITE_OFF)
- Выбор причины списания из справочника
- Указание комментария и дополнительной информации
- Проведение документа с автоматическим уменьшением остатков
- Синхронизация остатков на маркетплейсы после списания
- Отчётность по списаниям

---

## 2. Существующая архитектура

### 2.1 Уже реализовано
| Компонент | Статус | Описание |
|-----------|--------|----------|
| Модель `InventoryDocument` | ✅ Готово | Константа `TYPE_WRITE_OFF = 'WRITE_OFF'` |
| `DocumentPostingService` | ✅ Готово | Обработка WRITE_OFF с уменьшением остатков |
| API endpoints | ✅ Готово | CRUD документов через `/api/marketplace/inventory/documents` |
| `StockBalanceService` | ✅ Готово | Расчёт доступных остатков |
| Синхронизация маркетплейсов | ✅ Готово | `StockSyncService` через Observer |
| Переводы | ⚠️ Частично | Только ключ `write_off` |

### 2.2 Требуется реализовать
| Компонент | Приоритет | Описание |
|-----------|-----------|----------|
| Web UI страница | Высокий | `/warehouse/write-off/create` |
| Справочник причин | Средний | Модель `WriteOffReason` |
| Переводы | Высокий | Полный набор ключей |
| Отчёт по списаниям | Низкий | Фильтрация в documents |
| Массовое списание | Низкий | Загрузка из Excel |

---

## 3. Справочник причин списания

### 3.1 Модель `WriteOffReason`

```php
// app/Models/Warehouse/WriteOffReason.php

class WriteOffReason extends Model
{
    protected $fillable = [
        'company_id',
        'code',
        'name',
        'description',
        'is_default',
        'is_active',
        'requires_comment',  // Обязательный комментарий
        'affects_cost',      // Влияет на себестоимость
    ];
}
```

### 3.2 Миграция

```php
Schema::create('write_off_reasons', function (Blueprint $table) {
    $table->id();
    $table->foreignId('company_id')->constrained()->onDelete('cascade');
    $table->string('code', 50);
    $table->string('name');
    $table->text('description')->nullable();
    $table->boolean('is_default')->default(false);
    $table->boolean('is_active')->default(true);
    $table->boolean('requires_comment')->default(false);
    $table->boolean('affects_cost')->default(true);
    $table->timestamps();

    $table->unique(['company_id', 'code']);
});
```

### 3.3 Предустановленные причины (Seeder)

| Код | RU | EN | UZ |
|-----|----|----|-----|
| `damaged` | Брак/повреждение | Damaged/Defective | Buzilgan/Nuqsonli |
| `expired` | Истёк срок годности | Expired | Muddati o'tgan |
| `lost` | Утеря/недостача | Lost/Shortage | Yo'qolgan/Kamomad |
| `sample` | Образец/подарок | Sample/Gift | Namuna/Sovg'a |
| `theft` | Кража | Theft | O'g'irlik |
| `natural_loss` | Естественная убыль | Natural loss | Tabiiy yo'qotish |
| `production` | Использовано в производстве | Used in production | Ishlab chiqarishda ishlatilgan |
| `other` | Прочее | Other | Boshqa |

---

## 4. Интерфейс пользователя

### 4.1 Точки входа

1. **Боковое меню**: Склад → Списание
2. **Страница документов**: Кнопка "Новое списание"
3. **Страница остатков**: Контекстное меню товара → "Списать"
4. **Быстрое действие**: На странице товара

### 4.2 Страница создания списания

**URL**: `/warehouse/write-off/create`

**Секции формы**:

#### Шапка документа
- Дата/время (по умолчанию текущая)
- Склад (выбор из списка)
- Причина списания (выбор из справочника)
- Ответственный (автоматически текущий пользователь)
- Комментарий

#### Табличная часть (строки)
| Поле | Тип | Обязательное |
|------|-----|--------------|
| Товар (SKU/штрихкод/название) | Autocomplete | Да |
| Текущий остаток | Readonly | - |
| Количество списания | Number | Да |
| Единица измерения | Select | Да |
| Себестоимость | Number | Нет |
| Примечание к строке | Text | Нет |

#### Итоги
- Общее количество позиций
- Общая сумма списания (по себестоимости)

#### Кнопки действий
- "Сохранить черновик"
- "Сохранить и провести"
- "Отмена"

### 4.3 Список списаний

Фильтрация существующей страницы `/warehouse/documents`:
- Добавить фильтр по типу `WRITE_OFF`
- Добавить колонку "Причина"
- Добавить быструю кнопку "Новое списание"

### 4.4 Детали документа списания

На странице `/warehouse/documents/{id}` для типа WRITE_OFF показывать:
- Причину списания
- Ответственного
- Общую сумму списания
- Движения по складу (ledger entries)

---

## 5. API Endpoints

### 5.1 Существующие (используем как есть)

```
POST   /api/marketplace/inventory/documents           # Создать документ
POST   /api/marketplace/inventory/documents/{id}/lines   # Добавить строки
POST   /api/marketplace/inventory/documents/{id}/post    # Провести
POST   /api/marketplace/inventory/documents/{id}/reverse # Реверсия
GET    /api/marketplace/inventory/documents/{id}      # Получить документ
GET    /api/marketplace/inventory/documents           # Список документов
```

### 5.2 Новые endpoints

```
GET    /api/warehouse/write-off-reasons              # Список причин списания
POST   /api/warehouse/write-off-reasons              # Создать причину
PUT    /api/warehouse/write-off-reasons/{id}         # Обновить причину
DELETE /api/warehouse/write-off-reasons/{id}         # Удалить причину
```

### 5.3 Пример запроса создания списания

```json
// POST /api/marketplace/inventory/documents
{
    "type": "WRITE_OFF",
    "warehouse_id": 1,
    "reason": "damaged",
    "comment": "Повреждение при транспортировке"
}

// POST /api/marketplace/inventory/documents/{id}/lines
{
    "lines": [
        {
            "sku_id": 42,
            "qty": 5,
            "unit_id": 1,
            "unit_cost": 150.00,
            "meta_json": {
                "note": "Разбитая упаковка"
            }
        }
    ]
}

// POST /api/marketplace/inventory/documents/{id}/post
// (без body)
```

---

## 6. Бизнес-логика

### 6.1 Проведение документа списания

При проведении документа типа WRITE_OFF:

1. **Валидация**
   - Проверить наличие достаточного остатка (если `allow_negative_stock = false`)
   - Проверить активность SKU
   - Проверить заполненность обязательных полей

2. **Создание записей в StockLedger**
   ```sql
   INSERT INTO stock_ledger (
       company_id, warehouse_id, sku_id,
       qty_delta,      -- отрицательное значение
       cost_delta,     -- отрицательное значение
       document_id, source_type
   )
   ```

3. **Обновление stock_default в ProductVariant**
   - Автоматически через `syncStockDefaultForDocument()`

4. **Синхронизация маркетплейсов**
   - Через Observer `ProductVariantObserver` → `StockUpdated` event
   - `StockSyncService::syncVariantStock()` для каждого затронутого товара

### 6.2 Проверка остатков

```php
// В DocumentPostingService уже реализовано:
case InventoryDocument::TYPE_WRITE_OFF:
    if (!$allowNegative) {
        $this->ensureAvailable($companyId, $document->warehouse_id, $line->sku_id, (float) $line->qty);
    }
    $ledgerCreated[] = $this->ledgerEntry($document, $line, -$line->qty, -$totalCostBase, $userId);
    break;
```

### 6.3 Отмена/Реверсия списания

- Использовать существующий `DocumentReversalService`
- Создаётся документ типа REVERSAL
- Возвращает товар на склад (+qty)
- Синхронизирует маркетплейсы

---

## 7. Переводы

### 7.1 Файл `resources/lang/ru/warehouse.php`

```php
// Добавить в существующий файл:

// Write-off section
'write_off' => 'Списание',
'write_offs' => 'Списания',
'new_write_off' => 'Новое списание',
'create_write_off' => 'Создать списание',
'write_off_reason' => 'Причина списания',
'write_off_date' => 'Дата списания',
'responsible_person' => 'Ответственный',
'write_off_qty' => 'Количество списания',
'total_write_off' => 'Итого к списанию',
'current_stock' => 'Текущий остаток',
'note' => 'Примечание',

// Reasons
'reason_damaged' => 'Брак/повреждение',
'reason_expired' => 'Истёк срок годности',
'reason_lost' => 'Утеря/недостача',
'reason_sample' => 'Образец/подарок',
'reason_theft' => 'Кража',
'reason_natural_loss' => 'Естественная убыль',
'reason_production' => 'Использовано в производстве',
'reason_other' => 'Прочее',

// Messages
'write_off_created' => 'Списание создано',
'write_off_posted' => 'Списание проведено',
'write_off_reversed' => 'Списание отменено',
'not_enough_stock' => 'Недостаточно товара на складе',
'select_reason' => 'Выберите причину списания',
```

### 7.2 Файл `resources/lang/en/warehouse.php`

```php
// Write-off section
'write_off' => 'Write-off',
'write_offs' => 'Write-offs',
'new_write_off' => 'New write-off',
'create_write_off' => 'Create write-off',
'write_off_reason' => 'Write-off reason',
'write_off_date' => 'Write-off date',
'responsible_person' => 'Responsible person',
'write_off_qty' => 'Write-off quantity',
'total_write_off' => 'Total write-off',
'current_stock' => 'Current stock',
'note' => 'Note',

// Reasons
'reason_damaged' => 'Damaged/Defective',
'reason_expired' => 'Expired',
'reason_lost' => 'Lost/Shortage',
'reason_sample' => 'Sample/Gift',
'reason_theft' => 'Theft',
'reason_natural_loss' => 'Natural loss',
'reason_production' => 'Used in production',
'reason_other' => 'Other',

// Messages
'write_off_created' => 'Write-off created',
'write_off_posted' => 'Write-off posted',
'write_off_reversed' => 'Write-off reversed',
'not_enough_stock' => 'Not enough stock in warehouse',
'select_reason' => 'Select write-off reason',
```

### 7.3 Файл `resources/lang/uz/warehouse.php`

```php
// Write-off section
'write_off' => 'Hisobdan chiqarish',
'write_offs' => 'Hisobdan chiqarishlar',
'new_write_off' => 'Yangi hisobdan chiqarish',
'create_write_off' => 'Hisobdan chiqarish yaratish',
'write_off_reason' => 'Hisobdan chiqarish sababi',
'write_off_date' => 'Hisobdan chiqarish sanasi',
'responsible_person' => 'Mas\'ul shaxs',
'write_off_qty' => 'Hisobdan chiqarish miqdori',
'total_write_off' => 'Jami hisobdan chiqarish',
'current_stock' => 'Joriy qoldiq',
'note' => 'Izoh',

// Reasons
'reason_damaged' => 'Buzilgan/Nuqsonli',
'reason_expired' => 'Muddati o\'tgan',
'reason_lost' => 'Yo\'qolgan/Kamomad',
'reason_sample' => 'Namuna/Sovg\'a',
'reason_theft' => 'O\'g\'irlik',
'reason_natural_loss' => 'Tabiiy yo\'qotish',
'reason_production' => 'Ishlab chiqarishda ishlatilgan',
'reason_other' => 'Boshqa',

// Messages
'write_off_created' => 'Hisobdan chiqarish yaratildi',
'write_off_posted' => 'Hisobdan chiqarish o\'tkazildi',
'write_off_reversed' => 'Hisobdan chiqarish bekor qilindi',
'not_enough_stock' => 'Omborda tovar yetarli emas',
'select_reason' => 'Hisobdan chiqarish sababini tanlang',
```

---

## 8. Маршруты (Routes)

### 8.1 Web routes (`routes/web.php`)

```php
// Добавить в группу warehouse
Route::prefix('warehouse')->name('warehouse.')->group(function () {
    // ... существующие маршруты ...

    // Write-off
    Route::get('/write-off', [WarehouseController::class, 'writeOffs'])->name('write-offs');
    Route::get('/write-off/create', [WarehouseController::class, 'createWriteOff'])->name('write-off.create');
});

// Дублировать для cabinet
Route::prefix('cabinet/warehouse')->group(function () {
    // ... существующие маршруты ...

    Route::get('/write-off', [WarehouseController::class, 'writeOffs'])->name('cabinet.warehouse.write-offs');
    Route::get('/write-off/create', [WarehouseController::class, 'createWriteOff'])->name('cabinet.warehouse.write-off.create');
});
```

### 8.2 API routes (`routes/api.php`)

```php
Route::prefix('warehouse')->middleware(['auth:sanctum'])->group(function () {
    // Write-off reasons
    Route::get('/write-off-reasons', [WriteOffReasonController::class, 'index']);
    Route::post('/write-off-reasons', [WriteOffReasonController::class, 'store']);
    Route::put('/write-off-reasons/{id}', [WriteOffReasonController::class, 'update']);
    Route::delete('/write-off-reasons/{id}', [WriteOffReasonController::class, 'destroy']);
});
```

---

## 9. Структура файлов

### 9.1 Новые файлы

```
app/
├── Models/Warehouse/
│   └── WriteOffReason.php                    # Модель причин списания
├── Http/Controllers/
│   ├── Api/Warehouse/
│   │   └── WriteOffReasonController.php      # API контроллер причин
│   └── Web/Warehouse/
│       └── WarehouseController.php           # Добавить методы writeOffs, createWriteOff

resources/
├── views/warehouse/
│   ├── write-off-create.blade.php            # Форма создания списания
│   └── write-offs.blade.php                  # Список списаний (опционально)

database/
├── migrations/
│   └── 2026_01_26_000001_create_write_off_reasons_table.php
└── seeders/
    └── WriteOffReasonsSeeder.php
```

### 9.2 Модифицируемые файлы

```
routes/web.php                                # Добавить маршруты
routes/api.php                                # Добавить маршруты
resources/lang/ru/warehouse.php               # Добавить переводы
resources/lang/en/warehouse.php               # Добавить переводы
resources/lang/uz/warehouse.php               # Добавить переводы
app/Models/Warehouse/InventoryDocument.php    # Добавить связь с reason (опционально)
resources/views/warehouse/documents.blade.php # Добавить кнопку "Новое списание"
resources/views/components/sidebar.blade.php  # Добавить пункт меню
```

---

## 10. План реализации

### Этап 1: Базовая функциональность (MVP)
1. ✅ Backend уже готов (DocumentPostingService поддерживает WRITE_OFF)
2. Создать миграцию `write_off_reasons`
3. Создать модель `WriteOffReason`
4. Создать seeder с предустановленными причинами
5. Добавить переводы во все языковые файлы
6. Создать `write-off-create.blade.php`
7. Добавить маршруты
8. Добавить методы в WarehouseController

### Этап 2: Улучшения
1. Добавить API для управления причинами
2. Добавить фильтрацию по причинам в списке документов
3. Добавить отчёт по списаниям
4. Добавить быстрое списание из страницы остатков

### Этап 3: Расширения (опционально)
1. Массовое списание из Excel
2. Сканирование штрихкодов
3. Фотофиксация брака
4. Интеграция с модулем инвентаризации

---

## 11. Тестирование

### 11.1 Функциональные тесты

1. **Создание списания**
   - Создать документ WRITE_OFF в статусе DRAFT
   - Добавить строки
   - Провести документ
   - Проверить уменьшение остатков в stock_ledger
   - Проверить обновление stock_default в ProductVariant

2. **Валидация остатков**
   - Попытаться списать больше чем есть на складе
   - При `allow_negative_stock = false` должна быть ошибка
   - При `allow_negative_stock = true` списание должно пройти

3. **Реверсия списания**
   - Провести списание
   - Создать реверсию
   - Проверить возврат остатков

4. **Синхронизация маркетплейсов**
   - Провести списание для товара связанного с маркетплейсом
   - Проверить вызов StockSyncService
   - Проверить обновление остатков на маркетплейсе

### 11.2 UI тесты

1. Открытие формы создания списания
2. Поиск товара по SKU/штрихкоду
3. Добавление/удаление строк
4. Сохранение черновика
5. Проведение документа
6. Отображение ошибок валидации

---

## 12. Безопасность

### 12.1 Права доступа

- Списание должно быть доступно только пользователям с правом на склад
- Логировать все операции списания
- Хранить created_by для аудита

### 12.2 Валидация

- Проверять принадлежность склада к компании пользователя
- Проверять активность SKU
- Проверять корректность количества (> 0)
- Санитизация комментариев

---

## 13. Интеграция с другими модулями

### 13.1 Модуль продаж
- При возврате товара от покупателя можно сразу списать брак

### 13.2 Модуль инвентаризации
- После инвентаризации автоматически создавать документы списания для недостач

### 13.3 Модуль закупок
- При приёмке бракованного товара от поставщика

### 13.4 Маркетплейсы
- Синхронизация остатков после списания
- Обработка возвратов с маркетплейсов

---

## Приложение A: Mockup UI

```
┌─────────────────────────────────────────────────────────────────┐
│  ← Назад    Новое списание                    [Сохранить] [✓]  │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │ Реквизиты                                               │   │
│  │                                                         │   │
│  │ Дата:         [26.01.2026 14:30    ▼]                  │   │
│  │ Склад:        [Основной склад      ▼]                  │   │
│  │ Причина:      [Брак/повреждение    ▼]                  │   │
│  │ Комментарий:  [Повреждение при перевозке___________]   │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                 │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │ Товары к списанию                      [+ Добавить]    │   │
│  │                                                         │   │
│  │ ┌─────────────────────────────────────────────────────┐ │   │
│  │ │ 🖼️ iPhone 15 Pro Max 256GB Black                    │ │   │
│  │ │    SKU: IPH15PM-256-BLK                             │ │   │
│  │ │    Остаток: 45 шт                                   │ │   │
│  │ │    Списать: [3      ] шт     [🗑️]                  │ │   │
│  │ └─────────────────────────────────────────────────────┘ │   │
│  │                                                         │   │
│  │ ┌─────────────────────────────────────────────────────┐ │   │
│  │ │ 🖼️ Samsung Galaxy S24 Ultra 512GB                   │ │   │
│  │ │    SKU: SGS24U-512                                  │ │   │
│  │ │    Остаток: 28 шт                                   │ │   │
│  │ │    Списать: [1      ] шт     [🗑️]                  │ │   │
│  │ └─────────────────────────────────────────────────────┘ │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                 │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │ Итого                                                   │   │
│  │                                                         │   │
│  │ Позиций: 2                                              │   │
│  │ Количество: 4 шт                                        │   │
│  │ Сумма: ~$4,500.00 (по себестоимости)                   │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                 │
│         [Сохранить черновик]  [Сохранить и провести]          │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

**Версия документа**: 1.0
**Дата создания**: 26.01.2026
**Автор**: Claude Code
