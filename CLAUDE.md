# SellerMind AI - Claude Code Configuration

> **Режим:** Автономная разработка с минимальным участием человека

---

## 🎯 О проекте

**SellerMind AI** — платформа управления продажами на маркетплейсах СНГ.

| Параметр | Значение |
|----------|----------|
| Домен | sellermind.uz |
| Версия | 1.0 Production Ready |
| Репозиторий | github.com/Ilyosxon2721/sellermind |

---

## 🛠 Технический стек

```yaml
Backend:
  framework: Laravel 12
  php: 8.2+
  database: MySQL 8.0
  cache: Redis
  queue: Redis + Supervisor
  websocket: Laravel Reverb

Frontend:
  js: Alpine.js 3.x
  css: Tailwind CSS 4.0
  charts: Chart.js 4.4
  templates: Blade

Infrastructure:
  hosting: Laravel Forge
  server: Nginx + PHP-FPM
  ssl: Let's Encrypt
```

---

## 📦 Модули проекта

### ✅ Реализовано (Quick Wins)
1. **Bulk Operations** — массовые операции с товарами
2. **Telegram Notifications** — уведомления в реальном времени
3. **Smart Promotions** — автоматические акции
4. **Sales Analytics** — дашборд аналитики
5. **AI Review Responses** — генерация ответов на отзывы

### 🔗 Интеграции маркетплейсов
| Маркетплейс | API | Статус |
|-------------|-----|--------|
| Wildberries | Statistics, Content, Prices, Warehouse | ✅ |
| Ozon | Seller API | ✅ |
| Uzum | Market API | ⚠️ Частично |
| Yandex Market | Partner API | ✅ |

---

## 📁 Структура проекта

```
sellermind/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/              # API контроллеры
│   │   │   └── Web/              # Web контроллеры
│   │   ├── Requests/             # Form Requests
│   │   └── Resources/            # API Resources
│   ├── Models/                   # Eloquent модели
│   ├── Services/                 # Бизнес-логика
│   │   ├── Marketplaces/         # API клиенты
│   │   ├── Analytics/            # Аналитика
│   │   ├── Promotions/           # Smart Promotions
│   │   └── Reviews/              # AI ответы
│   ├── Jobs/                     # Фоновые задачи
│   ├── Events/                   # События
│   ├── Listeners/                # Обработчики
│   └── Notifications/            # Уведомления
├── resources/views/              # Blade шаблоны
├── tests/                        # Тесты
├── TASKS.md                      # Файл задач
├── AUTOPILOT_LOG.md              # Лог автопилота
└── BLOCKERS.md                   # Блокеры
```

---

## 🤖 РЕЖИМ АВТОПИЛОТА

### Правила автономной работы

1. **Источник задач:** Читай `TASKS.md`
2. **Приоритет:** Сначала 🔴 Critical, потом 🟡 High, потом 🟢 Normal
3. **Одна задача за раз:** Не начинай новую пока не закончил текущую
4. **Тесты обязательны:** Не коммить без прохождения тестов
5. **Логирование:** Пиши всё в `AUTOPILOT_LOG.md`

### Когда остановиться и спросить человека

- ❌ Тесты падают 3 раза подряд на одной задаче
- ❌ Нужно изменить структуру БД (миграции)
- ❌ Задача непонятна или противоречива
- ❌ Нужен доступ к внешним сервисам (API ключи)
- ❌ Удаление данных или файлов
- ❌ Изменения в production конфигурации

### Что можно делать автономно

- ✅ Создавать/редактировать PHP, JS, Blade файлы
- ✅ Писать и запускать тесты
- ✅ Коммитить с осмысленными сообщениями
- ✅ Рефакторинг существующего кода
- ✅ Добавлять новые endpoints/страницы
- ✅ Исправлять баги
- ✅ Улучшать UI компоненты

---

## 📝 Стандарты кодирования

### PHP / Laravel
```php
<?php

declare(strict_types=1);

namespace App\Services;

final class ExampleService
{
    public function __construct(
        private readonly Repository $repository,
    ) {}

    /**
     * Описание метода на русском
     */
    public function doSomething(int $id): Model
    {
        // Логика
    }
}
```

### Правила
- PSR-12 code style (используй Pint)
- Type hints везде
- Final классы по умолчанию
- Form Requests для валидации
- Services для бизнес-логики
- Комментарии на русском

### Frontend (Alpine.js + Tailwind)
- Mobile-first подход
- Tailwind утилиты (не кастомный CSS)
- Alpine.js для интерактивности
- Blade компоненты для переиспользования

---

## 🧪 Тестирование

```bash
# Перед каждым коммитом
php artisan test --parallel

# Конкретный тест
php artisan test --filter=TestName

# С coverage
php artisan test --coverage --min=70
```

### Требования
- Каждая новая функция = минимум 1 тест
- Исправление бага = regression тест
- Coverage не должен падать

---

## 🚀 Git Workflow

### Формат коммитов
```
<type>(<scope>): <description>

Типы:
- feat: новая функция
- fix: исправление бага
- refactor: рефакторинг
- test: тесты
- docs: документация
- style: форматирование
- chore: прочее

Примеры:
feat(products): добавить фильтрацию по маркетплейсу
fix(sync): исправить ошибку 429 при синхронизации WB
test(promotions): добавить тесты для SmartPromotionService
```

### Ветки
- `main` — production
- `develop` — разработка
- `feature/*` — новые функции
- `fix/*` — исправления

---

## 📚 Документация проекта

| Файл | Описание |
|------|----------|
| `TASKS.md` | Список задач |
| `AUTOPILOT_LOG.md` | Лог автономной работы |
| `BLOCKERS.md` | Блокирующие проблемы |
| `docs/SMART_PROMOTIONS_GUIDE.md` | Smart Promotions |
| `docs/SALES_ANALYTICS_GUIDE.md` | Аналитика |
| `docs/BULK_OPERATIONS_GUIDE.md` | Массовые операции |

---

## ⚠️ Критические правила

### НЕ ДЕЛАЙ без разрешения:
1. `php artisan migrate:fresh` — удалит все данные
2. `rm -rf` — удаление файлов
3. Изменения в `.env.production`
4. Изменения в платёжных модулях

### Git Push
Claude Code может делать `git push` после коммита.

### ВСЕГДА ДЕЛАЙ:
1. Запускай тесты перед коммитом
2. Пиши осмысленные commit messages
3. Логируй свои действия
4. Проверяй что не сломал существующий функционал

---

## 🔧 Полезные команды

```bash
# Разработка
php artisan serve
npm run dev

# Тесты
php artisan test --parallel
./vendor/bin/pint

# Кэш
php artisan optimize:clear

# Очереди
php artisan queue:work

# Статус
php artisan about
```
