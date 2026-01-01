# Тестирование исправления маппинга складов OZON

## Быстрый тест через Tinker

### 1. Найти OZON аккаунт

```bash
php artisan tinker
```

```php
$account = \App\Models\MarketplaceAccount::where('marketplace', 'ozon')->first();

if (!$account) {
    echo "OZON аккаунт не найден. Создайте аккаунт сначала.\n";
    exit;
}

echo "Найден OZON аккаунт ID: {$account->id}\n";
```

### 2. Проверить текущие настройки

```php
$currentSettings = $account->credentials_json ?? [];
print_r($currentSettings);
```

**Ожидается:** массив с настройками или пустой массив `[]`

### 3. Сохранить тестовые настройки

```php
$testSettings = [
    'stock_sync_mode' => 'basic',
    'warehouse_id' => '12345678',
    'source_warehouse_ids' => [1, 2, 3],
];

$account->credentials_json = $testSettings;
$saved = $account->save();

echo $saved ? "✅ Сохранено успешно\n" : "❌ Ошибка сохранения\n";
```

### 4. Проверить что данные сохранились

```php
// Перезагрузить из БД
$account->refresh();

$verifySettings = $account->credentials_json;
print_r($verifySettings);

// Проверить совпадение
$matches = (
    ($verifySettings['stock_sync_mode'] ?? null) === 'basic' &&
    ($verifySettings['warehouse_id'] ?? null) === '12345678' &&
    ($verifySettings['source_warehouse_ids'] ?? []) === [1, 2, 3]
);

echo $matches ? "✅ Данные совпадают\n" : "❌ Данные НЕ совпадают\n";
```

### 5. Полный тест одним блоком

```php
// Копируйте всё это в tinker

$account = \App\Models\MarketplaceAccount::where('marketplace', 'ozon')->first();

if (!$account) {
    echo "❌ OZON аккаунт не найден\n";
    exit;
}

echo "📦 Тестирование OZON аккаунта ID: {$account->id}\n\n";

// Исходные настройки
echo "1️⃣ Текущие настройки:\n";
print_r($account->credentials_json ?? []);
echo "\n";

// Сохранение тестовых данных
$testSettings = [
    'stock_sync_mode' => 'basic',
    'warehouse_id' => 'TEST_' . time(),
    'source_warehouse_ids' => [99, 100],
];

echo "2️⃣ Сохраняем тестовые настройки:\n";
print_r($testSettings);
echo "\n";

$account->credentials_json = $testSettings;
$saved = $account->save();
echo $saved ? "✅ save() вернул true\n\n" : "❌ save() вернул false\n\n";

// Проверка
$account->refresh();
echo "3️⃣ Проверка после refresh():\n";
$verifiedSettings = $account->credentials_json;
print_r($verifiedSettings);
echo "\n";

// Валидация
$isValid = (
    isset($verifiedSettings['stock_sync_mode']) &&
    $verifiedSettings['stock_sync_mode'] === $testSettings['stock_sync_mode'] &&
    isset($verifiedSettings['warehouse_id']) &&
    $verifiedSettings['warehouse_id'] === $testSettings['warehouse_id'] &&
    isset($verifiedSettings['source_warehouse_ids']) &&
    $verifiedSettings['source_warehouse_ids'] === $testSettings['source_warehouse_ids']
);

echo $isValid ? "✅ ТЕСТ ПРОЙДЕН: Данные сохранены корректно\n" : "❌ ТЕСТ ПРОВАЛЁН: Данные не совпадают\n";
```

## Тест через API (curl)

### Предварительные требования

1. Получите Bearer токен для авторизации
2. Найдите ID вашего OZON аккаунта

```bash
# Получить список аккаунтов
curl -X GET "http://localhost/api/marketplaces/accounts" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

### 1. Получить текущие настройки маппинга

```bash
curl -X GET "http://localhost/api/ozon/accounts/1/settings/warehouse-mapping" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

