# Аудит раздела «Инвентаризация / Складской учёт»

> **Дата:** 2026-03-24
> **Охват:** 9 сервисов, 10 контроллеров, 13 моделей, миграции, маршруты, тесты

---

## Общая архитектура

Раздел состоит из **7 подсистем**:

| Подсистема | Файлов | Статус |
|------------|--------|--------|
| Локальный складской учёт (Warehouse, SKU, Ledger) | ~25 | Работает с замечаниями |
| Документооборот (приход, расход, перемещение, сторно) | ~10 | Частично работает |
| Резервирование остатков | ~8 | Частично работает |
| Инвентаризация (подсчёт) | ~6 | **Не работает** |
| Синхронизация с маркетплейсами | ~15 | Работает с замечаниями |
| Обработка заказов (списание/возврат) | ~8 | Работает с замечаниями |
| Дашборд и аналитика остатков | ~5 | Работает с замечаниями |

---

## КРИТИЧЕСКИЕ ПРОБЛЕМЫ (P0) — требуют немедленного исправления

### 1. Web WarehouseController: обход аутентификации
**Файл:** `app/Http/Controllers/Web/Warehouse/WarehouseController.php`, строки 362-377

Метод `ensureUser()` при отсутствии авторизованного пользователя берёт **первого пользователя из БД** и логинит его через `Auth::login($fallback)`. Любой неавторизованный запрос получает полный доступ от имени первого пользователя (вероятно, администратора).

**Решение:** Удалить fallback-логику. Использовать стандартный middleware `auth`.

---

### 2. StockRecalculateController: полное отсутствие изоляции данных
**Файл:** `app/Http/Controllers/Api/StockRecalculateController.php`

Ни один метод (`preview`, `setInitialStock`, `recalculate`, `variants`) не фильтрует данные по компании. Любой авторизованный пользователь может:
- Просматривать заказы **всех** компаний
- Изменять остатки **любых** вариантов товаров

**Решение:** Добавить `HasCompanyScope` трейт и фильтрацию по `company_id` во все методы.

---

### 3. Inventory::applyResults() ссылается на несуществующий класс
**Файл:** `app/Models/Inventory.php`, строка 119

Метод использует `WarehouseStock`, который **не существует** в проекте (ни модели, ни таблицы). Вызов гарантированно падает с `Class not found`.

**Решение:** Переписать `applyResults()` используя `StockLedger` (актуальная система учёта).

---

### 4. ChannelImportController: undefined variable
**Файл:** `app/Http/Controllers/Api/Warehouse/ChannelImportController.php`, строка 102

`$user?->id` — переменная `$user` **нигде не определена**. PHP ошибка при каждой попытке создать резервирование при импорте заказов.

**Решение:** Заменить `$user?->id` на `Auth::id()`.

---

## СЕРЬЁЗНЫЕ ПРОБЛЕМЫ (P1) — влияют на корректность данных

### 5. Race condition при резервировании
**Файл:** `app/Services/Warehouse/ReservationService.php`, строки 29-36

Проверка доступного остатка и создание резерва не защищены `SELECT ... FOR UPDATE`. Два параллельных запроса могут оба увидеть `available = 5` и оба зарезервировать 5 единиц → **перерезервирование**.

---

### 6. `convertReserveToSold` без транзакции
**Файлы:**
- `app/Services/Stock/OrderStockService.php`, строки 338-394
- `app/Services/Stock/OfflineSaleStockService.php`, строки 202-263

Создание записей StockLedger и consume резервов не обёрнуты в `DB::transaction()`. При ошибке между операциями — **рассогласование данных**: ledger создан, а резерв не consumed.

---

### 7. Расхождение fillable-полей моделей с БД
| Модель | Поля в `$fillable` без колонок в БД |
|--------|--------------------------------------|
| `Warehouse` | `code`, `address_comment`, `comment`, `group_name`, `external_code`, `meta_json`, `is_active` |
| `Sku` | `product_variant_id` |
| `InventoryDocumentLine` | `currency_code`, `exchange_rate` |

Операции с этими полями будут **молча игнорироваться** или вызовут ошибку при mass assignment.

---

### 8. IDOR: warehouse_id не проверяется на принадлежность к компании
**Файлы:**
- `InventoryController.php`, строка 73
- `DocumentController.php`, строка 298

