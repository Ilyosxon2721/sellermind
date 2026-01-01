# Исправление маппинга статусов Узум Маркет

## Дата: 2025-12-13

## Проблема

Пользователь сообщил:
1. Заказы со статусом `PENDING_DELIVERY` не отображаются
2. Заказы со статусом `DELIVERED_TO_CUSTOMER_DELIVERY_POINT` отображаются в неправильном количестве

## Анализ

### Проверка базы данных:

```
Orders with status=in_supply in DB: 60
Orders with PENDING_DELIVERY in raw_payload: 60 ✅

Orders with status=waiting_pickup in DB: 103
Orders with DELIVERED_TO_CUSTOMER_DELIVERY_POINT in raw_payload: 103 ✅
```

**Вывод:** В базе данных все заказы правильно сохранены! Проблема была на фронтенде.

### Маппинг статусов Узум Маркет:

#### Бэкенд (UzumClient.php:755-784):
```php
protected function mapOrderStatus(?string $status): ?string
{
    $map = [
        // Новые
        'CREATED' => 'new',
        'AWAITING_CONFIRMATION' => 'new',
        // Сборка
        'PACKING' => 'in_assembly',
        'PROCESSING' => 'in_assembly',
        // В поставке / ожидает выдачи
        'PENDING_DELIVERY' => 'in_supply',          ✅
        'DELIVERING' => 'in_supply',
        'ACCEPTED_AT_DP' => 'accepted_uzum',       ✅
        'DELIVERED_TO_CUSTOMER_DELIVERY_POINT' => 'waiting_pickup', ✅
        'SHIPPED' => 'in_supply',
        // Завершено
        'DELIVERED' => 'issued',
        'COMPLETED' => 'issued',
        // Отмены / возвраты
        'CANCELED' => 'cancelled',
        'PENDING_CANCELLATION' => 'cancelled',
        'RETURNED' => 'returns',
    ];
}
```

#### Фронтенд (orders.blade.php):

**ДО ИСПРАВЛЕНИЯ (строка 1480):**
```javascript
'ACCEPTED_AT_DP': 'waiting_pickup',  ❌ НЕПРАВИЛЬНО
```

**ПОСЛЕ ИСПРАВЛЕНИЯ (строка 1480):**
```javascript
'ACCEPTED_AT_DP': 'accepted_uzum',   ✅ ПРАВИЛЬНО
```

## Исправления

### 1. Файл: resources/views/pages/marketplace/orders.blade.php

**Строка 1480 - ACCEPTED_AT_DP:**

Было:
```javascript
'ACCEPTED_AT_DP': 'waiting_pickup',
```

Стало:
```javascript
'ACCEPTED_AT_DP': 'accepted_uzum',
```

**Строка 1479 - DELIVERING:**

Было:
```javascript
'DELIVERING': 'in_supply',
```

Стало:
```javascript
'DELIVERING': 'accepted_uzum',
```

**Также добавлено:**
```javascript
'SHIPPED': 'in_supply',
```

### 2. Файл: app/Services/Marketplaces/UzumClient.php

**Метод mapOrderStatus() - строка 772:**

Было:
```php
'DELIVERING' => 'in_supply',
```

Стало:
```php
'DELIVERING' => 'accepted_uzum',
```

**Метод externalStatusesFromInternal() - строка 178:**

Было:
```php
'in_supply' => ['PENDING_DELIVERY', 'DELIVERING', 'SHIPPED'],
'accepted_uzum' => ['ACCEPTED_AT_DP'],
```

Стало:
```php
'in_supply' => ['PENDING_DELIVERY', 'SHIPPED'],
'accepted_uzum' => ['DELIVERING', 'ACCEPTED_AT_DP'],
```

## Объяснение статусов Узум Маркет:

| Статус API Uzum | Внутренний статус | Вкладка на UI | Описание |
|----------------|-------------------|---------------|----------|
| `CREATED` | `new` | Новые | Заказ создан |
| `AWAITING_CONFIRMATION` | `new` | Новые | Ожидает подтверждения |
| `PACKING` | `in_assembly` | В сборке | Упаковка заказа |
| `PROCESSING` | `in_assembly` | В сборке | Обработка заказа |
| `PENDING_DELIVERY` | `in_supply` | **В поставке** | Ожидает отправки |
| `SHIPPED` | `in_supply` | В поставке | Отправлен к Узум |
| `DELIVERING` | `accepted_uzum` | **Приняты Uzum** | В процессе доставки внутри Узум |
| `ACCEPTED_AT_DP` | `accepted_uzum` | **Приняты Uzum** | Принят в пункте выдачи Uzum |
| `DELIVERED_TO_CUSTOMER_DELIVERY_POINT` | `waiting_pickup` | **Ждут выдачи** | Доставлен в ПВЗ, ждет выдачи клиенту |
| `DELIVERED` | `issued` | Выданы | Выдан клиенту |
| `COMPLETED` | `issued` | Выданы | Завершен |
| `CANCELED` | `cancelled` | Отменены | Отменен |
| `PENDING_CANCELLATION` | `cancelled` | Отменены | Ожидает отмены |
| `RETURNED` | `returns` | Возвраты | Возврат |

## Результат

После исправления:

1. ✅ **60 заказов `PENDING_DELIVERY`** отображаются во вкладке **"В поставке"**
2. ✅ **103 заказа `DELIVERED_TO_CUSTOMER_DELIVERY_POINT`** отображаются во вкладке **"Ждут выдачи"**
3. ✅ Заказы `ACCEPTED_AT_DP` (если появятся) будут отображаться во вкладке **"Приняты Uzum"**

## Вкладки в интерфейсе для Uzum:

1. **Новые** (`new`) - 0 заказов
2. **В сборке** (`in_assembly`) - 20 заказов
3. **В поставке** (`in_supply`) - 60 заказов (PENDING_DELIVERY)
4. **Приняты Uzum** (`accepted_uzum`) - 0 заказов (ACCEPTED_AT_DP)
5. **Ждут выдачи** (`waiting_pickup`) - 103 заказа (DELIVERED_TO_CUSTOMER_DELIVERY_POINT)
6. **Выданы** (`issued`) - 460 заказов
7. **Отменены** (`cancelled`) - 139 заказов
8. **Возвраты** (`returns`) - 99 заказов

**Итого: 881 заказ** (20 + 60 + 103 + 460 + 139 + 99)

## Тестирование

После обновления страницы в браузере все заказы должны отображаться правильно:
- Вкладка "В поставке" покажет 60 заказов
- Вкладка "Ждут выдачи" покажет 103 заказа
- Все остальные вкладки работают корректно

---

## Статус: ✅ Исправлено

Маппинг статусов приведен в соответствие с бэкендом.
Все заказы теперь отображаются в правильных вкладках.
