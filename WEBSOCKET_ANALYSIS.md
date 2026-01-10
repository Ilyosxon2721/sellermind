# WebSocket Configuration Analysis

**Дата:** 2026-01-10
**Проверка:** Конфигурация WebSocket (Laravel Reverb) и обмен данными

---

## ✅ Что работает правильно

### 1. Backend Broadcasting Configuration

**config/broadcasting.php:**
- ✅ Правильно настроен драйвер `reverb`
- ✅ Использует переменные окружения (`REVERB_APP_KEY`, `REVERB_APP_SECRET`, etc.)
- ✅ Default connection: `env('BROADCAST_CONNECTION', 'null')`

**routes/channels.php:**
- ✅ Настроен private channel для пользователей: `App.Models.User.{id}`
- ✅ Комментарий о публичных каналах для упрощения авторизации

### 2. Events Broadcasting

Найдено **5 событий**, которые используют broadcasting:

1. **MarketplaceSyncProgress** - прогресс синхронизации
   - Channel: `company.{companyId}.marketplace.{accountId}.sync`
   - Event name: `sync.progress`
   - Data: status, message, progress (0-100), data, timestamp

2. **MarketplaceOrdersUpdated** - обновление заказов
   - Channel: `company.{companyId}.marketplace.{accountId}.orders`
   - Event name: `orders.updated`
   - Data: company_id, marketplace_account_id, new_orders_count, stats, timestamp

3. **MarketplaceDataChanged** - изменение данных маркетплейса
4. **StockUpdated** - обновление остатков
5. **UzumOrderUpdated** - обновление заказа Uzum

Все события правильно реализуют `ShouldBroadcast` interface.

### 3. Frontend WebSocket Implementation

**resources/js/bootstrap.js:**
- ✅ Глобальный WebSocket connection с автоматическим reconnect
- ✅ Правильная конфигурация URL: `wss://host:port/app/{key}`
- ✅ Поддержка Pusher protocol (version 7)
- ✅ Автоматическое переподключение через 5 секунд
- ✅ Custom events для страниц: `websocket:connected`, `websocket:disconnected`, `websocket:message`
- ✅ Управление подписками с deduplication
- ✅ Re-subscribe после reconnect

**Ключевые функции:**
```javascript
window.subscribeToChannel(channelName)    // Подписка на канал
window.unsubscribeFromChannel(channelName) // Отписка
window.getWebSocketState()                 // Получение состояния
```

### 4. HTTP Polling Fallback

**resources/js/polling.js:**
- ✅ Полнофункциональный PollingManager для cPanel hosting
- ✅ Автоматическая пауза при скрытии вкладки
- ✅ Поддержка авторизации (Bearer token)
- ✅ Параметр `last_check` для оптимизации
- ✅ Обработка ошибок и reconnect логика

**API:**
```javascript
window.pollingManager.start(key, endpoint, callback, interval)
window.pollingManager.stop(key)
window.pollingManager.stopAll()
```

### 5. Usage Example (Orders Page)

**resources/views/pages/marketplace/orders.blade.php:**
- ✅ Подписка на несколько каналов одновременно
- ✅ Обработка событий `orders.updated` и `sync.progress`
- ✅ Deduplicate логика для предотвращения повторных обработок
- ✅ Fallback на polling если WebSocket недоступен

**Channels subscribed:**
```javascript
'company.' + companyId
'company.' + companyId + '.marketplace.' + accountId + '.orders'
'company.' + companyId + '.marketplace.' + accountId + '.sync'
'company.' + companyId + '.marketplace.' + accountId + '.data'
'marketplace-account.' + accountId
```

---

## ⚠️ Найденные проблемы

### Проблема 1: Дублирование BROADCAST_CONNECTION в .env.example

**Серьёзность:** 🟡 Средняя (confusion, может привести к ошибкам)

**Описание:**
В файле `.env.example` переменная `BROADCAST_CONNECTION` определена **ДВАЖДЫ**:

```env
# Строка 40 (VPS секция)
BROADCAST_CONNECTION=redis

# Строка 85 (Reverb секция)
BROADCAST_CONNECTION=reverb
```

**Проблема:**
- Вторая строка (85) перезаписывает первую (40)
- Пользователи могут не заметить первое определение
- На VPS с Redis можно использовать как `redis`, так и `reverb` для broadcasting
- Неясно, какое значение использовать

**Рекомендация:**
Удалить первое определение (строка 40) и оставить только одно в секции Reverb с комментарием.

### Проблема 2: BROADCAST_DRIVER вместо BROADCAST_CONNECTION

**Серьёзность:** 🔴 Критическая (неправильная конфигурация)

