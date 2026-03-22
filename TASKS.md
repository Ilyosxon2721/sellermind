# SellerMind - Задачи (Аудит 2026-02-01)

> Последнее обновление: 2026-03-23 (добавлен модуль Uzum Analytics — 27 задач)
> Исполнитель: Claude Code Autopilot

---

## 🔴 В работе (In Progress)

<!-- Claude: перемести сюда задачу когда начинаешь работу -->

---

## 🟡 Очередь (Queue)

### Критический приоритет 🔥

#### 📊 Uzum Analytics Module (Новый модуль)

- [ ] #060 **[SECURITY]** Юридическая консультация по использованию неофициального API Uzum
  - **Проблема:** Модуль использует неофициальный API — может нарушать ToS Uzum
  - **Риск:** Претензии или судебные иски от Uzum
  - **Решение:** Получить юрконсультацию по законодательству РУз, подготовить disclaimer для клиентов
  - **Блокирует:** Запуск модуля в продакшн

- [ ] #061 **[SECURITY]** Выделенный IP/прокси для краулера Uzum
  - **Проблема:** Краулер не должен работать с основного IP сервера
  - **Риск:** Блокировка IP → недоступность всего SellerMind
  - **Решение:** Настроить выделенный сервер или прокси-ротацию
  - **Где:** Инфраструктура, Laravel Forge

### Критический приоритет 🔥

- [x] #016 **[SECURITY]** getCompanyId() фоллбэк на первую компанию в БД — ✅ 2026-02-01 (commit: 465781b)

- [x] #017 **[SECURITY]** $request->all() передаётся в сервисы без фильтрации — ✅ 2026-02-01 (commit: 529baed)

- [x] #018 **[SECURITY]** IDOR в других контроллерах — findOrFail без company scoping — ✅ 2026-02-01 (commit: 4e023bc)

- [x] #019 **[BUG]** Пустые catch блоки скрывают ошибки в финансовом модуле — ✅ 2026-02-01 (commit: 215a017)

- [x] #002 **[BUG]** Telegram уведомления не отправляются
  - **Проблема:** TELEGRAM_BOT_TOKEN не задан в .env
  - **Где:** `app/Notifications/`, `app/Services/TelegramService.php`
  - **Решение:** Проверить конфигурацию бота, очереди, webhook

- [x] #055 **[BUG]** API /marketplace/sync-logs/json возвращает 404 "Route not found" — ✅ 2026-03-04
  - **Причина:** Валидация статуса в контроллере разрешала только success,error,partial, но UI отправлял pending,running
  - **Решение:** Исправлена валидация в MarketplaceSyncLogController::index() и ::json() — теперь pending,running,success,error

- [x] #056 **[BUG]** Кнопка "Синхронизировать" на странице аккаунта вызывает неверный endpoint — ✅ УЖЕ ИСПРАВЛЕНО
  - **Где:** resources/views/pages/marketplace/show.blade.php, orders-table.blade.php
  - **Решение:** Endpoints уже правильные - show.blade.php использует syncAll() для полной синхронизации, orders-table использует /orders/sync для заказов

- [x] #057 **[BUG]** Аккаунт не создаёт записи в журнал синхронизации — ✅ РАБОТАЕТ
  - **Проблема:** POST возвращает 200, но логи не создаются в БД
  - **Решение:** Проверено - в БД 121 лог, MarketplaceSyncLog::start() вызывается корректно во всех методах sync*

- [x] #040 **[BUG]** Исправить цвета кнопок - все primary кнопки должны быть синими (bg-blue-600) — ✅ 2026-02-01 (commit: 4114d34)

- [x] #041 **[BUG]** Исправить 'undefined шт' на странице Финансы → Обзор — ✅ 2026-02-01 (commit: 9ad34c9)

- [x] #042 **[BUG]** Исправить цвет активного таба Зарплата (оранжевый → синий) — ✅ 2026-02-01 (commit: 7d63efd)

- [x] #043 **[BUG]** Исправить смешение языков на лендинге - всё на русский — ✅ 2026-02-01 (commit: 0dc3d96)

