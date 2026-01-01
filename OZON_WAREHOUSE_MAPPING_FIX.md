# Исправление: Настройки маппинга складов OZON не сохранялись

## Проблема

При попытке связать склад OZON с локальным складом в разделе "Настройки синхронизации остатков", данные не сохранялись.

## Причина

В проекте используются **два разных поля** для хранения данных в таблице `marketplace_accounts`:

1. **`credentials` (TEXT)** - Зашифрованное поле, используется методом `getDecryptedCredentials()`
2. **`credentials_json` (JSON)** - JSON поле с автоматическим приведением типов

Методы в `OzonSettingsController` использовали несогласованный подход:
- `getWarehouseMapping()` читал из `credentials` (зашифрованное)
- `saveWarehouseMapping()` читал из `credentials`, но пытался сохранить в `credentials_json`
- `show()` читал из `credentials`

**Результат:** Данные сохранялись в `credentials_json`, но при следующем запросе читались из `credentials`, где их не было.

## Решение

Унифицировали хранение настроек складов:

### 1. Разделение ответственности

- **Аутентификационные данные** (`client_id`, `api_key`) → хранятся в отдельных столбцах БД
- **Настройки синхронизации** (`stock_sync_mode`, `warehouse_id`, `source_warehouse_ids`) → хранятся в `credentials_json`

### 2. Исправленные методы

#### `show()` - Получение настроек аккаунта
```php
// БЫЛО: читали из getDecryptedCredentials()
$credentials = $account->getDecryptedCredentials();

// СТАЛО: читаем credentials из столбцов БД, настройки из credentials_json
$settings = $account->credentials_json ?? [];

return [
    'credentials' => [
        'client_id' => !empty($account->client_id),
        'api_key' => !empty($account->api_key),
    ],
    'settings' => [
        'stock_sync_mode' => $settings['stock_sync_mode'] ?? 'basic',
        'warehouse_id' => $settings['warehouse_id'] ?? null,
        'source_warehouse_ids' => $settings['source_warehouse_ids'] ?? [],
    ],
];
```

#### `update()` - Обновление настроек
```php
// БЫЛО: всё сохранялось в credentials через getDecryptedCredentials()
$credentials = $account->getDecryptedCredentials();
$credentials['stock_sync_mode'] = $validated['stock_sync_mode'];
$account->credentials_json = $credentials;

// СТАЛО: credentials в столбцы БД, настройки в credentials_json
$account->client_id = $validated['client_id'];
$account->api_key = $validated['api_key'];

$settings = $account->credentials_json ?? [];
$settings['stock_sync_mode'] = $validated['stock_sync_mode'];
$account->credentials_json = $settings;
```

#### `getWarehouseMapping()` - Получение маппинга складов
```php
// БЫЛО:
$credentials = $account->getDecryptedCredentials();

// СТАЛО:
$settings = $account->credentials_json ?? [];
```

#### `saveWarehouseMapping()` - Сохранение маппинга складов
```php
// БЫЛО:
$credentials = $account->getDecryptedCredentials();
$credentials['stock_sync_mode'] = $validated['stock_sync_mode'];
$account->credentials_json = $credentials;

// СТАЛО:
$settings = $account->credentials_json ?? [];
$settings['stock_sync_mode'] = $validated['stock_sync_mode'];
$account->credentials_json = $settings;
$account->save();

// Добавлена верификация сохранения
$account->refresh();
$verifySettings = $account->credentials_json;
```

### 3. Добавлено логирование

Для отладки добавлены логи на каждом этапе:
- Получение текущих настроек
- Валидация данных
- Сохранение
- Верификация после сохранения

Логи можно просмотреть командой:
```bash
tail -f storage/logs/laravel.log | grep "Ozon"
```

## Структура данных

### `marketplace_accounts` таблица

```
┌─────────────────────┬──────────────────────────────────────────┐
│ Поле                │ Назначение                               │
├─────────────────────┼──────────────────────────────────────────┤
│ client_id           │ OZON Client ID (отдельный столбец)       │
│ api_key             │ OZON API Key (зашифрован, отд. столбец)  │
│ credentials         │ Legacy: зашифрованный JSON (не исп.)     │
│ credentials_json    │ Настройки синхронизации (JSON)           │
└─────────────────────┴──────────────────────────────────────────┘
```

### `credentials_json` формат для OZON

```json
{
  "stock_sync_mode": "basic",
  "warehouse_id": "22548172863000",
  "source_warehouse_ids": [1, 2, 3]
}
```

## Проверка работы

### 1. Сохранение настроек

```bash
curl -X POST "http://localhost/api/ozon/accounts/1/settings/warehouse-mapping" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "stock_sync_mode": "basic",
    "warehouse_id": "22548172863000",
    "source_warehouse_ids": [1, 2]
  }'
```

**Ожидаемый результат:**
```json
{
  "success": true,
  "message": "Настройки маппинга складов сохранены",
  "saved_settings": {
    "stock_sync_mode": "basic",
    "warehouse_id": "22548172863000",
    "source_warehouse_ids": [1, 2]
  }
}
```

### 2. Получение настроек

```bash
curl -X GET "http://localhost/api/ozon/accounts/1/settings/warehouse-mapping" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Ожидаемый результат:**
```json
{
  "stock_sync_mode": "basic",
  "warehouse_id": "22548172863000",
  "source_warehouse_ids": [1, 2]
}
```

### 3. Проверка в базе данных

```sql
SELECT id, marketplace, credentials_json
FROM marketplace_accounts
WHERE marketplace = 'ozon';
```

Должно показать:
```
credentials_json: {"stock_sync_mode":"basic","warehouse_id":"22548172863000","source_warehouse_ids":[1,2]}
```

## Измененные файлы

- [app/Http/Controllers/Api/OzonSettingsController.php](app/Http/Controllers/Api/OzonSettingsController.php)
  - `show()` - строки 19-51
  - `update()` - строки 56-132
  - `getWarehouseMapping()` - строки 214-237
  - `saveWarehouseMapping()` - строки 242-313

## Дата исправления

Декабрь 2024
