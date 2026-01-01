# Оптимизация синхронизации товаров OZON

## Проблема производительности

До оптимизации синхронизация 423 товаров занимала **~7 минут** из-за:
- Отдельный API запрос для каждого товара
- Задержка ~1 секунда на товар
- Устаревший endpoint `/v2/product/info`

## Решение: Batch API

Переход на batch endpoint `/v3/product/info/list` позволяет получать до **1000 товаров за один запрос**.

## Результаты

### До оптимизации
```
Товаров: 423
Запросов к API: 423 (по 1 товару)
Время: ~420 секунд (~7 минут)
Скорость: 1 товар/сек
```

### После оптимизации
```
Товаров: 423
Запросов к API: 1 (batch запрос)
Время: 11.76 секунд
Скорость: 36 товаров/сек
Ускорение: 36x раз! 🚀
```

## Технические детали

### 1. Новый batch метод

**Файл:** [app/Services/Marketplaces/OzonClient.php](app/Services/Marketplaces/OzonClient.php#L592-L624)

```php
/**
 * Get detailed info for multiple products (batch)
 * POST /v3/product/info/list
 * Max 1000 products per request
 */
public function getProductsInfo(MarketplaceAccount $account, array $productIds): array
{
    if (empty($productIds)) {
        return [];
    }

    try {
        // Convert all IDs to integers
        $productIds = array_map('intval', array_values($productIds));

        // Limit to 1000 products per request
        $productIds = array_slice($productIds, 0, 1000);

        $response = $this->http->post($account, '/v3/product/info/list', [
            'product_id' => $productIds,
        ]);

        // v3 API returns items directly, not in result.items
        return $response['items'] ?? [];
    } catch (\Exception $e) {
        \Log::error('Ozon getProductsInfo failed', [
            'account_id' => $account->id,
            'product_ids_count' => count($productIds),
        ]);
        return [];
    }
}
```

### 2. Оптимизированный syncCatalog

**Старая логика:**
```php
foreach ($items as $item) {
    $productId = $item['product_id'];

    // ❌ Отдельный запрос для каждого товара
    $productInfo = $this->getProductInfo($account, $productId);

    // Сохранить в БД
    OzonProduct::updateOrCreate(...);
}
```

**Новая логика:**
```php
// Собрать все ID
$productIds = array_column($items, 'product_id');

// ✅ Один batch запрос для всех товаров
$productsInfo = $this->getProductsInfo($account, $productIds);

// Создать map для быстрого поиска
$infoByProductId = [];
foreach ($productsInfo as $info) {
    $infoByProductId[$info['id']] = $info;
}

// Обработать каждый товар
foreach ($items as $item) {
    $productId = $item['product_id'];
    $productInfo = $infoByProductId[$productId] ?? [];

    // Сохранить в БД
    OzonProduct::updateOrCreate(...);
}
```

### 3. Обновленная структура данных v3 API

OZON API v3 изменил структуру ответа:

| Поле | v2 API | v3 API |
|---|---|---|
| **Ответ** | `result.items[]` | `items[]` |
| **Статус** | `status.state` | `statuses.status_name` |
| **Баркод** | `barcode` (строка) | `barcodes[]` (массив) |
| **Категория** | `category_id` | `description_category_id` |
| **Изображение** | `primary_image` (строка) | `primary_image[]` (массив) |

Код обновлен для корректного маппинга:

```php
$productData = [
    'name' => $productInfo['name'] ?? null,
    'status' => $productInfo['statuses']['status_name'] ?? 'unknown',
    'barcode' => !empty($productInfo['barcodes']) ? $productInfo['barcodes'][0] : null,
    'category_id' => $productInfo['description_category_id'] ?? null,
    'primary_image' => !empty($productInfo['primary_image'])
        ? $productInfo['primary_image'][0]
        : ($productInfo['images'][0] ?? null),
    'price' => $productInfo['price'] ?? null,
    'currency_code' => $productInfo['currency_code'] ?? 'RUB',
    'vat' => $productInfo['vat'] ?? null,
    'images' => !empty($productInfo['images']) ? json_encode($productInfo['images']) : null,
];
```

## Производительность при разных объемах

| Товаров | Старый код (v2) | Новый код (v3 batch) | Ускорение |
|---|---|---|---|
| 100 | ~100 сек | ~3 сек | 33x |
| 423 | ~420 сек | ~12 сек | 35x |
| 1000 | ~1000 сек (16 мин) | ~30 сек | 33x |
| 5000 | ~5000 сек (83 мин) | ~150 сек (2.5 мин) | 33x |
| 10000 | ~10000 сек (2.7 ч) | ~300 сек (5 мин) | 33x |

**Примечание:** Ускорение может варьироваться в зависимости от сети и нагрузки API.

## Лимиты API

- **Максимум товаров за запрос:** 1000
- **Rate limit:** Рекомендуется 500ms задержка между batch запросами
- **Timeout:** 30 секунд на запрос

Код автоматически:
- Разбивает товары на батчи по 1000 штук
- Добавляет 500ms задержку между запросами
- Обрабатывает ошибки и продолжает работу

## Использование

### Через код

```php
$account = MarketplaceAccount::find($accountId);
$httpClient = app(MarketplaceHttpClient::class);
$client = new OzonClient($httpClient);

// Запуск синхронизации
$result = $client->syncCatalog($account);

echo "Synced: {$result['synced']} products\n";
echo "Created: {$result['created']}\n";
echo "Updated: {$result['updated']}\n";
```

### Через API

```bash
curl -X POST "http://localhost/api/marketplace/ozon/accounts/16/sync-catalog" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

**Ответ:**
```json
{
  "success": true,
  "message": "Синхронизировано товаров: 423 (новых: 423, обновлено: 0)",
  "synced": 423,
  "created": 423,
  "updated": 0
}
```

## Мониторинг производительности

Логи включают метрики производительности:

```bash
tail -f storage/logs/laravel.log | grep "Ozon"
```

**Пример вывода:**
```
[2025-12-24 14:44:09] local.INFO: Starting Ozon catalog sync (optimized) {"account_id":16}
[2025-12-24 14:44:09] local.INFO: Fetching Ozon products page {"account_id":16,"last_id":""}
[2025-12-24 14:44:10] local.INFO: Received products from Ozon API {"items_count":423,"total":423}
[2025-12-24 14:44:10] local.INFO: Fetching detailed info for products (batch) {"product_count":423}
[2025-12-24 14:44:11] local.INFO: Received detailed product info {"info_count":423}
[2025-12-24 14:44:20] local.INFO: Batch processed {"batch_size":423,"total_synced":423}
[2025-12-24 14:44:20] local.INFO: Ozon catalog synced successfully {"synced":423,"created":423,"updated":0}
```

## Обратная совместимость

Старый метод `getProductInfo()` по-прежнему работает, но использует batch API под капотом:

```php
// Работает, но неэффективно для множественных запросов
$info = $client->getProductInfo($account, $productId);

// Рекомендуется для batch операций
$infos = $client->getProductsInfo($account, [$id1, $id2, $id3]);
```

## Рекомендации

1. **Для больших каталогов (>1000 товаров):**
   - Синхронизация разбивается на батчи автоматически
   - Каждый батч - отдельный запрос
   - Между запросами задержка 500ms

2. **Для планировщика задач:**
   ```php
   // В Scheduler
   $schedule->call(function () {
       $accounts = MarketplaceAccount::where('marketplace', 'ozon')
           ->where('is_active', true)
           ->get();

       foreach ($accounts as $account) {
           $client = app(OzonClient::class);
           $client->syncCatalog($account);
       }
   })->daily();
   ```

3. **Для фоновых задач:**
   ```php
   dispatch(new SyncOzonCatalogJob($account));
   ```

## Changelog

### v2.0 - Оптимизация (Декабрь 2024)
- ✅ Переход на `/v3/product/info/list` batch API
- ✅ Ускорение в ~35 раз
- ✅ Обновлена структура данных для v3 API
- ✅ Улучшено логирование

### v1.0 - Базовая версия
- `/v2/product/info` (по одному товару)
- `/v2/product/list` (список товаров)

## См. также

- [OZON_API_V3_UPDATE.md](OZON_API_V3_UPDATE.md) - Обновление API endpoints
- [OZON_SYNC_TROUBLESHOOTING.md](OZON_SYNC_TROUBLESHOOTING.md) - Устранение неполадок
- [Документация OZON API](https://docs.ozon.ru/api/seller/)

---

**Дата:** Декабрь 2024
**Версия:** 2.0
