# Smart Promotions Guide

**Version:** 1.0
**Date:** 2026-01-11

---

## Overview

Smart Promotions автоматически выявляет медленно движущиеся товары и применяет скидки для ускорения оборачиваемости. Система снижает неликвид на 25% и помогает освободить складские мощности.

**Ключевые возможности:**
- 🤖 Автоматическое определение медленных товаров
- 📊 Умные скидки на основе оборачиваемости
- ⏰ Уведомления за 3 дня до окончания акции
- 📈 Аналитика эффективности акций
- 💰 ROI tracking для каждого товара

---

## Quick Start

### 1. Автоматическое создание акции

Самый простой способ:

```bash
php artisan promotions:process --create-auto
```

Система автоматически:
1. Найдет медленно движущиеся товары (>30 дней без продаж)
2. Рассчитает оптимальные скидки (15-50%)
3. Создаст акцию на 30 дней
4. Применит скидки к товарам

### 2. Через UI

1. Перейдите в `/promotions`
2. Нажмите **"🔍 Найти медленные товары"**
3. Подтвердите создание автоакции
4. Система создаст акцию и покажет результаты

---

## Критерии медленно движущихся товаров

**По умолчанию:**
- ≥ 30 дней без продаж
- ≥ 5 единиц на складе
- ≥ 100 ₽ базовая цена
- Оборачиваемость < 0.1 единиц/день

**Настраиваемые параметры:**

```php
$promotionService->detectSlowMovingProducts($companyId, [
    'min_days_no_sale' => 30,    // Минимум дней без продаж
    'min_stock' => 5,             // Минимальный остаток
    'min_price' => 100,           // Минимальная цена
]);
```

---

## Расчет скидок

Система рассчитывает рекомендуемую скидку на основе срочности:

| Дней без продаж | Рекомендуемая скидка |
|-----------------|----------------------|
| 180+            | 50% (очень срочно)   |
| 90-179          | 35% (срочно)         |
| 60-89           | 25% (умеренно)       |
| 30-59           | 15% (слегка)         |

**Формула дисконтированной цены:**

- **Percentage:** `discounted_price = original_price × (1 - discount_value / 100)`
- **Fixed amount:** `discounted_price = original_price - discount_value`

---

## API Reference

### Получить все акции

```http
GET /api/promotions
```

**Query Parameters:**
- `status` (optional): `active`, `expired`, `upcoming`
- `is_automatic` (optional): `true`, `false`

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Автоакция 11.01.2026",
      "discount_value": 35,
      "type": "percentage",
      "products_count": 45,
      "start_date": "2026-01-11T10:00:00Z",
      "end_date": "2026-02-10T10:00:00Z",
      "is_active": true,
      "is_automatic": true,
      "stats": {
        "total_units_sold": 120,
        "total_revenue": 45600,
        "average_roi": 245
      }
    }
  ]
}
```

### Создать акцию

```http
POST /api/promotions
```

**Body:**
```json
{
  "name": "Летняя распродажа",
  "description": "Скидки на летние товары",
  "type": "percentage",
  "discount_value": 25,
  "start_date": "2026-06-01T00:00:00Z",
  "end_date": "2026-06-30T23:59:59Z",
  "product_variant_ids": [1, 2, 3, 4, 5]
}
```

### Найти медленные товары

```http
GET /api/promotions/detect-slow-moving
```

**Response:**
```json
{
  "count": 45,
  "products": [
    {
      "variant": {...},
      "days_since_last_sale": 67,
      "turnover_rate": 0.05,
      "stock": 23,
      "recommended_discount": 25
    }
  ]
}
```

### Создать автоакцию

```http
POST /api/promotions/create-automatic
```

**Body:**
```json
{
  "duration_days": 30,
  "max_discount": 50,
  "apply_immediately": true
}
```

### Применить/отменить акцию

```http
POST /api/promotions/{id}/apply
POST /api/promotions/{id}/remove
```

---

## Уведомления

### Уведомление об истекающей акции

Отправляется за **3 дня** до окончания (настраивается).

**Пример в Telegram:**
```
⏰ Внимание: Акция заканчивается!

Автоакция 11.01.2026
Умные скидки на медленно движущиеся товары

⏰ Осталось: 3 дня
📅 Конец: 10.02.2026 10:00
🏷️ Товаров: 45
💰 Скидка: 35%

Проверьте результаты и решите, продлить или завершить акцию.
```

**Настройка уведомлений:**

```php
$promotion->update([
    'notify_before_expiry' => true,
    'notify_days_before' => 3,  // Дней до окончания
]);
```

---

## Artisan Commands

### Автоматическая обработка

Создает автоакции и отправляет уведомления:

```bash
php artisan promotions:process
```

**Опции:**
- `--create-auto` — только создание автоакций
- `--notify-expiring` — только уведомления об истечении
- `--company=123` — для конкретной компании

**Примеры:**

```bash
# Создать автоакции для всех компаний
php artisan promotions:process --create-auto

# Отправить уведомления об истекающих акциях
php artisan promotions:process --notify-expiring

# Обработать конкретную компанию
php artisan promotions:process --company=5