- [x] #044 **[BUG]** Перевести 'STATUS' на 'Статус' в таблице долгов — ✅ 2026-02-01 (commit: c92484b)

- [x] #045 **[STYLE]** Страница ошибки 500 - светлая тема вместо тёмной — ✅ 2026-02-01 (commit: 1bceae6)

### Высокий приоритет ⚡

#### 📊 Uzum Analytics - Этап 2: Краулер категорий (1 неделя)

- [ ] #068 **[FEATURE]** Job: RefreshTokenPoolJob
  - **Где:** `app/Modules/UzumAnalytics/Jobs/RefreshTokenPoolJob.php`
  - **Функции:**
    - Запускается каждые 5 минут (Laravel Scheduler)
    - Проверяет expires_at всех токенов в пуле
    - Обновляет токены за 2 минуты до истечения
    - Создаёт новые токены если в пуле < 3
  - **Зависит от:** #064 (TokenRefreshService)

- [ ] #069 **[FEATURE]** Job: CrawlCategoryJob
  - **Где:** `app/Modules/UzumAnalytics/Jobs/CrawlCategoryJob.php`
  - **Функции:**
    - Получение списка товаров категории через GraphQL
    - Pagination (48 товаров на страницу)
    - Сохранение в uzum_products_snapshots
    - Retry 3 попытки с backoff при ошибках
  - **Параметры:** category_id, offset, limit
  - **Зависит от:** #065 (UzumApiClient), #070 (AnalyticsRepository)

- [ ] #070 **[FEATURE]** Repository: AnalyticsRepository
  - **Где:** `app/Modules/UzumAnalytics/Repositories/AnalyticsRepository.php`
  - **Методы:**
    - saveProductSnapshot() — сохранение снепшота товара
    - getPriceHistory() — история цен товара
    - getCategoryStats() — статистика категории
    - getCompetitorData() — данные конкурента
  - **Кэш:** Redis 30 минут для агрегированных данных

- [ ] #071 **[FEATURE]** Синхронизация дерева категорий Uzum
  - **Где:** Job или Artisan команда
  - **API:** GET /main/root-categories
  - **Функции:**
    - Получение корневых категорий
    - Рекурсивный обход дочерних категорий
    - Сохранение в uzum_categories (id, parent_id, title, products_count)
    - Запуск 1 раз в сутки (Laravel Scheduler)
  - **Зависит от:** #065 (UzumApiClient), #070 (AnalyticsRepository)

### Высокий приоритет ⚡

- [x] #020 **[REFACTOR]** Извлечь getCompanyId() в trait — дублирование в 12 контроллерах ✅ `6a051ed`
  - Создан trait `HasCompanyScope` с единой безопасной реализацией (abort 403)
  - Применён в 12 контроллерах, удалены 12 дублированных методов

- [x] #021 **[REFACTOR]** Унифицировать шаблоны заказов маркетплейсов ✅ `d1a32b2`
  - Создан компонент orders-table.blade.php (850 строк)

- [x] #058 **[BUG]** Отрицательное время синхронизации (-262s, -397s) — ✅ УЖЕ ИСПРАВЛЕНО
  - **Проблема:** duration = started_at - finished_at (неправильный порядок)
  - **Решение:** В MarketplaceSyncLog::getDuration() используется max(0, finished_at - started_at) — уже исправлено

- [x] #059 **[UX]** Нет feedback при нажатии кнопок синхронизации — ✅ УЖЕ ИСПРАВЛЕНО
  - **Проблема:** Нет spinner, нет toast, пользователь не понимает нажалась ли кнопка
  - **Решение:** Добавлен loading state (syncing.all), toast уведомления (showNotification), обработка ошибок с try/catch в show.blade.php
  - Сокращение кода: 11765→167 строк (-98.6%)
  - Единый дизайн: синие табы/кнопки, белые карточки

- [x] #022 **[BUG]** N+1 запросы в 7+ местах ✅ `39ba7db`
  - SaleReservationService: добавлен loadMissing('items') в 2 методах
  - StockRecalculateController: предзагрузка VariantMarketplaceLink (N*M→2) и ProductVariant (N→1)