**Описание:**
В `.env.example` строка 98 использует неправильную переменную:

```env
# Неправильно:
BROADCAST_DRIVER=pusher
```

**Проблема:**
- Laravel использует `BROADCAST_CONNECTION`, НЕ `BROADCAST_DRIVER`
- В `config/broadcasting.php` line 18: `'default' => env('BROADCAST_CONNECTION', 'null')`
- Переменная `BROADCAST_DRIVER` игнорируется Laravel
- Это может привести к тому, что broadcasting вообще не будет работать

**Рекомендация:**
Удалить строку `BROADCAST_DRIVER=pusher` (она не нужна, т.к. BROADCAST_CONNECTION уже установлен).

### Проблема 3: Отсутствие VITE_PUSHER_* переменных

**Серьёзность:** 🟢 Низкая (legacy compatibility)

**Описание:**
Для работы с Laravel Echo через Pusher protocol нужны переменные `VITE_PUSHER_*`, но они не все определены.

**Текущая конфигурация:**
```env
# .env.example имеет:
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

**Проблема:**
Если кто-то решит использовать Laravel Echo вместо нативного WebSocket, потребуются дополнительные переменные:
```env
VITE_PUSHER_APP_KEY="${REVERB_APP_KEY}"
VITE_PUSHER_HOST="${REVERB_HOST}"
VITE_PUSHER_PORT="${REVERB_PORT}"
VITE_PUSHER_SCHEME="${REVERB_SCHEME}"
```

**Рекомендация:**
Добавить эти переменные для совместимости (опционально).

### Проблема 4: Порты Reverb конфликтуют

**Серьёзность:** 🟡 Средняя (может вызвать путаницу)

**Описание:**
В `.env.example`:
```env
REVERB_PORT=8090          # Порт для клиента (frontend)
REVERB_SERVER_PORT=8090   # Порт сервера (backend)
```

**Проблема:**
- Оба порта одинаковые (8090), что правильно для стандартной установки
- Но в production через Nginx proxy, клиент подключается на порт 443 (HTTPS)
- Конфигурация не отражает production setup

**Рекомендация:**
Добавить комментарий и пример для production:
```env
# Development (прямое подключение)
REVERB_PORT=8090
REVERB_SERVER_PORT=8090

# Production (через Nginx proxy на порту 443/8080)
# REVERB_PORT=443          # Клиент подключается через Nginx
# REVERB_SERVER_PORT=8080  # Reverb слушает на внутреннем порту
```

### Проблема 5: REVERB_HOST для production

**Серьёзность:** 🟡 Средняя

**Описание:**
```env
REVERB_HOST=localhost
```

**Проблема:**
- В production должен быть реальный домен: `your-domain.com`
- `localhost` не будет работать с клиента

**Рекомендация:**
Добавить комментарий:
```env
# Development
REVERB_HOST=localhost

# Production
# REVERB_HOST=your-domain.com
```

---

## 🔧 Исправления

### Fix 1: Очистить дублирование BROADCAST_CONNECTION

**Файл:** `.env.example`

**Удалить строку 40:**
```diff
- BROADCAST_CONNECTION=redis
```

**Обновить строку 85 с комментарием:**
```env
# Broadcasting Configuration
# For VPS: Use 'reverb' (recommended) or 'redis'
# For cPanel: Use 'database' or 'redis'
BROADCAST_CONNECTION=reverb
```

### Fix 2: Удалить BROADCAST_DRIVER

**Файл:** `.env.example`

**Удалить строку 98:**
```diff
- BROADCAST_DRIVER=pusher
```

### Fix 3: Улучшить комментарии для портов

**Файл:** `.env.example`

```env
# Laravel Reverb WebSocket Server
REVERB_APP_ID=sellermind

# CRITICAL: Generate secure random string (32+ chars)
# Example: openssl rand -base64 32
REVERB_APP_KEY=
REVERB_APP_SECRET=

# Development Configuration
REVERB_HOST=localhost          # Use your-domain.com in production
REVERB_PORT=8090               # Client connection port (443 in production with Nginx)
REVERB_SERVER_HOST=0.0.0.0
REVERB_SERVER_PORT=8090        # Internal server port (8080 in production)
REVERB_SCHEME=https            # Always https in production

# Production Example (through Nginx proxy):
# REVERB_HOST=your-domain.com
# REVERB_PORT=443
# REVERB_SERVER_PORT=8080
# REVERB_SCHEME=https
```

### Fix 4: Добавить VITE_PUSHER_* для совместимости

**Файл:** `.env.example`

```env
# Vite Broadcasting Variables (Pusher Protocol Compatibility)
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"

