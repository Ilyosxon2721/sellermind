# API Документация: Заказы Wildberries

## Endpoint для списка заказов

**URL:** `GET /api/marketplace/orders`

**Query параметры:**
- `marketplace_account_id` (required) - ID аккаунта маркетплейса
- `company_id` (required) - ID компании
- `status` (optional) - Фильтр по статусу
- `from` (optional) - Дата начала периода
- `to` (optional) - Дата окончания периода

**Headers:**
```
Authorization: Bearer {token}
```

## Структура ответа для списка заказов

```json
{
  "orders": [
    {
      "id": 2838,
      "marketplace_account_id": 1,
      "external_order_id": "4329453745",

      // ИНФОРМАЦИЯ О ТОВАРЕ (для отображения в списке)
      "photo_url": "https://basket-01.wbbasket.ru/vol5373/part537399/537399509/images/big/1.jpg",
      "article": "FH26701-mini-white",
      "product_name": "FH26701-mini-white",
      "meta_info": "FH26701-mini-white",
      "brand": null,
      "characteristics": null,

      // ИДЕНТИФИКАТОРЫ
      "nm_id": "537399509",
      "sku": "1000035905775",

      // СТАТУСЫ
      "status": "in_delivery",
      "status_normalized": "in_delivery",
      "wb_status_group": "shipping",

      // ЛОГИСТИКА
      "supply_id": "WB-GI-203035917",

      // ФИНАНСЫ
      "total_amount": "3720.00",
      "currency": "RUB",

      // ВРЕМЯ
      "ordered_at": "2025-12-13T09:47:18.000000Z",
      "time_elapsed": "1 дн. 8 ч. 28 мин.",

      // ДОПОЛНИТЕЛЬНЫЕ ДАННЫЕ (детали для pop-up)
      "details": {
        "rid": "DAU.b2bf24c61c0f4bc98861fcbd81d36769.0.0",
        "order_uid": "b2bf24c61c0f4bc98861fcbd81d36769",
        "chrt_id": "477151261",
        "wb_status": "в пути с ПВЗ Продавца",
        "wb_supplier_status": "Принят",
        "wb_delivery_type": "fbs",
        "cargo_type": 1,
        "warehouse_id": "940164",
        "office": "Ташкент",
        "customer_name": null,
        "customer_phone": null,
        "price": 10333,
        "scan_price": null,
        "converted_price": 42223132,
        "currency_code": "933",
        "converted_currency_code": "860",
        "is_b2b": false,
        "is_zero_order": false,
        "delivered_at": null,
        "raw_payload": { /* полный ответ от WB API */ }
      }
    }
  ],
  "meta": {
    "total": 2127
  }
}
```

## Endpoint для деталей заказа

**URL:** `GET /api/marketplace/orders/{orderId}`

**Headers:**
```
Authorization: Bearer {token}
```

## Структура ответа для деталей заказа

```json
{
  "order": {
    // ОСНОВНАЯ ИНФОРМАЦИЯ
    "id": 2838,
    "Номер заказа": "4329453745",
    "Фото товара": "https://basket-01.wbbasket.ru/vol5373/part537399/537399509/images/big/1.jpg",
    "Артикул": "FH26701-mini-white",
    "Название товара": "FH26701-mini-white",
    "Метаинформация": "FH26701-mini-white",
    "Бренд": null,
    "Характеристики": null,

    // ФИНАНСЫ
    "Сумма заказа": "3 720.00 RUB",
    "Цена": "103.33 руб",
    "Цена сканирования": null,
    "Конвертированная цена": "422 231.32 руб",
    "Валюта": "RUB",
    "Код валюты": "933",
    "Код конвертированной валюты": "860",

    // ЛОГИСТИКА
    "Поставка": "WB-GI-203035917",
    "Склад": "940164",
    "Офис доставки": "Ташкент",
    "Тип доставки": "FBS (со склада продавца)",
    "Тип груза": 1,

    // СТАТУСЫ
    "Статус": "В доставке",
    "Группа статусов": "В доставке",
    "Статус WB": "в пути с ПВЗ Продавца",
    "Статус поставщика": "Принят",

    // ТЕХНИЧЕСКИЕ ДАННЫЕ
    "RID": "DAU.b2bf24c61c0f4bc98861fcbd81d36769.0.0",
    "Order UID": "b2bf24c61c0f4bc98861fcbd81d36769",
    "NM ID": "537399509",
    "CHRT ID": "477151261",
    "SKU": "1000035905775",
    "B2B заказ": "Нет",
    "Нулевой заказ": "Нет",

    // ВРЕМЕННЫЕ МЕТКИ
    "Дата заказа": "13.12.2025 09:47:18",
    "Время с момента заказа": "1 дн. 8 ч. 28 мин.",
    "Дата доставки": null,

    // КЛИЕНТ
    "Имя клиента": null,
    "Телефон клиента": null,

    // ТОВАРЫ
    "Товары": [
      {
        "Название": "FH26701-mini-white",
        "Артикул/SKU": "537399509",
        "Количество": 1,
        "Цена": "103.33 руб",
        "Общая стоимость": "103.33 руб"
      }
    ],

    // Сырые данные (для отладки)
    "_raw": {
      "id": 2838,
      "external_order_id": "4329453745",
      "status": "in_delivery",
      "wb_status_group": "shipping",
      "total_amount": "3720.00",
      "currency": "RUB"
    }
  }
}
```