- [x] #023 **[BUG]** Отсутствие валидации входных данных в 11+ контроллерах ✅ `d8ef87d`
  - Добавлен $request->validate() в 10 контроллеров (Warehouse/ReservationController не найден)
  - Покрыты webhook-контроллеры платёжных систем (Click, Payme)

- [x] #024 **[FEATURE]** Биллинг — вся вкладка использует фейковые данные ✅ `d60a7bb`
  - Убраны mock-данные (баланс 15000, фейковые счета), заменены нулями
  - Кнопки «Пополнить», «Изменить план», «Скачать» отключены, баннер «В разработке»

- [x] #025 **[FEATURE]** Все 4 Product Publisher'а — пустые заглушки ✅ `06e22db`
  - Добавлен RuntimeException в 4 publisher'а, кнопка «Опубликовать» отключена

- [x] #026 **[FEATURE]** Система уведомлений не подключена к backend ✅ `067a3a7`
  - Пометка «Система уведомлений в разработке», кнопка «Отметить все» отключена

- [x] #027 **[FEATURE]** Управление правами доступа — только клиентское ✅ `8212ff1`
  - Создан API GET/POST /companies/{id}/access-rights
  - Права сохраняются в company.settings JSON, фронтенд подключён к API

- [x] #046 **[BUG]** Восстановить sidebar на supplies и passes ✅ `4da5065`
- [x] #047 **[BUG]** Перевести статусы Ozon (processing→В обработке) ✅ `4da5065`
- [x] #048 **[BUG]** Кнопки WB товаров фиолетовые → синие ✅ `4da5065`
- [x] #051 **[STYLE]** Кнопки "Добавить аккаунт" → синие ✅ `4da5065`
- [x] #052 **[STYLE]** Yandex секция — белый фон ✅ `4da5065`
- [x] #054 **[STYLE]** Расширить колонку статусов поставок ✅ `4da5065`

- [x] #003 **[FEATURE]** Добавить фильтрацию товаров по маркетплейсу
  - **Где:** `/products`
  - **Решение:** Dropdown с выбором маркетплейса, query scope в Product модели

- [x] #004 **[FEATURE]** Экспорт товаров в Excel
  - **Где:** `GET /api/products/export`
  - **Решение:** XLSX через Laravel Excel или PhpSpreadsheet

- [x] #005 **[FEATURE]** Импорт товаров из Excel
  - **Где:** `POST /api/products/import`
  - **Решение:** Валидация + Drag & drop UI

### Нормальный приоритет 📋

#### 📊 Uzum Analytics - Этап 3: Мониторинг конкурентов (1-2 недели)

- [ ] #072 **[FEATURE]** Job: CrawlProductJob
  - **Где:** `app/Modules/UzumAnalytics/Jobs/CrawlProductJob.php`
  - **Функции:**
    - Получение карточки товара GET /v2/product/{productId}
    - Парсинг полей: title, minSellPrice, maxFullPrice, rating, reviewsAmount, ordersAmount
    - Конвертация цены из тийинов (÷ 100)
    - Сохранение снепшота в uzum_products_snapshots
  - **Параметры:** product_id
  - **Зависит от:** #065 (UzumApiClient), #070 (AnalyticsRepository)

- [ ] #073 **[FEATURE]** Сбор снепшотов цен — 4 раза в сутки
  - **Где:** Laravel Scheduler
  - **Расписание:** 00:00, 06:00, 12:00, 18:00 (UTC+5 Ташкент)
  - **Функции:**
    - Получение списка отслеживаемых товаров из настроек пользователя
    - Dispatch CrawlProductJob для каждого товара (с задержкой 6 сек)
    - Лимит: max 20 товаров на Pro аккаунт, 50 на Business
  - **Зависит от:** #072 (CrawlProductJob)

