#!/bin/bash

# Скрипт для запуска воркеров очереди
# Использование: ./start-queue-workers.sh [количество_воркеров]

WORKERS=${1:-3}  # По умолчанию 3 воркера
TIMEOUT=600      # Таймаут 10 минут
APP_DIR=$(dirname "$0")

echo "🚀 Запуск $WORKERS воркеров очереди..."
echo ""

# Создаём директорию для логов если её нет
mkdir -p "$APP_DIR/storage/logs"

# Останавливаем старые воркеры
echo "🛑 Остановка старых воркеров..."
pkill -f "queue:work" 2>/dev/null || true
sleep 2

# Проверяем что воркеры остановлены
RUNNING=$(ps aux | grep "queue:work" | grep -v grep | wc -l | tr -d ' ')
if [ "$RUNNING" -gt 0 ]; then
    echo "⚠️  Обнаружены запущенные воркеры ($RUNNING), убиваем принудительно..."
    pkill -9 -f "queue:work" 2>/dev/null || true
    sleep 1
fi

# Запускаем новые воркеры
echo "✅ Запуск $WORKERS новых воркеров..."
for i in $(seq 1 $WORKERS); do
    nohup php "$APP_DIR/artisan" queue:work \
        --timeout=$TIMEOUT \
        --sleep=3 \
        --tries=3 \
        > "$APP_DIR/storage/logs/queue-worker-$i.log" 2>&1 &

    PID=$!
    echo "   Воркер #$i запущен (PID: $PID)"
    sleep 0.5
done

echo ""
echo "✅ Запущено воркеров: $WORKERS"
echo ""

# Проверяем что воркеры запустились
sleep 2
RUNNING=$(ps aux | grep "queue:work" | grep -v grep | wc -l | tr -d ' ')
echo "📊 Активных воркеров: $RUNNING"
echo ""

if [ "$RUNNING" -eq "$WORKERS" ]; then
    echo "✅ Все воркеры успешно запущены!"
else
    echo "⚠️  Запущено воркеров ($RUNNING) меньше чем ожидалось ($WORKERS)"
fi

echo ""
echo "💡 Полезные команды:"
echo "   Проверить воркеры:    ps aux | grep queue:work"
echo "   Остановить воркеры:   pkill -f 'queue:work'"
echo "   Посмотреть логи:      tail -f storage/logs/queue-worker-1.log"
echo "   Проверить очередь:    php artisan queue:monitor database"
echo ""
