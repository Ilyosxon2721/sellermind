#!/bin/bash
# Перезапуск Queue Worker для SellerMind AI

cd "$(dirname "$0")"

echo "🛑 Stopping existing queue workers..."
ps aux | grep "queue:work" | grep -v grep | awk '{print $2}' | xargs kill -9 2>/dev/null
sleep 1

echo "🧹 Clearing stuck jobs..."
php artisan queue:clear database

echo "🚀 Starting new queue worker..."
nohup php artisan queue:work --tries=3 --timeout=300 > storage/logs/queue-worker.log 2>&1 &

sleep 2

echo ""
echo "✅ Queue worker status:"
if ps aux | grep "queue:work" | grep -v grep > /dev/null; then
    ps aux | grep "queue:work" | grep -v grep
    echo ""
    echo "✅ Worker is running!"
else
    echo "❌ Worker failed to start!"
    exit 1
fi

echo ""
echo "📊 Jobs in queue:"
php artisan tinker --execute="echo DB::table('jobs')->count();"

echo ""
echo "✅ Done! You can now try syncing again."
