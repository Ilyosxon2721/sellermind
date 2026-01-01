# Настройка изображений товаров Wildberries

## Проблема
Изображения товаров с маркетплейса Wildberries не отображаются на странице заказов.

## Причина
1. CDN Wildberries (`basket-XX.wbbasket.ru`) может блокировать прямые запросы с локальных серверов
2. Алгоритм определения номера корзины (basket) был неточным
3. Не было fallback механизма при ошибках загрузки

## Решение

### 1. Обновлен алгоритм генерации URL изображений

**Файл:** `resources/views/pages/marketplace/orders.blade.php` (строки 240-271)

Wildberries использует CDN с балансировкой нагрузки:
```
https://basket-{XX}.wbbasket.ru/vol{VOL}/part{PART}/{nmId}/images/{size}/1.jpg
```

Где:
- `{XX}` - номер корзины (01-19), определяется по формуле от vol
- `{VOL}` - floor(nmId / 100000)
- `{PART}` - floor(nmId / 1000)
- `{nmId}` - артикул товара WB
- `{size}` - размер изображения: tm (thumbnail), c246x328, c516x688, big

**Алгоритм определения корзины:**
```javascript
let basket = 0;

if (vol < 144) {
    basket = Math.floor(vol / 10) + 1;      // 01-14
} else if (vol < 288) {
    basket = Math.floor((vol - 144) / 10) + 1;  // 01-14
} else if (vol < 432) {
    basket = Math.floor((vol - 288) / 10) + 1;  // 01-14
} else {
    basket = (vol % 16) + 1;                     // 01-16
}

// Ограничиваем диапазон 01-19
if (basket > 19) basket = (basket % 19) + 1;
if (basket === 0) basket = 1;
```

### 2. Добавлен SVG placeholder

**Функция:** `getProductPlaceholder()`

При отсутствии `wb_nm_id` или ошибке загрузки показывается SVG placeholder с текстом "ФОТО".

```javascript
getProductPlaceholder() {
    return 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgZmlsbD0iI2YzZjRmNiIvPjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBmb250LWZhbWlseT0iQXJpYWwiIGZvbnQtc2l6ZT0iMTQiIGZpbGw9IiM5Y2EzYWYiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIuM2VtIj5GT1RPPC90ZXh0Pjwvc3ZnPg==';
}
```

### 3. Обработка ошибок загрузки

**Функция:** `handleImageError(event)`

Вместо скрытия изображения при ошибке показывается placeholder:

```javascript
handleImageError(event) {
    // При ошибке загрузки показываем placeholder
    event.target.src = this.getProductPlaceholder();
    event.target.style.display = '';
    // Убираем обработчик, чтобы избежать бесконечного цикла
    event.target.onerror = null;
}
```

**Использование в HTML:**
```html
<img :src="getWbProductImageUrl(order.wb_nm_id)"
     :alt="order.wb_article"
     class="w-24 h-24 object-cover rounded-lg border border-gray-200"
     loading="lazy"
     @error="handleImageError">
```

## Примеры URL

### Пример 1: nmId = 316321379
```
vol = 3163
part = 316321
basket = 12 (формула: (3163 % 16) + 1 = 12)

URL: https://basket-12.wbbasket.ru/vol3163/part316321/316321379/images/tm/1.jpg
```

### Пример 2: nmId = 100000
```
vol = 1
part = 100
basket = 01 (формула: floor(1 / 10) + 1 = 1)

URL: https://basket-01.wbbasket.ru/vol1/part100/100000/images/tm/1.jpg
```

### Пример 3: nmId = 1000000
```
vol = 10
part = 1000
basket = 02 (формула: floor(10 / 10) + 1 = 2)

URL: https://basket-02.wbbasket.ru/vol10/part1000/1000000/images/tm/1.jpg
```

## Размеры изображений

- **tm** - Thumbnail (~196x260 пикселей) - используется по умолчанию
- **c246x328** - Средний размер (246x328 пикселей)
- **c516x688** - Большой размер (516x688 пикселей)
- **big** - Максимальный размер (оригинал)

## Возможные проблемы и решения

### Проблема 1: Изображения не загружаются (CORS)

**Причина:** CDN Wildberries может блокировать запросы с localhost.

**Решение 1 - Прокси:**
Создать API endpoint в Laravel для проксирования изображений:

```php
// routes/api.php
Route::get('/wb-image/{nmId}/{size?}', function($nmId, $size = 'tm') {
    $vol = floor($nmId / 100000);
    $part = floor($nmId / 1000);
    // ... алгоритм basket
    $url = "https://basket-{$basket}.wbbasket.ru/vol{$vol}/part{$part}/{$nmId}/images/{$size}/1.jpg";

    $contents = @file_get_contents($url);
    if ($contents === false) {
        abort(404);
    }

    return response($contents, 200)->header('Content-Type', 'image/jpeg');
});
```

Затем в JavaScript:
```javascript
getWbProductImageUrl(nmId, size = 'tm') {
    return `/api/wb-image/${nmId}/${size}`;
}
```

**Решение 2 - HTTPS на локальном сервере:**
Настроить HTTPS для локального сервера (localhost → https://localhost).

### Проблема 2: Некоторые изображения всё равно не загружаются

**Причина:** Товар удалён или изображение отсутствует на CDN.

**Решение:** Используется механизм fallback с placeholder (уже реализован).

## Проверка

1. Откройте страницу заказов: `http://127.0.0.1:8000/marketplace/2/orders`
2. В карточках заказов должны отображаться:
   - Изображения товаров (если доступны на CDN WB)
   - Серый placeholder с текстом "ФОТО" (если изображение недоступно)
3. Откройте DevTools → Network → Img:
   - Проверьте URL изображений (должны быть `basket-XX.wbbasket.ru`)
   - Проверьте статусы ответов (200 OK или 404)

## Оптимизация

Все изображения используют:
- `loading="lazy"` - отложенная загрузка
- `@error="handleImageError"` - обработка ошибок
- Кэширование браузером (CDN WB отдаёт заголовки кэширования)

## Альтернативы

Если CDN WB недоступен, можно:
1. Хранить изображения локально в `storage/app/public/products/`
2. Загружать изображения через синхронизацию заказов
3. Использовать официальный API WB для получения изображений (если доступен)

## Дата обновления
2025-12-01
