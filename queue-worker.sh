#!/bin/bash
# Queue Worker для SellerMind AI
# Запускает worker для обработки фоновых задач (синхронизация с маркетплейсами)

cd "$(dirname "$0")"

echo "Starting queue worker..."
php artisan queue:work --sleep=3 --tries=3 --timeout=300
