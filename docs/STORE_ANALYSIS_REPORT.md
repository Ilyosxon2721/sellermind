# Аналитический отчёт: Раздел «Интернет-магазин» (Store Builder)

> **Дата:** 2026-04-02
> **Аналитик:** Claude Code Autopilot
> **Версия проекта:** SellerMind AI 1.0

---

## 1. Общее описание модуля

**Store Builder** — подсистема SellerMind, позволяющая пользователям создавать и управлять
собственными интернет-магазинами с публичной витриной. Магазин интегрирован с внутренней
системой складского учёта, продаж и уведомлений.

### Два уровня архитектуры:

| Уровень | Описание | Префикс маршрутов |
|---------|----------|-------------------|
| **Admin Panel** (Store Builder) | Управление магазинами через внутреннюю панель SellerMind | `/my-store/*` (web), `/api/store/*` (API) |
| **Storefront** (Публичная витрина) | Открытый интернет-магазин для покупателей | `/store/{slug}/*` |

---

## 2. Структура модуля

### 2.1 Модели (12 штук)

| Модель | Таблица | Описание |
|--------|---------|----------|
| `Store` | `stores` | Основная сущность магазина |
| `StoreTheme` | `store_themes` | Тема оформления (шаблон, цвета, hero-секция) |
| `StoreBanner` | `store_banners` | Баннеры/карусель |
| `StoreCategory` | `store_categories` | Категории товаров магазина |
| `StoreProduct` | `store_products` | Привязка товаров SellerMind к витрине |
| `StoreDeliveryMethod` | `store_delivery_methods` | Способы доставки |
| `StorePaymentMethod` | `store_payment_methods` | Способы оплаты |
| `StoreOrder` | `store_orders` | Заказы покупателей |
| `StoreOrderItem` | `store_order_items` | Позиции заказов |
| `StorePage` | `store_pages` | Статические страницы (О нас, Контакты и т.д.) |
| `StorePromocode` | `store_promocodes` | Промокоды |
| `StoreAnalytics` | `store_analytics` | Ежедневная аналитика (визиты, конверсия, выручка) |

### 2.2 Контроллеры

**Admin (9 контроллеров):**

| Контроллер | Функционал |
|-----------|-----------|
| `StoreAdminController` | CRUD магазинов |
| `StoreThemeController` | Настройка темы |
| `StoreBannerController` | Управление баннерами + загрузка изображений |
| `StoreCatalogController` | Каталог товаров и категорий, синхронизация |
| `StoreDeliveryController` | Способы доставки |
| `StorePaymentController` | Способы оплаты |
| `StoreOrderController` | Заказы + статистика |
| `StorePageController` | Статические страницы |
| `StoreAnalyticsController` | Аналитика за период |

**Storefront (5 контроллеров):**

| Контроллер | Функционал |
|-----------|-----------|
| `StorefrontController` | Главная страница, статические страницы, wishlist |
| `CatalogController` | Каталог с фильтрацией, поиск, карточка товара |
| `CartController` | Корзина (сессионная) + API |
| `CheckoutController` | Оформление заказа, быстрый заказ (1 клик) |
| `PaymentController` | Оплата Click/Payme + оффлайн-методы |

### 2.3 Сервисы

| Сервис | Описание |
|--------|----------|
| `StoreOrderService` | Синхронизация заказов с внутренней системой Sale, резервирование остатков, уведомления |

### 2.4 Шаблоны витрины (5 тем)

Каждая тема включает 8 страниц (home, catalog, product, cart, checkout, order, payment-success, payment-fail, page):

- **default** — универсальная тема
- **boutique** — для fashion/бутиков
- **grocery** — для продуктовых магазинов
- **minimal** — минималистичная
- **tech** — для техники/электроники

**Итого:** 40+ Blade-шаблонов витрины + 8 компонентов (header, footer, wishlist, quick-view, lightbox, buy-one-click, recently-viewed)

### 2.5 Миграции (17 штук)

Основные (12): stores, store_themes, store_banners, store_categories, store_products, store_delivery_methods, store_payment_methods, store_orders, store_order_items, store_pages, store_promocodes, store_analytics

Дополнительные (5): text_color баннеров, store_type для sales, custom_old_price для store_products, display_mode для баннеров, store + sale sources для KPI

---

## 3. Функциональный анализ

### 3.1 Реализованные функции ✅

