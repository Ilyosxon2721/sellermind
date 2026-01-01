# ✅ Статус интеграции Wildberries API

**Дата обновления:** 29 ноября 2025, 00:11
**Статус:** Требуется настройка токенов

---

## 🎯 Что исправлено

### 1. ✅ Миграция на новые API домены
- **БЫЛО:** `suppliers-api.wildberries.ru` (устарело, не работает)
- **СТАЛО:** `marketplace-api.wildberries.ru`
- **Файл:** [config/marketplaces.php](../config/marketplaces.php)

### 2. ✅ Обновлены API endpoints
| Метод | Старый endpoint | Новый endpoint | Статус |
|-------|----------------|----------------|--------|
| Ping | `/public/api/v1/info` | `/api/v3/orders?limit=1` | ✅ |
| Test Connection | `/public/api/v1/info` | `/api/v3/orders?limit=1` | ✅ |
| Заказы | `/api/v3/orders` | `/api/v3/orders` | ✅ |
| Цены | `/public/api/v1/prices` | `/api/v1/prices` | ✅ |

**Файл:** [app/Services/Marketplaces/WildberriesClient.php](../app/Services/Marketplaces/WildberriesClient.php)

### 3. ✅ Правильный формат параметров для Orders API
- Используется `limit` (1-1000)
- Используется `next` для пагинации
- `dateFrom`/`dateTo` в формате Unix timestamp (секунды)
- Ограничение периода максимум 30 дней

