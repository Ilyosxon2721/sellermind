# ТЗ для SellerMind: Синхронизация товаров с RISMENT

**Проект:** SellerMind (sellermind.uz)
**Связанный проект:** RISMENT (risment.uz)
**Дата:** 2026-02-11

---

## Контекст

RISMENT-сторона уже реализована полностью:
- Миграция: поля sellermind_sync_status, sellermind_sync_error, sellermind_synced_at в таблице products
- Job PushProductToSellermind отправляет товар в Redis-очередь sellermind:products
- Команда ProcessSellermindQueues слушает очередь risment:product_confirm и обновляет статус
- UI в кабинете показывает статус синхронизации с кнопкой повторной отправки
- Роут POST /cabinet/products/{product}/resync для ручного повтора

Осталось реализовать **3 задачи на стороне SellerMind**.

---

## Задача 1: Исправить проблему дубликатов при приёме товаров

### Проблема

Когда RISMENT отправляет товар в очередь sellermind:products, SellerMind пытается создать новый товар. Но если товар с таким же артикулом уже существует в SellerMind (был создан вручную, без risment_product_id), возникает ошибка Duplicate entry из-за уникального индекса на article.

**Пример:** Товар с артикулом KP11004 уже есть в SellerMind без привязки к RISMENT. При синхронизации из RISMENT — ошибка дубликата.

### Решение

**Файл:** app/Console/Commands/ProcessRismentQueues.php (или аналогичный обработчик очереди sellermind:products)

Изменить логику создания/обновления товара. Вместо простого create() использовать двухэтапный поиск:

```php
private function handleProduct(array $data): void
{
    $productData = $data['data'] ?? [];
    $rismentProductId = $productData['risment_product_id'] ?? null;
    $article = $productData['article'] ?? null;
    $companyId = $this->resolveCompanyId($data);

    if (!$rismentProductId) {
        Log::warning('Product message missing risment_product_id');
        return;
    }

    // ШАГ 1: Ищем по risment_product_id (точное совпадение)
    $product = Product::where('company_id', $companyId)
        ->where('risment_product_id', $rismentProductId)
        ->first();

    // ШАГ 2: Если не нашли — ищем по артикулу (товар создан вручную)
    if (!$product && !empty($article)) {
        $product = Product::where('company_id', $companyId)
            ->where('article', $article)
            ->first();
    }

    try {
        if ($product) {
            $product->update([
                'risment_product_id' => $rismentProductId,
                'name' => $productData['title'] ?? $product->name,
                'description' => $productData['description'] ?? $product->description,
                'article' => $article ?? $product->article,
            ]);
        } else {
            $product = Product::create([
                'company_id' => $companyId,
                'risment_product_id' => $rismentProductId,
                'name' => $productData['title'] ?? '',
                'description' => $productData['description'] ?? '',
                'article' => $article,
            ]);
        }

        $this->syncVariants($product, $productData['variants'] ?? []);
        $this->sendConfirmation($data, $product, 'success');

    } catch (\Exception $e) {
        $humanReadableError = $this->translateError($e->getMessage());
        $this->sendConfirmation($data, null, 'error', $humanReadableError);
    }
}
```

### Обработка вариантов

```php
private function syncVariants(Product $product, array $variantsData): void
{
    foreach ($variantsData as $variantData) {
        $rismentVariantId = $variantData['risment_variant_id'] ?? null;
        $variant = null;

        if ($rismentVariantId) {
            $variant = $product->variants()
                ->where('risment_variant_id', $rismentVariantId)
                ->first();
        }

        if (!$variant && !empty($variantData['sku_code'])) {
            $variant = $product->variants()
                ->where('sku_code', $variantData['sku_code'])
                ->first();
        }

        if ($variant) {
            $variant->update([
                'risment_variant_id' => $rismentVariantId,
                'variant_name' => $variantData['variant_name'] ?? $variant->variant_name,
                'sku_code' => $variantData['sku_code'] ?? $variant->sku_code,
                'barcode' => $variantData['barcode'] ?? $variant->barcode,
                'dims_l' => $variantData['dims_l'] ?? $variant->dims_l,
                'dims_w' => $variantData['dims_w'] ?? $variant->dims_w,
                'dims_h' => $variantData['dims_h'] ?? $variant->dims_h,
                'weight' => $variantData['weight'] ?? $variant->weight,
            ]);
        } else {
            $product->variants()->create([
                'risment_variant_id' => $rismentVariantId,
                'variant_name' => $variantData['variant_name'] ?? '',
                'sku_code' => $variantData['sku_code'] ?? '',
                'barcode' => $variantData['barcode'] ?? null,
                'dims_l' => $variantData['dims_l'] ?? null,
                'dims_w' => $variantData['dims_w'] ?? null,
                'dims_h' => $variantData['dims_h'] ?? null,
                'weight' => $variantData['weight'] ?? null,
            ]);
        }
    }
}
```

