# Финальное исправление синхронизации заказов Wildberries

## Дата: 2025-12-13

## Проблема

Пользователь сообщил: "синхронизация не правильно работает"

При проверке обнаружено:
1. **Фронтенд использовал неправильный endpoint** - `/api/marketplace/orders/new` вместо общего endpoint синхронизации
2. **Endpoint получал только НОВЫЕ заказы** - метод `fetchNewOrders()` использует `/api/v3/orders/new`
3. **Статусы не обновлялись** - существующие заказы не меняли свой статус при синхронизации
4. **Ошибка в scheduled tasks** - команда `wb:sync-products --account_id=all` использовала неправильный формат (аргумент вместо опции)

## Исправления

### 1. Исправлена команда планировщика (routes/console.php:51)

**Было:**
```php
Schedule::command('wb:sync-products --account_id=all')
```

**Стало:**
```php
Schedule::command('wb:sync-products all')
```

**Почему:** Команда `WildberriesSyncProducts` использует `{account_id}` как аргумент, а не опцию `--account_id`.

---

### 2. Фронтенд теперь использует общий endpoint синхронизации

**Файл:** `resources/views/pages/marketplace/orders.blade.php` (строки 1148-1155)

**Было:**
```javascript
async fetchNewOrders() {
    if (this.fetchingNewOrders) return;
    if (this.accountMarketplace !== 'wb') {
        return;
    }

    this.fetchingNewOrders = true;

    try {
        const response = await axios.get('/api/marketplace/orders/new', {
            headers: this.getAuthHeaders(),
            params: {
                marketplace_account_id: {{ $accountId }}
            }
        });

        await this.loadOrders();
        await this.loadStats();

        const message = response.data.created > 0
            ? `Получено ${response.data.created} новых заказов`
            : 'Новых заказов нет';

        this.showNotification(message);

        console.log('New orders fetched:', response.data);

    } catch (error) {
        console.error('Error fetching new orders:', error);
        alert(error.response?.data?.message || 'Ошибка при получении новых заказов');
    } finally {
        this.fetchingNewOrders = false;
    }
},
async handleSyncButton() {
    if (this.accountMarketplace === 'wb') {
        await this.fetchNewOrders();
    } else {
        await this.triggerSync();
    }
},
```

**Стало:**
```javascript
async fetchNewOrders() {
    // Теперь используем общий метод triggerSync для всех маркетплейсов
    await this.triggerSync();
},
async handleSyncButton() {
    // Используем единый метод синхронизации для всех маркетплейсов
    await this.triggerSync();
},
```

**Почему:**
- Старый endpoint `/api/marketplace/orders/new` вызывал только `fetchNewOrders()`
- Новый подход использует `/api/marketplace/accounts/{id}/sync/orders`, который запускает полную синхронизацию через `MarketplaceSyncService`
- Это обеспечивает вызов `fetchAllOrders()` вместо `fetchNewOrders()`

---

### 3. Поток синхронизации теперь правильный

**Когда пользователь нажимает "Получить новые":**

1. **Фронтенд:** `handleSyncButton()` → `triggerSync()`
2. **API:** `POST /api/marketplace/accounts/{id}/sync/orders`
3. **Controller:** `MarketplaceSyncController::syncOrders()`
4. **Job:** `SyncMarketplaceOrdersJob::dispatch()`
5. **Service:** `MarketplaceSyncService::syncOrders()`
6. **WB Service:** `WildberriesOrderService::fetchAllOrders()` ✅

**Ранее (неправильно):**

1. **Фронтенд:** `handleSyncButton()` → `fetchNewOrders()`
2. **API:** `GET /api/marketplace/orders/new`
3. **Controller:** `MarketplaceOrderController::getNew()`
4. **WB Service:** `WildberriesOrderService::fetchNewOrders()` ❌ (только новые заказы)

---

## Напоминание о предыдущих исправлениях

В методе `WildberriesOrderService::fetchAllOrders()` уже были внесены следующие исправления:

### A. Использование правильного endpoint
- Эндпоинт: `/api/v3/orders` (без "new") - получает ВСЕ заказы

### B. Правильное обновление статусов
- Статус устанавливается из `$wbStatusGroup`, а не hardcoded `'new'`

### C. Отметка отменённых заказов
- Заказы, которых нет в API response, помечаются как `canceled`

### D. Временная зона и цены
- Время конвертируется в `Asia/Tashkent` (UTC+5)
- Цены делятся на 100 и округляются до 2 знаков

См. подробности в `WB_SYNC_FIXES.md`

---

## Тестирование

### Как проверить:

1. **Убедиться что queue worker запущен:**
```bash
ps aux | grep "queue:work"
# Если не запущен:
./restart-queue-worker.sh
```

2. **В браузере:**
   - Открыть страницу заказов Wildberries
   - Нажать кнопку "Получить новые"
   - Дождаться завершения синхронизации

3. **Проверить логи:**
```bash
tail -f storage/logs/laravel.log | grep -E "Fetching all WB|fetchAllOrders|Marked.*canceled"
```

Вы должны увидеть:
```
Fetching all WB FBS orders
WB all orders fetch completed
Marked X orders as canceled (not in API response)
```

4. **Проверить результат:**
   - ✅ Заказы с правильными статусами (Новые / На сборке / В доставке / Архив)
   - ✅ Старые отменённые заказы во вкладке "Отменённые"
   - ✅ Время в формате UTC+5
   - ✅ Цены корректные (рубли.копейки)

---

## Связанные файлы

Исправленные файлы:
- `routes/console.php` - исправлена команда планировщика (строка 51)
- `resources/views/pages/marketplace/orders.blade.php` - унифицирован метод синхронизации (строки 1148-1155)

Ранее исправленные (см. WB_SYNC_FIXES.md):
- `app/Services/Marketplaces/Wildberries/WildberriesOrderService.php`
  - Метод `fetchAllOrders()` (строки 133-247)
  - Метод `processOrderFromMarketplace()` (строки 339-406)
- `app/Services/Marketplaces/MarketplaceSyncService.php` (строка 234)

---

## Статус: ✅ Готово к тестированию

Все изменения внесены. Queue worker перезапущен.

Теперь синхронизация должна работать правильно:
- Получаются ВСЕ заказы (не только новые)
- Статусы обновляются корректно
- Отменённые заказы помечаются
- Временная зона и цены правильные
