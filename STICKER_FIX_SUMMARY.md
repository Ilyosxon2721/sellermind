# Исправление печати стикеров Wildberries

## Дата: 2025-12-15

## Проблема

Печать стикеров заказов не работала из-за двух основных проблем:

1. **Неправильная передача order_ids**: Фронтенд передавал `external_order_id` как строку, но WB API ожидает массив целых чисел
2. **Неправильная обработка ответа WB API**: API Wildberries возвращает JSON с base64-закодированным изображением, а не бинарные данные напрямую

## Внесённые изменения

### 1. MarketplaceOrderController.php

**Файл**: `app/Http/Controllers/Api/MarketplaceOrderController.php`

**Изменение**: Добавлена конвертация order_ids в целые числа

```php
// Конвертируем order_ids в массив целых чисел
$orderIds = array_map('intval', $request->order_ids);

$binaryContent = $orderService->getOrdersStickers(
    $account,
    $orderIds,  // Теперь передаём конвертированные ID
    $type,
    $width,
    $height
);
```

### 2. WildberriesOrderService.php

**Файл**: `app/Services/Marketplaces/Wildberries/WildberriesOrderService.php`

**Изменение**: Добавлена обработка JSON ответа от WB API

```php
// Use postBinary for sticker generation
$response = $this->httpClient->postBinary(
    'marketplace',
    '/api/v3/orders/stickers',
    ['orders' => array_map('intval', $orderIds)],
    [
        'type' => $type,
        'width' => $width,
        'height' => $height,
    ]
);

// WB API может вернуть либо бинарные данные, либо JSON с base64
$fileContent = $response;

// Проверяем, является ли ответ JSON
if (substr($response, 0, 1) === '{' || substr($response, 0, 1) === '[') {
    $json = json_decode($response, true);

    if (json_last_error() === JSON_ERROR_NONE && isset($json['stickers'])) {
        // Декодируем base64 из первого стикера
        $fileContent = base64_decode($json['stickers'][0]['file']);

        Log::info('WB API returned JSON response, decoded base64', [
            'account_id' => $account->id,
            'stickers_count' => count($json['stickers']),
        ]);
    }
}
```

### 3. Настройка окружения

- Создана директория для стикеров: `storage/app/public/stickers/orders/`
- Создан symlink: `php artisan storage:link`
- Установлены права доступа: `chmod -R 775 storage/app/public/stickers`

## Формат ответа WB API

WB API v3 возвращает JSON следующего формата:

```json
{
  "stickers": [
    {
      "partA": "4604890",
      "partB": "1040",
      "barcode": "*Cri6d7CY",
      "file": "iVBORw0KGgoAAAANSUhEUgAA... (base64)",
      "orderId": 4329453745
    }
  ]
}
```

Где `file` - это base64-закодированное PNG изображение.

## Тестирование

Создан тестовый скрипт `test-sticker.php` для проверки:

```bash
php test-sticker.php
```

Результаты тестирования:
- ✅ Стикер получен от WB API: 17102 байт (JSON)
- ✅ Декодирован base64: 12748 байт
- ✅ Файл сохранён как PNG изображение: 580x400 px
- ✅ URL доступен: `http://localhost:8888/storage/stickers/orders/2/stickers_test_*.png`

## Проверка файла

```bash
$ file storage/app/public/stickers/orders/2/stickers_test_*.png
PNG image data, 580 x 400, 8-bit/color RGB, non-interlaced
```

## Логирование

Добавлено улучшенное логирование для отладки:

```php
Log::info('Order stickers generated', [
    'account_id' => $account->id,
    'user_id' => $request->user()->id,
    'orders_count' => count($orderIds),
    'order_ids' => $orderIds,
    'type' => $type,
    'file_size' => strlen($binaryContent),
]);
```

При обработке JSON:

```php
Log::info('WB API returned JSON response, decoded base64', [
    'account_id' => $account->id,
    'stickers_count' => count($json['stickers']),
]);
```

## Frontend (без изменений)

Frontend остался без изменений и продолжает работать как ожидалось:

```javascript
async printOrderSticker(order) {
    const payload = {
        marketplace_account_id: this.accountId,
        order_ids: [order.external_order_id],  // Строка конвертируется в backend
        type: 'png',
        width: 58,
        height: 40
    };

    const response = await axios.post('/api/marketplace/orders/stickers', payload, {
        headers: this.getAuthHeaders()
    });

    // Печать стикера...
}
```

## Статус

✅ **Все задачи выполнены**:
1. ✅ Исправлена конвертация order_ids в integer
2. ✅ Добавлена обработка JSON ответа от WB API
3. ✅ Создана директория и настроены права доступа
4. ✅ Протестирована генерация стикеров
5. ✅ Стикеры корректно сохраняются как PNG файлы

## Следующие шаги

Рекомендации для дальнейшего улучшения:

1. **Обработка нескольких стикеров**: Если WB API вернёт массив с несколькими стикерами, нужно решить, как их объединить или обработать отдельно

2. **Оптимизация хранения**: Рассмотреть возможность очистки старых стикеров (уже есть метод `cleanupOldStickers` в `WildberriesStickerService`)

3. **Обработка ошибок**: Улучшить обработку ошибок при декодировании base64 (добавить try-catch)

4. **Миграция на новый контроллер**: Рассмотреть использование `WildberriesStickerController` вместо старого `MarketplaceOrderController`

## Документация

Создано подробное руководство: [STICKER_PRINTING_GUIDE.md](STICKER_PRINTING_GUIDE.md)
