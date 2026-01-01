# Финальное исправление маппинга статусов Узум Маркет

## Дата: 2025-12-13

## Проблема

Пользователь сообщил о несоответствии количества заказов между API Узум и платформой:

| Раздел | В API Узум | На платформе | Статус |
|--------|------------|--------------|--------|
| В сборке | 53 | 20 | ❌ |
| Приняты Узум | 61 | 0 | ❌ |
| Ждут выдачи | 71 | 103 | ❌ |

## Корневая причина

### 1. Неправильный маппинг статуса `DELIVERED`

Статус `DELIVERED` мапился на `issued` (Выданы), но на самом деле означает "Доставлен в ПВЗ" и должен отображаться в разделе "Приняты Узум".

**Было:**
```php
'DELIVERED' => 'issued',
```

**Должно быть:**
```php
'DELIVERED' => 'accepted_uzum',
```

### 2. Использование неподдерживаемых статусов API

В коде использовались статусы, которые API Узум не поддерживает:
- `AWAITING_CONFIRMATION` ❌
- `PROCESSING` ❌
- `SHIPPED` ❌ (вызывал ошибку 400)

### 3. Фильтрация активных заказов по дате

Активные заказы (в работе) фильтровались по дате создания, что приводило к пропуску старых заказов, которые все еще находятся в работе.

## Исправления

### 1. Файл: `app/Services/Marketplaces/UzumClient.php`

#### A. Метод `mapOrderStatus()` (строки 798-818)

**Было:**
```php
$map = [
    'CREATED' => 'new',
    'PACKING' => 'in_assembly',
    'PENDING_DELIVERY' => 'in_supply',
    'DELIVERING' => 'accepted_uzum',
    'ACCEPTED_AT_DP' => 'accepted_uzum',
    'DELIVERED_TO_CUSTOMER_DELIVERY_POINT' => 'waiting_pickup',
    'DELIVERED' => 'issued',      // ❌ Неправильно
    'COMPLETED' => 'issued',
    'CANCELED' => 'cancelled',
    'PENDING_CANCELLATION' => 'cancelled',
    'RETURNED' => 'returns',
];
```

**Стало:**
```php
$map = [
    'CREATED' => 'new',
    'PACKING' => 'in_assembly',
    'PENDING_DELIVERY' => 'in_supply',
    'DELIVERING' => 'accepted_uzum',
    'ACCEPTED_AT_DP' => 'accepted_uzum',
    'DELIVERED' => 'accepted_uzum',   // ✅ Исправлено - доставлен в ПВЗ
    'DELIVERED_TO_CUSTOMER_DELIVERY_POINT' => 'waiting_pickup',
    'COMPLETED' => 'issued',          // ✅ Только COMPLETED = выдан клиенту
    'CANCELED' => 'cancelled',
    'PENDING_CANCELLATION' => 'cancelled',
    'RETURNED' => 'returns',
];
```

#### B. Метод `externalStatusesFromInternal()` (строки 175-184)

**Было:**
```php
$map = [
    'new' => ['CREATED'],
    'in_assembly' => ['PACKING'],
    'in_supply' => ['PENDING_DELIVERY'],
    'accepted_uzum' => ['DELIVERING', 'ACCEPTED_AT_DP'],
    'waiting_pickup' => ['DELIVERED_TO_CUSTOMER_DELIVERY_POINT'],
    'issued' => ['DELIVERED', 'COMPLETED'],  // ❌ DELIVERED был здесь
    'cancelled' => ['CANCELED', 'PENDING_CANCELLATION'],
    'returns' => ['RETURNED'],
];
```

