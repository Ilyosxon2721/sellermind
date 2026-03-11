# SellerMind — Автоматизация Uzum Market

## 📦 Фича 1: Авто-подтверждение заказов FBS/DBS

Автоматически переводит заказы из статуса `CREATED` (Новые) в `PACKING` (Сборка) каждые 15 минут.

### Как работает:
1. Scheduler запускает `AutoConfirmFbsOrders` каждые 15 минут
2. Job получает все магазины с `auto_confirm_enabled = true`
3. Для каждого магазина запрашивает заказы со статусом `CREATED`
4. Подтверждает каждый заказ через `POST /v1/fbs/order/{orderId}/confirm`
5. Логирует результат в `order_confirm_logs`

### API эндпоинты:
- `GET /v2/fbs/orders?status=CREATED&shopIds=...` — получить новые заказы
- `POST /v1/fbs/order/{orderId}/confirm` — подтвердить заказ

---

## 🤖 Фича 2: ИИ авто-ответ на отзывы

Генерирует персонализированные ответы на отзывы покупателей через Claude AI.

### Как работает:
1. Scheduler запускает `AutoReplyReviews` каждые 30 минут
2. Получает отзывы: `POST /api/seller/product-reviews?page=0&size=20`
3. Фильтрует только неотвеченные (`replyStatus === null`)
4. Для каждого отзыва генерирует ответ через Claude API:
   - Учитывает: rating, content, pros, cons, productTitle, skuTitle, characteristics
   - Положительные (4-5★) — благодарность
   - Нейтральные (3★) — благодарность + предложение помощи
   - Негативные (1-2★) — извинение + эмпатия + предложение решения
5. Отправляет ответ: `POST /api/seller/product-reviews/reply/create`
6. Логирует в `review_reply_logs`

### API эндпоинты отзывов:
- `POST /api/seller/product-reviews?page=0&size=20` + body `{}` — список отзывов
- `GET /api/seller/product-reviews/review/{reviewId}` — детали отзыва
- `POST /api/seller/product-reviews/reply/create` — ответ (batch: массив `[{reviewId, content}]`)

---

## 🚀 Установка

### 1. Скопируй файлы в проект SellerMind:

```
Services/UzumSellerApi.php         → app/Services/
Services/ReviewAutoResponder.php   → app/Services/
Jobs/AutoConfirmFbsOrders.php      → app/Jobs/
Jobs/AutoReplyReviews.php          → app/Jobs/
Console/Commands/UzumAutoProcess.php → app/Console/Commands/
Models/UzumShop.php                → app/Models/
Models/OrderConfirmLog.php         → app/Models/
Models/ReviewReplyLog.php          → app/Models/
Http/Livewire/UzumAutomation.php   → app/Http/Livewire/
config/uzum.php                    → config/
database/migrations/*              → database/migrations/
resources/views/livewire/*         → resources/views/livewire/
```

### 2. Добавь переменные в `.env`:
```env
UZUM_API_TOKEN=your_token
UZUM_AUTO_CONFIRM_ENABLED=true
UZUM_REVIEW_AI_PROVIDER=anthropic
UZUM_REVIEW_AI_API_KEY=sk-ant-...
```

### 3. Выполни миграции:
```bash
php artisan migrate
```

### 4. Добавь расписание в `routes/console.php`:
```php
// Скопируй содержимое Console/schedule.php
```

### 5. Убедись что scheduler запущен:
```bash
# crontab -e
* * * * * cd /path/to/sellermind && php artisan schedule:run >> /dev/null 2>&1

# Или для разработки:
php artisan schedule:work
```

### 6. Добавь роут для Livewire:
```php
// routes/web.php
Route::middleware('auth')->group(function () {
    Route::get('/automation', function () {
        return view('pages.automation'); // содержит <livewire:uzum-automation />
    })->name('automation');
});
```

---

## 🧪 Ручное тестирование

```bash
# Подтвердить заказы прямо сейчас
php artisan uzum:auto-process --confirm

# Ответить на отзывы
php artisan uzum:auto-process --reviews

# Всё сразу
php artisan uzum:auto-process --all
```

---

## 📁 Структура файлов

```
app/
├── Services/
│   ├── UzumSellerApi.php          # HTTP клиент для всех Uzum API
│   └── ReviewAutoResponder.php    # ИИ генерация ответов на отзывы
├── Jobs/
│   ├── AutoConfirmFbsOrders.php   # Job авто-подтверждения
│   └── AutoReplyReviews.php       # Job авто-ответа на отзывы
├── Console/Commands/
│   └── UzumAutoProcess.php        # Artisan команда
├── Models/
│   ├── UzumShop.php               # Модель магазина
│   ├── OrderConfirmLog.php        # Лог подтверждений
│   └── ReviewReplyLog.php         # Лог ответов на отзывы
└── Http/Livewire/
    └── UzumAutomation.php         # UI управления

config/
└── uzum.php                       # Конфигурация

resources/views/livewire/
└── uzum-automation.blade.php      # Blade шаблон
```
