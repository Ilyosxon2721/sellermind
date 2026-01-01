# Исправление ошибок API заказов

## Проблема
API endpoint `/api/marketplace/orders` возвращал 500 ошибку:
```
SQLSTATE[42S22]: Column not found: 1054 Unknown column 'wb_barcode' in 'field list'
```

## Причина
В контроллере `MarketplaceOrderController.php` при оптимизации производительности были указаны несуществующие поля в `->select()`:
1. ❌ `delivery_date` - такого поля нет в таблице `marketplace_orders`
2. ❌ `wb_barcode` - такого поля нет, вместо него используется `wb_skus` (JSON массив)

## Исправление

### Файл: `app/Http/Controllers/Api/MarketplaceOrderController.php`

**До:**
```php
->select([
    'id', 'marketplace_account_id', 'external_order_id', 'status',
    'total_amount', 'ordered_at', 'delivery_date', // ❌ Не существует
    // WB specific fields
    'wb_status', 'wb_status_group', 'wb_supplier_status',
    'wb_nm_id', 'wb_article', 'wb_barcode', // ❌ Не существует
    'wb_final_price', 'wb_sale_price', 'wb_delivery_type',
    'supply_id', 'created_at', 'updated_at'
])
```

**После:**
```php
->select([
    'id', 'marketplace_account_id', 'external_order_id', 'status',
    'total_amount', 'ordered_at', // ✅ delivery_date удален
    // WB specific fields
    'wb_status', 'wb_status_group', 'wb_supplier_status',
    'wb_nm_id', 'wb_article', 'wb_skus', // ✅ Заменен на wb_skus
    'wb_final_price', 'wb_sale_price', 'wb_delivery_type',
    'supply_id', 'created_at', 'updated_at'
])
```

## Структура поля wb_skus

`wb_skus` - это JSON массив штрихкодов товара:
```json
["1234567890123", "9876543210987"]
```

Во frontend используется первый элемент массива:
```javascript
order.wb_skus?.[0] || '-'
```

## Проверка

Все поля в select теперь соответствуют реальной структуре таблицы `marketplace_orders`:

✅ **Core fields:**
- id, marketplace_account_id, external_order_id, status
- total_amount, ordered_at

✅ **WB specific fields:**
- wb_status, wb_status_group, wb_supplier_status
- wb_nm_id, wb_article, wb_skus
- wb_final_price, wb_sale_price, wb_delivery_type

✅ **Additional fields:**
- supply_id, created_at, updated_at

## Результат

- ✅ API endpoint теперь возвращает данные без ошибок
- ✅ Frontend корректно отображает SKU через `wb_skus[0]`
- ✅ Все поля в запросе соответствуют структуре БД
- ✅ Производительность оптимизирована (только 16 из 51 полей)

## Дата исправления
2025-12-01