**Файл:** [WildberriesClient.php:167-219](../app/Services/Marketplaces/WildberriesClient.php#L167-L219)

### 4. ✅ Умный выбор токенов по категории API
Система автоматически определяет, какой токен использовать, основываясь на `base_url`:
- `marketplace-api.wildberries.ru` → `wb_marketplace_token`
- `content-api.wildberries.ru` → `wb_content_token`
- `discounts-prices-api.wildberries.ru` → `wb_prices_token`
- `statistics-api.wildberries.ru` → `wb_statistics_token`
- Fallback: `api_key`

**Файл:** [MarketplaceHttpClient.php:122-139](../app/Services/Marketplaces/MarketplaceHttpClient.php#L122-L139)

### 5. ✅ Расширена модель для хранения WB токенов
Добавлены поля в `MarketplaceAccount`:
- `wb_marketplace_token` (для заказов, остатков)
- `wb_content_token` (для товаров)
- `wb_prices_token` (для цен)
- `wb_statistics_token` (для статистики)

Все токены автоматически шифруются Laravel Crypt.

**Файл:** [MarketplaceAccount.php:366-394](../app/Models/MarketplaceAccount.php#L366-L394)

### 6. ✅ Исправлена БД для больших токенов
Изменён тип поля `api_key` с `varchar(255)` на `text` для поддержки зашифрованных токенов (~600+ символов).

**Миграция:** [2025_11_28_204751_change_api_key_to_text_in_marketplace_accounts.php](../database/migrations/2025_11_28_204751_change_api_key_to_text_in_marketplace_accounts.php)

### 7. ✅ Исправлена фронтенд аутентификация
- Исправлен доступ к Alpine.js persist token (`_x_auth_token`)
- Добавлена функция `getToken()` с fallback цепочкой
- Добавлена функция `getAuthHeaders()`
- Добавлено ожидание инициализации Alpine store

**Файлы:**
- [marketplace/index.blade.php](../resources/views/pages/marketplace/index.blade.php)
- [marketplace/wb-settings.blade.php](../resources/views/pages/marketplace/wb-settings.blade.php)

---

## 🔧 Результаты тестирования

### Текущее состояние (29.11.2025 00:45)

```bash
$ php public/test-wb-sync.php

✅ Found WB account ID: 2
✅ All 4 category tokens present
📡 Testing connection...
✅ Connection successful!
📦 Fetching orders...
✅ Orders fetched: 0 (нет новых заказов)

✅ Test completed
```

**Статус:** ✅ **ВСЕ РАБОТАЕТ!**
- ✅ `api_key` - **настроен**
- ✅ `wb_marketplace_token` - **настроен**
- ✅ `wb_content_token` - **настроен**
- ✅ `wb_prices_token` - **настроен**
- ✅ `wb_statistics_token` - **настроен**

---

## 📋 Что нужно сделать

### ⚠️ ВАЖНО: Получить и сохранить правильные токены

Wildberries требует **отдельные токены для каждой категории API**. Один общий `api_key` больше не работает.

#### Шаги для настройки:

1. **Войти в личный кабинет Wildberries** ([seller.wildberries.ru](https://seller.wildberries.ru))

2. **Создать API токены** в разделе "Настройки" → "Доступ к API":
   - **Токен для Marketplace API** (заказы, остатки)
   - **Токен для Content API** (товары, карточки)
   - **Токен для Prices API** (цены, скидки)
   - **Токен для Statistics API** (статистика, аналитика)

3. **Сохранить токены в SellerMind AI:**

   **Вариант A: Через интерфейс**
   - Перейти в раздел "Маркетплейсы"
   - Выбрать аккаунт Wildberries
   - Нажать "Настройки"
   - Вставить каждый токен в соответствующее поле
   - Сохранить

   **Вариант B: Через Tinker (для разработки)**
   ```php
   php artisan tinker

   $account = App\Models\MarketplaceAccount::find(2);
   $account->wb_marketplace_token = 'ВАШ_ТОКЕН_MARKETPLACE';
   $account->wb_content_token = 'ВАШ_ТОКЕН_CONTENT';
   $account->wb_prices_token = 'ВАШ_ТОКЕН_PRICES';
   $account->wb_statistics_token = 'ВАШ_ТОКЕН_STATISTICS';
   $account->save();
   ```

4. **Проверить подключение:**
   ```bash
   php public/test-wb-sync.php
   ```

---

## 📊 Структура WB API v3

### Домены и их назначение

```
┌─────────────────────────────────────────────────────────┐
│ marketplace-api.wildberries.ru                          │
├─────────────────────────────────────────────────────────┤
│ • GET /api/v3/orders              - список заказов      │
│ • PUT /api/v3/stocks/{warehouse}  - обновление остатков │
│ • Токен: wb_marketplace_token                           │
└─────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────┐
│ content-api.wildberries.ru                              │
├─────────────────────────────────────────────────────────┤
│ • POST /content/v2/get/cards/list - список карточек     │
│ • POST /content/v2/cards/upload   - создание карточки   │
│ • POST /content/v2/cards/update   - обновление карточки │
│ • Токен: wb_content_token                               │
└─────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────┐
│ discounts-prices-api.wildberries.ru                     │
├─────────────────────────────────────────────────────────┤
│ • POST /api/v1/prices             - обновление цен      │
│ • Токен: wb_prices_token                                │
└─────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────┐
│ statistics-api.wildberries.ru                           │
├─────────────────────────────────────────────────────────┤
│ • Статистика и аналитика                                │
│ • Токен: wb_statistics_token                            │
└─────────────────────────────────────────────────────────┘
```

---

## 🔗 Полезные ссылки

- [Официальная документация WB API](https://openapi.wb.ru/)
- [Orders API (FBS)](https://openapi.wb.ru/marketplace/api/ru/#tag/Sborka-zakaza)
- [Dev Portal Wildberries](https://dev.wildberries.ru/)
- [Миграционное руководство](WB_API_MIGRATION.md)

---

## ✅ Чек-лист готовности

- [x] Обновлён `base_url` на `marketplace-api.wildberries.ru`
- [x] Добавлены поля для категорийных токенов в БД
- [x] Реализован автоматический выбор токена по домену
- [x] Обновлены endpoints на API v3
- [x] Параметры запросов соответствуют документации WB
- [x] Исправлена аутентификация фронтенда
- [ ] **Получены и сохранены токены от Wildberries** ⚠️
- [ ] **Протестирована синхронизация заказов**
- [ ] **Протестирована синхронизация цен**
- [ ] **Протестирована синхронизация остатков**

---

## 🚀 Следующие шаги

1. **Получить токены от Wildberries** (см. раздел выше)
2. Сохранить токены в систему
3. Запустить тест: `php public/test-wb-sync.php`
4. При успехе: протестировать синхронизацию через UI
5. При ошибках: проверить логи в `storage/logs/laravel.log`

---

**Статус:** 🟡 Система готова, требуется настройка токенов от пользователя
