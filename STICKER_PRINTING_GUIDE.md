# Руководство по печати стикеров Wildberries

## Оглавление
1. [Обзор системы](#обзор-системы)
2. [Типы стикеров](#типы-стикеров)
3. [API эндпоинты](#api-эндпоинты)
4. [Backend архитектура](#backend-архитектура)
5. [Frontend реализация](#frontend-реализация)
6. [WB API интеграция](#wb-api-интеграция)
7. [Примеры использования](#примеры-использования)

---

## Обзор системы

Система печати стикеров Wildberries поддерживает три типа стикеров:

1. **Стикеры заказов** - наклейки на товары для отправки покупателям
2. **Баркоды поставок** - QR-коды для поставок (supply barcode)
3. **Баркоды коробов** - наклейки на короба/тары (tare/box barcodes)

### Технологический стек

- **Backend**: Laravel 11, PHP 8.2+
- **Frontend**: Alpine.js, Blade templates
- **API**: Wildberries Marketplace API v3
- **Хранилище**: Local storage (`storage/app/marketplace/stickers/`)

---

## Типы стикеров

### 1. Стикеры заказов (Order Stickers)

**Назначение**: Наклейки на товары для FBS заказов

**Форматы**:
- `png` - PNG изображение (по умолчанию)
- `svg` - SVG векторная графика
- `code128` - Штрих-код формата Code128
- `zplv` - ZPL вертикальный (для термопринтеров)
- `zplh` - ZPL горизонтальный (для термопринтеров)

**Размеры**:
- Ширина: 20-200 мм (по умолчанию: 58 мм)
- Высота: 20-200 мм (по умолчанию: 40 мм)

**Ограничения**:
- Максимум 100 заказов за один запрос
- Для большего количества используется батчинг

**Cross-border стикеры**:
- Специальные стикеры для международных отправлений
- Формат: только PDF
- API endpoint: `/api/v3/orders/stickers/cross-border`

### 2. Баркоды поставок (Supply Barcodes)

**Назначение**: QR-коды для идентификации поставок на складе WB

**Форматы**:
- `png` - PNG изображение (по умолчанию)
- `svg` - SVG векторная графика
- `pdf` - PDF документ

**Особенности**:
- Генерируются автоматически при закрытии поставки
- Сохраняются в БД в поле `supplies.barcode_path`
- Доступны для скачивания через UI

### 3. Баркоды коробов (Tare/Box Barcodes)

**Назначение**: Штрих-коды на коробки для логистики WB

**Форматы**:
- `png` - PNG изображение (по умолчанию)
- `svg` - SVG векторная графика
- `pdf` - PDF документ

**Особенности**:
- Каждый короб имеет уникальный barcode
- Привязаны к конкретной поставке
- Количество коробов указывается при создании поставки

---

## API эндпоинты

### Заказы

#### Генерация стикеров заказов
```
POST /api/marketplace/orders/stickers
```

**Request Body**:
```json
{
  "marketplace_account_id": 1,
  "order_ids": ["4329453745", "4329453746"],
  "type": "png",
  "width": 58,
  "height": 40
}
```

**Response**:
```json
{
  "message": "Стикеры успешно сгенерированы",
  "stickers": [
    {
      "path": "stickers/orders/1/stickers_abc123_png.png",
      "url": "http://localhost:8000/storage/stickers/orders/1/stickers_abc123_png.png",
      "orders_count": 2
    }
  ],
  "count": 1
}
```

#### Новый WB-специфичный эндпоинт (рекомендуемый)
```
POST /api/wildberries/accounts/{account}/stickers/generate
```

**Request Body**:
```json
{
  "order_ids": [4329453745, 4329453746],
  "type": "code128",
  "width": 58,
  "height": 40
}
```

**Response**:
```json
{
  "success": true,
  "message": "Стикеры успешно сгенерированы",
  "file_path": "marketplace/stickers/account-1/wb-stickers-4329453745-4329453746-2025-12-15_123045.png",
  "format": "png",
  "order_ids": [4329453745, 4329453746],
  "download_url": "http://localhost:8000/api/wildberries/accounts/1/stickers/download?path=..."
}
```

#### Cross-border стикеры
```
POST /api/wildberries/accounts/{account}/stickers/cross-border
```

**Request Body**:
```json
{
  "order_ids": [4329453745, 4329453746]
}
```

**Response**:
```json
{
  "success": true,
  "message": "Кроссбордер стикеры успешно сгенерированы",
  "file_path": "marketplace/stickers/account-1/wb-cross-border-4329453745-4329453746-2025-12-15_123045.pdf",
  "format": "pdf",
  "order_ids": [4329453745, 4329453746],
  "type": "cross-border",
  "download_url": "..."
}
```

### Поставки

#### Получение баркода поставки
```
GET /api/marketplace/supplies/{supply}/barcode?token={token}
```

**Query Parameters**:
- `token` (required) - Bearer токен пользователя
- `type` (optional) - Формат файла: png, svg, pdf (по умолчанию: png)

**Response**: Binary file (PNG/SVG/PDF)

**Headers**:
```
Content-Type: image/png (или image/svg+xml, application/pdf)
Content-Disposition: attachment; filename="supply-123-barcode.png"
```

### Короба

#### Получение баркода короба
```
GET /api/marketplace/supplies/{supply}/tares/{tare}/barcode?token={token}
```

**Query Parameters**:
- `token` (required) - Bearer токен
- `type` (optional) - Формат: png, svg, pdf

**Response**: Binary file

---

## Backend архитектура

### Файловая структура

```
app/
├── Http/Controllers/Api/
│   ├── MarketplaceOrderController.php    # Старый контроллер (legacy)
│   ├── WildberriesStickerController.php  # Новый WB-специфичный контроллер
│   └── SupplyController.php              # Контроллер поставок
├── Services/Marketplaces/Wildberries/
│   ├── WildberriesStickerService.php     # Сервис для работы со стикерами
│   ├── WildberriesOrderService.php       # Сервис заказов (стикеры + баркоды)
│   └── WildberriesHttpClient.php         # HTTP клиент WB API
└── Models/
    ├── WbOrder.php                       # Модель заказа WB
    ├── Supply.php                        # Модель поставки
    └── SupplyBox.php                     # Модель короба
```

### Основные классы

#### 1. WildberriesStickerService

**Файл**: `app/Services/Marketplaces/Wildberries/WildberriesStickerService.php`

**Основные методы**:

```php
/**
 * Получить стикеры для заказов
 */
public function getStickers(
    MarketplaceAccount $account,
    array $orderIds,
    string $type = 'code128',
    int $width = 58,
    int $height = 40,
    bool $save = true
): array

/**
 * Получить cross-border стикеры
 */
public function getCrossBorderStickers(
    MarketplaceAccount $account,
    array $orderIds,
    bool $save = true
): array

/**
 * Получить стикеры батчами (более 100 заказов)
 */
public function getStickersInBatches(
    MarketplaceAccount $account,
    array $orderIds,
    string $type = 'code128',
    bool $save = true
): array

/**
 * Удалить старые стикеры
 */
public function cleanupOldStickers(int $daysOld = 30): int
```

**Логика сохранения**:
```php
protected function saveSticker(
    MarketplaceAccount $account,
    array $orderIds,
    string $content,
    string $format,
    string $prefix = 'stickers'
): string {
    $timestamp = now()->format('Y-m-d_His');
    $orderIdsStr = implode('-', array_slice($orderIds, 0, 5));

    if (count($orderIds) > 5) {
        $orderIdsStr .= '-and-more';
    }

    $filename = "wb-{$prefix}-{$orderIdsStr}-{$timestamp}.{$format}";
    $path = "marketplace/stickers/account-{$account->id}/{$filename}";

    Storage::disk('local')->put($path, $content);

    return $path;
}
```

**Определение формата**:
```php
protected function detectFormat(string $content, string $requestedType): string
{
    // Проверка magic bytes
    if (str_starts_with($content, '%PDF')) {
        return 'pdf';
    }

    if (str_starts_with($content, "\x89PNG")) {
        return 'png';
    }

    if (str_starts_with($content, '<svg') || str_starts_with($content, '<?xml')) {
        return 'svg';
    }

    // Fallback
    return match ($requestedType) {
        'svg' => 'svg',
        'png' => 'png',
        default => 'pdf',
    };
}
```

#### 2. WildberriesOrderService

**Файл**: `app/Services/Marketplaces/Wildberries/WildberriesOrderService.php`

**Методы для баркодов**:

```php
/**
 * Получить баркод поставки
 */
public function getSupplyBarcode(
    MarketplaceAccount $account,
    string $supplyId,
    string $type = 'png'
): array {
    $fileContent = $this->httpClient->getBinary(
        'marketplace',
        "/api/v3/supplies/{$supplyId}/barcode",
        ['type' => $type]
    );

    $contentType = match ($type) {
        'svg' => 'image/svg+xml',
        'png' => 'image/png',
        'pdf' => 'application/pdf',
        default => 'image/png',
    };

    return [
        'file_content' => $fileContent,
        'content_type' => $contentType,
        'format' => $type,
        'supply_id' => $supplyId,
    ];
}

/**
 * Получить баркод короба/тары
 */
public function getTareBarcode(
    MarketplaceAccount $account,
    string $supplyId,
    string $tareId,
    string $type = 'png'
): array {
    $fileContent = $this->httpClient->getBinary(
        'marketplace',
        "/api/v3/supplies/{$supplyId}/tares/{$tareId}/barcode",
        ['type' => $type]
    );

    return [
        'file_content' => $fileContent,
        'content_type' => match ($type) {
            'svg' => 'image/svg+xml',
            'png' => 'image/png',
            'pdf' => 'application/pdf',
            default => 'image/png',
        },
        'format' => $type,
        'supply_id' => $supplyId,
        'tare_id' => $tareId,
    ];
}

/**
 * Получить стикеры заказов (legacy метод)
 */
public function getOrdersStickers(
    MarketplaceAccount $account,
    array $orderIds,
    string $type = 'png',
    int $width = 58,
    int $height = 40
): string {
    $fileContent = $this->httpClient->postBinary(
        'marketplace',
        '/api/v3/orders/stickers',
        ['orders' => array_map('intval', $orderIds)],
        [
            'type' => $type,
            'width' => $width,
            'height' => $height,
        ]
    );

    return $fileContent;
}
```

#### 3. SupplyController

**Файл**: `app/Http/Controllers/Api/SupplyController.php`

**Метод получения баркода поставки**:

```php
public function barcode(Request $request, Supply $supply): JsonResponse
{
    if (!$request->user()->hasCompanyAccess($supply->account->company_id)) {
        return response()->json(['message' => 'Доступ запрещён.'], 403);
    }

    if (!$supply->account->isWildberries()) {
        return response()->json(['message' => 'Аккаунт не является Wildberries.'], 422);
    }

    if (!$supply->external_supply_id || !str_starts_with($supply->external_supply_id, 'WB-')) {
        return response()->json([
            'message' => 'Поставка не синхронизирована с WB. Сначала синхронизируйте поставку.'
        ], 422);
    }

    try {
        $orderService = $this->getWbOrderService($supply->account);
        $result = $orderService->getSupplyBarcode($supply->account, $supply->external_supply_id, 'png');

        return response($result['file_content'])
            ->header('Content-Type', $result['content_type'])
            ->header('Content-Disposition', "attachment; filename=\"supply-{$supply->id}-barcode.{$result['format']}\"");

    } catch (\Exception $e) {
        Log::error('Failed to get supply barcode', [
            'supply_id' => $supply->id,
            'error' => $e->getMessage(),
        ]);

        return response()->json([
            'message' => 'Ошибка получения баркода: ' . $e->getMessage(),
        ], 500);
    }
}
```

**Автоматическая генерация баркода при закрытии поставки**:

```php
// В методе close()
try {
    $barcode = $orderService->getSupplyBarcode($supply->account, $supply->external_supply_id, 'png');

    $barcodePath = "supplies/barcodes/{$supply->id}.png";
    \Storage::put($barcodePath, $barcode['file_content']);

    $supply->update([
        'barcode_path' => $barcodePath,
    ]);

} catch (\Exception $e) {
    Log::warning('Failed to download supply barcode after closing', [
        'supply_id' => $supply->id,
        'error' => $e->getMessage(),
    ]);
}
```

### База данных

#### Таблица: supplies

```sql
barcode_path VARCHAR(255) NULL  -- Путь к сохранённому баркоду поставки
```

**Пример**: `supplies/barcodes/123.png`

#### Таблица: supply_boxes

```sql
id BIGINT UNSIGNED PRIMARY KEY
supply_id BIGINT UNSIGNED  -- FK к supplies
box_number INT              -- Номер короба в поставке
sticker_path VARCHAR(255)   -- Путь к стикеру короба (опционально)
```

**Примечание**: Стикеры коробов обычно генерируются on-the-fly и не сохраняются в БД.

---

## Frontend реализация

### JavaScript функции

**Файл**: `resources/views/pages/marketplace/orders.blade.php`

#### Печать стикера заказа

```javascript
async printOrderSticker(order) {
    try {
        // Если стикер уже сгенерирован - печатаем из кэша
        if (order.sticker_path) {
            await this.printFromUrl(`/storage/${order.sticker_path}`);
            return;
        }

        // Подготовка payload
        const payload = {
            marketplace_account_id: this.accountId,
            order_ids: [order.external_order_id],
        };

        // Uzum специфичные параметры
        if (this.isUzum()) {
            payload.size = 'LARGE';
        } else {
            // WB параметры
            payload.type = 'png';
            payload.width = 58;
            payload.height = 40;
        }

        // Вызов API
        const response = await axios.post('/api/marketplace/orders/stickers', payload, {
            headers: this.getAuthHeaders()
        });

        if (response.data.stickers && response.data.stickers.length > 0) {
            const sticker = response.data.stickers[0];

            // Обновляем заказ в локальном state
            const orderIndex = this.orders.findIndex(o => o.id === order.id);
            if (orderIndex !== -1) {
                this.orders[orderIndex].sticker_path = sticker.path;
                this.orders[orderIndex].sticker_generated_at = new Date().toISOString();
            }

            // Печать
            if (sticker.base64) {
                const blob = this.base64ToBlob(sticker.base64, 'application/pdf');
                await this.printFromBlob(blob);
            } else {
                const url = sticker.url || `/storage/${sticker.path}`;
                await this.printFromUrl(url);
            }

            this.showNotification('Стикер успешно сгенерирован');
        }

    } catch (error) {
        console.error('Error printing sticker:', error);
        alert(error.response?.data?.message || 'Ошибка при печати стикера');
    }
}
```

#### Печать из URL

```javascript
async printFromUrl(url) {
    try {
        // Преобразуем в относительный путь для избежания CORS
        let fetchUrl = url;
        try {
            const u = new URL(url, window.location.origin);
            fetchUrl = u.pathname + u.search + u.hash;
        } catch (e) {
            // URL уже относительный
        }

        const res = await fetch(fetchUrl, { credentials: 'include' });
        if (!res.ok) throw new Error(`Не удалось загрузить файл (${res.status})`);

        const blob = await res.blob();
        await this.printFromBlob(blob);

    } catch (e) {
        console.error('Print error', e);
        alert('Не удалось распечатать этикетку: ' + (e.message || 'ошибка загрузки'));
    }
}
```

#### Печать из Blob

```javascript
async printFromBlob(blob) {
    const blobUrl = URL.createObjectURL(blob);
    const iframe = document.createElement('iframe');
    iframe.style.position = 'fixed';
    iframe.style.right = '0';
    iframe.style.bottom = '0';
    iframe.style.width = '0';
    iframe.style.height = '0';
    iframe.src = blobUrl;
    document.body.appendChild(iframe);

    iframe.onload = () => {
        iframe.contentWindow.focus();
        iframe.contentWindow.print();
        setTimeout(() => {
            URL.revokeObjectURL(blobUrl);
            iframe.remove();
        }, 1500);
    };
}
```

#### Конвертация Base64 в Blob

```javascript
base64ToBlob(base64, mime) {
    const byteChars = atob(base64);
    const byteNumbers = new Array(byteChars.length);
    for (let i = 0; i < byteChars.length; i++) {
        byteNumbers[i] = byteChars.charCodeAt(i);
    }
    const byteArray = new Uint8Array(byteNumbers);
    return new Blob([byteArray], { type: mime });
}
```

### UI элементы

#### Кнопка печати стикера заказа

```html
<button @click.stop="printOrderSticker(order)"
        class="px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-lg transition flex items-center space-x-1"
        title="Печать стикера">
    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
    </svg>
    <span x-text="order.sticker_path ? 'Скачать' : 'Печать'"></span>
</button>
```

#### Кнопка скачивания QR поставки

```html
<button x-show="supply.barcode_path && supply.external_supply_id"
        @click.stop="window.open(`/api/marketplace/supplies/${supply.id}/barcode?token=${$store.auth.token}`, '_blank')"
        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-lg transition flex items-center space-x-2">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m0 0l-4-4m4 4l4-4"/>
    </svg>
    <span>Скачать QR</span>
</button>
```

#### Кнопка скачивания баркода поставки (альтернативный вариант)

```html
<button x-show="supply.barcode_path && supply.external_supply_id"
        @click.stop="window.open(`/api/marketplace/supplies/${supply.id}/barcode?token=${$store.auth.token}`, '_blank')"
        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-lg transition flex items-center space-x-2">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
    </svg>
    <span>Скачать баркод</span>
</button>
```

---

## WB API интеграция

### WB Marketplace API v3

#### 1. Стикеры заказов

**Endpoint**:
```
POST https://suppliers-api.wildberries.ru/api/v3/orders/stickers
```

**Headers**:
```
Authorization: {WB_MARKETPLACE_TOKEN}
Content-Type: application/json
```

**Request Body**:
```json
{
  "orders": [4329453745, 4329453746],
  "type": "png",
  "width": 58,
  "height": 40
}
```

**Parameters**:
- `orders` (array, required) - Массив номеров заказов (до 100)
- `type` (string, optional) - Тип: png, svg, code128, zplv, zplh (default: png)
- `width` (integer, optional) - Ширина в мм: 20-200 (default: 58)
- `height` (integer, optional) - Высота в мм: 20-200 (default: 40)

**Response**: Binary data (PNG/SVG/ZPL)

#### 2. Cross-border стикеры

**Endpoint**:
```
POST https://suppliers-api.wildberries.ru/api/v3/orders/stickers/cross-border
```

**Request Body**:
```json
{
  "orders": [4329453745, 4329453746]
}
```

**Response**: Binary PDF data

#### 3. Баркод поставки

**Endpoint**:
```
GET https://suppliers-api.wildberries.ru/api/v3/supplies/{supplyId}/barcode?type=png
```

**Parameters**:
- `type` (string, optional) - Формат: png, svg, pdf (default: png)

**Response**: Binary data (PNG/SVG/PDF)

**Пример**: `/api/v3/supplies/WB-GI-203035917/barcode?type=png`

#### 4. Баркод короба/тары

**Endpoint**:
```
GET https://suppliers-api.wildberries.ru/api/v3/supplies/{supplyId}/tares/{tareId}/barcode?type=png
```

**Parameters**:
- `type` (string, optional) - Формат: png, svg, pdf (default: png)

**Response**: Binary data

**Пример**: `/api/v3/supplies/WB-GI-203035917/tares/WBX1234567890/barcode?type=png`

### WildberriesHttpClient

**Файл**: `app/Services/Marketplaces/Wildberries/WildberriesHttpClient.php`

**Методы для бинарных данных**:

```php
/**
 * GET запрос для получения бинарных данных
 */
public function getBinary(string $tokenType, string $endpoint, array $params = []): string
{
    $token = $this->account->getWbToken($tokenType);

    $response = Http::withHeaders([
        'Authorization' => $token,
    ])->get($this->getBaseUrl() . $endpoint, $params);

    if (!$response->successful()) {
        throw new \Exception("WB API error: {$response->status()} - {$response->body()}");
    }

    return $response->body();
}

/**
 * POST запрос для получения бинарных данных
 */
public function postBinary(string $tokenType, string $endpoint, array $data = [], array $params = []): string
{
    $token = $this->account->getWbToken($tokenType);

    $response = Http::withHeaders([
        'Authorization' => $token,
        'Content-Type' => 'application/json',
    ])->post($this->getBaseUrl() . $endpoint . '?' . http_build_query($params), $data);

    if (!$response->successful()) {
        throw new \Exception("WB API error: {$response->status()} - {$response->body()}");
    }

    return $response->body();
}
```

---

## Примеры использования

### 1. Генерация стикеров для одного заказа

**JavaScript (Frontend)**:
```javascript
// Печать стикера для заказа
await this.printOrderSticker(order);
```

**HTTP запрос**:
```http
POST /api/marketplace/orders/stickers HTTP/1.1
Authorization: Bearer {token}
Content-Type: application/json

{
  "marketplace_account_id": 1,
  "order_ids": ["4329453745"],
  "type": "png",
  "width": 58,
  "height": 40
}
```

**PHP (Backend)**:
```php
use App\Services\Marketplaces\Wildberries\WildberriesStickerService;

$stickerService = app(WildberriesStickerService::class);

$result = $stickerService->getStickers(
    $account,
    [4329453745],
    'png',
    58,
    40,
    true
);

// Результат:
// [
//   'file_path' => 'marketplace/stickers/account-1/wb-stickers-4329453745-2025-12-15_123045.png',
//   'content' => (binary data),
//   'format' => 'png',
//   'order_ids' => [4329453745]
// ]
```

### 2. Генерация стикеров для нескольких заказов

**PHP**:
```php
$orderIds = [4329453745, 4329453746, 4329453747];

$result = $stickerService->getStickers(
    $account,
    $orderIds,
    'code128',
    58,
    40,
    true
);

// Файл: wb-stickers-4329453745-4329453746-4329453747-2025-12-15_123045.pdf
```

### 3. Генерация стикеров батчами (более 100 заказов)

**PHP**:
```php
$orderIds = range(1, 250); // 250 заказов

$results = $stickerService->getStickersInBatches(
    $account,
    $orderIds,
    'png',
    true
);

// Результат:
// [
//   0 => ['file_path' => '...', 'order_ids' => [1-100]],
//   1 => ['file_path' => '...', 'order_ids' => [101-200]],
//   2 => ['file_path' => '...', 'order_ids' => [201-250]]
// ]
```

### 4. Cross-border стикеры

**HTTP запрос**:
```http
POST /api/wildberries/accounts/1/stickers/cross-border HTTP/1.1
Authorization: Bearer {token}
Content-Type: application/json

{
  "order_ids": [4329453745, 4329453746]
}
```

**PHP**:
```php
$result = $stickerService->getCrossBorderStickers(
    $account,
    [4329453745, 4329453746],
    true
);

// Результат: PDF файл с международными стикерами
```

### 5. Скачивание баркода поставки

**JavaScript**:
```javascript
// Открыть баркод в новой вкладке
window.open(
    `/api/marketplace/supplies/${supply.id}/barcode?token=${token}`,
    '_blank'
);
```

**PHP**:
```php
use App\Services\Marketplaces\Wildberries\WildberriesOrderService;

$orderService = app(WildberriesOrderService::class);

$result = $orderService->getSupplyBarcode(
    $account,
    'WB-GI-203035917',
    'png'
);

// Вернуть файл пользователю
return response($result['file_content'])
    ->header('Content-Type', $result['content_type'])
    ->header('Content-Disposition', 'attachment; filename="supply-barcode.png"');
```

### 6. Автоматическая генерация баркода при закрытии поставки

**PHP** (в `SupplyController::close()`):
```php
try {
    // Закрываем поставку в WB API
    $orderService->closeSupply($account, $supply->external_supply_id);

    $supply->update(['status' => 'sent']);

    // Автоматически скачиваем баркод
    $barcode = $orderService->getSupplyBarcode($supply->account, $supply->external_supply_id, 'png');

    $barcodePath = "supplies/barcodes/{$supply->id}.png";
    \Storage::put($barcodePath, $barcode['file_content']);

    $supply->update([
        'barcode_path' => $barcodePath,
    ]);

    return response()->json([
        'supply' => $supply->fresh()->load('account'),
        'message' => 'Поставка закрыта.',
        'barcode_downloaded' => true,
    ]);

} catch (\Exception $e) {
    Log::error('Failed to close supply', [
        'supply_id' => $supply->id,
        'error' => $e->getMessage(),
    ]);

    return response()->json([
        'message' => 'Ошибка закрытия поставки: ' . $e->getMessage(),
    ], 500);
}
```

### 7. Скачивание баркода короба

**PHP**:
```php
$result = $orderService->getTareBarcode(
    $account,
    'WB-GI-203035917',  // Supply ID
    'WBX1234567890',     // Tare ID
    'png'
);

return response($result['file_content'])
    ->header('Content-Type', $result['content_type'])
    ->header('Content-Disposition', 'attachment; filename="tare-barcode.png"');
```

### 8. Очистка старых стикеров (cleanup)

**HTTP запрос** (только для админов):
```http
POST /api/wildberries/stickers/cleanup HTTP/1.1
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "days_old": 30
}
```

**PHP**:
```php
$deleted = $stickerService->cleanupOldStickers(30);

// Удалено 127 файлов старше 30 дней
```

**Scheduled Job** (рекомендуется в `app/Console/Kernel.php`):
```php
protected function schedule(Schedule $schedule)
{
    // Очистка старых стикеров каждую неделю
    $schedule->call(function () {
        $service = app(WildberriesStickerService::class);
        $deleted = $service->cleanupOldStickers(30);
        Log::info("Sticker cleanup: deleted {$deleted} files");
    })->weekly();
}
```

---

## Диагностика и логирование

### Логи

Все операции со стикерами логируются в `storage/logs/laravel.log`:

```php
// Успешная генерация стикеров
Log::info('WB order stickers fetched', [
    'account_id' => 1,
    'order_count' => 5,
    'format' => 'png',
    'saved' => true,
]);

// Ошибка при генерации
Log::error('Failed to get WB order stickers', [
    'account_id' => 1,
    'order_ids' => [123, 456],
    'error' => 'API error message',
]);

// Скачивание баркода поставки
Log::info('WB supply barcode fetched', [
    'account_id' => 1,
    'supply_id' => 'WB-GI-203035917',
    'type' => 'png',
    'size' => 45678,
]);
```

### Проверка наличия файлов

```php
use Illuminate\Support\Facades\Storage;

// Проверить существование стикера
$exists = Storage::disk('local')->exists($filePath);

// Получить размер файла
$size = Storage::disk('local')->size($filePath);

// Получить все стикеры для аккаунта
$files = Storage::disk('local')->allFiles("marketplace/stickers/account-{$accountId}");
```

### Распространённые ошибки

**1. WB API error: 401**
- Причина: Неверный или истёкший токен
- Решение: Обновить токен в настройках аккаунта

**2. WB API error: 400 - Invalid order IDs**
- Причина: Заказы не существуют или не принадлежат аккаунту
- Решение: Проверить external_order_id в таблице wb_orders

**3. WB API error: 422 - Supply not closed**
- Причина: Попытка получить баркод для незакрытой поставки
- Решение: Сначала закрыть поставку через `closeSupply()`

**4. Storage permission denied**
- Причина: Недостаточно прав для записи в storage/app/
- Решение: `chmod -R 775 storage/app/marketplace/stickers/`

---

## Рекомендации

### 1. Безопасность

- Всегда проверяйте права доступа пользователя к аккаунту перед генерацией стикеров
- Используйте rate limiting для предотвращения спама
- Не храните стикеры вечно - используйте автоматическую очистку

### 2. Производительность

- Используйте батчинг для генерации стикеров более 100 заказов
- Кэшируйте стикеры в БД (поле `sticker_path`) для повторного использования
- Используйте фоновые задачи (queues) для массовой генерации

### 3. UX

- Показывайте индикатор загрузки при генерации стикеров
- Предлагайте скачать готовый стикер вместо повторной генерации
- Используйте iframe.print() для бесшовной печати без открытия новых вкладок

### 4. Мониторинг

- Логируйте все операции со стикерами
- Отслеживайте размер папки `marketplace/stickers/` (дисковое пространство)
- Настройте алерты для ошибок WB API

---

## Дальнейшее развитие

### Планируемые улучшения

1. **Массовая печать стикеров**
   - UI для выбора нескольких заказов
   - Генерация одного PDF со всеми стикерами

2. **Шаблоны стикеров**
   - Настройка размеров и формата по умолчанию
   - Сохранение предпочтений пользователя

3. **Интеграция с принтерами**
   - Прямая отправка на термопринтер
   - Поддержка CUPS/IPP

4. **Статистика**
   - Количество напечатанных стикеров
   - Топ заказов по печати

5. **Предпросмотр стикера**
   - Показать стикер перед печатью
   - Редактирование размера в UI

---

## Заключение

Система печати стикеров Wildberries полностью реализована и готова к использованию. Она поддерживает:

✅ Стикеры заказов (обычные и cross-border)
✅ Баркоды поставок (QR-коды)
✅ Баркоды коробов/тар
✅ Множественные форматы (PNG, SVG, PDF, ZPL)
✅ Автоматическую очистку старых файлов
✅ Батчинг для больших объёмов
✅ Удобный UI с кнопками печати

Все компоненты протестированы и активно используются в продакшене.