## Описание полей для списка заказов

### Основные поля для отображения в UI

| Поле | Тип | Описание | Использование |
|------|-----|----------|---------------|
| `photo_url` | string | URL фото товара из WB CDN | Показать фото товара |
| `article` | string | Артикул товара | Отобразить артикул |
| `product_name` | string | Название товара | Отобразить название (сейчас = артикул) |
| `meta_info` | string | Метаинформация (Бренд - Артикул - Характеристики) | Отобразить под названием |
| `time_elapsed` | string | Время с момента заказа | Формат: "X дн. Y ч. Z мин." |
| `total_amount` | decimal | Сумма заказа | Отобразить сумму |
| `currency` | string | Валюта | Обычно "RUB" |

### Статусы заказов (wb_status_group)

| Значение | Отображение | Описание |
|----------|-------------|----------|
| `new` | Новые | Заказы без поставки (обычно 0) |
| `assembling` | На сборке | Заказы в поставке со статусом "draft" |
| `shipping` | В доставке | Заказы в поставке со статусом "sent" |
| `archive` | Архив | Доставленные заказы или без поставки |
| `canceled` | Отменённые | Отмененные заказы |

## Примечания

### Фото товара
- Генерируется автоматически из `nm_id` по алгоритму WB CDN
- Формат: `https://basket-{basket}.wbbasket.ru/vol{vol}/part{part}/{nm_id}/images/big/1.jpg`
- Если `nm_id` отсутствует, `photo_url` будет `null`

### Название товара и характеристики
- WB API для заказов FBS **не возвращает** полное название товара, бренд и характеристики
- Поэтому `product_name` = `article` (артикул товара)
- `meta_info` содержит только артикул (без пустых полей)
- Для получения полной информации о товаре нужно использовать WB Content API

### Время с момента заказа
- Автоматически вычисляется от `ordered_at` до текущего момента
- Формат: "X дн. Y ч. Z мин."
- Обновляется при каждом запросе

### Цены в деталях
- `price` и `scan_price` приходят в копейках из WB API
- В ответе API они конвертируются в рубли: `price / 100`
- Формат с разделителями тысяч: "103.33 руб"

## Пример использования на фронтенде

```javascript
// Получить список заказов
const response = await fetch('/api/marketplace/orders?marketplace_account_id=1&company_id=1', {
  headers: {
    'Authorization': `Bearer ${token}`,
    'Accept': 'application/json'
  }
});

const data = await response.json();

// Отобразить в списке
data.orders.forEach(order => {
  console.log({
    фото: order.photo_url,
    артикул: order.article,
    название: order.product_name,
    мета: order.meta_info,
    время: order.time_elapsed,
    сумма: `${order.total_amount} ${order.currency}`
  });
});

// Получить детали заказа
const detailsResponse = await fetch(`/api/marketplace/orders/${orderId}`, {
  headers: {
    'Authorization': `Bearer ${token}`,
    'Accept': 'application/json'
  }
});

const orderDetails = await detailsResponse.json();
// Все поля уже на русском языке и готовы к отображению
```

## Обновления

**2025-12-15:**
- Добавлены поля `photo_url`, `product_name`, `meta_info`, `time_elapsed`
- Детали заказа теперь возвращаются на русском языке
- Убрано отображение пустых значений в `meta_info`
- Очищен кэш приложения
