# Техническое задание: SellerMind — Интеграция с RISMENT

**Версия:** 2.0  
**Дата:** 04.02.2026  
**Проект:** SellerMind (sellermind.uz)  
**Статус:** В разработке

---

## 📋 Содержание

1. [Обзор проекта](#1-обзор-проекта)
2. [Текущее состояние](#2-текущее-состояние)
3. [Модуль 1: Выбор склада для товаров RISMENT](#3-модуль-1-выбор-склада-для-товаров-risment)
4. [Модуль 2: Полная обработка карточки товара](#4-модуль-2-полная-обработка-карточки-товара)
5. [Модуль 3: Синхронизация заказов в RISMENT](#5-модуль-3-синхронизация-заказов-в-risment)
6. [Модуль 4: Синхронизация остатков](#6-модуль-4-синхронизация-остатков)
7. [Модуль 5: Автодобавление аккаунтов маркетплейсов](#7-модуль-5-автодобавление-аккаунтов-маркетплейсов)
8. [Redis очереди и события](#8-redis-очереди-и-события)
9. [План реализации](#9-план-реализации)

---

## 1. Обзор проекта

### 1.1 Описание SellerMind

SellerMind — платформа управления продажами на маркетплейсах:
- Управление карточками товаров на Wildberries, Ozon, Uzum, Yandex Market
- Синхронизация заказов с маркетплейсов
- Управление остатками и ценами
- Аналитика продаж
- Автоматизация (автоценообразование, автоответы)

### 1.2 Цель интеграции с RISMENT

- Получать товары из RISMENT и привязывать к выбранному складу
- Отправлять FBS заказы в RISMENT для фулфилмента
- Синхронизировать остатки со склада RISMENT
- Автоматически создавать аккаунты маркетплейсов на основе токенов из RISMENT

### 1.3 Технический стек

| Компонент | Технология |
|-----------|------------|
| Backend | Laravel 11+ (PHP 8.4) |
| Database | MySQL 8 |
| Queue | Redis (database 2) |
| Frontend | Blade + Alpine.js + Tailwind |
| Деплой | Laravel Forge |

---

## 2. Текущее состояние

### 2.1 Что реализовано ✅

| Функционал | Статус | Файлы |
|------------|--------|-------|
| Таблица связки `integration_links` | ✅ | migration |
| Модель `IntegrationLink` | ✅ | `app/Models/` |
| Worker `integration:process-risment` | ✅ | `app/Console/Commands/ProcessRismentQueues.php` |
| Обработка товаров из RISMENT | ✅ | updateOrCreate для Product, Variant, SKU |
| Job'ы отправки в RISMENT | ✅ | `app/Jobs/Integration/` |
| Страница интеграции | ✅ | `resources/views/integrations/` |
| Supervisor daemon | ✅ | Forge |

### 2.2 Что требует доработки ⚠️

| Проблема | Приоритет | Модуль |
|----------|-----------|--------|
| Нет выбора склада для товаров RISMENT | 🔴 Высокий | Модуль 1 |
| Неполная обработка полей товара | 🟡 Средний | Модуль 2 |
| Нет синхронизации заказов в RISMENT | 🟡 Средний | Модуль 3 |
| Нет синхронизации остатков | 🟡 Средний | Модуль 4 |
| Нет автодобавления аккаунтов МП | 🟢 Низкий | Модуль 5 |

---

## 3. Модуль 1: Выбор склада для товаров RISMENT

### 3.1 Описание

При настройке интеграции пользователь выбирает склад, куда будут попадать синхронизированные товары из RISMENT. Это позволяет:
- Вести раздельный учёт товаров на фулфилменте
- Корректно отображать остатки
- Понимать какие товары хранятся в RISMENT

### 3.2 Миграция

**Файл:** `database/migrations/xxxx_add_warehouse_to_integration_links.php`

```php
public function up(): void
{
    Schema::table('integration_links', function (Blueprint $table) {
        $table->unsignedBigInteger('warehouse_id')->nullable()->after('link_token');
        $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('set null');
    });
}

public function down(): void
{
    Schema::table('integration_links', function (Blueprint $table) {
        $table->dropForeign(['warehouse_id']);
        $table->dropColumn('warehouse_id');
    });
}
```

### 3.3 Модель

**Файл:** `app/Models/IntegrationLink.php`

```php
protected $fillable = [
    'user_id',
    'company_id',
    'external_system',
    'external_user_id',
    'link_token',
    'warehouse_id', // ← ДОБАВИТЬ
    'is_active',
    'linked_at',
];

public function warehouse(): BelongsTo
{
    return $this->belongsTo(Warehouse::class);
}
```

### 3.4 UI настройки интеграции

**Файл:** `resources/views/integrations/risment.blade.php`

```blade
<div class="card">
    <h2>Настройки RISMENT</h2>
    
    @if($link && $link->is_active)
        <div class="status-connected">
            ● Подключено ({{ $link->linked_at->format('d.m.Y H:i') }})
        </div>
        
        {{-- Выбор склада --}}
        <form action="{{ route('integrations.risment.update') }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="form-group">
                <label>Склад для товаров RISMENT</label>
                <select name="warehouse_id" class="form-select">
                    <option value="">-- Выберите склад --</option>
                    @foreach($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}" 
                                {{ $link->warehouse_id == $warehouse->id ? 'selected' : '' }}>
                            {{ $warehouse->name }}
                        </option>
                    @endforeach
                </select>
                <p class="help-text">
                    Все товары из RISMENT будут привязаны к этому складу
                </p>
            </div>
            
            <div class="form-group">
                <label>Настройки синхронизации</label>
                <label class="checkbox">
                    <input type="checkbox" name="sync_products" value="1" checked>
                    Синхронизировать товары
                </label>
                <label class="checkbox">
                    <input type="checkbox" name="sync_orders" value="1" checked>
                    Отправлять заказы в RISMENT
                </label>
                <label class="checkbox">
                    <input type="checkbox" name="sync_stock" value="1" checked>
                    Синхронизировать остатки
                </label>
            </div>
            
            <button type="submit" class="btn-primary">Сохранить</button>
        </form>
        
        <hr>
        
        <form action="{{ route('integrations.risment.disconnect') }}" method="POST">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn-danger">Отключить RISMENT</button>
        </form>
    @else
        {{-- Форма подключения --}}
        <form action="{{ route('integrations.risment.connect') }}" method="POST">
            @csrf
            <div class="form-group">
                <label>Токен подключения</label>
                <input type="text" name="link_token" placeholder="Введите токен из RISMENT" required>
                <p class="help-text">
                    Получите токен в личном кабинете RISMENT → Интеграции → SellerMind
                </p>
            </div>
            
            <div class="form-group">
                <label>Склад для товаров</label>
                <select name="warehouse_id" class="form-select">
                    <option value="">-- Выберите склад --</option>
                    @foreach($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                    @endforeach
                </select>
            </div>
            
            <button type="submit" class="btn-primary">Подключить</button>
        </form>
    @endif
</div>
```

### 3.5 Контроллер

**Файл:** `app/Http/Controllers/Web/IntegrationLinkController.php`

```php
public function update(Request $request)
{
    $request->validate([
        'warehouse_id' => 'nullable|exists:warehouses,id',
    ]);
    
    $link = IntegrationLink::where('user_id', auth()->id())
        ->where('external_system', 'risment')
        ->firstOrFail();
    
    $link->update([
        'warehouse_id' => $request->warehouse_id,
    ]);
    
    return redirect()->back()->with('success', 'Настройки сохранены');
}
```

### 3.6 Использование в обработчике товаров

**Файл:** `app/Console/Commands/ProcessRismentQueues.php`

```php
protected function onProductCreated(IntegrationLink $link, array $data): void
{
    // Использовать warehouse_id из настроек интеграции
    $warehouseId = $link->warehouse_id;
    
    $product = Product::updateOrCreate(
        ['company_id' => $link->company_id, 'risment_product_id' => $data['product_id']],
        [
            'name' => $data['name'],
            'article' => $data['article'],
            // ... другие поля
        ]
    );
    
    // При создании SKU — привязывать к складу
    foreach ($data['variants'] as $variantData) {
        $variant = ProductVariant::updateOrCreate(...);
        
        $sku = Sku::updateOrCreate(
            ['company_id' => $link->company_id, 'sku_code' => $variantData['sku']],
            [
                'product_id' => $product->id,
                'product_variant_id' => $variant->id,
                'warehouse_id' => $warehouseId, // ← Привязка к складу
                // ...
            ]
        );
    }
}
```

### 3.7 Промпт для Claude Code

```
Добавь выбор склада при интеграции с RISMENT.

1. Создай миграцию: добавить поле warehouse_id в таблицу integration_links
   - Foreign key на таблицу warehouses
   - nullable, on delete set null

2. Обнови модель IntegrationLink:
   - Добавить warehouse_id в fillable
   - Добавить связь warehouse()

3. Обнови страницу настройки интеграции RISMENT:
   - Добавить select для выбора склада
   - Показывать список складов компании

4. Обнови контроллер:
   - Сохранение warehouse_id
   - При подключении — сразу выбирать склад

5. В ProcessRismentQueues при создании товара/SKU — использовать warehouse_id из настроек

Найди таблицу и модель складов в проекте:
grep -r "warehouses" database/migrations --include="*.php" | head -5
```

---

## 4. Модуль 2: Полная обработка карточки товара

### 4.1 Описание

Обрабатывать ВСЕ поля карточки товара из RISMENT и сохранять в соответствующие поля SellerMind.

### 4.2 Маппинг полей (RISMENT → SellerMind)

| RISMENT (входящее) | SellerMind (сохранять) | Таблица |
|-------------------|------------------------|---------|
| `name` | `name` | products |
| `article` | `article` | products |
| `description` | `description` | products |
| `short_description` | `short_description` | products |
| `brand_name` | `brand_name` | products |
| `category` | category_id (найти/создать) | products |
| `is_active` | `is_active` | products |
| `images[]` | скачать и сохранить | product_images |
| **Варианты:** | | |
| `name` | `name` | product_variants |
| `sku` | `sku` | product_variants |
| `barcode` | `barcode` | product_variants |
| `price` | `price_default` | product_variants |
| `cost_price` | `purchase_price` | product_variants |
| `weight` | `weight` | product_variants |
| `length` | `length` | product_variants |
| `width` | `width` | product_variants |
| `height` | `height` | product_variants |

### 4.3 Обновление обработчика

**Файл:** `app/Console/Commands/ProcessRismentQueues.php`

```php
protected function onProductCreated(IntegrationLink $link, array $data): void
{
    $warehouseId = $link->warehouse_id;
    
    // Найти или создать категорию
    $categoryId = null;
    if (!empty($data['category'])) {
        $category = Category::firstOrCreate(
            ['company_id' => $link->company_id, 'name' => $data['category']],
            ['slug' => Str::slug($data['category'])]
        );
        $categoryId = $category->id;
    }
    
    // Создать/обновить товар
    $product = Product::updateOrCreate(
        ['company_id' => $link->company_id, 'risment_product_id' => $data['product_id']],
        [
            'name' => $data['name'],
            'article' => $data['article'] ?? null,
            'description' => $data['description'] ?? null,
            'short_description' => $data['short_description'] ?? null,
            'brand_name' => $data['brand_name'] ?? null,
            'category_id' => $categoryId,
            'is_active' => $data['is_active'] ?? true,
            'is_archived' => false,
        ]
    );
    
    // Обработать изображения
    if (!empty($data['images'])) {
        $this->syncProductImages($product, $data['images']);
    }
    
    // Обработать варианты
    foreach ($data['variants'] ?? [] as $variantData) {
        $variant = ProductVariant::updateOrCreate(
            ['product_id' => $product->id, 'sku' => $variantData['sku']],
            [
                'company_id' => $link->company_id,
                'name' => $variantData['name'] ?? $product->name,
                'barcode' => $variantData['barcode'] ?? null,
                'price_default' => $variantData['price'] ?? 0,
                'purchase_price' => $variantData['cost_price'] ?? 0,
                'weight' => $variantData['weight'] ?? null,
                'length' => $variantData['length'] ?? null,
                'width' => $variantData['width'] ?? null,
                'height' => $variantData['height'] ?? null,
                'risment_variant_id' => $variantData['risment_variant_id'],
                'is_active' => $variantData['is_active'] ?? true,
            ]
        );
        
        // Создать SKU
        Sku::updateOrCreate(
            ['company_id' => $link->company_id, 'sku_code' => $variantData['sku']],
            [
                'product_id' => $product->id,
                'product_variant_id' => $variant->id,
                'warehouse_id' => $warehouseId,
                'barcode_ean13' => $variantData['barcode'] ?? null,
                'is_active' => true,
            ]
        );
    }
    
    Log::info("Product synced from RISMENT", [
        'product_id' => $product->id,
        'risment_product_id' => $data['product_id'],
        'variants_count' => count($data['variants'] ?? []),
    ]);
}

protected function syncProductImages(Product $product, array $imageUrls): void
{
    foreach ($imageUrls as $index => $url) {
        try {
            // Скачать изображение
            $contents = file_get_contents($url);
            $filename = 'products/' . $product->id . '/' . basename($url);
            
            Storage::disk('public')->put($filename, $contents);
            
            $product->images()->updateOrCreate(
                ['path' => $filename],
                ['sort_order' => $index, 'is_main' => $index === 0]
            );
        } catch (\Exception $e) {
            Log::warning("Failed to download image", ['url' => $url, 'error' => $e->getMessage()]);
        }
    }
}
```

### 4.4 Промпт для Claude Code

```
Обнови обработку товаров из RISMENT для полной синхронизации.

В ProcessRismentQueues.php метод onProductCreated должен:

1. Обрабатывать ВСЕ поля товара:
   - name, article, description, short_description
   - brand_name, category (найти/создать категорию)
   - is_active
   - images[] (скачать и сохранить)

2. Обрабатывать ВСЕ поля вариантов:
   - name, sku, barcode
   - price (→ price_default), cost_price (→ purchase_price)
   - weight, length, width, height
   - risment_variant_id

3. Привязывать SKU к складу из настроек интеграции (warehouse_id)

4. Добавить логирование

Покажи текущий код onProductCreated и обнови его.
```

---

## 5. Модуль 3: Синхронизация заказов в RISMENT

### 5.1 Описание

Отправлять FBS заказы в RISMENT для фулфилмента. **Важно:** синхронизировать только заказы, которые содержат товары с `risment_product_id`.

### 5.2 Логика фильтрации

```php
public function shouldSyncToRisment(Order $order): bool
{
    // 1. Только FBS заказы
    if ($order->fulfillment_type !== 'fbs') {
        return false;
    }
    
    // 2. Должна быть активная связка с RISMENT
    $link = IntegrationLink::where('company_id', $order->company_id)
        ->where('external_system', 'risment')
        ->where('is_active', true)
        ->first();
    
    if (!$link) {
        return false;
    }
    
    // 3. Хотя бы один товар должен быть синхронизирован с RISMENT
    return $order->items()
        ->whereHas('product', fn($q) => $q->whereNotNull('risment_product_id'))
        ->exists();
}
```

### 5.3 Job для отправки заказа

**Файл:** `app/Jobs/Integration/SendOrderToRisment.php`

```php
<?php

namespace App\Jobs\Integration;

use App\Models\Order;
use App\Models\IntegrationLink;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class SendOrderToRisment
{
    public function __construct(
        protected int $orderId,
        protected string $event = 'order.created'
    ) {}

    public function handle(): void
    {
        $order = Order::with(['items.product', 'items.productVariant'])->find($this->orderId);
        
        if (!$order) {
            return;
        }
        
        // Проверяем условия
        if ($order->fulfillment_type !== 'fbs') {
            return;
        }
        
        $link = IntegrationLink::where('company_id', $order->company_id)
            ->where('external_system', 'risment')
            ->where('is_active', true)
            ->first();
        
        if (!$link) {
            return;
        }
        
        // Фильтруем только товары из RISMENT
        $rismentItems = $order->items->filter(function ($item) {
            return $item->product && $item->product->risment_product_id;
        });
        
        if ($rismentItems->isEmpty()) {
            return;
        }
        
        $payload = json_encode([
            'event' => $this->event,
            'timestamp' => now()->toIso8601String(),
            'link_token' => $link->link_token,
            'data' => [
                'sellermind_order_id' => $order->id,
                'marketplace' => $order->marketplace,
                'marketplace_order_id' => $order->marketplace_order_id,
                'fulfillment_type' => 'fbs',
                'status' => $order->status,
                'created_at' => $order->created_at->toIso8601String(),
                'items' => $rismentItems->map(function ($item) {
                    return [
                        'risment_product_id' => $item->product->risment_product_id,
                        'risment_variant_id' => $item->productVariant?->risment_variant_id,
                        'sku' => $item->sku,
                        'barcode' => $item->productVariant?->barcode,
                        'name' => $item->name,
                        'quantity' => $item->quantity,
                        'price' => $item->price,
                    ];
                })->values()->toArray(),
                'customer' => [
                    'name' => $order->customer_name,
                    'phone' => $order->customer_phone,
                    'address' => $order->shipping_address,
                ],
                'shipping' => [
                    'deadline' => $order->shipping_deadline?->toIso8601String(),
                ],
            ],
        ]);
        
        try {
            Redis::connection('integration')->rpush('risment:orders', $payload);
            Log::info("Order sent to RISMENT", ['order_id' => $order->id]);
        } catch (\Exception $e) {
            Log::error("Failed to send order to RISMENT", [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
```

### 5.4 Observer для заказов

**Файл:** `app/Observers/OrderRismentObserver.php`

```php
<?php

namespace App\Observers;

use App\Models\Order;
use App\Jobs\Integration\SendOrderToRisment;

class OrderRismentObserver
{
    public function created(Order $order): void
    {
        if ($this->shouldSync($order)) {
            (new SendOrderToRisment($order->id, 'order.created'))->handle();
        }
    }
    
    public function updated(Order $order): void
    {
        // Если статус изменился на "отменён"
        if ($order->wasChanged('status') && $order->status === 'cancelled') {
            if ($this->shouldSync($order)) {
                (new SendOrderToRisment($order->id, 'order.cancelled'))->handle();
            }
        }
    }
    
    protected function shouldSync(Order $order): bool
    {
        if ($order->fulfillment_type !== 'fbs') {
            return false;
        }
        
        // Проверяем есть ли товары с risment_product_id
        return $order->items()
            ->whereHas('product', fn($q) => $q->whereNotNull('risment_product_id'))
            ->exists();
    }
}
```

### 5.5 Регистрация Observer

**Файл:** `app/Providers/AppServiceProvider.php`

```php
public function boot(): void
{
    // ... существующие observer'ы
    
    Order::observe(OrderRismentObserver::class);
}
```

### 5.6 Промпт для Claude Code

```
Добавь синхронизацию FBS заказов в RISMENT.

1. Создай Job app/Jobs/Integration/SendOrderToRisment.php:
   - Проверять fulfillment_type === 'fbs'
   - Проверять наличие активной связки с RISMENT
   - Фильтровать только товары с risment_product_id
   - Отправлять в очередь risment:orders

2. Создай Observer app/Observers/OrderRismentObserver.php:
   - created() — отправить order.created
   - updated() — если статус стал 'cancelled', отправить order.cancelled

3. Зарегистрируй Observer в AppServiceProvider

4. Найди модель Order и убедись что есть связи items, product

Формат сообщения:
{
  "event": "order.created",
  "link_token": "xxx",
  "data": {
    "sellermind_order_id": 123,
    "marketplace": "wildberries",
    "items": [
      { "risment_product_id": 1, "sku": "XXX", "quantity": 2, "price": 150000 }
    ],
    "customer": { "name": "...", "phone": "...", "address": "..." }
  }
}
```

---

## 6. Модуль 4: Синхронизация остатков

### 6.1 Описание

Обрабатывать обновления остатков из RISMENT и обновлять остатки на складе в SellerMind.

### 6.2 Формат входящего сообщения

**Очередь:** `sellermind:stock`

```json
{
  "event": "stock.updated",
  "timestamp": "2026-02-04T12:00:00+05:00",
  "link_token": "xxx",
  "data": {
    "reason": "receiving_completed",
    "stocks": [
      {
        "risment_product_id": 1,
        "risment_variant_id": 1,
        "sku": "SHIRT-001-RED-M",
        "barcode": "4607123456789",
        "quantity": 150,
        "reserved": 10,
        "available": 140
      }
    ]
  }
}
```

### 6.3 Обработка в ProcessRismentQueues

**Файл:** `app/Console/Commands/ProcessRismentQueues.php`

```php
protected function handleStockEvent(array $message): void
{
    $link = IntegrationLink::where('link_token', $message['link_token'])
        ->where('is_active', true)
        ->first();
    
    if (!$link) {
        Log::warning('Stock update rejected: invalid link token');
        return;
    }
    
    $data = $message['data'];
    $warehouseId = $link->warehouse_id;
    
    foreach ($data['stocks'] ?? [] as $stockData) {
        // Найти товар по risment_product_id
        $product = Product::where('company_id', $link->company_id)
            ->where('risment_product_id', $stockData['risment_product_id'])
            ->first();
        
        if (!$product) {
            Log::warning('Stock update skipped: product not found', [
                'risment_product_id' => $stockData['risment_product_id']
            ]);
            continue;
        }
        
        // Найти вариант
        $variant = ProductVariant::where('product_id', $product->id)
            ->where(function ($q) use ($stockData) {
                $q->where('risment_variant_id', $stockData['risment_variant_id'])
                  ->orWhere('sku', $stockData['sku']);
            })
            ->first();
        
        if (!$variant) {
            continue;
        }
        
        // Обновить остатки на складе
        $sku = Sku::where('product_variant_id', $variant->id)
            ->where('warehouse_id', $warehouseId)
            ->first();
        
        if ($sku) {
            $sku->update([
                'quantity' => $stockData['quantity'],
                'reserved' => $stockData['reserved'] ?? 0,
            ]);
            
            Log::info('Stock updated from RISMENT', [
                'sku' => $stockData['sku'],
                'quantity' => $stockData['quantity'],
            ]);
        }
    }
}
```

### 6.4 Добавить очередь в worker

```php
protected function getQueues(): array
{
    return [
        'sellermind:products',
        'sellermind:stock',     // ← Добавить
        'sellermind:shipments',
        'sellermind:marketplaces',
    ];
}
```

### 6.5 Промпт для Claude Code

```
Добавь обработку обновления остатков из RISMENT.

1. В ProcessRismentQueues добавь очередь sellermind:stock в список

2. Добавь метод handleStockEvent:
   - Найти товар по risment_product_id
   - Найти вариант по risment_variant_id или sku
   - Обновить остатки в таблице SKU для склада из настроек интеграции

3. Добавь логирование

Формат входящего сообщения:
{
  "event": "stock.updated",
  "link_token": "xxx",
  "data": {
    "stocks": [
      { "risment_product_id": 1, "sku": "XXX", "quantity": 100, "reserved": 10 }
    ]
  }
}
```

---

## 7. Модуль 5: Автодобавление аккаунтов маркетплейсов

### 7.1 Описание

При получении токенов маркетплейсов из RISMENT — автоматически создавать аккаунты маркетплейсов в SellerMind.

### 7.2 Формат входящего сообщения

**Очередь:** `sellermind:marketplaces`

```json
{
  "event": "marketplace.created",
  "timestamp": "2026-02-04T12:00:00+05:00",
  "link_token": "xxx",
  "data": {
    "risment_credential_id": 1,
    "marketplace": "wildberries",
    "name": "WB Основной",
    "credentials": {
      "api_token": "eyJhbGciOiJIUzI1NiIsIn...",
      "supplier_id": "123456"
    },
    "is_active": true
  }
}
```

### 7.3 Обработка в ProcessRismentQueues

```php
protected function handleMarketplaceEvent(array $message): void
{
    $link = IntegrationLink::where('link_token', $message['link_token'])
        ->where('is_active', true)
        ->first();
    
    if (!$link) {
        return;
    }
    
    $data = $message['data'];
    
    match($message['event']) {
        'marketplace.created', 'marketplace.updated' => $this->createOrUpdateMarketplace($link, $data),
        'marketplace.deleted' => $this->deleteMarketplace($link, $data),
        default => null,
    };
}

protected function createOrUpdateMarketplace(IntegrationLink $link, array $data): void
{
    // Маппинг marketplace → модель аккаунта
    $accountClass = match($data['marketplace']) {
        'wildberries' => WildberriesAccount::class,
        'ozon' => OzonAccount::class,
        'uzum' => UzumAccount::class,
        'yandex_market' => YandexMarketAccount::class,
        default => null,
    };
    
    if (!$accountClass) {
        return;
    }
    
    $credentials = $data['credentials'];
    
    $account = $accountClass::updateOrCreate(
        [
            'company_id' => $link->company_id,
            'risment_credential_id' => $data['risment_credential_id'],
        ],
        [
            'name' => $data['name'],
            'api_token' => encrypt($credentials['api_token'] ?? ''),
            // Другие поля в зависимости от маркетплейса
            'is_active' => $data['is_active'],
            'source' => 'risment',
        ]
    );
    
    // Отправить подтверждение в RISMENT
    Redis::connection('integration')->rpush('risment:marketplace_confirm', json_encode([
        'event' => 'marketplace.confirmed',
        'timestamp' => now()->toIso8601String(),
        'link_token' => $link->link_token,
        'data' => [
            'risment_credential_id' => $data['risment_credential_id'],
            'sellermind_account_id' => $account->id,
        ],
    ]));
    
    Log::info('Marketplace account created from RISMENT', [
        'marketplace' => $data['marketplace'],
        'account_id' => $account->id,
    ]);
}
```

### 7.4 Миграции для связи с RISMENT

```php
// Для каждой таблицы аккаунтов маркетплейсов
Schema::table('wildberries_accounts', function (Blueprint $table) {
    $table->unsignedBigInteger('risment_credential_id')->nullable();
    $table->string('source')->default('manual'); // 'manual' или 'risment'
});
```

### 7.5 Промпт для Claude Code

```
Добавь автоматическое создание аккаунтов маркетплейсов из RISMENT.

1. В ProcessRismentQueues добавь очередь sellermind:marketplaces

2. Добавь метод handleMarketplaceEvent:
   - Определить модель аккаунта по marketplace (wildberries, ozon, uzum, yandex_market)
   - Создать/обновить аккаунт с токенами
   - Зашифровать токены через encrypt()
   - Отправить подтверждение в risment:marketplace_confirm

3. Создай миграции для добавления полей:
   - risment_credential_id
   - source ('manual' или 'risment')
   
   В таблицы: wildberries_accounts, ozon_accounts, uzum_accounts, yandex_market_accounts (или как они называются)

4. Найди модели аккаунтов маркетплейсов:
find app/Models -name "*Account*" | head -10
```

---

## 8. Redis очереди и события

### 8.1 Входящие очереди (RISMENT → SellerMind)

| Очередь | Событие | Описание |
|---------|---------|----------|
| `sellermind:products` | `product.created` | Товар создан |
| `sellermind:products` | `product.updated` | Товар обновлён |
| `sellermind:products` | `product.deleted` | Товар удалён |
| `sellermind:stock` | `stock.updated` | Остатки изменены |
| `sellermind:shipments` | `order.shipped` | Заказ отгружен |
| `sellermind:shipments` | `order.delivered` | Заказ доставлен |
| `sellermind:marketplaces` | `marketplace.created` | Аккаунт МП создан |
| `sellermind:marketplaces` | `marketplace.updated` | Аккаунт МП обновлён |
| `sellermind:marketplaces` | `marketplace.deleted` | Аккаунт МП удалён |

### 8.2 Исходящие очереди (SellerMind → RISMENT)

| Очередь | Событие | Описание |
|---------|---------|----------|
| `risment:orders` | `order.created` | Новый FBS заказ |
| `risment:orders` | `order.cancelled` | Заказ отменён |
| `risment:stock` | `stock.marketplace_updated` | Остатки на МП изменены |
| `risment:link` | `link.confirm` | Связка подтверждена |
| `risment:marketplace_confirm` | `marketplace.confirmed` | Аккаунт МП подтверждён |

### 8.3 Конфигурация Redis

```php
// config/database.php
'redis' => [
    'integration' => [
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'password' => env('REDIS_PASSWORD'),
        'port' => env('REDIS_PORT', 6379),
        'database' => 2,
        'prefix' => '',
    ],
],
```

---

## 9. План реализации

### 9.1 Приоритеты

| # | Модуль | Приоритет | Время |
|---|--------|-----------|-------|
| 1 | Выбор склада | 🔴 Высокий | 2-3 часа |
| 2 | Полная обработка карточки | 🔴 Высокий | 3-4 часа |
| 3 | Синхронизация заказов | 🟡 Средний | 4-6 часов |
| 4 | Синхронизация остатков | 🟡 Средний | 3-4 часа |
| 5 | Автодобавление аккаунтов МП | 🟢 Низкий | 6-8 часов |

### 9.2 Чеклист готовности

- [ ] Модуль 1: Выбор склада работает
- [ ] Модуль 2: Полная обработка карточки товара
- [ ] Модуль 3: Синхронизация заказов в RISMENT
- [ ] Модуль 4: Синхронизация остатков работает
- [ ] Модуль 5: Автодобавление аккаунтов МП
- [ ] Worker стабильно работает (Forge Daemon)
- [ ] Логирование настроено
- [ ] Тестирование проведено

---

**Конец документа**