- [ ] #074 **[FEATURE]** История изменения цен конкурентов
  - **Где:** UzumAnalytics API
  - **Функции:**
    - График цены товара за 30/90 дней (Chart.js)
    - Выявление скидочных акций (price vs original_price)
    - Сравнение с медианной ценой категории
  - **Endpoint:** GET /api/analytics/uzum/price-history/{product_id}
  - **Зависит от:** #070 (AnalyticsRepository)

- [ ] #075 **[FEATURE]** Алерты в Telegram при изменении цен
  - **Где:** Observer или Event Listener
  - **Функции:**
    - Отслеживание изменений price в uzum_products_snapshots
    - Уведомление если цена изменилась > N% (настраивается)
    - Отправка в Telegram бот пользователя
    - Настройка: включить/выключить, порог %
  - **Интеграция:** TelegramService (уже существует)
  - **Зависит от:** #073 (сбор снепшотов)

- [ ] #076 **[FEATURE]** GraphQL запросы для поиска товаров
  - **Где:** UzumApiClient::searchProducts()
  - **API:** POST https://graphql.umarket.uz
  - **Query:** makeSearch (categoryId, sort, pagination)
  - **Заголовки:** Authorization, X-Iid, Accept-Language
  - **Функции:**
    - Поиск товаров по категории
    - Сортировка: BY_REVIEWS_COUNT_DESC, BY_PRICE_ASC, BY_RATING_DESC
    - Пагинация (offset, limit)
  - **Зависит от:** #065 (UzumApiClient)

#### 📊 Uzum Analytics - Этап 4: UI Дашборд (1 неделя)

- [ ] #077 **[FEATURE]** API эндпоинты для фронтенда
  - **Где:** `app/Modules/UzumAnalytics/Http/Controllers/AnalyticsController.php`
  - **Endpoints:**
    - GET /api/analytics/uzum/categories — список категорий с метриками
    - GET /api/analytics/uzum/category/{id}/products — товары категории
    - GET /api/analytics/uzum/competitor/{slug} — данные конкурента
    - GET /api/analytics/uzum/price-history/{id} — история цен
    - GET /api/analytics/uzum/market-overview — сводка по отслеживаемым позициям
  - **Middleware:** auth, company scope
  - **Зависит от:** #070 (AnalyticsRepository)

- [ ] #078 **[FEATURE]** Livewire компонент дашборда Uzum Analytics
  - **Где:** `app/Modules/UzumAnalytics/Livewire/AnalyticsDashboard.php`
  - **Blade:** `resources/views/livewire/uzum-analytics-dashboard.blade.php`
  - **Функции:**
    - Сводный виджет "Рынок сегодня" (изменения за 24 часа)
    - Таблица отслеживаемых конкурентов
    - Список категорий с метриками
    - Фильтры: магазин, категория, ценовой диапазон
  - **Зависит от:** #077 (API endpoints)

- [ ] #079 **[FEATURE]** Графики Chart.js для аналитики
  - **Где:** Blade шаблон дашборда
  - **Графики:**
    - Line chart: динамика цены товара (30/90 дней)
    - Bar chart: распределение цен в категории (перцентили)
    - Pie chart: доля магазинов в категории
  - **JS:** `resources/js/uzum-analytics.js`
  - **Зависит от:** #078 (Livewire компонент)

- [ ] #080 **[FEATURE]** Фильтры и таблицы аналитики
  - **Где:** Livewire компонент + Alpine.js
  - **Функции:**
    - Фильтр по категории (dropdown)
    - Фильтр по ценовому диапазону (slider)
    - Фильтр по магазину (search input)
    - Таблица товаров с сортировкой (цена, рейтинг, отзывы)
    - Пагинация (Livewire)
  - **Зависит от:** #078 (Livewire компонент)

- [ ] #081 **[FEATURE]** Экспорт аналитики в CSV/Excel
  - **Где:** AnalyticsController::export()
  - **Endpoint:** GET /api/analytics/uzum/export
  - **Форматы:** CSV, XLSX (Laravel Excel)
  - **Данные:** История цен, список конкурентов, статистика категорий
  - **Зависит от:** #077 (API endpoints)

#### 📊 Uzum Analytics - Дополнительно

