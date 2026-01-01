# Исправления синхронизации Wildberries

## Дата: 2025-12-12

## Проблемы которые были исправлены:

### 1. Синхронизация только заказов со статусом "Новые"

**Проблема:** Синхронизировались только новые заказы (статус NEW), заказы в других статусах (в сборке, в доставке, завершенные) не синхронизировались.

**Причина:** Использовался метод `fetchNewOrders()` с эндпоинтом `/api/v3/orders/new`, который возвращает только новые заказы.

**Решение:**
- Создан новый метод `fetchAllOrders()` в `WildberriesOrderService.php` (строки 125-228)
- Использует эндпоинт `/api/v3/orders` (без "new"), который возвращает заказы со ВСЕМИ статусами
- Обновлен `MarketplaceSyncService.php` для вызова `fetchAllOrders()` вместо `fetchNewOrders()` (строка 234)

**Файлы:**
- `app/Services/Marketplaces/Wildberries/WildberriesOrderService.php`: строки 125-228
- `app/Services/Marketplaces/MarketplaceSyncService.php`: строка 234

---

### 2. Неправильная временная зона (время в UTC вместо +0500)

**Проблема:** Время заказов отображалось в UTC, а не в Ташкентском времени (UTC+5).

**Причина:** Использовался `Carbon::parse()` без указания временной зоны.

**Решение:**
- Добавлена явная конвертация в временную зону Asia/Tashkent (UTC+5)
- Обновлен метод `processOrderFromMarketplace()` в `WildberriesOrderService.php`

**Код (строки 370-373):**
```php
// Время в WB API в UTC, конвертируем в Ташкентское время (UTC+5)
$orderedAt = isset($orderData['createdAt'])
    ? Carbon::parse($orderData['createdAt'])->timezone('Asia/Tashkent')
    : null;
```

**Файл:**
- `app/Services/Marketplaces/Wildberries/WildberriesOrderService.php`: строки 370-373

---

### 3. Неправильное отображение цен

**Проблема:** Цены отображались некорректно (не в рублях с копейками).

**Причина:** API Wildberries возвращает цены в копейках (cents), нужно делить на 100.

**Решение:**
- Добавлено деление на 100 и округление до 2 знаков после запятой для всех цен
- Обновлен метод `processOrderFromMarketplace()` в `WildberriesOrderService.php`

**Код (строки 364-368):**
```php
// Цены в WB API приходят в копейках, конвертируем в рубли
$totalAmount = isset($orderData['convertedPrice']) ? round($orderData['convertedPrice'] / 100, 2) : null;
$finalPrice = isset($orderData['convertedPrice']) ? round($orderData['convertedPrice'] / 100, 2) : null;
$salePrice = isset($orderData['price']) ? round($orderData['price'] / 100, 2) : null;
$scanPrice = isset($orderData['scanPrice']) ? round($orderData['scanPrice'] / 100, 2) : null;
```

**Файл:**
- `app/Services/Marketplaces/Wildberries/WildberriesOrderService.php`: строки 364-368

---

## Дополнительное улучшение:

### Сохранение raw_payload для отладки

Добавлено сохранение полного ответа API в поле `raw_payload` для возможности отладки и анализа данных.

**Код (строка 400):**
```php
'raw_payload' => $orderData,
```

---

## Как проверить исправления:

1. Убедитесь, что queue worker запущен:
```bash
ps aux | grep "queue:work"
```

2. Если не запущен, запустите скрипт:
```bash
./restart-queue-worker.sh
```

3. В браузере откройте страницу заказов Wildberries

4. Нажмите кнопку "Получить новые" для запуска синхронизации

5. Проверьте:
   - ✅ Заказы со всеми статусами синхронизируются (не только NEW)
   - ✅ Время отображается в формате UTC+5 (Ташкентское время)
   - ✅ Цены отображаются в рублях с двумя знаками после запятой

6. Проверьте логи:
```bash
tail -f storage/logs/laravel.log | grep -E "Fetching all WB|completed"
```

Вы должны увидеть:
```
Fetching all WB FBS orders
WB all orders fetch completed
```