| Функция | Статус | Качество кода |
|---------|--------|---------------|
| Создание/редактирование магазинов | ✅ Готово | Хорошо — валидация, company scoping |
| Настройка темы (5 шаблонов) | ✅ Готово | Хорошо — resolvedTemplate() с fallback |
| Управление баннерами + карусель | ✅ Готово | Хорошо — drag & drop позиционирование |
| Каталог товаров (фильтры, поиск, сортировка) | ✅ Готово | Хорошо — escapeLike, пагинация |
| Карточка товара с вариантами | ✅ Готово | Хорошо — JSON вариантов для Alpine.js |
| Корзина (сессионная) | ✅ Готово | Хорошо — поддержка вариантов |
| Оформление заказа | ✅ Готово | Хорошо — DB::transaction, валидация |
| Быстрый заказ (1 клик) | ✅ Готово | Хорошо |
| Промокоды | ✅ Готово | Хорошо — валидация, лимиты |
| Интеграция Click/Payme | ✅ Готово | Средне — см. замечания |
| Синхронизация с SellerMind Sales | ✅ Готово | Хорошо — non-blocking |
| Управление доставкой/оплатой | ✅ Готово | Хорошо |
| Статические страницы | ✅ Готово | Хорошо |
| Аналитика (визиты, конверсия, выручка) | ✅ Готово | Средне — см. замечания |
| Wishlist (избранное) | ✅ Готово | На клиенте (localStorage) |
| Недавно просмотренные товары | ✅ Готово | На клиенте |
| Quick View модалка | ✅ Готово | |
| SEO (мета-теги) | ✅ Готово | |
| Maintenance mode | ✅ Готово | |
| Sidebar ссылка «Мой магазин» | ✅ Готово | |
| Уведомления о заказах (Telegram) | ✅ Готово | NewStoreOrderNotification |

### 3.2 Архитектурные решения

**Сильные стороны:**

1. **Чёткое разделение Admin/Storefront** — разные контроллеры, маршруты, middleware
2. **Non-blocking интеграция с SellerMind** — ошибки синхронизации Sale не блокируют заказ покупателя
3. **Company scoping** — HasCompanyScope trait везде в Admin-контроллерах
4. **5 готовых тем** — хорошая гибкость для пользователей
5. **Валидация на всех входах** — $request->validate() во всех методах
6. **Промокоды** — calculateDiscount(), isValid(), usage tracking
7. **Трекинг аналитики fire-and-forget** — не замедляет UX

---

## 4. Выявленные проблемы и риски

### 4.1 Критические 🔴

| # | Проблема | Где | Риск |
|---|---------|-----|------|
| S-1 | **Нет тестов для модуля Store** | `tests/` | 0 тестов для 14 контроллеров, 12 моделей и 1 сервиса. Регрессии не отслеживаются |
| S-2 | **IDOR в PaymentController** | `PaymentController::resolveOrderFromRequest()` | order_id из query string не проверяется на принадлежность текущему покупателю — любой может увидеть чужой заказ по ID |
| S-3 | **Корзина в сессии — не масштабируется** | `CartController` | При горизонтальном масштабировании (несколько серверов) сессия теряется. Нет привязки к user_id |

### 4.2 Высокие ⚡

| # | Проблема | Где | Рекомендация |
|---|---------|-----|-------------|
| S-4 | **Admin routes используют closure** | `routes/web.php:1286-1325` | 10 маршрутов admin-панели используют `function()` вместо контроллеров — нельзя кэшировать маршруты (`php artisan route:cache`) |
| S-5 | **Нет CSRF для Storefront API** | `routes/web.php:1342-1356` | POST/PUT/DELETE эндпоинты cart/checkout внутри web.php — CSRF токен требуется, но не проверяется на стороне фронтенда через Alpine.js |
| S-6 | **Дублирование getPublishedStore()** | 4 контроллера Storefront | Одинаковый метод скопирован в каждый контроллер — нужен trait или base class |
| S-7 | **Дублирование trackPageView()** | StorefrontController, CatalogController | Одинаковая логика трекинга — 3 отдельных вызова к БД вместо одного |
| S-8 | **N+1 запросы в каталоге** | `CatalogController::index()` | `product.variants` загружается для каждого StoreProduct, но фильтр по цене делает дополнительные whereHas |
| S-9 | **Отсутствие rate limiting** | Storefront API endpoints | Нет throttle middleware на cart/checkout/quick-order — возможна DDoS атака через заказы |

### 4.3 Средние 📋