### Важно
- **Не удалять** варианты, которых нет в payload — они могли быть созданы в SellerMind вручную
- При обновлении: перезаписывать только те поля, которые пришли (не null)
- Логировать все операции для отладки

---

## Задача 2: Перевод ошибок на понятный русский язык

### Зачем

Когда RISMENT получает ошибку синхронизации, она отображается пользователю в интерфейсе кабинета. Технические сообщения MySQL/PHP непонятны клиентам. Нужен перевод на человекочитаемый русский.

### Реализация

**Файл:** app/Console/Commands/ProcessRismentQueues.php (или отдельный helper/service)

```php
protected function translateError(string $error): string
{
    // Дубликат артикула
    if (str_contains($error, 'Duplicate entry') && str_contains($error, 'article')) {
        return 'Товар с таким артикулом уже существует в SellerMind. '
             . 'Измените артикул в RISMENT или удалите дубликат в SellerMind.';
    }

    // Дубликат SKU
    if (str_contains($error, 'Duplicate entry') && str_contains($error, 'sku')) {
        return 'Вариант с таким SKU уже используется другим товаром в SellerMind. '
             . 'Проверьте уникальность SKU-кодов.';
    }

    // Дубликат штрих-кода
    if (str_contains($error, 'Duplicate entry') && str_contains($error, 'barcode')) {
        return 'Штрих-код уже используется другим товаром в SellerMind.';
    }

    // Общий дубликат
    if (str_contains($error, 'Duplicate entry')) {
        return 'Запись с такими данными уже существует в SellerMind. Проверьте уникальность полей.';
    }

    // Ошибка категории
    if (str_contains($error, 'category')) {
        return 'Ошибка привязки категории товара. Проверьте настройки категорий.';
    }

    // Превышена длина поля
    if (str_contains($error, 'Data too long') || str_contains($error, 'too long')) {
        return 'Одно из полей товара слишком длинное. Сократите название или описание.';
    }

    // Обязательное поле не заполнено
    if (str_contains($error, 'cannot be null')) {
        return 'Не заполнено обязательное поле товара. Проверьте все поля и попробуйте снова.';
    }

    // Ошибка подключения к Redis
    if (str_contains($error, 'Connection refused') || str_contains($error, 'Redis')) {
        return 'Временная ошибка связи между системами. Попробуйте позже.';
    }

    // Общая ошибка базы данных
    if (str_contains($error, 'SQLSTATE')) {
        return 'Ошибка сохранения данных в SellerMind. Обратитесь в поддержку.';
    }

    // Таймаут
    if (str_contains($error, 'timeout') || str_contains($error, 'Timeout')) {
        return 'Превышено время ожидания. Попробуйте повторить синхронизацию позже.';
    }

    return 'Ошибка синхронизации: ' . mb_substr($error, 0, 200);
}
```

### Где вызывать

В catch-блоке обработчика товаров (Задача 1), перед отправкой подтверждения с ошибкой.

---

## Задача 3: Отправка подтверждений в RISMENT

### Зачем

После того как SellerMind обработал товар из очереди sellermind:products, он должен отправить результат обратно в RISMENT через очередь risment:product_confirm. RISMENT уже слушает эту очередь и обновляет статус товара (synced/error) в своей базе и UI.

### Формат сообщений

#### Успешная синхронизация

```json
{
    "event": "product.synced",
    "link_token": "<link_token из исходного сообщения>",
    "data": {
        "risment_product_id": 42,
        "sellermind_product_id": 128,
        "status": "success"
    }
}
```

#### Ошибка синхронизации

```json
{
    "event": "product.sync_error",
    "link_token": "<link_token из исходного сообщения>",
    "data": {
        "risment_product_id": 42,
        "status": "error",
        "error": "Товар с таким артикулом уже существует в SellerMind."
    }
}
```

