# Настройка Queue Worker для синхронизации

## Проблема
Синхронизация заказов с Wildberries работает в **асинхронном режиме** через систему очередей Laravel. Это позволяет:
- Не блокировать браузер при долгой синхронизации
- Получать прогресс через WebSocket
- Обрабатывать несколько синхронизаций параллельно

## Решение

### 1. Настройка .env
Убедитесь, что в `.env` установлено:
```env
QUEUE_CONNECTION=database
```

### 2. Запуск Queue Worker

#### Вариант А: Вручную (для разработки)
Откройте отдельный терминал и выполните:
```bash
cd /Applications/MAMP/htdocs/sellermind-ai
php artisan queue:work
```

Оставьте этот терминал открытым. Worker будет обрабатывать задачи в фоне.

#### Вариант Б: Через скрипт
```bash
./queue-worker.sh
```

#### Вариант В: В фоновом режиме (не рекомендуется для разработки)
```bash
nohup php artisan queue:work > storage/logs/queue-worker.log 2>&1 &
```

### 3. Проверка работы
После запуска worker'а попробуйте синхронизацию заказов в интерфейсе.
Вы должны увидеть прогресс и сообщения через WebSocket.

## Команды для управления очередью

### Посмотреть количество задач в очереди
```bash
php artisan queue:work --once
```

### Очистить застрявшие задачи
```bash
php artisan queue:clear database
```

### Посмотреть провалившиеся задачи
```bash
php artisan queue:failed
```

### Повторить провалившиеся задачи
```bash
php artisan queue:retry all
```

## Production Setup

Для production рекомендуется использовать **Supervisor** для автоматического перезапуска worker'а:

```ini
[program:sellermind-queue-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/sellermind-ai/artisan queue:work --sleep=3 --tries=3 --timeout=300
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/path/to/sellermind-ai/storage/logs/worker.log
```

## Troubleshooting

### Синхронизация не работает
1. Проверьте, что worker запущен
2. Проверьте логи: `tail -f storage/logs/laravel.log`
3. Очистите кеш: `php artisan config:clear`

### Worker завис
```bash
# Найти процесс
ps aux | grep "queue:work"

# Убить процесс
kill -9 <PID>

# Перезапустить
php artisan queue:work
```