**Стало:**
```php
$map = [
    'new' => ['CREATED'],
    'in_assembly' => ['PACKING'],
    'in_supply' => ['PENDING_DELIVERY'],
    'accepted_uzum' => ['DELIVERING', 'ACCEPTED_AT_DP', 'DELIVERED'],  // ✅ Добавлен DELIVERED
    'waiting_pickup' => ['DELIVERED_TO_CUSTOMER_DELIVERY_POINT'],
    'issued' => ['COMPLETED'],  // ✅ Только COMPLETED
    'cancelled' => ['CANCELED', 'PENDING_CANCELLATION'],
    'returns' => ['RETURNED'],
];
```

#### C. Список поддерживаемых статусов (строки 156-168)

Удалены неподдерживаемые статусы:
```php
$default = [
    'CREATED',
    // 'AWAITING_CONFIRMATION', ❌ Удален
    'PACKING',
    // 'PROCESSING',            ❌ Удален
    'PENDING_DELIVERY',
    // 'SHIPPED',               ❌ Удален (вызывал 400 Bad Request)
    'DELIVERING',
    'ACCEPTED_AT_DP',
    'DELIVERED_TO_CUSTOMER_DELIVERY_POINT',
    'DELIVERED',
    'COMPLETED',
    'CANCELED',
    'PENDING_CANCELLATION',
    'RETURNED',
];
```

#### D. Отключена фильтрация по дате для активных статусов (строки 481-527)

**Добавлено:**
```php
// Активные статусы (заказы в работе) - загружаем ВСЕ без фильтрации по дате
$activeStatuses = ['CREATED', 'PACKING', 'PENDING_DELIVERY', 'DELIVERING', 'ACCEPTED_AT_DP', 'DELIVERED_TO_CUSTOMER_DELIVERY_POINT'];

foreach ($statuses as $status) {
    $isActiveStatus = in_array($status, $activeStatuses);

    foreach ($list as $orderData) {
        // Для активных статусов загружаем все заказы без фильтрации по дате
        if (!$isActiveStatus) {
            // Фильтрация по дате только для архивных статусов
            if ($fromMs && $created && $created < $fromMs) {
                $stopStatus = true;
                continue;
            }
        }
        $orders[] = $this->mapOrderData($orderData, 'fbs');
    }
}
```

### 2. Файл: `resources/views/pages/marketplace/orders.blade.php`

#### Метод `normalizeStatus()` для Uzum (строки 1483-1495)

**Было:**
```javascript
const map = {
    'CREATED': 'new',
    'PACKING': 'in_assembly',
    'PENDING_DELIVERY': 'in_supply',
    'DELIVERING': 'accepted_uzum',
    'ACCEPTED_AT_DP': 'accepted_uzum',
    'DELIVERED_TO_CUSTOMER_DELIVERY_POINT': 'waiting_pickup',
    'DELIVERED': 'issued',      // ❌ Неправильно
    'COMPLETED': 'issued',
    'CANCELED': 'cancelled',
    'PENDING_CANCELLATION': 'cancelled',
    'RETURNED': 'returns',
};
```

**Стало:**
```javascript
const map = {
    'CREATED': 'new',
    'PACKING': 'in_assembly',
    'PENDING_DELIVERY': 'in_supply',
    'DELIVERING': 'accepted_uzum',
    'ACCEPTED_AT_DP': 'accepted_uzum',
    'DELIVERED': 'accepted_uzum',  // ✅ Исправлено
    'DELIVERED_TO_CUSTOMER_DELIVERY_POINT': 'waiting_pickup',
    'COMPLETED': 'issued',         // ✅ Только COMPLETED
    'CANCELED': 'cancelled',
    'PENDING_CANCELLATION': 'cancelled',
    'RETURNED': 'returns',
};
```

## Актуальный маппинг статусов Узум Маркет

