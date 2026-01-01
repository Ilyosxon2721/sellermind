# 🔄 Миграция на новые API Wildberries

**Дата:** 28 ноября 2025
**Статус:** ✅ ОБНОВЛЕНО

---

## 📋 Проблема

Wildberries отключил старый домен `suppliers-api.wildberries.ru` и перевёл API на новые домены с разделением по категориям.

### ❌ Старая архитектура (УСТАРЕЛА):
```
https://suppliers-api.wildberries.ru
└── все API endpoints
```

### ✅ Новая архитектура (АКТУАЛЬНА):
```
https://marketplace-api.wildberries.ru       - Заказы, остатки
https://content-api.wildberries.ru           - Товары, карточки
https://discounts-prices-api.wildberries.ru  - Цены, скидки
https://statistics-api.wildberries.ru        - Статистика, аналитика
```

---

## 🔧 Что изменено

### 1. Конфигурация (config/marketplaces.php)

**БЫЛО:**
```php
'wildberries' => [
    'base_url' => 'https://suppliers-api.wildberries.ru',  // ❌ Устарело
    'content_url' => 'https://content-api.wildberries.ru',
    'statistics_url' => 'https://statistics-api.wildberries.ru',
],
```

**СТАЛО:**
```php
'wildberries' => [
    'base_url' => 'https://marketplace-api.wildberries.ru',      // ✅ Обновлено
    'content_url' => 'https://content-api.wildberries.ru',
    'statistics_url' => 'https://statistics-api.wildberries.ru',
    'discounts_prices_url' => 'https://discounts-prices-api.wildberries.ru',  // ✅ Добавлено
],
```

### 2. Маппинг endpoints

| Метод | Старый URL | Новый URL | Категория |
|-------|-----------|-----------|-----------|
| Инфо продавца | `/public/api/v1/info` | `/api/v3/supplier/info` | marketplace |
| Заказы | `/api/v3/orders` | `/api/v3/orders` | marketplace |
| Цены | `/public/api/v1/prices` | `/api/v1/prices` | discounts-prices |
| Карточки товаров | `/content/v2/get/cards/list` | `/content/v2/get/cards/list` | content |
| Остатки | `/api/v3/stocks/{warehouse}` | `/api/v3/stocks/{warehouse}` | marketplace |

---

## 📊 Статус миграции

### ✅ Обновлено:
1. **config/marketplaces.php** - изменён base_url
2. **Документация** - создан WB_API_MIGRATION.md

### 🔄 Требуется обновление:
1. **WildberriesClient.php** - нужно использовать разные base_url для разных категорий запросов
2. **MarketplaceHttpClient.php** - добавить поддержку выбора URL по категории

---

## 🎯 План дальнейших действий

### Вариант 1: Простое решение (рекомендуется)
Обновить все endpoint'ы в `WildberriesClient.php`:

```php
// Для заказов и остатков
$this->http->get($account, '/api/v3/orders', $params);
// Используется base_url: marketplace-api.wildberries.ru

// Для цен
$this->http->post($account, '/api/v1/prices', $priceUpdates, 'discounts_prices');
// Используется discounts_prices_url

// Для товаров
$this->http->post($account, '/content/v2/get/cards/list', $params, 'content');
// Используется content_url
```

### Вариант 2: Продвинутое решение
Создать `WildberriesHttpClient` с автоматическим выбором правильного домена по endpoint:

```php
class WildberriesHttpClient
{
    protected array $baseUrls = [
        'marketplace' => 'https://marketplace-api.wildberries.ru',
        'content' => 'https://content-api.wildberries.ru',
        'discounts_prices' => 'https://discounts-prices-api.wildberries.ru',
        'statistics' => 'https://statistics-api.wildberries.ru',
    ];

    public function get($account, $path, $params = [])
    {
        $category = $this->getCategoryByPath($path);
        $baseUrl = $this->baseUrls[$category];
        // ... request logic
    }

    protected function getCategoryByPath($path): string
    {
        if (str_starts_with($path, '/api/v3/orders') ||
            str_starts_with($path, '/api/v3/stocks')) {
            return 'marketplace';
        }

        if (str_starts_with($path, '/content/')) {
            return 'content';
        }

        if (str_starts_with($path, '/api/v1/prices')) {
            return 'discounts_prices';
        }

        if (str_starts_with($path, '/api/v1/supplier')) {
            return 'statistics';
        }

        return 'marketplace'; // default
    }
}
```

---

## 🔗 Ссылки

- [Документация WB API](https://openapi.wb.ru/)
- [Новые домены API](https://dev.wildberries.ru/)
- [Миграционное руководство WB](https://openapi.wb.ru/#tag/Marketplace)

---

## ✅ Проверка работоспособности

После миграции проверьте:

```bash
# 1. Проверка доступности нового API
curl -I https://marketplace-api.wildberries.ru
# Должно вернуть: HTTP/2 401 (это нормально, нужна авторизация)

# 2. Тест через Laravel
php artisan tinker
>>> $account = App\Models\MarketplaceAccount::find(2);
>>> $client = app(App\Services\Marketplaces\WildberriesClient::class);
>>> $client->ping($account);
```

---

**Обновлено:** 28.11.2025, 22:00
**Статус:** Конфигурация обновлена, код требует доработки