- [ ] #082 **[TEST]** Тесты для UzumApiClient
  - **Где:** `tests/Unit/UzumAnalytics/UzumApiClientTest.php`
  - **Покрытие:**
    - Mock HTTP-ответов (Guzzle Mock Handler)
    - Тест retry при 401, 429, 503
    - Тест экспоненциального backoff
    - Тест ротации токенов
    - Тест rate limiting
  - **Зависит от:** #065 (UzumApiClient)

- [ ] #083 **[TEST]** Тесты для краулер Jobs
  - **Где:** `tests/Feature/UzumAnalytics/`
  - **Тесты:**
    - RefreshTokenPoolJobTest — проверка обновления токенов
    - CrawlCategoryJobTest — сохранение снепшотов категории
    - CrawlProductJobTest — сохранение снепшота товара
  - **Покрытие:** Mock API, проверка БД, обработка ошибок
  - **Зависит от:** #068, #069, #072

- [ ] #084 **[FEATURE]** Мониторинг изменений структуры API Uzum
  - **Где:** Artisan команда или Scheduled Job
  - **Функции:**
    - Ежедневный тест структуры ответа /v2/product/{id}
    - Проверка наличия ключевых полей (title, minSellPrice, rating)
    - Алерт в Telegram разработчику при изменении структуры
  - **Команда:** `php artisan uzum:check-api-structure`
  - **Scheduler:** Daily at 09:00

- [ ] #085 **[DOCS]** Документация модуля Uzum Analytics
  - **Где:** `docs/UZUM_ANALYTICS_GUIDE.md`
  - **Разделы:**
    - Описание модуля и архитектуры
    - Настройка (config, env переменные)
    - Использование API эндпоинтов
    - Rate limiting и меры безопасности
    - Troubleshooting (блокировки, ошибки)
    - Лимиты по тарифам (Pro: 20 товаров, Business: 50)

- [ ] #086 **[FEATURE]** Healthcheck и метрики краулера
  - **Где:** AnalyticsController::healthcheck()
  - **Endpoint:** GET /api/analytics/uzum/health
  - **Метрики:**
    - Количество активных токенов в пуле
    - Количество запросов за час (по типам)
    - Количество ошибок 429/503 за день
    - Время последней успешной синхронизации
    - Статус Circuit Breaker (open/closed)
  - **Интеграция:** Laravel Telescope, еженедельный email-отчёт

### Нормальный приоритет 📋

- [x] #028 **[BUG]** LIKE injection в поиске товаров ✅ `12e8e14`
  - Создан метод escapeLike() в базовом Controller
  - Применён в 17 контроллерах для экранирования символов % и _

- [x] #029 **[PERFORMANCE]** Ozon и Yandex синхронизация расходов возвращает нули ✅ `5e37916`
  - Добавлены Log::info() вызовы и флаг _not_implemented: true
  - syncOzon() и syncYandex() помечены как не реализованные

- [x] #030 **[REFACTOR]** Бизнес-логика в контроллерах — перенести в сервисы ✅ `ddb4030`
  - Создан InventoryService: addInventoryItems()
  - Создан CounterpartyService: CRUD операции
  - Расширен SaleService: getProductsForSale()

- [x] #031 **[REFACTOR]** Извлечь пагинацию в trait — дублируется в 8+ контроллерах ✅ `a0f2394`
  - Создан trait HasPaginatedResponse с getPerPage() и paginationMeta()
  - Применён в 14 контроллерах, устранено дублирование

- [x] #032 **[CLEANUP]** Удалить 100+ console.log из production кода ✅ `3adb40c`
  - Удалено 105 console.log из 14 JS файлов и 11 Blade шаблонов
  - Сохранены console.error/warn для отладки ошибок

- [x] #033 **[TEST]** Исправить 14 падающих тестов (SQLite driver) — ✅ 2026-03-04 (commit: e7d8289)
  - Создана БД sellermind_test, исправлены FK-порядок миграций (disableForeignKeyConstraints)
  - Исправлен Company::factory(), CompanyFactory faker, ExampleTest, bootstrap/app.php catch-all handler
  - 38/38 тестов проходят