| Статус API Uzum | Внутренний статус | Раздел UI | Описание |
|----------------|-------------------|-----------|----------|
| `CREATED` | `new` | Новые | Заказ создан |
| `PACKING` | `in_assembly` | В сборке | Упаковка заказа |
| `PENDING_DELIVERY` | `in_supply` | В поставке | Ожидает отправки к Узум |
| `DELIVERING` | `accepted_uzum` | **Приняты Узум** | В процессе доставки внутри Узум |
| `ACCEPTED_AT_DP` | `accepted_uzum` | **Приняты Узум** | Принят в ПВЗ Узум |
| `DELIVERED` | `accepted_uzum` | **Приняты Узум** | Доставлен в ПВЗ (ждет выдачи) |
| `DELIVERED_TO_CUSTOMER_DELIVERY_POINT` | `waiting_pickup` | Ждут выдачи | В ПВЗ, готов к выдаче клиенту |
| `COMPLETED` | `issued` | Выданы | Выдан клиенту |
| `CANCELED` | `cancelled` | Отменены | Отменен |
| `PENDING_CANCELLATION` | `cancelled` | Отменены | Ожидает отмены |
| `RETURNED` | `returns` | Возвраты | Возврат |

## Понимание жизненного цикла заказа Узум

```
CREATED (Новые)
    ↓
PACKING (В сборке)
    ↓
PENDING_DELIVERY (В поставке)
    ↓
DELIVERING (Приняты Узум - в пути к ПВЗ)
    ↓
ACCEPTED_AT_DP (Приняты Узум - принят в ПВЗ)
    ↓
DELIVERED (Приняты Узум - доставлен, ждет выдачи)
    ↓
DELIVERED_TO_CUSTOMER_DELIVERY_POINT (Ждут выдачи - готов к выдаче)
    ↓
COMPLETED (Выданы - выдан клиенту)
```

**Важно:** Статусы `DELIVERING`, `ACCEPTED_AT_DP` и `DELIVERED` все относятся к этапу "Приняты Узум", когда заказ уже у Узум, но еще не готов к выдаче клиенту.

## Результат после исправления

```
=== Статусы в uzum_orders ===

Новые (new): 0
В сборке (in_assembly): 53       ✅ (было 20)
В поставке (in_supply): 0
Приняты Узум (accepted_uzum): 61 ✅ (было 0)
Ждут выдачи (waiting_pickup): 71 ✅ (было 103)
Выданы (issued): 1433             ✅ (было 1494)
Отменены (cancelled): 346
Возвраты (returns): 410

Всего: 2374 заказа
```

### Сравнение с требованиями:

| Раздел | Требовалось | Стало | Статус |
|--------|-------------|-------|--------|
| В сборке | 53 | 53 | ✅ |
| Приняты Узум | 61 | 61 | ✅ |
| Ждут выдачи | 71 | 71 | ✅ |

**Все совпадает идеально!**

## Преимущества исправлений

1. **Точность данных:** Количество заказов в каждом разделе теперь полностью совпадает с API Узум
2. **Полнота данных:** Загружаются ВСЕ активные заказы, независимо от даты создания
3. **Производительность:** Удалены неподдерживаемые статусы, что устраняет ошибки 400 Bad Request
4. **Понятность:** Маппинг статусов теперь логически соответствует жизненному циклу заказа

## Связанные документы

- [UZUM_SYNC_ISSUE_INVESTIGATION.md](UZUM_SYNC_ISSUE_INVESTIGATION.md) - Детальное исследование проблемы
- [UZUM_STATUSES_FIX.md](UZUM_STATUSES_FIX.md) - Исправление неподдерживаемых статусов
- [UZUM_STATUS_MAPPING_FIX.md](UZUM_STATUS_MAPPING_FIX.md) - Предыдущие исправления маппинга
- [UZUM_ORDERS_FILTERING_FIX.md](UZUM_ORDERS_FILTERING_FIX.md) - Исправление фильтрации во фронтенде

---

## Статус: ✅ ПОЛНОСТЬЮ ИСПРАВЛЕНО

Все проблемы с синхронизацией и отображением заказов Узум Маркет решены.
Количество заказов в каждом разделе полностью соответствует данным из API Узум.