**Ожидаемый ответ:**
```json
{
  "stock_sync_mode": "basic",
  "warehouse_id": null,
  "source_warehouse_ids": []
}
```

### 2. Сохранить настройки маппинга

```bash
curl -X PUT "http://localhost/api/ozon/accounts/1/settings/warehouse-mapping" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "stock_sync_mode": "basic",
    "warehouse_id": "22548172863000",
    "source_warehouse_ids": [1, 2]
  }'
```

**Ожидаемый ответ:**
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

### 3. Проверить что настройки сохранились

Повторите запрос из пункта 1. Должны вернуться сохраненные значения.

```bash
curl -X GET "http://localhost/api/ozon/accounts/1/settings/warehouse-mapping" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

**Ожидается:**
```json
{
  "stock_sync_mode": "basic",
  "warehouse_id": "22548172863000",
  "source_warehouse_ids": [1, 2]
}
```

## Проверка логов

После теста через API проверьте логи:

```bash
tail -50 storage/logs/laravel.log | grep "Ozon"
```

**Должно быть примерно так:**
```
[2024-12-XX XX:XX:XX] local.INFO: Ozon: Saving warehouse mapping - Start {"account_id":1,"user_id":1,"request_data":{...}}
[2024-12-XX XX:XX:XX] local.INFO: Ozon: Validated warehouse mapping data {"validated":{...}}
[2024-12-XX XX:XX:XX] local.INFO: Ozon: Current settings before update {"current_settings":{...}}
[2024-12-XX XX:XX:XX] local.INFO: Ozon: New settings to be saved {"new_settings":{...}}
[2024-12-XX XX:XX:XX] local.INFO: Ozon: Save operation completed {"save_result":true,"account_id":1}
[2024-12-XX XX:XX:XX] local.INFO: Ozon: Verification after save {"verified_settings":{...},"stock_sync_mode_matches":true,"warehouse_id_matches":true}
```

## Проверка в базе данных

```sql
-- Посмотреть все OZON аккаунты с настройками
SELECT
    id,
    name,
    marketplace,
    credentials_json
FROM marketplace_accounts
WHERE marketplace = 'ozon';
```

Поле `credentials_json` должно содержать JSON с настройками:
```json
{
  "stock_sync_mode": "basic",
  "warehouse_id": "22548172863000",
  "source_warehouse_ids": [1, 2]
}
```

## Возможные проблемы

### Проблема: "Только владелец может изменять настройки"

**Причина:** Пользователь не является владельцем компании

**Решение:**
- Войдите как владелец компании
- Или временно измените проверку с `isOwnerOf` на `hasCompanyAccess`

### Проблема: Данные не сохраняются

**Диагностика:**

1. Проверьте права на запись в БД:
```bash
php artisan tinker --execute="
\$account = \App\Models\MarketplaceAccount::first();
\$account->credentials_json = ['test' => 'value'];
echo \$account->save() ? 'OK' : 'FAILED';
"
```

2. Проверьте что поле `credentials_json` существует:
```bash
php artisan tinker --execute="
echo \Illuminate\Support\Facades\Schema::hasColumn('marketplace_accounts', 'credentials_json') ? 'EXISTS' : 'MISSING';
"
```

3. Проверьте логи на наличие SQL ошибок:
```bash
tail -100 storage/logs/laravel.log | grep -i "sql\|error"
```

### Проблема: Тест через tinker работает, API не работает

**Причина:** Проблема с роутами или middleware

**Решение:**

1. Очистите кэш роутов:
```bash
php artisan route:clear
php artisan cache:clear
```

2. Проверьте что роут существует:
```bash
php artisan route:list | grep warehouse-mapping
```

Должно быть:
```
PUT    api/ozon/accounts/{account}/settings/warehouse-mapping
GET    api/ozon/accounts/{account}/settings/warehouse-mapping
```

3. Проверьте middleware и авторизацию

## Дата создания

Декабрь 2024
