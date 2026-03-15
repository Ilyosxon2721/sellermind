# SellerMind AI

Платформа управления продажами на маркетплейсах СНГ.

---

## О проекте

SellerMind AI помогает продавцам автоматизировать работу с маркетплейсами: синхронизация товаров, аналитика продаж, умные акции, AI-ответы на отзывы и массовые операции.

| Параметр | Значение |
|----------|----------|
| Домен | sellermind.uz |
| Версия | 1.0 |

---

## Технический стек

**Backend:**
- Laravel 12
- PHP 8.2+
- MySQL 8.0
- Redis
- Laravel Reverb (WebSocket)

**Frontend:**
- Alpine.js 3.x
- Tailwind CSS 4.0
- Chart.js 4.4
- Blade Templates

**Инфраструктура:**
- Laravel Forge
- Nginx + PHP-FPM
- Supervisor (Queue Workers)

---

## Требования

- PHP 8.2+
- Composer 2.x
- MySQL 8.0
- Redis
- Node.js 18+ и npm

---

## Установка

### 1. Клонирование репозитория

```bash
git clone https://github.com/Ilyosxon2721/sellermind.git
cd sellermind
```

### 2. Установка зависимостей

```bash
composer install
npm install
```

### 3. Настройка окружения

```bash
cp .env.example .env
php artisan key:generate
```

### 4. Настройка базы данных

Создайте базу данных MySQL и укажите параметры в `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sellermind_ai
DB_USERNAME=your_user
DB_PASSWORD=your_password
```

### 5. Миграции

```bash
php artisan migrate
```

### 6. Сборка фронтенда

```bash
npm run build
```

---

## Конфигурация

### Основные переменные `.env`

```env
# Приложение
APP_NAME="SellerMind AI"
APP_URL=https://your-domain.com

# База данных
DB_CONNECTION=mysql
DB_DATABASE=sellermind_ai

# Redis (кэш, очереди, сессии)
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

# OpenAI (для AI-ответов на отзывы)
OPENAI_API_KEY=your_key

# Маркетплейсы
WB_API_KEY=your_wildberries_key
OZON_CLIENT_ID=your_ozon_client_id
OZON_API_KEY=your_ozon_key
UZUM_API_KEY=your_uzum_key
YM_API_KEY=your_yandex_key
YM_CAMPAIGN_ID=your_campaign_id

# Telegram (опционально)
TELEGRAM_BOT_TOKEN=your_bot_token
```

---

## Запуск

### Разработка

```bash
# Все сервисы одной командой
composer dev

# Или по отдельности:
php artisan serve        # HTTP сервер
npm run dev              # Vite (фронтенд)
php artisan queue:work   # Очереди
php artisan reverb:start # WebSocket
```

### Production

```bash
npm run build
php artisan optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## Тестирование

```bash
# Все тесты
php artisan test

# Параллельный запуск
php artisan test --parallel

# Конкретный тест
php artisan test --filter=TestName

# С покрытием
php artisan test --coverage --min=70
```

### Линтинг

```bash
./vendor/bin/pint
```

---

## Модули проекта

### Bulk Operations
Массовые операции с товарами: изменение цен, статусов, категорий.

### Telegram Notifications
Уведомления о заказах, остатках и синхронизации в Telegram.

### Smart Promotions
Автоматические акции на основе аналитики продаж.

### Sales Analytics
Дашборд с графиками продаж, остатков и прибыльности.

### AI Review Responses
Генерация ответов на отзывы покупателей с помощью OpenAI.

---

## Интеграции маркетплейсов

| Маркетплейс | API | Статус |
|-------------|-----|--------|
| Wildberries | Statistics, Content, Prices, Warehouse | Готово |
| Ozon | Seller API | Готово |
| Uzum | Market API | Частично |
| Yandex Market | Partner API | Готово |

---

## Структура проекта

```
sellermind/
├── app/
│   ├── Http/Controllers/     # Контроллеры
│   ├── Models/               # Eloquent модели
│   ├── Services/             # Бизнес-логика
│   │   ├── Marketplaces/     # API клиенты маркетплейсов
│   │   ├── Analytics/        # Сервисы аналитики
│   │   ├── Promotions/       # Smart Promotions
│   │   └── Reviews/          # AI ответы
│   ├── Jobs/                 # Фоновые задачи
│   └── Notifications/        # Уведомления
├── resources/views/          # Blade шаблоны
├── tests/                    # Тесты
└── docs/                     # Документация
```

---

## Полезные команды

```bash
# Очистка кэша
php artisan optimize:clear

# Статус приложения
php artisan about

# Логи в реальном времени
php artisan pail
```

---

## Документация

| Файл | Описание |
|------|----------|
| `CLAUDE.md` | Конфигурация Claude Code |
| `TASKS.md` | Список задач |
| `docs/SMART_PROMOTIONS_GUIDE.md` | Smart Promotions |
| `docs/SALES_ANALYTICS_GUIDE.md` | Аналитика |
| `docs/BULK_OPERATIONS_GUIDE.md` | Массовые операции |

---

## Лицензия

MIT
