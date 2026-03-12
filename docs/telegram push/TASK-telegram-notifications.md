# ЗАДАЧА: Внедрение Telegram-уведомлений о заказах в SellerMind

## Контекст проекта
- **Стек**: Laravel 11, Livewire 3, PHP 8.2+, Tailwind CSS
- **Проект**: SellerMind — SaaS для управления заказами с маркетплейсов
- **Маркетплейсы**: Uzum Market, Wildberries, Ozon, Yandex Market, интернет-магазин (Store Builder), оффлайн-продажи

## Что нужно сделать

Реализовать модуль Telegram-уведомлений, который отправляет продавцам структурированные уведомления о заказах через Telegram-бота. Каждое уведомление включает: маркетплейс, статус, список товаров, сумму, адрес доставки, склад, покупателя, дневную статистику и кнопку для перехода в SellerMind.

---

## ЭТАП 1: Конфигурация и миграции

### 1.1 Создать `config/telegram.php`

```php
return [
    'bot_token' => env('TELEGRAM_BOT_TOKEN'),
    'webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET'),
    'app_url' => env('APP_URL', 'https://sellermind.uz'),

    'marketplaces' => [
        'uzum'    => ['emoji' => '🟣', 'name' => 'Uzum Market'],
        'wb'      => ['emoji' => '🟪', 'name' => 'Wildberries'],
        'ozon'    => ['emoji' => '🔵', 'name' => 'Ozon'],
        'yandex'  => ['emoji' => '🟡', 'name' => 'Yandex Market'],
        'online'  => ['emoji' => '🌐', 'name' => 'Интернет-магазин'],
        'offline' => ['emoji' => '🏪', 'name' => 'Оффлайн'],
    ],

    'statuses' => [
        'new'        => ['emoji' => '🆕', 'label' => 'Новый'],
        'assembling' => ['emoji' => '📦', 'label' => 'В сборке'],
        'shipped'    => ['emoji' => '🚚', 'label' => 'Отправлен'],
        'delivered'  => ['emoji' => '✅', 'label' => 'Доставлен'],
        'cancelled'  => ['emoji' => '❌', 'label' => 'Отменён'],
        'returned'   => ['emoji' => '↩️', 'label' => 'Возврат'],
    ],
];
```

### 1.2 Добавить в `.env`

```
TELEGRAM_BOT_TOKEN=
TELEGRAM_WEBHOOK_SECRET=
```

### 1.3 Создать миграцию `telegram_subscriptions`

```php
Schema::create('telegram_subscriptions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('chat_id');
    $table->string('marketplace')->nullable(); // null = все маркетплейсы
    $table->foreignId('account_id')->nullable()->constrained()->nullOnDelete();
    $table->boolean('notify_new')->default(true);
    $table->boolean('notify_status')->default(true);
    $table->boolean('notify_cancel')->default(true);
    $table->boolean('is_active')->default(true);
    $table->boolean('daily_summary')->default(false);
    $table->time('summary_time')->nullable(); // напр. "20:00"
    $table->timestamps();

    $table->index(['user_id', 'is_active']);
    $table->index(['marketplace', 'account_id']);
});
```

### 1.4 Создать модель `TelegramSubscription`

Связи: `belongsTo User`, `belongsTo Account` (nullable). Scope `active()` — `where('is_active', true)`.

---

## ЭТАП 2: Telegram Bot API клиент

### 2.1 Создать `app/Services/Telegram/TelegramApiClient.php`

```php
namespace App\Services\Telegram;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramApiClient
{
    private string $baseUrl;

    public function __construct()
    {
        $token = config('telegram.bot_token');
        $this->baseUrl = "https://api.telegram.org/bot{$token}";
    }

    public function sendMessage(
        string $chatId,
        string $text,
        ?array $replyMarkup = null
    ): bool {
        $payload = [
            'chat_id'    => $chatId,
            'text'       => $text,
            'parse_mode' => 'HTML',
        ];

        if ($replyMarkup) {
            $payload['reply_markup'] = json_encode($replyMarkup);
        }

        try {
            $response = Http::timeout(10)
                ->post("{$this->baseUrl}/sendMessage", $payload);

            if (!$response->successful()) {
                Log::warning('Telegram API error', [
                    'chat_id'  => $chatId,
                    'status'   => $response->status(),
                    'response' => $response->json(),
                ]);
                return false;
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Telegram API exception', [
                'chat_id' => $chatId,
                'error'   => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function setWebhook(string $url, string $secret): bool
    {
        $response = Http::post("{$this->baseUrl}/setWebhook", [
            'url'          => $url,
            'secret_token' => $secret,
        ]);

        return $response->successful();
    }
}
```

---

## ЭТАП 3: Сборка сообщений

### 3.1 Создать `app/Services/Telegram/OrderMessageBuilder.php`