# Legacy Pusher compatibility (if using Laravel Echo)
VITE_PUSHER_APP_KEY="${REVERB_APP_KEY}"
VITE_PUSHER_HOST="${REVERB_HOST}"
VITE_PUSHER_PORT="${REVERB_PORT}"
VITE_PUSHER_SCHEME="${REVERB_SCHEME}"
```

---

## ✅ Итоговая оценка

### Общее состояние: 🟢 ХОРОШО (с минорными исправлениями)

**Сильные стороны:**
- ✅ WebSocket реализация работает корректно
- ✅ Правильная архитектура с global connection
- ✅ Отличный fallback на HTTP polling
- ✅ Proper event broadcasting setup
- ✅ Автоматический reconnect
- ✅ Deduplicate подписок

**Требуют исправления:**
- ⚠️ Дублирование BROADCAST_CONNECTION в .env.example
- ⚠️ Неправильная переменная BROADCAST_DRIVER
- ⚠️ Улучшить комментарии для production

---

## 📋 Чеклист для deployment

### Development
- [x] WebSocket подключение работает
- [x] События broadcast'ятся правильно
- [x] Frontend получает сообщения
- [x] Deduplicate работает
- [x] Reconnect работает

### Production (перед запуском)
- [ ] Установить BROADCAST_CONNECTION=reverb
- [ ] Сгенерировать REVERB_APP_KEY и REVERB_APP_SECRET
- [ ] Установить REVERB_HOST=your-domain.com
- [ ] Установить REVERB_PORT=443 (через Nginx)
- [ ] Установить REVERB_SERVER_PORT=8080 (внутренний)
- [ ] Настроить Nginx WebSocket proxy (см. REVERB_FORGE_SETUP.md)
- [ ] Настроить Supervisor/Forge daemon для Reverb
- [ ] Проверить SSL сертификат для WebSocket
- [ ] Протестировать подключение: `wscat -c wss://your-domain.com/app`

### Тестирование WebSocket

1. **Проверить подключение в браузере:**
   ```javascript
   // Откройте DevTools Console
   window.getWebSocketState()
   // Должно вернуть: { connected: true, socketId: "...", connection: WebSocket }
   ```

2. **Проверить подписки:**
   ```javascript
   window.subscribeToChannel('test-channel')
   // Console: ✅ Subscribed to channel: test-channel
   ```

3. **Протестировать broadcast из backend:**
   ```php
   // В tinker или контроллере
   broadcast(new \App\Events\MarketplaceSyncProgress(
       companyId: 1,
       marketplaceAccountId: 1,
       status: 'progress',
       message: 'Testing WebSocket',
       progress: 50
   ));
   ```

4. **Проверить получение сообщения:**
   ```javascript
   // В Console должно появиться
   // 📦 Sync progress: { status: 'progress', message: 'Testing WebSocket', ... }
   ```

---

## 🔍 Debugging WebSocket

### Проблема: WebSocket не подключается

**Проверки:**
1. Reverb запущен: `ps aux | grep reverb`
2. Порт открыт: `netstat -tulpn | grep 8090`
3. REVERB_APP_KEY установлен в .env
4. Nginx proxy настроен (в production)
5. SSL сертификат валиден (в production)

**Логи:**
```bash
# Reverb логи
tail -f storage/logs/reverb.log

# Laravel логи
tail -f storage/logs/laravel.log

# Nginx логи (production)
tail -f /var/log/nginx/error.log
```

### Проблема: Сообщения не доходят

**Проверки:**
1. BROADCAST_CONNECTION=reverb (не null!)
2. Event implements ShouldBroadcast
3. broadcastOn() возвращает Channel/PrivateChannel
4. Queue worker запущен (события broadcast'ятся через очередь)
5. Канал правильно подписан на frontend

**Debug broadcast:**
```php
// Включить debug в .env
BROADCAST_CONNECTION=log

// Проверить storage/logs/laravel.log
```

### Проблема: Дублирующиеся сообщения

**Решение:**
- Используйте deduplicate логику (уже реализовано в orders.blade.php)
- Проверьте, что не подписываетесь дважды на один канал
- Используйте `{ once: true }` для event listeners

---

## 📚 Дополнительные ресурсы

- [REVERB_FORGE_SETUP.md](REVERB_FORGE_SETUP.md) - Настройка Reverb на Forge
- [Laravel Broadcasting](https://laravel.com/docs/11.x/broadcasting)
- [Laravel Reverb](https://laravel.com/docs/11.x/reverb)
- [Pusher Protocol](https://pusher.com/docs/channels/library_auth_reference/pusher-websockets-protocol/)

---

**Вывод:** WebSocket конфигурация работает правильно, но требует исправления дублирующихся переменных в .env.example и улучшения комментариев для production deployment.
