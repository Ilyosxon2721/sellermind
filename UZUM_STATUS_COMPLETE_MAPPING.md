# Полный маппинг статусов Узум Маркет

## Дата: 2025-12-13

## Актуальное состояние

### Статусы в базе данных (uzum_orders):

```
Новые (new): 0
В сборке (in_assembly): 53
В поставке (in_supply): 0
Приняты Узум (accepted_uzum): 61
Ждут выдачи (waiting_pickup): 71
Выданы (issued): 1433
Отменены (cancelled): 346
Возвраты (returns): 410

Всего: 2374 заказа
```

### Статусы из API (raw_payload):

```
COMPLETED: 1433 заказов
RETURNED: 410 заказов
CANCELED: 346 заказов
DELIVERED_TO_CUSTOMER_DELIVERY_POINT: 71 заказов
DELIVERED: 61 заказов
PACKING: 53 заказов
```

## Полный маппинг статусов

### 1. Активные заказы (в работе)

| Статус API | Внутренний | Раздел UI | Количество | Описание |
|-----------|-----------|-----------|------------|----------|
| `CREATED` | `new` | Новые | 0 | Заказ создан |
| `PACKING` | `in_assembly` | В сборке | 53 | Упаковка заказа |
| `PENDING_DELIVERY` | `in_supply` | В поставке | 0 | Ожидает отправки |

### 2. Заказы приняты Узум (в системе Узум)

| Статус API | Внутренний | Раздел UI | Количество | Описание |
|-----------|-----------|-----------|------------|----------|
| `DELIVERING` | `accepted_uzum` | Приняты Узум | 0 | В процессе доставки |
| `ACCEPTED_AT_DP` | `accepted_uzum` | Приняты Узум | 0 | Принят в ПВЗ |
| `DELIVERED` | `accepted_uzum` | **Приняты Узум** | **61** | Доставлен в ПВЗ |

**Итого Приняты Узум: 61**

### 3. Ждут выдачи клиенту

| Статус API | Внутренний | Раздел UI | Количество | Описание |
|-----------|-----------|-----------|------------|----------|
| `DELIVERED_TO_CUSTOMER_DELIVERY_POINT` | `waiting_pickup` | **Ждут выдачи** | **71** | Готов к выдаче |

### 4. Завершенные заказы

| Статус API | Внутренний | Раздел UI | Количество | Описание |
|-----------|-----------|-----------|------------|----------|
| `COMPLETED` | `issued` | **Выданы** | **1433** | Выдан клиенту |

### 5. Отмененные заказы

| Статус API | Внутренний | Раздел UI | Количество | Описание |
|-----------|-----------|-----------|------------|----------|
| `CANCELED` | `cancelled` | **Отменены** | **346** | Отменен |
| `PENDING_CANCELLATION` | `cancelled` | Отменены | 0 | Ожидает отмены |

**Итого Отменены: 346**

### 6. Возвраты

| Статус API | Внутренний | Раздел UI | Количество | Описание |
|-----------|-----------|-----------|------------|----------|
| `RETURNED` | `returns` | **Возвраты** | **410** | Возврат |

## Жизненный цикл заказа

```
┌─────────────────────────────────────────────────────────────┐
│                    АКТИВНЫЕ ЗАКАЗЫ                          │
└─────────────────────────────────────────────────────────────┘
    CREATED (Новые) → 0
        ↓
    PACKING (В сборке) → 53
        ↓
    PENDING_DELIVERY (В поставке) → 0
        ↓
┌─────────────────────────────────────────────────────────────┐
│                   ПРИНЯТЫ УЗУМ (61)                         │
└─────────────────────────────────────────────────────────────┘
    DELIVERING (В процессе доставки) → 0
        ↓
    ACCEPTED_AT_DP (Принят в ПВЗ) → 0
        ↓
    DELIVERED (Доставлен в ПВЗ) → 61
        ↓
┌─────────────────────────────────────────────────────────────┐
│                  ЖДУТ ВЫДАЧИ (71)                           │
└─────────────────────────────────────────────────────────────┘
    DELIVERED_TO_CUSTOMER_DELIVERY_POINT → 71
        ↓
┌─────────────────────────────────────────────────────────────┐
│                    ВЫДАНЫ (1433)                            │
└─────────────────────────────────────────────────────────────┘
    COMPLETED (Выдан клиенту) → 1433

┌─────────────────────────────────────────────────────────────┐
│              ОТМЕНЕНЫ / ВОЗВРАТЫ                            │
└─────────────────────────────────────────────────────────────┘
    CANCELED → 346
    RETURNED → 410
```

## Код маппинга

### Backend: `app/Services/Marketplaces/UzumClient.php`

