# WebSocket (Reverb) Setup Guide

Этот документ описывает, как настроить WebSocket сервер (Laravel Reverb) для real-time обновлений в production.

## Статус: Опциональная функция

WebSocket настроен для graceful degradation:
- ✅ Если WebSocket **не запущен** - приложение работает нормально, просто без real-time обновлений
- ✅ Если WebSocket **запущен** - пользователи получают real-time обновления (заказы, синхронизации и т.д.)

## Быстрый старт (Production)

### 1. Генерация ключей

```bash
cd /home/forge/sellermind.uz/current
php artisan reverb:install
```

Это создаст REVERB_APP_KEY и REVERB_APP_SECRET в .env

### 2. Настройка .env

Обновите следующие переменные в `.env`:

```bash
# Broadcasting
BROADCAST_CONNECTION=reverb

# Reverb Configuration
REVERB_APP_ID=sellermind
REVERB_APP_KEY=<сгенерированный_ключ>
REVERB_APP_SECRET=<сгенерированный_секрет>
REVERB_HOST=sellermind.uz          # Ваш домен
REVERB_PORT=443                     # Порт для клиентов (443 с Nginx proxy)
REVERB_SERVER_HOST=0.0.0.0         # Bind на все интерфейсы
REVERB_SERVER_PORT=8080             # Внутренний порт сервера
REVERB_SCHEME=https                 # Всегда https в production

# Pusher (используется Reverb)
PUSHER_APP_ID=${REVERB_APP_ID}
PUSHER_APP_KEY=${REVERB_APP_KEY}
PUSHER_APP_SECRET=${REVERB_APP_SECRET}

# Vite (для frontend)
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="sellermind.uz"
VITE_REVERB_PORT="443"
VITE_REVERB_SCHEME="https"
```

### 3. Пересборка frontend

После изменения VITE_* переменных:

```bash
npm run build
```

### 4. Настройка Supervisor

Создайте файл `/etc/supervisor/conf.d/reverb.conf`:

```ini
[program:reverb]
process_name=%(program_name)s
command=/usr/bin/php8.4 /home/forge/sellermind.uz/current/artisan reverb:start
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=forge
numprocs=1
redirect_stderr=true
stdout_logfile=/home/forge/sellermind.uz/storage/logs/reverb.log
stopwaitsecs=3600
```

Запустите:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start reverb
```

### 5. Настройка Nginx

Добавьте в конфиг сайта (перед location /):

```nginx
# WebSocket proxy для Laravel Reverb
location /app {
    proxy_pass http://127.0.0.1:8080;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "upgrade";
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;
    proxy_read_timeout 3600;
    proxy_send_timeout 3600;
}
```

Перезагрузите Nginx:

```bash
sudo nginx -t
sudo systemctl reload nginx
```

### 6. Проверка

```bash
# Проверить что Reverb запущен
sudo supervisorctl status reverb

# Посмотреть логи
tail -f /home/forge/sellermind.uz/storage/logs/reverb.log

# Проверить что порт слушается
netstat -tlnp | grep 8080
```

Откройте сайт и проверьте консоль браузера:
- ✅ "✅ Global WebSocket connected" - все работает
- ⚠️  Никаких сообщений - WebSocket отключен (это нормально)

## Как отключить WebSocket

Если не хотите использовать WebSocket:

1. Не запускайте Reverb сервер
2. Удалите `VITE_REVERB_APP_KEY` из .env (или оставьте пустым)
3. Пересоберите: `npm run build`

Приложение будет работать нормально без real-time обновлений.

## Troubleshooting

### WebSocket не подключается

**Проблема:** В консоли браузера нет сообщения о подключении

**Решение:**
1. Проверьте что Reverb запущен: `sudo supervisorctl status reverb`
2. Проверьте логи: `tail -f storage/logs/reverb.log`
3. Проверьте что VITE_REVERB_APP_KEY установлен в .env
4. Пересоберите frontend: `npm run build`

### ERR_CONNECTION_REFUSED

**Проблема:** В консоли браузера ошибка "WebSocket connection failed"

**Решение:**
1. Проверьте Nginx конфиг (location /app)
2. Проверьте что Reverb слушает на 127.0.0.1:8080
3. Проверьте firewall

### 403 Forbidden на /app

**Проблема:** Nginx возвращает 403

**Решение:**
1. Проверьте что в Nginx есть `location /app`
2. Убедитесь что конфиг выше location /
3. Перезагрузите Nginx

## Полезные команды

```bash
# Запустить Reverb вручную (для тестирования)
php artisan reverb:start

# Остановить Reverb
sudo supervisorctl stop reverb

# Перезапустить Reverb
sudo supervisorctl restart reverb

# Посмотреть подключенные клиенты
# (в логах Reverb)
tail -f storage/logs/reverb.log | grep connected
```

## Что дает WebSocket

Когда WebSocket включен, пользователи получают real-time обновления:

- 📦 **Синхронизация заказов** - прогресс синхронизации в реальном времени
- 🔄 **Обновление статусов** - автоматическое обновление списков без перезагрузки
- 📊 **Dashboard** - статистика обновляется в реальном времени
- 🔔 **Уведомления** - мгновенные уведомления о событиях

Без WebSocket все это также работает, но требует перезагрузки страницы или использует HTTP polling.

## Дополнительно

- Reverb документация: https://laravel.com/docs/11.x/reverb
- Pusher Protocol: https://pusher.com/docs/channels/library_auth_reference/pusher-websockets-protocol
