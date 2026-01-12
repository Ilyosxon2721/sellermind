# Инструкция: Включение WebSocket (Reverb)

**Цель:** Переключить проект с HTTP polling на настоящий WebSocket для real-time обновлений

**Текущее состояние:**
- Broadcasting: null или database (не настроен)
- WebSocket код: ✅ готов (bootstrap.js)
- HTTP Polling: ✅ работает как fallback

**Целевое состояние:**
- Broadcasting: Reverb (WebSocket)
- Real-time обновления без задержек
- HTTP Polling как fallback для cPanel

---

## 📋 Шаги для включения WebSocket

### Шаг 1: Создать .env файл

Если у вас нет `.env` файла:

```bash
# Скопировать из примера
cp .env.example .env
```

### Шаг 2: Сгенерировать ключи

```bash
# Сгенерировать APP_KEY
php artisan key:generate

# Сгенерировать Reverb ключи (вручную)
# Выполните эти команды и скопируйте результат в .env:

# Для REVERB_APP_KEY
openssl rand -base64 32

# Для REVERB_APP_SECRET
openssl rand -base64 32
```

### Шаг 3: Настроить .env для WebSocket

Откройте `.env` и установите:

```env
# Broadcasting Configuration
BROADCAST_CONNECTION=reverb

# Reverb Keys (вставьте сгенерированные ключи)
REVERB_APP_ID=sellermind
REVERB_APP_KEY=your-generated-key-here
REVERB_APP_SECRET=your-generated-secret-here

# Development Configuration
REVERB_HOST=localhost
REVERB_PORT=8090
REVERB_SERVER_HOST=0.0.0.0
REVERB_SERVER_PORT=8090
REVERB_SCHEME=http  # В development используйте http

# Pusher Protocol (используется Reverb)
PUSHER_APP_ID=${REVERB_APP_ID}
PUSHER_APP_KEY=${REVERB_APP_KEY}
PUSHER_APP_SECRET=${REVERB_APP_SECRET}
PUSHER_HOST=${REVERB_HOST}
PUSHER_PORT=${REVERB_SERVER_PORT}
PUSHER_SCHEME=${REVERB_SCHEME}

# Vite (для frontend)
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

### Шаг 4: Очистить кеш конфигурации

```bash
php artisan config:clear
php artisan cache:clear
```

### Шаг 5: Запустить Reverb сервер

**В отдельном терминале:**

```bash
# Запустить Reverb
php artisan reverb:start

# Вы должны увидеть:
# ┌ Starting Reverb server...
# │ Host: 0.0.0.0
# │ Port: 8090
# │
# └ Server running...
```

**Оставьте этот терминал открытым!**

### Шаг 6: Запустить Queue Worker

**В ещё одном терминале:**

```bash
# Queue worker нужен для broadcast событий
php artisan queue:work

# Вы должны увидеть:
# [2024-01-10 12:00:00][1] Processing: ...
```

### Шаг 7: Скомпилировать frontend

```bash
# Development режим
npm run dev

# Или для production
npm run build
```

### Шаг 8: Проверить подключение

Откройте браузер и зайдите на сайт. Откройте Console (F12):

```javascript
// Проверить состояние WebSocket
window.getWebSocketState()

// Должно вернуть:
// { connected: true, socketId: "12345.67890", connection: WebSocket }
```

Если видите `connected: true` - **WebSocket работает!** 🎉

---

## 🧪 Тестирование WebSocket

### Тест 1: Подключение

В Console браузера:

```javascript
window.getWebSocketState()
// Expected: { connected: true, ... }
```

### Тест 2: Подписка на канал

```javascript
window.subscribeToChannel('test-channel')
// Console: ✅ Subscribed to channel: test-channel
```

### Тест 3: Отправка события (из backend)

В другом терминале:

```bash
php artisan tinker
```

В tinker:

```php
// Создать тестовое событие
broadcast(new \App\Events\MarketplaceSyncProgress(
    companyId: 1,
    marketplaceAccountId: 1,
    status: 'progress',
    message: 'Test WebSocket',
    progress: 50
));
```

В Console браузера вы должны увидеть сообщение!

### Тест 4: Real-world test

1. Зайдите на страницу заказов
2. Запустите синхронизацию с Wildberries
3. Вы должны видеть прогресс в реальном времени без перезагрузки страницы

---

## 📊 Сравнение: До и После

### До (HTTP Polling)
```
Клиент                    Сервер
  │                         │
  ├──── GET /api/orders ────>│
  │<──── 200 OK ─────────────┤
  │ (ждёт 15 секунд)        │
  ├──── GET /api/orders ────>│
  │<──── 200 OK ─────────────┤
  │ (ждёт 15 секунд)        │
  └──── ...                  │

