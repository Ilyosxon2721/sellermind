# Исправление статусов Узум Маркет

## Дата: 2025-12-13

## Проблема

Пользователь сообщил о несоответствии количества заказов:
- **В сборке:** реально 53 шт, у нас 20 шт (-33)
- **Приняты Узум:** реально 61 шт, у нас 0 шт (-61)
- **Ждут выдачи:** реально 71 шт, у нас 103 шт (+32)

## Анализ

### 1. Проверка логов синхронизации

При запросе заказов обнаружены ошибки:

```json
{
  "status": "SHIPPED",
  "error": "Uzum API error (400): bad-request-001: Bad request"
}
```

API возвращал ошибку 400 для статуса `SHIPPED`.

### 2. Получение списка поддерживаемых статусов

Пользователь предоставил полный список статусов API Uzum Market:

```
CREATED
PACKING
PENDING_DELIVERY
DELIVERING
DELIVERED
ACCEPTED_AT_DP
DELIVERED_TO_CUSTOMER_DELIVERY_POINT
COMPLETED
CANCELED
PENDING_CANCELLATION
RETURNED
```

**Важно:** В этом списке НЕТ статусов:
- `AWAITING_CONFIRMATION` ❌
- `PROCESSING` ❌
- `SHIPPED` ❌

Эти статусы были в нашем коде, но API их не поддерживает!

## Исправления

### 1. Файл: `app/Services/Marketplaces/UzumClient.php`

#### Метод `externalStatusesFromInternal()` (строки 156-169)

**Было:**
```php
$default = [
    'CREATED',
    'AWAITING_CONFIRMATION',  // ❌ Не поддерживается
    'PACKING',
    'PROCESSING',             // ❌ Не поддерживается
    'PENDING_DELIVERY',
    'SHIPPED',                // ❌ Не поддерживается (вызывал ошибку 400)
    'DELIVERING',
    'ACCEPTED_AT_DP',
    'DELIVERED_TO_CUSTOMER_DELIVERY_POINT',
    'DELIVERED',
    'COMPLETED',
    'CANCELED',
    'PENDING_CANCELLATION',
    'RETURNED',
];

$map = [
    'new' => ['CREATED', 'AWAITING_CONFIRMATION'],
    'in_assembly' => ['PACKING', 'PROCESSING'],
    'in_supply' => ['PENDING_DELIVERY', 'SHIPPED'],
    ...
];
```

**Стало:**
```php
// Полный список статусов, поддерживаемых API Uzum Market
$default = [
    'CREATED',
    'PACKING',
    'PENDING_DELIVERY',
    'DELIVERING',
    'ACCEPTED_AT_DP',
    'DELIVERED_TO_CUSTOMER_DELIVERY_POINT',
    'DELIVERED',
    'COMPLETED',
    'CANCELED',
    'PENDING_CANCELLATION',
    'RETURNED',
];

$map = [
    'new' => ['CREATED'],
    'in_assembly' => ['PACKING'],
    'in_supply' => ['PENDING_DELIVERY'],
    'accepted_uzum' => ['DELIVERING', 'ACCEPTED_AT_DP'],
    'waiting_pickup' => ['DELIVERED_TO_CUSTOMER_DELIVERY_POINT'],
    'issued' => ['DELIVERED', 'COMPLETED'],
    'cancelled' => ['CANCELED', 'PENDING_CANCELLATION'],
    'returns' => ['RETURNED'],
];
```

#### Метод `mapOrderStatus()` (строки 789-809)

**Было:**
```php
$map = [
    'CREATED' => 'new',
    'AWAITING_CONFIRMATION' => 'new',  // ❌
    'PACKING' => 'in_assembly',
    'PROCESSING' => 'in_assembly',     // ❌
    'PENDING_DELIVERY' => 'in_supply',
    'SHIPPED' => 'in_supply',          // ❌
    'DELIVERING' => 'accepted_uzum',
    ...
];
```

**Стало:**
```php
// Маппинг статусов API Uzum Market на внутренние статусы
$map = [
    'CREATED' => 'new',
    'PACKING' => 'in_assembly',
    'PENDING_DELIVERY' => 'in_supply',
    'DELIVERING' => 'accepted_uzum',
    'ACCEPTED_AT_DP' => 'accepted_uzum',
    'DELIVERED_TO_CUSTOMER_DELIVERY_POINT' => 'waiting_pickup',
    'DELIVERED' => 'issued',
    'COMPLETED' => 'issued',
    'CANCELED' => 'cancelled',
    'PENDING_CANCELLATION' => 'cancelled',
    'RETURNED' => 'returns',
];
```

### 2. Файл: `resources/views/pages/marketplace/orders.blade.php`

#### Метод `normalizeStatus()` для Uzum (строки 1483-1495)