---

## Связанные файлы:

- `WB_SYNC_TROUBLESHOOTING.md` - Руководство по устранению проблем с синхронизацией
- `restart-queue-worker.sh` - Скрипт для перезапуска queue worker
- `QUEUE_SETUP.md` - Настройка очередей Laravel

---

---

## ОБНОВЛЕНИЕ 2025-12-12 (22:20):

### 4. Статусы заказов не обновляются при синхронизации

**Проблема:** После синхронизации все старые заказы остаются в статусе "Новые", хотя на маркетплейсе они уже "На сборке" или в других статусах.

**Причина:** В методе `processOrderFromMarketplace()` статус всегда устанавливался как `'status' => 'new'` (строка 376), игнорируя рассчитанный `$wbStatusGroup`.

**Решение:**
- Изменена строка 376: теперь используется `'status' => $wbStatusGroup`
- Метод `mapWbStatusGroup()` правильно определяет группу статуса на основе `wb_status` и `supplyId`
- Статусы теперь правильно обновляются при каждой синхронизации

**Код (строка 376):**
```php
// ДО:
'status' => 'new',

// ПОСЛЕ:
'status' => $wbStatusGroup,
```

**Файл:**
- `app/Services/Marketplaces/Wildberries/WildberriesOrderService.php`: строка 376

---

### 5. Отмененные заказы остаются в базе

**Проблема:** Заказы, которых больше нет в API (отменены или удалены), остаются в базе в старом статусе.

**Решение:**
- Добавлен массив `$syncedOrderIds` для отслеживания всех синхронизированных заказов
- После синхронизации все заказы, которых нет в `$syncedOrderIds`, автоматически помечаются как `canceled`
- Исключение: заказы уже в статусах `archive` или `canceled` не меняются

**Код (строки 187-201):**
```php
// Помечаем заказы, которых нет в ответе API, как отменённые
if (!empty($syncedOrderIds)) {
    $archivedCount = MarketplaceOrder::where('marketplace_account_id', $account->id)
        ->whereNotIn('external_order_id', $syncedOrderIds)
        ->whereNotIn('status', ['archive', 'canceled'])
        ->update([
            'status' => 'canceled',
            'wb_status_group' => 'canceled',
        ]);

    if ($archivedCount > 0) {
        Log::info("Marked {$archivedCount} orders as canceled (not in API response)");
    }
}
```

**Файл:**
- `app/Services/Marketplaces/Wildberries/WildberriesOrderService.php`: строки 139, 170, 187-201

---

## Дополнительные инструменты:

### Скрипт очистки заказов WB

Создан скрипт `clear-wb-orders.sh` для полной очистки заказов Wildberries и пересинхронизации.

**Использование:**
```bash
./clear-wb-orders.sh
```

Скрипт:
1. Запрашивает подтверждение
2. Находит ID аккаунта Wildberries
3. Удаляет все заказы этого аккаунта
4. Предлагает запустить синхронизацию заново

---

## Статус: ✅ Все исправления готовы к тестированию

Queue worker перезапущен, очередь очищена, все изменения внесены в код.

### Рекомендуемый порядок тестирования:

1. **Очистить старые заказы** (опционально):
   ```bash
   ./clear-wb-orders.sh
   ```

2. **Проверить queue worker**:
   ```bash
   ps aux | grep "queue:work"
   ```

3. **В браузере**:
   - Открыть страницу заказов Wildberries
   - Нажать "Получить новые"
   - Дождаться завершения синхронизации

4. **Проверить результаты**:
   - ✅ Только 1 заказ в статусе "Новые" (как на маркетплейсе)
   - ✅ 2 заказа в статусе "На сборке" (как на маркетплейсе)
   - ✅ Время отображается в формате UTC+5
   - ✅ Цены корректные (рубли с копейками)
   - ✅ Старые отменённые заказы помечены как "Отменённые"

5. **Проверить логи**:
   ```bash
   tail -f storage/logs/laravel.log | grep -E "Fetching all WB|Marked.*canceled|completed"
   ```
