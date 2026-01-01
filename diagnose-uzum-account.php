<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\MarketplaceAccount;
use App\Models\MarketplaceShop;
use App\Models\UzumOrder;

$accountId = $argv[1] ?? 6;

echo "=== Диагностика аккаунта Uzum (ID: {$accountId}) ===\n\n";

$account = MarketplaceAccount::find($accountId);

if (!$account) {
    echo "❌ Аккаунт не найден!\n";
    exit(1);
}

if ($account->marketplace !== 'uzum') {
    echo "❌ Это не аккаунт Uzum! Marketplace: {$account->marketplace}\n";
    exit(1);
}

echo "✅ Аккаунт найден:\n";
echo "   ID: {$account->id}\n";
echo "   Название: " . ($account->name ?: 'без названия') . "\n";
echo "   Активен: " . ($account->is_active ? 'Да' : 'Нет') . "\n";
echo "   Подключён: {$account->connected_at}\n\n";

// Проверяем shops
echo "📦 Магазины (Shops):\n";
$shops = MarketplaceShop::where('marketplace_account_id', $account->id)->get();

if ($shops->isEmpty()) {
    echo "   ❌ Магазины не найдены!\n";
    echo "   → Синхронизируйте магазины через API\n\n";
} else {
    echo "   Всего магазинов: {$shops->count()}\n\n";

    foreach ($shops as $shop) {
        echo "   Shop:\n";
        echo "      External ID: {$shop->external_id}\n";
        echo "      Название: {$shop->name}\n";
        echo "      Создан: {$shop->created_at}\n";
        echo "\n";
    }
}

// Проверяем заказы
echo "📋 Заказы (Orders):\n";
$ordersCount = UzumOrder::where('marketplace_account_id', $account->id)->count();
echo "   Всего заказов: {$ordersCount}\n";

if ($ordersCount > 0) {
    $latestOrder = UzumOrder::where('marketplace_account_id', $account->id)
        ->orderBy('created_at', 'desc')
        ->first();
    echo "   Последний заказ: {$latestOrder->created_at}\n";
    echo "   ✅ Заказы синхронизируются\n";
} else {
    echo "   ⚠️  Заказов нет!\n";
}
echo "\n";

// Проверяем логи ошибок
echo "📝 Последние ошибки API (из логов):\n";
$logFile = storage_path('logs/laravel.log');

if (file_exists($logFile)) {
    exec("tail -500 {$logFile} | grep -i 'account.*{$account->id}.*403\\|uzum.*403' | tail -5", $errorLines);

    if (empty($errorLines)) {
        echo "   ✅ Ошибок 403 не найдено\n";
    } else {
        echo "   ⚠️  Найдены ошибки 403:\n";
        foreach ($errorLines as $line) {
            // Извлекаем сообщение об ошибке
            if (preg_match('/"message":"([^"]+)"/', $line, $matches)) {
                echo "      - {$matches[1]}\n";
            } elseif (preg_match('/"error":"([^"]+)"/', $line, $matches)) {
                echo "      - {$matches[1]}\n";
            }
        }
    }
} else {
    echo "   ⚠️  Лог-файл не найден\n";
}
echo "\n";

// Анализ и рекомендации
echo "=== АНАЛИЗ ===\n\n";

$shopIds = $shops->pluck('external_id')->filter()->values()->all();

if (empty($shopIds)) {
    echo "❌ ПРОБЛЕМА: Shop IDs не найдены\n";
    echo "\n";
    echo "РЕШЕНИЕ:\n";
    echo "1. Синхронизируйте магазины через API Uzum\n";
    echo "2. Проверьте что API токен активен\n";
    echo "\n";
} elseif ($ordersCount === 0) {
    echo "⚠️  ПРОБЛЕМА: Магазины есть (IDs: " . implode(', ', $shopIds) . "), но заказов нет\n";
    echo "\n";
    echo "Возможные причины:\n";
    echo "1. API токен не имеет доступа к этим shop IDs\n";
    echo "2. У магазинов действительно нет заказов\n";
    echo "3. API токен не имеет прав на чтение заказов\n";
    echo "\n";
    echo "РЕШЕНИЕ:\n";
    echo "1. Проверьте API токен в личном кабинете Uzum\n";
    echo "2. Убедитесь что токен имеет права:\n";
    echo "   - Чтение заказов (orders:read)\n";
    echo "   - Доступ к магазинам с ID: " . implode(', ', $shopIds) . "\n";
    echo "3. Пересоздайте токен если нужно\n";
    echo "4. Обновите credentials аккаунта\n";
    echo "\n";
    echo "Команда для обновления токена:\n";
    echo "php artisan tinker\n";
    echo "\$account = App\\Models\\MarketplaceAccount::find({$account->id});\n";
    echo "\$account->credentials = ['api_token' => 'НОВЫЙ_ТОКЕН', 'shop_ids' => [" . implode(', ', $shopIds) . "]];\n";
    echo "\$account->save();\n";
    echo "\n";
} else {
    echo "✅ Всё работает корректно!\n";
    echo "   Shop IDs: " . implode(', ', $shopIds) . "\n";
    echo "   Заказов: {$ordersCount}\n";
}

echo "\n=== Диагностика завершена ===\n";
