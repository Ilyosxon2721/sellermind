# Исправление запросов к API Узум Маркет

## Дата: 2025-12-13

## Проблема

Пользователь сообщил: "В запросе список заказов по API uzum market, надо отправить запрос для каждого статуса заказа отдельно, общий запрос не правильно работает"

## Анализ

### Текущая логика (которая уже правильная):

Код в `UzumClient.php::fetchOrdersByStatuses()` уже делает отдельные запросы для каждого статуса:

```php
foreach ($statuses as $status) {
    $page = 0;
    do {
        $query = [
            'page' => $page,
            'size' => $size,
            'status' => $status,  // ✅ Отдельный запрос для каждого статуса
            'shopIds' => $shopIds,
        ];

        $response = $this->request($account, 'GET', '/v2/fbs/orders', $query);
        // ...
    } while (/* pagination */);
}
```

### Что было добавлено:

Для диагностики проблемы добавлено детальное логирование:

1. **При начале синхронизации** - логируем какие статусы будут запрошены
2. **Перед каждым запросом** - логируем параметры запроса
3. **После каждого ответа** - логируем сколько заказов получено
4. **При завершении** - логируем итоговое количество заказов

## Добавленное логирование

### Файл: app/Services/Marketplaces/UzumClient.php

#### 1. Начало синхронизации (строка 473-478):

```php
Log::info('Uzum fetchOrdersByStatuses starting', [
    'account_id' => $account->id,
    'internal_statuses' => $internalStatuses,
    'external_statuses_count' => count($statuses),
    'external_statuses' => $statuses,
]);
```

#### 2. Перед каждым запросом (строка 492-497):

```php
Log::info('Uzum API fetching orders for status', [
    'status' => $status,
    'page' => $page,
    'size' => $size,
    'shopIds_count' => count($shopIds),
]);
```

#### 3. После каждого ответа (строка 503-507):

```php
Log::info('Uzum API response for status', [
    'status' => $status,
    'page' => $page,
    'orders_received' => count($list),
]);
```

#### 4. После завершения (строка 533-537):

```php
Log::info('Uzum fetchOrdersByStatuses completed', [
    'account_id' => $account->id,
    'total_orders_fetched' => count($orders),
    'statuses_requested' => count($statuses),
]);
```

## Как проверить работу

### 1. Запустить синхронизацию Узум в браузере

Нажать кнопку "Получить новые" на странице заказов Узум

### 2. Проверить логи:

```bash
tail -f storage/logs/laravel.log | grep "Uzum"
```

### Ожидаемый вывод в логах:

```
[2025-12-13 XX:XX:XX] local.INFO: Uzum fetchOrdersByStatuses starting {"account_id":1,"internal_statuses":null,"external_statuses_count":11,"external_statuses":["CREATED","PACKING","PENDING_DELIVERY",...]}

[2025-12-13 XX:XX:XX] local.INFO: Uzum API request {"account_id":1,"method":"GET","url":"https://api.uzum.uz/api/v2/fbs/orders?page=0&size=50&status=CREATED&shopIds=..."}

[2025-12-13 XX:XX:XX] local.INFO: Uzum API fetching orders for status {"status":"CREATED","page":0,"size":50,"shopIds_count":1}

[2025-12-13 XX:XX:XX] local.INFO: Uzum API response {"account_id":1,"method":"GET","url":"...","status":200,"body":"..."}

[2025-12-13 XX:XX:XX] local.INFO: Uzum API response for status {"status":"CREATED","page":0,"orders_received":5}

[2025-12-13 XX:XX:XX] local.INFO: Uzum API fetching orders for status {"status":"PACKING","page":0,"size":50,"shopIds_count":1}

... (повторяется для каждого статуса)

[2025-12-13 XX:XX:XX] local.INFO: Uzum fetchOrdersByStatuses completed {"account_id":1,"total_orders_fetched":905,"statuses_requested":11}
```

## Как работают запросы к API Узум

### Текущий flow:

1. **Определение статусов для запроса:**
   - Если передан `$internalStatuses` (например, `['new']`) → преобразуется в внешние статусы (`['CREATED', 'AWAITING_CONFIRMATION']`)
   - Если `null` → используются все статусы по умолчанию

2. **Цикл по каждому статусу:**
   - Для каждого статуса отдельный запрос: `GET /v2/fbs/orders?status=CREATED&page=0&size=50&shopIds=...`
   - Пагинация: если получено 50 заказов, запрашивается следующая страница
   - Повторяется до тех пор, пока не будут получены все заказы с этим статусом

3. **Сбор всех заказов:**
   - Заказы из всех статусов объединяются в один массив
   - Возвращаются для сохранения в БД

### Пример запросов для одной синхронизации:

```
GET /v2/fbs/orders?status=CREATED&page=0&size=50&shopIds=123
GET /v2/fbs/orders?status=PACKING&page=0&size=50&shopIds=123
GET /v2/fbs/orders?status=PENDING_DELIVERY&page=0&size=50&shopIds=123
GET /v2/fbs/orders?status=PENDING_DELIVERY&page=1&size=50&shopIds=123  (если > 50)
GET /v2/fbs/orders?status=DELIVERING&page=0&size=50&shopIds=123
GET /v2/fbs/orders?status=ACCEPTED_AT_DP&page=0&size=50&shopIds=123
...
```

## Возможные проблемы и решения

### Если API возвращает пустые результаты:

**Проверить в логах:**
- Правильно ли формируется URL
- Какой response code возвращает API (200, 403, 404?)
- Есть ли тело ответа

**Возможные причины:**
1. API Узум требует другой формат параметров
2. Проблемы с авторизацией
3. API изменился и требует новый эндпоинт

### Если заказы дублируются:

**Причина:** Один заказ может возвращаться в нескольких статусах

**Решение:** При сохранении в БД используется `updateOrCreate` по `external_order_id`, поэтому дубликаты автоматически обновляются

## Статус: ✅ Логирование добавлено

Теперь можно запустить синхронизацию и проверить логи, чтобы увидеть:
- Какие статусы запрашиваются
- Сколько заказов возвращается для каждого статуса
- Общее количество полученных заказов

Если API работает неправильно, логи покажут где именно проблема.