### Реализация

```php
private function sendConfirmation(array $originalData, ?Product $product, string $status, ?string $error = null): void
{
    $productData = $originalData['data'] ?? [];
    $rismentProductId = $productData['risment_product_id'] ?? null;
    $linkToken = $originalData['link_token'] ?? null;

    if (!$rismentProductId || !$linkToken) {
        Log::warning('Cannot send product confirmation: missing risment_product_id or link_token');
        return;
    }

    $confirmation = [
        'event' => $status === 'success' ? 'product.synced' : 'product.sync_error',
        'link_token' => $linkToken,
        'data' => [
            'risment_product_id' => $rismentProductId,
            'status' => $status,
        ],
    ];

    if ($status === 'success' && $product) {
        $confirmation['data']['sellermind_product_id'] = $product->id;
    }

    if ($status === 'error' && $error) {
        $confirmation['data']['error'] = $error;
    }

    try {
        Redis::connection('integration')->rpush(
            'risment:product_confirm',
            json_encode($confirmation, JSON_UNESCAPED_UNICODE)
        );
    } catch (\Exception $e) {
        Log::error("Failed to send product confirmation to RISMENT", [
            'risment_product_id' => $rismentProductId,
            'error' => $e->getMessage(),
        ]);
    }
}
```

### Важные моменты

1. **link_token** — берётся из исходного сообщения. RISMENT использует его для валидации.
2. **JSON_UNESCAPED_UNICODE** — чтобы русские символы в ошибках не экранировались.
3. **Всегда отправлять подтверждение** — даже при ошибке. Иначе товар навсегда останется в статусе "Ожидает".
4. **Логировать ошибки отправки** — если Redis недоступен, это критическая проблема.

---

## Полная схема взаимодействия

```
RISMENT                              Redis                           SellerMind
--------                             -----                           ----------

1. Пользователь создаёт товар
   или нажимает "Повторить"
        |
        v
2. PushProductToSellermind
   отправляет в Redis ---------> sellermind:products ----------> 3. ProcessRismentQueues
   status = 'pending'                                                handleProduct()
                                                                     |
                                                                     +-- Ищет по risment_product_id
                                                                     +-- Ищет по article (fallback)
                                                                     +-- Создаёт или обновляет
                                                                     +-- translateError() при ошибке
                                                                     |
                                                                     v
4. ProcessSellermindQueues      risment:product_confirm <------ 5. sendConfirmation()
   handleProductConfirm()                                          status: success/error
   |
   +-- success -> status='synced'
   +-- error -> status='error'
        |
        v
6. UI в кабинете:
   - Синхронизирован (зелёный)
   - Ошибка + "Повторить" (красный)
   - Ожидает (жёлтый)
```

---

## Payload из RISMENT (справочно)

```json
{
    "action": "sync_product",
    "link_token": "abc123...",
    "data": {
        "risment_product_id": 42,
        "title": "Кроссовки Nike Air Max",
        "article": "KP11004",
        "description": "Описание товара...",
        "is_active": true,
        "variants": [
            {
                "risment_variant_id": 101,
                "variant_name": "Размер 42, Чёрный",
                "sku_code": "KP11004-42-BLK",
                "barcode": "4607123456789",
                "dims_l": 35,
                "dims_w": 25,
                "dims_h": 15,
                "weight": 0.8,
                "attributes": [
                    { "name": "Размер", "value": "42" },
                    { "name": "Цвет", "value": "Чёрный" }
                ],
                "marketplace_links": [
                    { "marketplace": "uzum", "external_id": "UZ-123456" }
                ]
            }
        ]
    }
}
```

---

## Чек-лист для проверки

- [ ] Товар из RISMENT появляется в SellerMind (новый или обновлённый)
- [ ] Товар с существующим артикулом НЕ дублируется, а обновляется
- [ ] После успешной синхронизации в RISMENT статус = "Синхронизирован" (зелёный)
- [ ] При ошибке в RISMENT статус = "Ошибка" (красный) с понятным описанием
- [ ] Кнопка "Повторить синхронизацию" работает
- [ ] Варианты синхронизируются корректно (без дублей)
- [ ] При повторной отправке — обновление, а не создание нового
- [ ] Ошибки Redis логируются в SellerMind