Класс принимает модель Order и строит HTML-текст для Telegram.

**Шаблон сообщения (parse_mode: HTML):**

```
{mp_emoji} <b>{mp_name}</b>  │  {status_emoji} {status_label}
━━━━━━━━━━━━━━━━━━━━

📋 <b>Заказ #{order_external_id}</b>
🏬 {account_name}

🛒 <b>Товары:</b>
   {qty}× {item_name} — {price} сум
   ...

💰 <b>Итого: {total} сум</b>

📍 {delivery_address}
🏭 {warehouse_name}
👤 {buyer_name}

━━━━━━━━━━━━━━━━━━━━
📊 Сегодня: <b>{daily_count}</b> заказов · <b>{daily_sum}</b> сум
```

**Методы:**

```php
public function build(Order $order): array
{
    // Возвращает ['text' => string, 'reply_markup' => array]
}

private function buildItemsList(Order $order): string
{
    return $order->items->map(function ($item) {
        $prefix = $item->quantity > 1 ? "{$item->quantity}× " : '• ';
        $price = number_format($item->price, 0, '.', ' ');
        return "   {$prefix}{$item->name} — {$price} сум";
    })->implode("\n");
}

private function getDailyStats(Order $order): object
{
    return Order::query()
        ->where('marketplace', $order->marketplace)
        ->where('account_id', $order->account_id)
        ->whereDate('created_at', today())
        ->selectRaw('COUNT(*) as count, COALESCE(SUM(total), 0) as revenue')
        ->first();
}

private function buildInlineKeyboard(Order $order): array
{
    return [
        'inline_keyboard' => [[
            [
                'text' => '📋 Открыть в SellerMind',
                'url'  => config('telegram.app_url') . "/orders/{$order->id}",
            ],
        ]],
    ];
}

private function formatMoney(int|float $amount): string
{
    return number_format($amount, 0, '.', ' ');
}
```

---

## ЭТАП 4: Events и Listeners

### 4.1 Создать `app/Events/OrderReceived.php`

```php
class OrderReceived implements ShouldBroadcast
{
    public function __construct(public Order $order) {}
}
```

### 4.2 Создать `app/Listeners/SendOrderTelegramNotification.php`

```php
class SendOrderTelegramNotification implements ShouldQueue
{
    public function handle(OrderReceived $event): void
    {
        $order = $event->order;

        $subscriptions = TelegramSubscription::query()
            ->active()
            ->where('user_id', $order->user_id)
            ->where(function ($q) use ($order) {
                $q->whereNull('marketplace')
                  ->orWhere('marketplace', $order->marketplace);
            })
            ->where(function ($q) use ($order) {
                $q->whereNull('account_id')
                  ->orWhere('account_id', $order->account_id);
            })
            ->get();

        // Фильтр по типу уведомления
        $subscriptions = $subscriptions->filter(function ($sub) use ($order) {
            if ($order->status === 'new') return $sub->notify_new;
            if (in_array($order->status, ['cancelled', 'returned'])) return $sub->notify_cancel;
            return $sub->notify_status;
        });

        $builder = new OrderMessageBuilder();
        $message = $builder->build($order);

        $client = new TelegramApiClient();

        foreach ($subscriptions as $sub) {
            $client->sendMessage(
                $sub->chat_id,
                $message['text'],
                $message['reply_markup']
            );
        }
    }
}
```

### 4.3 Зарегистрировать в `EventServiceProvider`

```php
OrderReceived::class => [
    SendOrderTelegramNotification::class,
],
```

### 4.4 Добавить диспатч события

Найти в коде место где создаются заказы при синхронизации с маркетплейсами (скорее всего в сервисах синхронизации заказов) и добавить:

```php
event(new OrderReceived($order));
```

Также добавить диспатч при **смене статуса** заказа — создать отдельный Event `OrderStatusChanged` по аналогии.

---

## ЭТАП 5: Webhook и привязка аккаунта

### 5.1 Создать `app/Http/Controllers/TelegramWebhookController.php`

```php
class TelegramWebhookController extends Controller
{
    public function handle(Request $request): Response
    {
        // Верификация secret_token
        $secret = $request->header('X-Telegram-Bot-Api-Secret-Token');
        if ($secret !== config('telegram.webhook_secret')) {
            abort(403);
        }

        $message = $request->input('message');
        if (!$message) return response('ok');

        $chatId = $message['chat']['id'];
        $text = $message['text'] ?? '';

        // Обработка /start с deep-link токеном
        if (str_starts_with($text, '/start ')) {
            $token = str_replace('/start ', '', $text);
            $this->linkAccount($chatId, $token);
        }

        return response('ok');
    }

    private function linkAccount(string $chatId, string $token): void
    {
        // Найти токен в cache (сохранён при генерации в UI)
        $userId = Cache::get("telegram_link:{$token}");

        if (!$userId) {
            (new TelegramApiClient())->sendMessage(
                $chatId,
                '❌ Ссылка устарела. Сгенерируйте новую в настройках SellerMind.'
            );
            return;
        }

        // Создать подписку по умолчанию
        TelegramSubscription::updateOrCreate(
            ['user_id' => $userId, 'chat_id' => $chatId],
            [
                'notify_new'    => true,
                'notify_status' => true,
                'notify_cancel' => true,
                'is_active'     => true,
            ]
        );

        Cache::forget("telegram_link:{$token}");

        (new TelegramApiClient())->sendMessage(
            $chatId,
            "✅ Аккаунт привязан!\n\nТеперь вы будете получать уведомления о заказах из SellerMind.\n\nНастроить уведомления → " . config('telegram.app_url') . '/settings/telegram'
        );
    }
}
```