- [x] #034 **[TEST]** Установить и настроить PHPStan
  - **Проблема:** PHPStan не установлен — статический анализ не проводится
  - **Решение:** `composer require --dev phpstan/phpstan larastan/larastan`, настроить phpstan.neon, запустить level 0

- [x] #035 **[STYLE]** Запустить Pint для исправления code style
  - **Проблема:** Сотни файлов с нарушениями PSR-12: line endings, unused imports, trailing commas, spacing
  - **Решение:** `vendor/bin/pint` (автоматическое исправление)

- [x] #006 **[IMPROVE]** Оптимизировать загрузку страницы аналитики — ✅ 2026-03-04 (commit: fa95e3d)
  - SalesAnalyticsService: устранён N+1, объединены запросы (8→4), добавлен кэш 30 мин

- [x] #007 **[IMPROVE]** Добавить поиск по товарам
  - **Решение:** Full-text search или LIKE query с debounce UI

- [x] #008 **[FEATURE]** История изменения цен — ✅ 2026-03-06 (commit: e183192)
  - Таблица price_history, модель PriceHistory::record(), трекинг в Observer
  - API GET /api/products/{id}/price-history, Chart.js график на вкладке «Цены»

- [x] #009 **[FEATURE]** Уведомления о низком остатке
  - **Решение:** Настраиваемый порог, каналы Telegram/Email

### Низкий приоритет 📝

- [x] #036 **[CLEANUP]** Удалить комментированный код и debug-логи — ✅ 2026-03-04 (commit: 28e0d4b)
  - Удалены debug Log::info/debug блоки из 5 файлов (ProductWebController, Observer, HttpClient, WBService)
  - Удалён закомментированный код из UzumClient, WildberriesClient

- [x] #037 **[REFACTOR]** Создать FormRequest классы для контроллеров без валидации
  - **Проблема:** Только 4 FormRequest класса на 80+ контроллеров
  - **Где:** CounterpartyController, InventoryController, SalesManagementController, DialogController и др.
  - **Решение:** Создать FormRequest для каждого контроллера, принимающего пользовательский ввод

- [x] #038 **[BUG]** XSS через неэкранированный вывод в Blade
  - **Проблема:** 6 мест с `{!! !!}` — потенциальная XSS уязвимость
  - **Где:** `welcome.blade.php:619,630,641,652`, `empty-state.blade.php:38`, `marketplace/index.blade.php:156`
  - **Решение:** Заменить `{!! !!}` на `{{ }}` где возможно, или добавить санитизацию

- [x] #039 **[CLEANUP]** Дублирующийся route "/" в web.php
  - **Проблема:** Маршрут `/` определён дважды — первое определение мёртвый код
  - **Где:** `routes/web.php:53-59` и `62-79`
  - **Решение:** Удалить первое определение

- [x] #010 **[REFACTOR]** Вынести API клиенты в отдельные пакеты — ✅ 2026-03-09 (commit: b5790ec)
  - Унифицированы WildberriesClient, OzonClient, UzumClient, YandexMarketClient под единый интерфейс
  - Добавлен declare(strict_types=1), final class, constructor property promotion во все клиенты

- [x] #011 **[DOCS]** Обновить README с актуальными инструкциями — ✅ 2026-03-09 (commit: e9ebcb5)
- [x] #012 **[TEST]** Добавить тесты для PromotionService — ✅ 2026-03-09 (commit: ee42567)
- [x] #013 **[TEST]** Добавить тесты для AnalyticsService — ✅ 2026-03-09 (commit: 1acc3de)
- [x] #014 **[STYLE]** Унифицировать стили кнопок во всём проекте — ✅ 2026-03-09 (commit: 6b20a35)

---

## ✅ Выполнено (Done)

<!-- Claude: перемести сюда задачу после успешного коммита -->

### Март 2026

#### 📊 Uzum Analytics - Этап 1: Фундамент