#### Метод `mapOrderStatus()`:
```php
protected function mapOrderStatus(?string $status): ?string
{
    $map = [
        // Новые
        'CREATED' => 'new',

        // Сборка
        'PACKING' => 'in_assembly',

        // В поставке
        'PENDING_DELIVERY' => 'in_supply',

        // Приняты Узум (доставлены в ПВЗ, но еще не выданы клиенту)
        'DELIVERING' => 'accepted_uzum',
        'ACCEPTED_AT_DP' => 'accepted_uzum',
        'DELIVERED' => 'accepted_uzum', // ← Доставлен в ПВЗ

        // Ждут выдачи клиенту
        'DELIVERED_TO_CUSTOMER_DELIVERY_POINT' => 'waiting_pickup',

        // Завершено (выдано клиенту)
        'COMPLETED' => 'issued', // ← Выдан клиенту

        // Отмены / возвраты
        'CANCELED' => 'cancelled',
        'PENDING_CANCELLATION' => 'cancelled',
        'RETURNED' => 'returns',
    ];

    $upper = strtoupper($status);
    return $map[$upper] ?? strtolower($status);
}
```

#### Метод `externalStatusesFromInternal()`:
```php
protected function externalStatusesFromInternal(?array $internalStatuses): array
{
    $map = [
        'new' => ['CREATED'],
        'in_assembly' => ['PACKING'],
        'in_supply' => ['PENDING_DELIVERY'],
        'accepted_uzum' => ['DELIVERING', 'ACCEPTED_AT_DP', 'DELIVERED'],
        'waiting_pickup' => ['DELIVERED_TO_CUSTOMER_DELIVERY_POINT'],
        'issued' => ['COMPLETED'],
        'cancelled' => ['CANCELED', 'PENDING_CANCELLATION'],
        'returns' => ['RETURNED'],
    ];
}
```

### Frontend: `resources/views/pages/marketplace/orders.blade.php`

```javascript
const map = {
    'CREATED': 'new',
    'PACKING': 'in_assembly',
    'PENDING_DELIVERY': 'in_supply',
    'DELIVERING': 'accepted_uzum',
    'ACCEPTED_AT_DP': 'accepted_uzum',
    'DELIVERED': 'accepted_uzum', // ← Доставлен в ПВЗ
    'DELIVERED_TO_CUSTOMER_DELIVERY_POINT': 'waiting_pickup',
    'COMPLETED': 'issued', // ← Выдан клиенту
    'CANCELED': 'cancelled',
    'PENDING_CANCELLATION': 'cancelled',
    'RETURNED': 'returns',
};
```

## Важные замечания

### 1. Разница между DELIVERED и DELIVERED_TO_CUSTOMER_DELIVERY_POINT

- **`DELIVERED`** (61) = Доставлен **в ПВЗ** (но еще не готов к выдаче) → **Приняты Узум**
- **`DELIVERED_TO_CUSTOMER_DELIVERY_POINT`** (71) = Готов **к выдаче клиенту** → **Ждут выдачи**

### 2. Только COMPLETED = Выдано

- **`COMPLETED`** (1433) = Выдан клиенту → **Выданы**
- `DELIVERED` больше НЕ мапится на `issued`

### 3. Активные статусы загружаются БЕЗ фильтрации по дате

Следующие статусы загружаются полностью, независимо от даты создания:
- `CREATED`
- `PACKING`
- `PENDING_DELIVERY`
- `DELIVERING`
- `ACCEPTED_AT_DP`
- `DELIVERED_TO_CUSTOMER_DELIVERY_POINT`

Это гарантирует, что все активные заказы отображаются в системе.

## Проверка корректности

### Команда для проверки:
```bash
php artisan tinker --execute="
\$account = \App\Models\MarketplaceAccount::where('marketplace', 'uzum')->first();

echo 'Проверка маппинга:' . PHP_EOL;
echo 'В сборке (in_assembly): ' . \App\Models\UzumOrder::where('marketplace_account_id', \$account->id)->where('status', 'in_assembly')->count() . PHP_EOL;
echo 'Приняты Узум (accepted_uzum): ' . \App\Models\UzumOrder::where('marketplace_account_id', \$account->id)->where('status', 'accepted_uzum')->count() . PHP_EOL;
echo 'Ждут выдачи (waiting_pickup): ' . \App\Models\UzumOrder::where('marketplace_account_id', \$account->id)->where('status', 'waiting_pickup')->count() . PHP_EOL;
echo 'Выданы (issued): ' . \App\Models\UzumOrder::where('marketplace_account_id', \$account->id)->where('status', 'issued')->count() . PHP_EOL;
echo 'Отменены (cancelled): ' . \App\Models\UzumOrder::where('marketplace_account_id', \$account->id)->where('status', 'cancelled')->count() . PHP_EOL;
echo 'Возвраты (returns): ' . \App\Models\UzumOrder::where('marketplace_account_id', \$account->id)->where('status', 'returns')->count() . PHP_EOL;
"
```

### Ожидаемый результат:
```
В сборке (in_assembly): 53
Приняты Узум (accepted_uzum): 61
Ждут выдачи (waiting_pickup): 71
Выданы (issued): 1433
Отменены (cancelled): 346
Возвраты (returns): 410
```

## Статус: ✅ ВСЕ СТАТУСЫ НАСТРОЕНЫ ПРАВИЛЬНО

Все маппинги соответствуют требованиям:
- ✅ Выданы = COMPLETED
- ✅ Отменены = CANCELED
- ✅ Возвраты = RETURNED
- ✅ Приняты Узум = DELIVERED (+ DELIVERING, ACCEPTED_AT_DP)
- ✅ Ждут выдачи = DELIVERED_TO_CUSTOMER_DELIVERY_POINT
