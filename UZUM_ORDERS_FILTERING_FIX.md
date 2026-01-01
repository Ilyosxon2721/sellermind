# Исправление фильтрации заказов Узум по вкладкам

## Дата: 2025-12-13

## Проблема

Пользователь сообщил: "Настрой показ заказов по статусам в UI чтобы в каждом разделе показывались заказы соответствующий статусом к этой раздел"

Заказы не отображались в правильных вкладках, потому что метод `normalizeStatus()` не правильно обрабатывал статусы из БД.

## Анализ

### Как хранятся статусы в БД:

```
DB Status       | Count | Raw Status (из API)
------------------------------------------------
cancelled       | 136   | "CANCELED"
cancelled       | 3     | "RETURNED"
completed       | 24    | "COMPLETED"
in_assembly     | 20    | "PACKING"
in_supply       | 60    | "PENDING_DELIVERY"
issued          | 450   | "COMPLETED"
issued          | 10    | "DELIVERED"
returns         | 99    | "RETURNED"
waiting_pickup  | 103   | "DELIVERED_TO_CUSTOMER_DELIVERY_POINT"
```

**Важно:** В БД статусы уже нормализованы (lowercase: `issued`, `waiting_pickup`, etc.), а в `raw_payload.status` хранятся оригинальные статусы API (uppercase: `COMPLETED`, `DELIVERED_TO_CUSTOMER_DELIVERY_POINT`).

### Проблема в старом коде:

```javascript
// СТАРЫЙ КОД (неправильный)
const raw = (order.status || order.raw_payload?.status || '').toString().toUpperCase();
const map = {
    'COMPLETED': 'issued',
    'DELIVERED_TO_CUSTOMER_DELIVERY_POINT': 'waiting_pickup',
    ...
};
const mapped = map[raw];
```

**Что было не так:**
1. Брал `order.status` из БД (например, `"issued"`)
2. Переводил в uppercase → `"ISSUED"`
3. Искал в маппинге → не находил (там только `"COMPLETED": "issued"`)
4. Возвращал `null`
5. Заказ не отображался во вкладке

### Правильная логика:

1. **Сначала проверить** - статус из БД уже нормализован?
2. **Если да** - вернуть его как есть
3. **Если нет** - замаппить из `raw_payload.status`

## Исправление

### Файл: resources/views/pages/marketplace/orders.blade.php

**Метод `normalizeStatus()` (строки 1467-1501):**

#### ДО:

```javascript
normalizeStatus(order) {
    if (!order) return null;
    if (this.accountMarketplace === 'uzum') {
        const raw = (order.status || order.raw_payload?.status || '').toString().toUpperCase();
        const map = {
            'COMPLETED': 'issued',
            'DELIVERED_TO_CUSTOMER_DELIVERY_POINT': 'waiting_pickup',
            ...
        };
        return map[raw] || null;
    }
}
```

**Проблема:** Пытался маппить уже нормализованный статус из БД.

#### ПОСЛЕ:

```javascript
normalizeStatus(order) {
    if (!order) return null;
    if (this.accountMarketplace === 'uzum') {
        // 1. Сначала проверяем статус из БД (уже нормализованный)
        const dbStatus = (order.status || '').toString().toLowerCase();
        const validStatuses = ['new', 'in_assembly', 'in_supply', 'accepted_uzum', 'waiting_pickup', 'issued', 'cancelled', 'returns'];

        if (validStatuses.includes(dbStatus)) {
            order.status_normalized = dbStatus;
            return dbStatus;  // ✅ Возвращаем как есть
        }

        // 2. Если статус не нормализован, мапим из raw_payload
        const rawStatus = (order.raw_payload?.status || '').toString().toUpperCase();
        const map = {
            'CREATED': 'new',
            'AWAITING_CONFIRMATION': 'new',
            'PACKING': 'in_assembly',
            'PROCESSING': 'in_assembly',
            'PENDING_DELIVERY': 'in_supply',
            'SHIPPED': 'in_supply',
            'DELIVERING': 'accepted_uzum',
            'ACCEPTED_AT_DP': 'accepted_uzum',
            'DELIVERED_TO_CUSTOMER_DELIVERY_POINT': 'waiting_pickup',
            'DELIVERED': 'issued',
            'COMPLETED': 'issued',
            'CANCELED': 'cancelled',
            'PENDING_CANCELLATION': 'cancelled',
            'RETURNED': 'returns',
        };
        const mapped = map[rawStatus] || null;
        order.status_normalized = mapped;
        return mapped;
    }
}
```

### Также удален дублирующий код:

Удалены строки 1534-1555, которые дублировали ту же логику маппинга для Узум.

## Как работает фильтрация по вкладкам

### 1. Маппинг вкладок на статусы (строки 1614-1624):

```javascript
const statusMap = this.accountMarketplace === 'uzum'
    ? {
        'new': ['new'],
        'in_assembly': ['in_assembly'],
        'in_supply': ['in_supply'],
        'accepted_uzum': ['accepted_uzum'],
        'waiting_pickup': ['waiting_pickup'],
        'issued': ['issued'],
        'cancelled': ['cancelled'],
        'returns': ['returns'],
    }
    : { /* WB статусы */ };
```

### 2. Фильтрация заказов (строки 1634-1640):

```javascript
return baseFiltered.filter(order => {
    if (allowedStatuses === null) return true;
    if (allowedStatuses.length === 0) return false;
    const st = this.normalizeStatus(order);  // ✅ Получаем нормализованный статус
    if (!st) return false;
    return allowedStatuses.includes(st);     // ✅ Проверяем соответствие вкладке
});
```

### 3. Пример работы:

**Вкладка "Ждут выдачи":**
- `activeTab = 'waiting_pickup'`
- `allowedStatuses = ['waiting_pickup']`
- Для заказа с `order.status = 'waiting_pickup'`:
  - `normalizeStatus()` возвращает `'waiting_pickup'`
  - `allowedStatuses.includes('waiting_pickup')` → `true`
  - Заказ отображается ✅

**Вкладка "Выданы":**
- `activeTab = 'issued'`
- `allowedStatuses = ['issued']`
- Для заказа с `order.status = 'issued'`:
  - `normalizeStatus()` возвращает `'issued'`
  - `allowedStatuses.includes('issued')` → `true`
  - Заказ отображается ✅

## Результат

После исправления каждая вкладка отображает только свои заказы:

| Вкладка | Статусы в БД | Количество |
|---------|--------------|------------|
| Новые | `new` | 0 |
| В сборке | `in_assembly` | 20 |
| В поставке | `in_supply` | 60 |
| Приняты Узум | `accepted_uzum` | 0 |
| Ждут выдачи | `waiting_pickup` | 103 |
| Выданы | `issued` | 460 |
| Отменены | `cancelled` | 139 |
| Возвраты | `returns` | 99 |

**Итого: 881 заказ распределен по вкладкам**

## Тестирование

1. Откройте страницу заказов Узум в браузере
2. Перейдите по каждой вкладке:
   - **Новые** - должно быть 0 заказов
   - **В сборке** - 20 заказов
   - **В поставке** - 60 заказов
   - **Приняты Узум** - 0 заказов
   - **Ждут выдачи** - 103 заказа
   - **Выданы** - 460 заказов
   - **Отменены** - 139 заказов
   - **Возвраты** - 99 заказов

3. Проверьте что счетчики на вкладках совпадают с количеством отображаемых заказов

## Статус: ✅ Исправлено

Фильтрация заказов по вкладкам теперь работает правильно.
Каждая вкладка показывает только заказы с соответствующим статусом.