Валидация `exists:warehouses,id` проверяет только существование склада, но не его принадлежность к компании пользователя. Можно создать документ для **чужого** склада.

---

### 9. Себестоимость при сторнировании некорректна
**Файлы:**
- `DocumentReversalService.php`, строка 51 — `unit_cost` инвертируется
- `DocumentPostingService.php`, строки 74-86 — расчёт cost_delta при REVERSAL

Двойное отрицание (`-qty * -unit_cost = +cost`) приводит к тому, что при сторно прихода стоимость **не списывается**, а прибавляется повторно.

---

### 10. Дуализм данных: stock_default vs StockLedger
Система хранит остаток в **двух местах**:
- `ProductVariant.stock_default` — денормализованное поле
- `SUM(StockLedger.qty_delta)` — агрегат по журналу

`syncStockDefaultForDocument` синхронизирует их, но **без фильтра по warehouse/company** — суммирует по ВСЕМ складам.

---

### 11. Race condition при генерации номеров документов
**Файл:** `app/Services/Warehouse/DocNumberService.php`, строки 14-26

`MAX(doc_no)` + INSERT не атомарны. Параллельные вызовы генерируют **одинаковые номера**. Нет UNIQUE constraint на `doc_no`.

---

## СРЕДНИЕ ПРОБЛЕМЫ (P2) — влияют на производительность и надёжность

### 12. N+1 запросы
| Файл | Место | Проблема |
|------|-------|----------|
| `DocumentController.php` | `addLines`, строки 115-125 | SKU + Unit поиск в цикле для каждой строки |
| `ReservationController.php` | `getMarketplaceOrder`, строки 130-139 | `::find()` на каждый резерв |
| `DocumentPostingService.php` | `syncStockDefaultForDocument`, строки 113-151 | `Sku::find()` в цикле |
| `OrderStockReturnController.php` | `transform`, строки 55-56 | `getOrder()` на каждый возврат |
| `StockController.php` | `summary`, строки 535-539 | Повторный запрос SKU IDs в цикле по складам |
| `ChannelImportController.php` | строка 73 | `ChannelSkuMap::where()` на каждый item каждого заказа |

---

### 13. Отсутствие пагинации
| Файл | Лимит | Проблема |
|------|-------|----------|
| `DocumentController.php` | `limit(200)` | Документы свыше 200 недоступны |
| `ReservationController.php` | `limit(500)` | При большом количестве — OOM |
| `StockRecalculateController.php` | без лимита | Все заказы в память — OOM |

---

### 14. Утечка внутренней информации
**Файл:** `DocumentController.php`, строка 86

`'Ошибка создания документа: '.$e->getMessage()` — SQL-ошибки отдаются клиенту, раскрывая структуру БД.

---

### 15. API-вызовы внутри транзакции БД
**Файл:** `OrderStockService.php`, строка 289

`syncVariantToOtherMarketplaces` (HTTP-запросы к API маркетплейсов) вызывается внутри `DB::transaction()`. Если API зависнет — транзакция БД будет держаться открытой.

---

### 16. Автосоздание складов без защиты от дубликатов
**Файлы:** `OrderStockService.php`, `OfflineSaleStockService.php`

При параллельных вызовах `determineWarehouse` для одной компании возможно создание **двух** складов с кодом `DEFAULT`.

---

### 17. LIKE injection в поиске
| Файл | Строка |
|------|--------|
| `ReservationController.php` | 54 |
| `MarketplaceStockDashboardController.php` | 241-248 |

Спецсимволы `%` и `_` в пользовательском вводе не экранируются через `escapeLike()`.

---

## НИЗКИЕ ПРОБЛЕМЫ (P3) — качество кода

### 18. Нарушение стандартов кодирования (CLAUDE.md)
- **Все 9 сервисов**: отсутствует `declare(strict_types=1)` (кроме InventoryService)
- **Все модели**: классы не объявлены как `final`
- **Debug-логи в production**: `ReservationController` (строка 26), `StockSyncService` (строки 408, 421)

### 19. Дублирование маршрутов
- `GET /marketplace/stocks` зарегистрирован **дважды** в `web.php` (строки 403 и 414)
- API warehouse маршруты дублируются в единственном/множественном числе: `warehouse/list` = `warehouses`
- `GET /api/warehouses` зарегистрирован в **3 разных местах**