# Полная обработка (по умолчанию)
php artisan promotions:process
```

### Настройка Cron

Добавьте в `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // Создание автоакций каждый понедельник в 9:00
    $schedule->command('promotions:process --create-auto')
        ->weekly()
        ->mondays()
        ->at('09:00');

    // Проверка истекающих акций каждый день в 10:00
    $schedule->command('promotions:process --notify-expiring')
        ->daily()
        ->at('10:00');
}
```

---

## Метрики эффективности

### ROI (Return on Investment)

**Формула:**
```
ROI = (revenue_generated / total_discount_given) × 100
```

**Интерпретация:**
- **ROI > 150%** — Хорошо (окупилась с прибылью)
- **ROI 100-150%** — Приемлемо (окупилась)
- **ROI < 100%** — Убыток (не окупилась)

### Критерии успешной акции

Акция считается успешной, если:
- Продано ≥ 5 единиц товара
- ROI > 150%
- Оборачиваемость выросла в 2+ раза

### Получить статистику

```http
GET /api/promotions/{id}/stats
```

**Response:**
```json
{
  "total_products": 45,
  "total_units_sold": 120,
  "total_revenue": 45600,
  "total_discount_given": 18240,
  "average_roi": 250,
  "performing_well_count": 38
}
```

---

## Database Schema

### `promotions` Table

```sql
CREATE TABLE promotions (
    id BIGINT PRIMARY KEY,
    company_id BIGINT FOREIGN KEY,
    created_by BIGINT FOREIGN KEY,
    name VARCHAR(255),
    description TEXT,
    type ENUM('percentage', 'fixed_amount'),
    discount_value DECIMAL(10,2),
    start_date TIMESTAMP,
    end_date TIMESTAMP,
    is_active BOOLEAN,
    is_automatic BOOLEAN,
    conditions JSON,
    notify_before_expiry BOOLEAN,
    notify_days_before INT,
    expiry_notification_sent_at TIMESTAMP,
    products_count INT,
    total_revenue_impact DECIMAL(12,2),
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP
);
```

### `promotion_products` Table

```sql
CREATE TABLE promotion_products (
    id BIGINT PRIMARY KEY,
    promotion_id BIGINT FOREIGN KEY,
    product_variant_id BIGINT FOREIGN KEY,
    original_price DECIMAL(10,2),
    discounted_price DECIMAL(10,2),
    discount_amount DECIMAL(10,2),
    units_sold INT,
    revenue_generated DECIMAL(12,2),
    days_since_last_sale INT,
    stock_at_promotion_start INT,
    turnover_rate_before DECIMAL(8,4),
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

---

## Best Practices

### 1. Регулярный мониторинг

Запускайте автоматическое создание акций **еженедельно**:

```bash
0 9 * * 1 php /path/to/artisan promotions:process --create-auto
```

### 2. Настройка под бизнес

Адаптируйте критерии под ваш бизнес:

```php
// Для быстро оборачиваемых товаров
$criteria = [
    'min_days_no_sale' => 14,  // Меньше срок
    'min_stock' => 10,
    'min_price' => 50,
];

// Для дорогих товаров
$criteria = [
    'min_days_no_sale' => 60,  // Больше срок
    'min_stock' => 3,
    'min_price' => 5000,
];
```

### 3. Анализ результатов

Регулярно проверяйте ROI:

```php
$stats = $promotionService->getPromotionStats($promotion);

if ($stats['average_roi'] > 150) {
    // Акция эффективна, можно продлить
    $promotion->update(['end_date' => now()->addDays(14)]);
}
```

### 4. A/B тестирование скидок

Создайте несколько акций с разными скидками:

- Группа A: 15% скидка
- Группа B: 25% скидка
- Группа C: 35% скидка

Сравните ROI и выберите оптимальную.

---

## Troubleshooting

### Акция не создается автоматически

**Проблема:** `promotions:process --create-auto` не создает акций

**Решения:**
1. Проверьте, что нет активной автоакции:
   ```php
   Promotion::automatic()->active()->get();
   ```

2. Проверьте наличие медленных товаров:
   ```php
   $slow = $promotionService->detectSlowMovingProducts($companyId);
   dd($slow->count());
   ```

3. Снизьте критерии:
   ```php
   'min_days_no_sale' => 14,  // Вместо 30
   'min_stock' => 3,          // Вместо 5
   ```

### Уведомления не приходят

1. Проверьте настройки акции:
   ```php
   $promotion->notify_before_expiry; // true?
   $promotion->expiry_notification_sent_at; // null?
   ```

2. Проверьте работу очередей:
   ```bash
   php artisan queue:work
   ```

3. Проверьте настройки пользователя:
   ```php
   $user->notificationSettings->notify_price_changes; // true?
   ```

### Скидки не применяются

**Проблема:** Цены не обновляются после создания акции

**Решение:**
```php
// Вручную применить акцию
$promotionService->applyPromotion($promotion);

// Проверить что цены обновились
$variant = ProductVariant::find($variantId);
echo $variant->price; // Должна быть discounted_price
```

---

## Performance

### Оптимизация для больших объемов

Если >10,000 товаров:

```php
// Используйте батчинг
$variants->chunk(1000, function ($chunk) use ($promotionService) {
    foreach ($chunk as $variant) {
        $promotionService->applyPromotionToVariant($promotion, $variant);
    }
});

// Или используйте массовое обновление
DB::table('product_variants')
    ->whereIn('id', $variantIds)
    ->update(['price' => DB::raw('price * 0.7')]); // 30% скидка
```

---

## Roadmap

**Planned Features:**

- 📊 Графики эффективности акций
- 🎯 Персонализированные скидки
- 📧 Email digest с результатами акций
- 🤖 AI-рекомендации по оптимальным скидкам
- 📱 Push-уведомления
- 🔄 Автопродление успешных акций
- 📈 Прогнозирование ROI

---

## Support

- Email: [support@sellermind.ai](mailto:support@sellermind.ai)
- Telegram: [@sellermind_support](https://t.me/sellermind_support)

---

**Last Updated:** 2026-01-11
**Version:** 1.0
**Maintained by:** SellerMind AI Team
