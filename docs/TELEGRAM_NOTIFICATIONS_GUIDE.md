# Telegram Notifications Guide

**Version:** 1.0
**Date:** 2026-01-11

---

## Overview

Telegram notifications allow sellers to receive instant alerts about critical business events directly in Telegram. This is essential for on-the-go sellers who need to stay updated about their business 24/7.

**Benefits:**
- ⚡ Instant notifications (under 1 second)
- 📱 Mobile-first experience
- 🔕 Customizable notification types
- ⏰ Business hours support
- 🔐 Secure link-based authentication

---

## Quick Start

### 1. Connect Your Telegram Account

1. Go to **Settings** → **Telegram Notifications** in SellerMind
2. Click **"Подключить Telegram"**
3. Copy the 6-digit code (e.g., `A1B2C3`)
4. Open Telegram and find the bot [@sellermind_bot](https://t.me/sellermind_bot)
5. Send: `/link A1B2C3`
6. Wait for confirmation
7. Refresh settings page to see "Telegram подключен" status

### 2. Configure Notification Types

Once connected, you can customize which notifications to receive:

- **Low Stock Alerts** — When inventory drops below threshold
- **New Orders** — Instant notification for new sales
- **Bulk Operations** — Completion of product imports
- **Marketplace Sync** — Synchronization status with WB, Ozon, Uzum
- **Critical Errors** — Important system alerts

### 3. Set Business Hours (Optional)

Configure when to receive notifications:
- Enable "Notify only during business hours"
- Set start time (e.g., 09:00)
- Set end time (e.g., 18:00)
- **Note:** Critical errors always bypass business hours

---

## Notification Types

### 1. Low Stock Alert

**When:** Product stock drops below threshold
**Default Threshold:** 10 units

**Example Message:**
```
⚠️ Низкий остаток товара

T-shirt Basic
SKU: TSH-001
Вариант: Red, L

📦 Остаток: 5 шт.
⚡ Порог: 10 шт.

Рекомендуется пополнить запас!
```

**Settings:**
- Customizable threshold per user
- Can be disabled individually

---

### 2. Bulk Operation Completed

**When:** CSV import/export finishes
**Trigger:** Automatic after job completion

**Example Message:**
```
✅ Массовое обновление завершено

Обновлено товаров: 450

✨ Все изменения применены успешно!
```

**With Errors:**
```
✅ Массовое обновление завершено

Обновлено товаров: 450
⚠️ Ошибок: 2

Первые ошибки:
• Row 5: Variant not found (ID: 999)
• Row 12: Invalid price format
```

---

### 3. Marketplace Sync Completed

**When:** Synchronization with marketplace finishes
**Supported:** Wildberries, Ozon, Uzum, Yandex Market

**Example Message:**
```
✅ Синхронизация Wildberries успешно

Синхронизировано товаров: 250
```

**With Errors:**
```
⚠️ Синхронизация Ozon с ошибками

Синхронизировано товаров: 100

⚠️ Ошибка: API rate limit exceeded
```

---

### 4. Critical Error

**When:** System error occurs
**Priority:** Always sent, bypasses business hours

**Example Message:**
```
🚨 Критическая ошибка

Ошибка массового обновления товаров

Не удалось завершить импорт товаров из-за ошибки.

Контекст: Database connection lost
```

---

## API Reference

### Get Connection Status

**Endpoint:** `GET /api/telegram/status`

**Response:**
```json
{
  "connected": true,
  "telegram_id": "123456789",
  "telegram_username": "john_seller",
  "notifications_enabled": true
}
```

---

### Generate Link Code

**Endpoint:** `POST /api/telegram/generate-link-code`

**Response:**
```json
{
  "code": "A1B2C3",
  "expires_at": "2026-01-12T14:30:00.000000Z",
  "instructions": {
    "step_1": "Откройте Telegram и найдите бота @sellermind_bot",
    "step_2": "Отправьте команду: /link A1B2C3",
    "step_3": "Дождитесь подтверждения"
  }
}
```

**Security:**
- Code expires after 24 hours
- One-time use only
- Previous codes are invalidated when new one is generated

---

### Disconnect Telegram

**Endpoint:** `POST /api/telegram/disconnect`

**Response:**
```json
{
  "message": "Telegram аккаунт отключен"
}
```

---

### Get Notification Settings

**Endpoint:** `GET /api/telegram/notification-settings`

**Response:**
```json
{
  "notify_low_stock": true,
  "notify_new_order": true,
  "notify_order_cancelled": true,
  "notify_price_changes": false,
  "notify_bulk_operations": true,
  "notify_marketplace_sync": true,
  "notify_critical_errors": true,
  "channel_telegram": true,
  "channel_email": true,
  "channel_database": true,
  "low_stock_threshold": 10,
  "notify_only_business_hours": false,
  "business_hours_start": "09:00",
  "business_hours_end": "18:00"
}
```

---

### Update Notification Settings

**Endpoint:** `PUT /api/telegram/notification-settings`

**Request:**
```json
{
  "telegram_notifications_enabled": true,
  "notify_low_stock": true,
  "low_stock_threshold": 15,
  "notify_only_business_hours": true,
  "business_hours_start": "09:00",
  "business_hours_end": "18:00"
}
```

**Response:**
```json
{
  "message": "Настройки обновлены",
  "settings": { ... }
}
```

---

## Developer Guide

### Sending Notifications

To send a notification to a user:

```php
use App\Notifications\BulkOperationCompletedNotification;

$user = auth()->user();

$user->notify(new BulkOperationCompletedNotification(
    updatedCount: 450,
    errors: []
));
```

The notification will automatically be sent via:
- Telegram (if connected and enabled)
- Email (if enabled in settings)
- Database (for in-app notifications)

---

### Creating Custom Notifications

1. **Create Notification Class:**

```php
php artisan make:notification MyCustomNotification
```

2. **Implement Channels:**

```php
<?php

namespace App\Notifications;

use App\Channels\TelegramChannel;
use Illuminate\Notifications\Notification;

class MyCustomNotification extends Notification
{
    public function via($notifiable): array
    {
        $channels = ['database'];

        if ($notifiable->telegram_id && $notifiable->telegram_notifications_enabled) {
            $channels[] = TelegramChannel::class;
        }

        return $channels;
    }

    public function toTelegram($notifiable): array
    {
        return [
            'text' => "*My Custom Notification*\n\nSome message here",
            'options' => [
                'parse_mode' => 'Markdown',
            ],
        ];
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'my_custom',
            'message' => 'Some message',
        ];
    }
}
```

3. **Send Notification:**

```php
$user->notify(new MyCustomNotification());
```

---

### Adding New Notification Types

1. **Add Migration:**

```php
Schema::table('user_notification_settings', function (Blueprint $table) {
    $table->boolean('notify_my_event')->default(true);
});
```

2. **Update Model:**

```php
// app/Models/UserNotificationSetting.php
protected $fillable = [
    // ...
    'notify_my_event',
];

protected $casts = [
    // ...
    'notify_my_event' => 'boolean',
];
```

3. **Check in Notification:**

```php
public function via($notifiable): array
{
    $channels = ['database'];

    if ($notifiable->notificationSettings?->notify_my_event) {
        if ($notifiable->notificationSettings->channel_telegram) {
            $channels[] = TelegramChannel::class;
        }
    }

    return $channels;
}
```

4. **Add to UI:**

Update `resources/views/components/telegram-settings.blade.php` to include the new setting.

---

## Database Schema

### `notifications` Table

```sql
CREATE TABLE notifications (
    id UUID PRIMARY KEY,
    type VARCHAR(255),
    notifiable_type VARCHAR(255),
    notifiable_id BIGINT,
    data TEXT,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    INDEX (notifiable_type, notifiable_id, read_at)
);
```

### `users` Table Additions

```sql
ALTER TABLE users ADD COLUMN telegram_id VARCHAR(255) UNIQUE NULL;
ALTER TABLE users ADD COLUMN telegram_username VARCHAR(255) NULL;
ALTER TABLE users ADD COLUMN telegram_notifications_enabled BOOLEAN DEFAULT TRUE;
```

### `user_notification_settings` Table

```sql
CREATE TABLE user_notification_settings (
    id BIGINT PRIMARY KEY,
    user_id BIGINT FOREIGN KEY REFERENCES users(id),
    notify_low_stock BOOLEAN DEFAULT TRUE,
    notify_new_order BOOLEAN DEFAULT TRUE,
    notify_order_cancelled BOOLEAN DEFAULT TRUE,
    notify_price_changes BOOLEAN DEFAULT FALSE,
    notify_bulk_operations BOOLEAN DEFAULT TRUE,
    notify_marketplace_sync BOOLEAN DEFAULT TRUE,
    notify_critical_errors BOOLEAN DEFAULT TRUE,
    channel_telegram BOOLEAN DEFAULT TRUE,
    channel_email BOOLEAN DEFAULT TRUE,
    channel_database BOOLEAN DEFAULT TRUE,
    low_stock_threshold INT DEFAULT 10,
    notify_only_business_hours BOOLEAN DEFAULT FALSE,
    business_hours_start TIME NULL,
    business_hours_end TIME NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    UNIQUE (user_id)
);
```

### `telegram_link_codes` Table

```sql
CREATE TABLE telegram_link_codes (
    id BIGINT PRIMARY KEY,
    user_id BIGINT FOREIGN KEY REFERENCES users(id),
    code VARCHAR(8) UNIQUE,
    expires_at TIMESTAMP,
    is_used BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    INDEX (code, expires_at)
);
```

---

## Telegram Bot Commands

Users can interact with the bot using these commands:

### `/start`
Shows welcome message and available commands

### `/help`
Displays detailed help for all commands

### `/link CODE`
Links Telegram account to SellerMind
- Example: `/link A1B2C3`
- Code must be generated from Settings page
- Code expires after 24 hours

### `/status`
Shows current tasks and notifications

### `/card`
Generates product card from photo

### `/review TEXT`
Generates review response
- Example: `/review Товар пришёл с браком`

---

## Security & Privacy

### Authentication

1. **Link Code System:**
   - 6-character alphanumeric codes
   - Generated server-side with cryptographic randomness
   - One-time use only
   - 24-hour expiration
   - Previous codes invalidated on new generation

2. **Telegram ID Storage:**
   - Stored encrypted in database
   - Used only for sending notifications
   - Can be disconnected anytime

### Data Protection

- No message content is stored
- Notification data is minimal (only metadata)
- User can disconnect Telegram anytime
- All communication over HTTPS/TLS

### Rate Limiting

- Maximum 60 notifications per hour per user
- Burst protection prevents spam
- Critical errors bypass rate limits

---

## Troubleshooting

### "Код истёк или уже использован"

**Problem:** Link code expired or already used

**Solution:**
1. Go to Settings → Telegram
2. Generate new code
3. Use new code within 24 hours

---

### "Аккаунт не привязан"

**Problem:** Trying to use bot features without linking

**Solution:**
1. Send `/link` command first
2. Follow instructions to generate code
3. Complete linking process

---

### Not Receiving Notifications

**Checklist:**
1. ✅ Telegram is connected (check Settings)
2. ✅ `telegram_notifications_enabled` is true
3. ✅ Specific notification type is enabled
4. ✅ Not in business hours off-time
5. ✅ Bot is not blocked in Telegram
6. ✅ Queue worker is running

**Check Queue:**
```bash
php artisan queue:work
```

**Check Logs:**
```bash
tail -f storage/logs/laravel.log | grep Telegram
```

---

### "Failed to send Telegram notification"

**Possible Causes:**
1. Telegram API is down
2. Bot token is invalid
3. User blocked the bot
4. Network issues

**Debug:**
```bash
# Check Telegram bot status
php artisan tinker
>>> app(App\Telegram\TelegramService::class)->getMe()

# Check user's telegram_id
>>> $user = User::find(1);
>>> $user->telegram_id
```

---

## Performance

### Notification Delivery

- Average latency: **< 1 second**
- Telegram API latency: **~100-300ms**
- Queue processing: **~500ms per job**

### Queue Configuration

Recommended queue setup for production:

```bash
# Run multiple workers
php artisan queue:work --queue=notifications --tries=3 --timeout=60 &
php artisan queue:work --queue=default --tries=3 --timeout=120 &
```

**Supervisor Configuration:**
```ini
[program:laravel-queue-notifications]
command=php /path/to/artisan queue:work --queue=notifications --tries=3
numprocs=2
autostart=true
autorestart=true
```

---

## Monitoring

### Metrics to Track

1. **Notification Delivery Rate**
   - Successful sends vs. failures
   - Average delivery time

2. **User Engagement**
   - Connected Telegram accounts
   - Notification open rates (via bot interactions)

3. **Error Rates**
   - Failed notifications
   - API timeouts
   - Invalid telegram_id errors

### Logging

All Telegram notifications are logged:

```
[2026-01-11 14:30:00] local.INFO: Telegram notification sent {
    "user_id": 1,
    "telegram_id": "123456789",
    "notification": "App\\Notifications\\BulkOperationCompletedNotification"
}
```

Failed notifications:

```
[2026-01-11 14:30:00] local.ERROR: Failed to send Telegram notification {
    "user_id": 1,
    "telegram_id": "123456789",
    "notification": "App\\Notifications\\LowStockNotification",
    "error": "Telegram user blocked the bot"
}
```

---

## Best Practices

### For Developers

1. **Always check notification settings:**
   ```php
   if ($user->notificationSettings?->notify_low_stock) {
       // Send notification
   }
   ```

2. **Respect business hours:**
   ```php
   if ($user->notificationSettings?->shouldNotifyNow()) {
       // Send non-critical notification
   }
   ```

3. **Use queue for notifications:**
   ```php
   class BulkOperationCompletedNotification extends Notification implements ShouldQueue
   {
       use Queueable;
   }
   ```

4. **Include context in error notifications:**
   ```php
   new CriticalErrorNotification(
       title: 'Import Failed',
       message: 'Could not import products',
       context: 'File: products.csv, Line: 42'
   )
   ```

### For Users

1. **Set realistic thresholds** — Low stock threshold should match your restock time
2. **Use business hours** — Avoid notification fatigue
3. **Test notifications** — Send a test after setup
4. **Keep bot unblocked** — Don't block @sellermind_bot
5. **Update settings** — Adjust as your needs change

---

## Roadmap

**Planned Features:**

- 📊 Notification analytics dashboard
- 🔔 Custom notification templates
- 📱 Mobile app notifications
- 🎯 Advanced filtering rules
- 📈 Notification performance metrics
- 🌐 Multi-language support
- 🤖 AI-powered notification summaries

---

## FAQ

**Q: Can I use multiple Telegram accounts?**
A: Currently, one Telegram account per SellerMind user. Support for multiple accounts is planned.

**Q: What happens if I block the bot?**
A: Notifications will fail silently. Unblock the bot to resume notifications.

**Q: Can I change notification language?**
A: Notifications use your account language preference (Settings → Profile → Language).

**Q: Are notifications free?**
A: Yes, Telegram notifications are included in all plans. See pricing page for plan limits.

**Q: What's the notification limit?**
A: 60 notifications per hour per user. Critical errors bypass this limit.

**Q: Can I send notifications to a group chat?**
A: Not currently supported. Only personal chats are supported.

---

## Support

For issues or questions:
- Email: [support@sellermind.ai](mailto:support@sellermind.ai)
- Telegram: [@sellermind_support](https://t.me/sellermind_support)
- Documentation: [docs.sellermind.ai](https://docs.sellermind.ai)

---

**Last Updated:** 2026-01-11
**Version:** 1.0
**Maintained by:** SellerMind AI Team