❌ Задержка: 0-15 секунд
❌ Лишний трафик (постоянные запросы)
✅ Работает на cPanel
```

### После (WebSocket)
```
Клиент                    Сервер
  │                         │
  ├─── WebSocket Open ─────>│
  │<─────── Connected ──────┤
  │    (держит соединение)  │
  │                         │
  │      [Событие!]         │
  │<──── sync.progress ─────┤
  │  (мгновенно)           │

✅ Мгновенно (0ms задержка)
✅ Меньше трафика
✅ Bidirectional
❌ Требует VPS (не работает на shared hosting)
```

---

## 🔧 Troubleshooting

### Проблема: "WebSocket not connected"

**Причины:**
1. Reverb не запущен
2. Порт 8090 занят
3. REVERB_APP_KEY не установлен

**Решение:**
```bash
# Проверить, запущен ли Reverb
ps aux | grep reverb

# Проверить, занят ли порт
netstat -tulpn | grep 8090

# Проверить .env
cat .env | grep REVERB_APP_KEY

# Перезапустить Reverb
php artisan reverb:restart
```

### Проблема: "События не приходят"

**Причины:**
1. Queue worker не запущен
2. BROADCAST_CONNECTION не установлен
3. Канал не подписан

**Решение:**
```bash
# Запустить queue worker
php artisan queue:work

# Проверить конфигурацию
php artisan config:show broadcasting

# Проверить подписки в Console
window.subscribeToChannel('company.1')
```

### Проблема: "Reverb падает постоянно"

**Причины:**
1. Ошибки в коде событий
2. Недостаточно памяти

**Решение:**
```bash
# Посмотреть логи
tail -f storage/logs/laravel.log

# Запустить с debug
php artisan reverb:start --debug

# Увеличить memory_limit в php.ini
memory_limit = 256M
```

---

## 🎯 Production Deployment

Для production используйте Supervisor или Forge:

### Вариант 1: Supervisor

Создайте `/etc/supervisor/conf.d/sellermind-reverb.conf`:

```ini
[program:sellermind-reverb]
command=php /path/to/project/artisan reverb:start
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
redirect_stderr=true
stdout_logfile=/path/to/project/storage/logs/reverb.log
```

```bash
# Применить конфигурацию
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start sellermind-reverb
```

### Вариант 2: Laravel Forge

См. подробную инструкцию в `REVERB_FORGE_SETUP.md`

---

## ✅ Checklist

- [ ] Скопировали .env.example в .env
- [ ] Сгенерировали APP_KEY
- [ ] Сгенерировали REVERB_APP_KEY и REVERB_APP_SECRET
- [ ] Установили BROADCAST_CONNECTION=reverb
- [ ] Очистили кеш конфигурации
- [ ] Запустили Reverb сервер
- [ ] Запустили Queue Worker
- [ ] Скомпилировали frontend (npm run dev/build)
- [ ] Проверили подключение в Console (connected: true)
- [ ] Протестировали отправку события
- [ ] Проверили real-world функционал (синхронизация заказов)

---

## 📚 Дополнительные ресурсы

- [REVERB_FORGE_SETUP.md](REVERB_FORGE_SETUP.md) - Production setup для Forge
- [QUEUE_WORKER_PRODUCTION_GUIDE.md](QUEUE_WORKER_PRODUCTION_GUIDE.md) - Queue workers
- [WEBSOCKET_ANALYSIS.md](WEBSOCKET_ANALYSIS.md) - Анализ WebSocket конфигурации
- [Laravel Reverb Documentation](https://laravel.com/docs/11.x/reverb)

---

## 🎉 Результат

После выполнения всех шагов у вас будет:

- ✅ Real-time обновления заказов без задержек
- ✅ Мгновенный прогресс синхронизации
- ✅ Меньше нагрузки на сервер (нет постоянных HTTP запросов)
- ✅ Лучший пользовательский опыт
- ✅ HTTP Polling как fallback (автоматически, если WebSocket недоступен)

**Готово! Наслаждайтесь real-time обновлениями! 🚀**