### 5.2 Маршрут

```php
// routes/api.php
Route::post('/telegram/webhook', [TelegramWebhookController::class, 'handle'])
    ->withoutMiddleware(['auth', 'throttle']);
```

---

## ЭТАП 6: Livewire UI настроек

### 6.1 Создать `app/Livewire/Settings/TelegramSettings.php`

**Функционал:**
- Генерация deep-link для привязки (кнопка → Cache::put токен на 15 мин → URL `https://t.me/{bot_username}?start={token}`)
- Отображение статуса привязки (есть chat_id или нет)
- Чекбоксы: `notify_new`, `notify_status`, `notify_cancel`
- Выбор маркетплейса (select, null = все)
- Выбор аккаунта (select, null = все)
- Toggle `daily_summary` + выбор `summary_time`
- Кнопка «Отправить тестовое уведомление»
- Кнопка «Отвязать Telegram»

### 6.2 Blade-шаблон

Использовать существующий UI-стиль SellerMind (Tailwind, карточки с тенями, синий primary цвет). Разместить в разделе настроек `/settings/telegram`.

---

## ЭТАП 7: Дневной отчёт

### 7.1 Создать Artisan-команду `app/Console/Commands/SendDailySummary.php`

```php
class SendDailySummary extends Command
{
    protected $signature = 'telegram:daily-summary';

    public function handle(): void
    {
        $now = now()->format('H:i');

        $subscriptions = TelegramSubscription::query()
            ->active()
            ->where('daily_summary', true)
            ->where('summary_time', $now)
            ->with('user')
            ->get();

        foreach ($subscriptions as $sub) {
            // Собрать статистику за день по всем маркетплейсам пользователя
            // Сформировать сводное сообщение
            // Отправить через TelegramApiClient
        }
    }
}
```

**Шаблон дневного отчёта:**

```
📊 <b>Итоги дня — {дата}</b>
━━━━━━━━━━━━━━━━━━━━

🟣 Uzum Market
   Заказов: {n} · Выручка: {sum} сум

🟪 Wildberries
   Заказов: {n} · Выручка: {sum} сум

...

━━━━━━━━━━━━━━━━━━━━
💰 <b>ИТОГО: {total_orders} заказов · {total_revenue} сум</b>
❌ Отмены: {cancels} · ↩️ Возвраты: {returns}
```

### 7.2 Зарегистрировать в Scheduler

```php
// app/Console/Kernel.php или routes/console.php
Schedule::command('telegram:daily-summary')->everyMinute();
```

---

## ЭТАП 8: Artisan-команды для управления

```php
// Тестовая отправка
php artisan telegram:test {user_id}

// Установка webhook
php artisan telegram:set-webhook
```

---

## КРИТИЧЕСКИЕ ТРЕБОВАНИЯ

1. **Очереди**: Все отправки через `ShouldQueue` — не блокировать основной процесс
2. **Логирование**: Логировать все ошибки Telegram API в `telegram` канал
3. **Rate limiting**: Учитывать лимит Telegram — макс 30 сообщений/сек на бота
4. **Безопасность**: НЕ передавать телефон/точный адрес покупателя — только имя и тип доставки
5. **Форматирование сумм**: `number_format($amount, 0, '.', ' ')` с пробелами (123 680)
6. **Encoding**: HTML entities для спецсимволов в названиях товаров (`htmlspecialchars`)
7. **Привязать к существующей модели Order** — изучить текущую структуру заказов в проекте и адаптировать поля

---

## ПОРЯДОК ВЫПОЛНЕНИЯ

1. Изучить существующую структуру моделей Order, Account, User в проекте
2. Создать config и миграции
3. Создать TelegramApiClient
4. Создать OrderMessageBuilder + тесты
5. Создать Events/Listeners
6. Создать WebhookController + маршрут
7. Создать Livewire UI настроек
8. Создать дневной отчёт
9. Интегрировать диспатч событий в существующий код синхронизации заказов
10. Протестировать полный цикл: заказ → event → notification → Telegram