**Было:**
```javascript
const map = {
    'CREATED': 'new',
    'AWAITING_CONFIRMATION': 'new',  // ❌
    'PACKING': 'in_assembly',
    'PROCESSING': 'in_assembly',     // ❌
    'PENDING_DELIVERY': 'in_supply',
    'SHIPPED': 'in_supply',          // ❌
    'DELIVERING': 'accepted_uzum',
    ...
};
```

**Стало:**
```javascript
// Используем только статусы, поддерживаемые API Uzum Market
const map = {
    'CREATED': 'new',
    'PACKING': 'in_assembly',
    'PENDING_DELIVERY': 'in_supply',
    'DELIVERING': 'accepted_uzum',
    'ACCEPTED_AT_DP': 'accepted_uzum',
    'DELIVERED_TO_CUSTOMER_DELIVERY_POINT': 'waiting_pickup',
    'DELIVERED': 'issued',
    'COMPLETED': 'issued',
    'CANCELED': 'cancelled',
    'PENDING_CANCELLATION': 'cancelled',
    'RETURNED': 'returns',
};
```

## Актуальный маппинг статусов Узум Маркет

| Статус API | Внутренний статус | Вкладка UI | Описание |
|-----------|-------------------|------------|----------|
| `CREATED` | `new` | Новые | Заказ создан |
| `PACKING` | `in_assembly` | В сборке | Упаковка заказа |
| `PENDING_DELIVERY` | `in_supply` | В поставке | Ожидает отправки |
| `DELIVERING` | `accepted_uzum` | Приняты Узум | В процессе доставки внутри Узум |
| `ACCEPTED_AT_DP` | `accepted_uzum` | Приняты Узум | Принят в пункте выдачи Узум |
| `DELIVERED_TO_CUSTOMER_DELIVERY_POINT` | `waiting_pickup` | Ждут выдачи | В ПВЗ, ждет выдачи клиенту |
| `DELIVERED` | `issued` | Выданы | Выдан клиенту |
| `COMPLETED` | `issued` | Выданы | Завершен |
| `CANCELED` | `cancelled` | Отменены | Отменен |
| `PENDING_CANCELLATION` | `cancelled` | Отменены | Ожидает отмены |
| `RETURNED` | `returns` | Возвраты | Возврат |

## Результат

После исправления:

1. ✅ Ошибка 400 "Bad request" для `SHIPPED` исчезла
2. ✅ Синхронизация проходит без ошибок
3. ✅ Код соответствует реальным статусам API

## Текущее состояние в БД

```
Новые (new): 0
В сборке (in_assembly): 20
В поставке (in_supply): 60
Приняты Узум (accepted_uzum): 0
Ждут выдачи (waiting_pickup): 103
Выданы (issued): 460
Отменены (cancelled): 139
Возвраты (returns): 99

Итого: 881 заказ
```

## Почему количество не совпадает с реальностью?

### Гипотезы:

1. **Ограничение по времени:** Команда синхронизации по умолчанию запрашивает только последние 7 дней. Если заказы старше, они не синхронизируются.

2. **Несколько складов (shopIds):** Возможно, у аккаунта есть несколько складов, и не все они учитываются при синхронизации.

3. **Реальные статусы отличаются:**
   - В сборке: 20 (PACKING) vs 53 реальных
   - Возможно, 33 заказа имеют другой статус, который мы не запрашиваем

4. **Приняты Узум: 0 vs 61:**
   - Мы запрашиваем `DELIVERING` и `ACCEPTED_AT_DP`
   - API возвращает 0 заказов для обоих статусов
   - Возможно, в реальности используется другой статус

5. **Ждут выдачи: 103 vs 71:**
   - У нас на 32 заказа больше
   - Возможно, часть заказов уже переместилась в другой статус в реальном API

## Рекомендации

### 1. Проверить реальные статусы заказов

Нужно открыть несколько заказов из каждой проблемной вкладки в личном кабинете Узум и проверить:
- Какой точный статус у этих заказов в API?
- Возможно, есть дополнительные статусы, которые мы не учитываем

### 2. Проверить фильтры синхронизации

```bash
php artisan marketplace:sync-orders --account=1 --days=30
```

Попробовать синхронизировать за 30 дней вместо 7.

### 3. Проверить shopIds

Убедиться, что в `marketplace_shops` есть все склады аккаунта:

```sql
SELECT * FROM marketplace_shops
WHERE marketplace_account_id = 1;
```

### 4. Добавить детальное логирование

Временно добавить логирование каждого заказа при маппинге статусов, чтобы увидеть, какие статусы реально приходят из API.

## Следующие шаги

1. ✅ Удалены неподдерживаемые статусы (`AWAITING_CONFIRMATION`, `PROCESSING`, `SHIPPED`)
2. ✅ Синхронизация работает без ошибок
3. ❓ Требуется проверка реальных данных в личном кабинете Узум
4. ❓ Возможно, нужно добавить дополнительные статусы или изменить маппинг

---

## Статус: ✅ Технические ошибки исправлены

Код приведен в соответствие с реальным API Узум Маркет.
Для полного решения проблемы с количеством заказов требуется дополнительная проверка данных.