- [x] #062 **[FEATURE]** Создать структуру модуля UzumAnalytics — ✅ 2026-03-23 (commit: 540b330)
  - Создана структура директорий app/Modules/UzumAnalytics/
  - Обновлён config/uzum-crawler.php с полной конфигурацией
  - Создан UzumAnalyticsServiceProvider
  - Зарегистрирован в bootstrap/providers.php
  - Созданы routes/api.php с заглушками endpoints

- [x] #063 **[FEATURE]** Миграции БД для Uzum Analytics — ✅ 2026-03-23 (commit: 0856bb4)
  - Миграции: uzum_products_snapshots, uzum_categories, uzum_token_pool
  - Модели: UzumProductSnapshot, UzumCategory, UzumToken

- [x] #064 **[FEATURE]** TokenRefreshService — управление пулом токенов — ✅ 2026-03-23 (commit: 540b330)
  - Round-robin ротация, автообновление за 2 мин до истечения

- [x] #065 **[FEATURE]** UzumApiClient — HTTP клиент с retry и backoff — ✅ 2026-03-23 (commit: 540b330)
  - Retry при 401/429/503, экспоненциальный backoff

- [x] #066 **[FEATURE]** Rate Limiting механизм — ✅ 2026-03-23 (commit: 540b330)
  - Redis rate limiter с jitter

- [x] #067 **[FEATURE]** Circuit Breaker при ошибках — ✅ 2026-03-23 (commit: 540b330)
  - 5 ошибок → пауза 1 час, Telegram алерты

### Февраль 2026

- [x] #015 **[SECURITY]** IDOR в SalesManagementController — доступ к чужим продажам — ✅ 2026-02-01 (commit: 9021457)
- [x] #001 **[BUG]** Исправить ошибку 429 при синхронизации WB — ✅ 2026-02-01 (commit: 485393e)

### Январь 2026

- [x] Базовый CRUD товаров
- [x] Авторизация пользователей
- [x] Bulk Operations
- [x] Smart Promotions
- [x] Sales Analytics Dashboard
- [x] AI Review Responses
- [x] Telegram Bot интеграция

---

## 🚫 Заблокировано (Blocked)

<!-- Claude: перемести сюда если задача заблокирована -->

---

## 📊 Статистика

```
Всего задач:     71
В работе:        0
В очереди:       27 (Uzum Analytics Module)
Выполнено:       44 ✅
Заблокировано:   0
```

### По типам:

```
[SECURITY]      6 задач   🔥 (+2 Uzum)
[BUG]           6 задач
[FEATURE]       33 задачи (+23 Uzum)
[REFACTOR]      5 задач
[PERFORMANCE]   1 задача
[TEST]          6 задач   (+2 Uzum)
[CLEANUP]       3 задачи
[STYLE]         2 задачи
[IMPROVE]       2 задачи
[DOCS]          2 задачи  (+1 Uzum)
```

### По приоритету:

```
🔥 Критический:  7 задач  (+2 Uzum: юрконсультация, выделенный IP)
⚡ Высокий:      21 задача (+10 Uzum: фундамент + краулер)
📋 Нормальный:   27 задач (+15 Uzum: мониторинг + UI + тесты)
📝 Низкий:       9 задач
```

### 📊 Uzum Analytics Module (новый):

```
Всего задач:     27
Этап 1 (Фундамент):           7 задач  (#062-#067, #068)
Этап 2 (Краулер категорий):   3 задачи (#069-#071)
Этап 3 (Мониторинг):          5 задач  (#072-#076)
Этап 4 (UI Дашборд):          5 задач  (#077-#081)
Дополнительно:                5 задач  (#082-#086)
Безопасность:                 2 задачи (#060-#061)
```

---

## 📝 Как работать с задачами

### Для Claude Code:

1. Возьми первую задачу из "🟡 Очередь" (по приоритету)
2. Перенеси в "🔴 В работе"
3. Выполни задачу
4. Запусти тесты
5. Закоммить
6. Перенеси в "✅ Выполнено" с датой
7. Обнови статистику

### Формат выполненной задачи:

```markdown
- [x] #001 **[BUG]** Описание — ✅ 2026-01-15 (commit: abc123)
```
