# 🔌 Настройка Laravel Reverb WebSocket

## Что такое Reverb?

Laravel Reverb - это WebSocket сервер для realtime функций:
- Живые уведомления
- Чаты
- Обновление данных в реальном времени
- Broadcasting events

---

## 🏠 Локальная разработка (ваш Mac)

### 1. Проверьте .env файл

Убедитесь, что у вас правильные настройки. Откройте `.env` и проверьте:

```env
BROADCAST_CONNECTION=reverb

REVERB_APP_ID=sellermind
REVERB_APP_KEY=your-local-key  # Будет сгенерирован
REVERB_APP_SECRET=your-local-secret  # Будет сгенерирован
REVERB_HOST=localhost
REVERB_PORT=8090  # Порт для подключения клиентов
REVERB_SERVER_PORT=8090  # Порт сервера
REVERB_SCHEME=http  # Local = http

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

### 2. Запустите Reverb сервер

```bash
cd /Applications/MAMP/htdocs/sellermind-ai

# Запустите Reverb в отдельном терминале
php artisan reverb:start
```

**Должно показать:**
```
  INFO Server running...
  Local: http://0.0.0.0:8090
  Application key: sellermind
```

### 3. Запустите dev сервер

В другом терминале:

```bash
npm run dev
```

### 4. Проверьте подключение

Откройте браузер и проверьте консоль - ошибок WebSocket быть не должно.

---

## 🌐 Production (cPanel)

### ⚠️ Важно: Reverb НЕ работает на shared hosting!

**Проблема:** Reverb требует постоянно запущенный процесс WebSocket сервера. В cPanel (shared hosting) это невозможно без VPS/Dedicated сервера.

### ✅ Решение для cPanel:

**1. Отключить Reverb на production**

В `.env` на сервере:
```env
BROADCAST_CONNECTION=log  # Вместо reverb
```

**2. Использовать альтернативы:**

Если нужны realtime функции на production:
- **Pusher** (платный сервис, $29/месяц): https://pusher.com
- **Ably** (есть free tier): https://ably.com
- **Laravel Echo Server** (требует Node.js на сервере)
- **VPS** вместо shared hosting (DigitalOcean, AWS, Vultr)

---

## 📋 Чек-лист

### Local (Mac):
- [x] `.env` с `BROADCAST_CONNECTION=reverb`
- [x] Запущен `php artisan reverb:start`
- [x] Запущен `npm run dev`
- [ ] Нет ошибок WebSocket в консоли браузера

### Production (cPanel):
- [x] `.env` с `BROADCAST_CONNECTION=log`
- [x] Заполнены ключи Reverb (на будущее)
- [ ] Frontend собран и загружен
- [ ] Нет ошибок на production сайте

---

## 🛠️ Команды

### Локально:

```bash
# Запустить Reverb
php artisan reverb:start

# Сгенерировать новые ключи
php artisan reverb:install

# Проверить конфигурацию
php artisan config:show broadcasting
```

### На сервере:

```bash
# Проверить что broadcasting отключен
grep BROADCAST_CONNECTION .env
# Должно быть: BROADCAST_CONNECTION=log

# Очистить кэш
php artisan config:clear
```

---

## 🔧 Troubleshooting

### Ошибка: "WebSocket connection failed"

**Причина:** Reverb сервер не запущен

**Решение:**
```bash
php artisan reverb:start
```

---

### Ошибка: "Address already in use" (порт 8080/8090)

**Причина:** Порт занят другим процессом

**Решение 1:** Изменить порт в `.env`:
```env
REVERB_PORT=8091
REVERB_SERVER_PORT=8091
```

**Решение 2:** Найти и убить процесс:
```bash
lsof -i :8090
kill -9 <PID>
```

---

### На production нет realtime обновлений

**Это нормально!** В cPanel Reverb не работает. Используйте:
- Polling (обновление по таймеру)
- Pusher/Ably (платные сервисы)
- Перейти на VPS

---

## 📊 Когда нужен Reverb?

✅ **Используйте Reverb если:**
- Чат/мессенджер
- Live dashboard с обновлениями
- Уведомления в реальном времени
- Collaborative editing

❌ **Можно без Reverb:**
- Обычный CRUD
- Админ панель
- Маркетплейс интеграция
- Большинство веб-приложений

---

## 🎯 Итог

**Для разработки:** Reverb запущен локально ✅  
**Для production (cPanel):** Reverb отключен (broadcasting=log) ✅  

Если realtime критично - рассмотрите VPS или Pusher!