| # | Проблема | Где | Рекомендация |
|---|---------|-----|-------------|
| S-10 | **StoreOrder::boot() — order_number не уникален** | `StoreOrder.php:77` | `uniqid()` не гарантирует уникальность при высокой нагрузке. Нужен retry или DB sequence |
| S-11 | **Нет кэширования витрины** | StorefrontController, CatalogController | Публичные страницы магазина не кэшируются — каждый запрос идёт в БД |
| S-12 | **Аналитика: 3 запроса вместо 1** | `trackVisit()`, `trackPageView()` | updateOrCreate + 2x increment можно заменить на один upsert с DB::raw |
| S-13 | **Нет пагинации для аналитики** | `StoreAnalyticsController::index()` | За год будет 365 записей на магазин — пока не критично, но нет лимита |
| S-14 | **Store::boot() — slug collision** | `Store.php:66` | While-цикл для уникального slug — потенциальная бесконечная петля при race condition |
| S-15 | **Нет soft deletes** | `Store`, `StoreOrder` | Удаление магазина каскадно удаляет все заказы — потеря данных |
| S-16 | **Нет export заказов** | StoreOrderController | Нет возможности экспортировать заказы в Excel/CSV |
| S-17 | **Custom domain не работает** | `Store::getUrlAttribute()` | Логика генерации URL для custom_domain есть, но нет DNS-верификации и Nginx конфигурации |

---

## 5. Статистика кодовой базы

| Метрика | Значение |
|---------|----------|
| Модели | 12 |
| Контроллеры (Admin) | 9 |
| Контроллеры (Storefront) | 5 |
| Сервисы | 1 |
| Уведомления | 1 (NewStoreOrderNotification) |
| Миграции | 17 |
| Blade-шаблоны (Admin) | 10 |
| Blade-шаблоны (Storefront) | 56 (40 тем + 8 компонентов + layouts + wishlist + maintenance) |
| API-эндпоинты (Admin) | ~30 |
| API-эндпоинты (Storefront) | 14 |
| Темы оформления | 5 (default, boutique, grocery, minimal, tech) |
| Тесты | **0** ❌ |

---

## 6. Сравнение с аналогами

| Функция | SellerMind Store | Shopify | InSales |
|---------|-----------------|---------|---------|
| Создание магазина | ✅ | ✅ | ✅ |
| Темы оформления | 5 тем | 100+ | 50+ |
| Корзина | Сессионная | Redis/DB | DB |
| Оплата | Click, Payme, наличные | 100+ | 20+ |
| Промокоды | ✅ | ✅ | ✅ |
| SEO | Базовый (мета-теги) | Полный | Полный |
| Аналитика | Базовая (визиты, конверсия) | Полная | Полная |
| Custom domain | Заглушка | ✅ | ✅ |
| Email-маркетинг | ❌ | ✅ | ✅ |
| Отзывы на товары | ❌ | ✅ | ✅ |
| Регистрация покупателей | ❌ | ✅ | ✅ |
| Быстрый заказ (1 клик) | ✅ | ❌ | ✅ |
| Интеграция со складом | ✅ (SellerMind) | Сторонние | Базовая |

---

## 7. Рекомендации по приоритетам

### Немедленно (Sprint 1) 🔴

1. **Написать тесты** — минимум для CheckoutController, CartController, StoreOrderService
2. **Исправить IDOR в PaymentController** — добавить проверку session order ID
3. **Добавить rate limiting** — throttle:60,1 на Storefront API
4. **Заменить closure-маршруты** на контроллеры для `php artisan route:cache`

### Скоро (Sprint 2) ⚡

5. **Извлечь дублирование** — trait StorefrontHelpers для getPublishedStore(), trackPageView()
6. **Оптимизировать трекинг аналитики** — один upsert вместо 3 запросов
7. **Добавить кэширование** — Redis cache для главной и каталога (5 мин TTL)
8. **Гарантировать уникальность order_number** — DB unique + retry

### Позже (Sprint 3) 📋

9. **Soft deletes** для Store и StoreOrder
10. **Экспорт заказов** в CSV/Excel
11. **Регистрация покупателей** — личный кабинет с историей заказов
12. **Отзывы на товары** витрины
13. **Email-уведомления покупателям** о статусе заказа
14. **Custom domain** — DNS-верификация + автоматический Nginx конфиг

---

## 8. Заключение

Модуль **Store Builder** — это полноценный конструктор интернет-магазинов с хорошей архитектурой
и продуманной интеграцией с основной платформой SellerMind. Реализованы все ключевые функции
e-commerce: каталог, корзина, checkout, оплата, промокоды, 5 тем оформления, аналитика.

**Главный риск** — полное отсутствие тестов (0 из 14 контроллеров покрыто). Это делает любые
изменения в модуле потенциально опасными.

**Зрелость модуля:** 7/10 — функционально готов для beta-запуска, но требует тестов, исправления
безопасности (IDOR, rate limiting) и оптимизации производительности перед продакшн-нагрузкой.

---

*Отчёт сгенерирован автоматически. Для вопросов — см. исходный код в `app/Http/Controllers/Store/` и `app/Http/Controllers/Storefront/`.*
