# Решение проблем с синхронизацией Wildberries

## Проблема
Синхронизация заказов Wildberries не запускается или зависает.

## Причины

### 1. Queue Worker не запущен
Синхронизация работает асинхронно через систему очередей Laravel. Если worker не запущен, задачи ставятся в очередь, но не выполняются.

### 2. Queue Worker завис
Worker может зависнуть при обработке большого количества данных или при ошибках API.

### 3. Застрявшие задачи в очереди
Если worker перезапустили неправильно, задачи могут остаться в очереди и блокировать новые.

## Диагностика

### Проверить, запущен ли worker:
```bash
ps aux | grep "queue:work"
```

Должно быть что-то вроде:
```
ilyosxon  71055  0.0  0.2  435415648  13616  s004  S+  9:24PM  0:05.32  php artisan queue:work
```

### Проверить количество задач в очереди:
```bash
php artisan tinker --execute="echo 'Jobs in queue: ' . DB::table('jobs')->count();"
```

Если больше 10-20 задач - очередь застряла.

### Проверить логи:
```bash
tail -f storage/logs/laravel.log | grep -i sync
```

## Решение

### Быстрое решение (рекомендуется):

1. **Убить зависший worker:**
```bash
ps aux | grep "queue:work" | grep -v grep | awk '{print $2}' | xargs kill -9
```

2. **Очистить застрявшие задачи:**
```bash
php artisan queue:clear database
```

3. **Запустить worker заново:**
```bash
nohup php artisan queue:work --tries=3 --timeout=300 > storage/logs/queue-worker.log 2>&1 &
```

4. **Проверить, что worker запущен:**
```bash
ps aux | grep "queue:work"
```

5. **Попробовать синхронизацию снова** в браузере

### Автоматический скрипт:

Создан скрипт `restart-queue-worker.sh`:
```bash
#!/bin/bash
# Перезапуск Queue Worker

echo "Stopping existing queue workers..."
ps aux | grep "queue:work" | grep -v grep | awk '{print $2}' | xargs kill -9 2>/dev/null

echo "Clearing stuck jobs..."
php artisan queue:clear database

echo "Starting new queue worker..."
nohup php artisan queue:work --tries=3 --timeout=300 > storage/logs/queue-worker.log 2>&1 &

sleep 2

echo "Queue worker status:"
ps aux | grep "queue:work" | grep -v grep

echo "Done!"
```

Использование:
```bash
chmod +x restart-queue-worker.sh
./restart-queue-worker.sh
```

## Проверка работоспособности

После перезапуска worker'а:

1. Откройте страницу заказов Wildberries
2. Нажмите кнопку **"Получить новые"** (для WB) или **"Синхронизировать"**
3. Должен появиться индикатор синхронизации
4. Проверьте логи:
```bash
tail -f storage/logs/laravel.log | grep -i "sync\|job"
```

Вы должны увидеть логи типа:
```
Processing: App\Jobs\Marketplace\SyncMarketplaceOrdersJob
Processed:  App\Jobs\Marketplace\SyncMarketplaceOrdersJob
```

## Production Setup

Для production рекомендуется использовать **Supervisor** для автоматического перезапуска worker'а.

См. `QUEUE_SETUP.md` для подробной инструкции.

## Частые ошибки

### "QUEUE_CONNECTION=sync"
Если в `.env` стоит `QUEUE_CONNECTION=sync`, задачи выполняются синхронно (блокируют браузер).
Измените на:
```env
QUEUE_CONNECTION=database
```

### Worker зависает на больших данных
Увеличьте timeout:
```bash
php artisan queue:work --timeout=600
```

### События WebSocket не доходят
Проверьте, что Reverb (WebSocket сервер) запущен:
```bash
php artisan reverb:start
```

## Мониторинг

### Следить за очередью в реальном времени:
```bash
watch -n 2 'php artisan tinker --execute="echo DB::table(\"jobs\")->count();"'
```

### Логи worker'а:
```bash
tail -f storage/logs/queue-worker.log
```

### Логи Laravel:
```bash
tail -f storage/logs/laravel.log | grep -E "Sync|Job|ERROR"
```