### 20. Два параллельных подхода к инвентаризации
- **Подсистема A (Legacy):** `Inventory` + `InventoryItem` → привязка к `product_id`, использует несуществующий `WarehouseStock`
- **Подсистема B (Warehouse Core):** `InventoryDocument` + `InventoryDocumentLine` → привязка к `sku_id`, работает с `StockLedger`

Подсистемы **не интегрированы**. Legacy-подсистема фактически нерабочая.

### 21. Номер инвентаризации генерируется через `rand()`
**Файл:** `Inventory.php`, строка 49 — `rand(1, 999)` может выдать дубликат в один день. Нет UNIQUE индекса.

### 22. `cost_delta = 0` при продаже
При consume резервов и offline-продажах `cost_delta` всегда 0. Себестоимость проданного товара **не отражается** в ledger → отчёты по прибыльности невозможны на основе StockLedger.

### 23. Нет обработки expires_at резервов
Резервы создаются с `expires_at` (7-30 дней), но **нет scheduled job** для автоматического освобождения истёкших резервов.

### 24. MarketplaceStockDashboard — только Wildberries
Дашборд остатков показывает данные только для Wildberries. Ozon, Uzum, Yandex Market — **не отображаются**.

---

## ПОКРЫТИЕ ТЕСТАМИ: 0%

Поиск по `tests/**/*Inventory*`, `tests/**/*Warehouse*`, `tests/**/*Stock*` — **ни одного теста не найдено**.

### Приоритет покрытия:
1. `ReservationService` — резервирование и race conditions
2. `DocumentPostingService` + `DocumentReversalService` — проводка и сторно
3. `StockBalanceService` — расчёт остатков
4. `OrderStockService.convertReserveToSold` — списание при продаже
5. `InventoryController` CRUD — создание и применение инвентаризации
6. API маршруты — авторизация и company scope

---

## СВОДКА: ЧТО РАБОТАЕТ / ЧТО НЕТ / ЧТО НУЖНО ДОБАВИТЬ

### Работает (с замечаниями)
| Компонент | Замечания |
|-----------|-----------|
| Создание/редактирование складов | Race condition при смене default |
| Просмотр остатков (balance) | N+1 в summary |
| Журнал движения (ledger) | Корректно |
| Создание документов (приход/расход) | IDOR по warehouse_id |
| Синхронизация остатков с WB/Ozon/Uzum/YM | WB basic mode: одинаковый stock на все склады |
| Обработка заказов (резерв/продажа/отмена) | Нет транзакции в convertReserveToSold |
| Offline-продажи | Нет транзакции в convertReserveToSold |
| Дашборд маркетплейс-остатков | Только Wildberries |

### Не работает
| Компонент | Причина |
|-----------|---------|
| `Inventory.applyResults()` | Ссылается на несуществующий `WarehouseStock` |
| Импорт заказов из каналов (`ChannelImportController`) | `$user` undefined → PHP error |
| Legacy-инвентаризация (Inventory + InventoryItem) | Вся подсистема нерабочая |
| Web-интерфейс склада без auth | `ensureUser` автологинит первого пользователя |

### Нужно добавить
| Что | Приоритет |
|-----|-----------|
| Тесты для всего раздела (покрытие = 0%) | P0 |
| Company scope в `StockRecalculateController` | P0 |
| `SELECT FOR UPDATE` в `ReservationService.reserve()` | P1 |
| `DB::transaction()` в `convertReserveToSold` (оба сервиса) | P1 |
| Scheduled job для освобождения истёкших резервов | P1 |
| UNIQUE constraint на `doc_no` + retry логика | P1 |
| Пагинация в `DocumentController`, `ReservationController` | P2 |
| `escapeLike()` в поисковых запросах | P2 |
| Миграции для недостающих колонок (Warehouse, Sku, InventoryDocumentLine) | P2 |
| Дашборд остатков для Ozon/Uzum/Yandex Market | P2 |
| Учёт себестоимости при продаже (`cost_delta != 0`) | P2 |
| Rate limiting при массовой синхронизации с маркетплейсами | P3 |
| Удаление/рефакторинг Legacy-инвентаризации | P3 |
