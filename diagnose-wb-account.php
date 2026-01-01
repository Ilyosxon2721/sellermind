<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\MarketplaceAccount;
use App\Models\WbOrder;
use App\Models\Supply;

$accountId = $argv[1] ?? null;

if (!$accountId) {
    echo "Usage: php diagnose-wb-account.php <account_id>\n";
    echo "\nДоступные WB аккаунты:\n";
    $wbAccounts = MarketplaceAccount::where('marketplace', 'wb')->get();
    foreach ($wbAccounts as $acc) {
        echo "   ID: {$acc->id} - " . ($acc->name ?: $acc->getDisplayName()) . "\n";
    }
    exit(1);
}

echo "=== Диагностика аккаунта Wildberries (ID: {$accountId}) ===\n\n";

$account = MarketplaceAccount::find($accountId);

if (!$account) {
    echo "❌ Аккаунт не найден!\n";
    exit(1);
}

if ($account->marketplace !== 'wb') {
    echo "❌ Это не аккаунт Wildberries! Marketplace: {$account->marketplace}\n";
    exit(1);
}

echo "✅ Аккаунт найден:\n";
echo "   ID: {$account->id}\n";
echo "   Название: " . ($account->name ?: $account->getDisplayName()) . "\n";
echo "   Активен: " . ($account->is_active ? 'Да' : 'Нет') . "\n";
echo "   Подключён: {$account->connected_at}\n\n";

// Проверяем заказы
echo "📋 Заказы (Orders):\n";
$ordersCount = WbOrder::where('marketplace_account_id', $account->id)->count();
echo "   Всего заказов: {$ordersCount}\n";

if ($ordersCount > 0) {
    $latestOrder = WbOrder::where('marketplace_account_id', $account->id)
        ->orderBy('created_at', 'desc')
        ->first();
    echo "   Последний заказ создан в БД: {$latestOrder->created_at}\n";
    echo "   Последний заказ обновлён в БД: {$latestOrder->updated_at}\n";

    // Группировка по статусам
    $statusGroups = WbOrder::where('marketplace_account_id', $account->id)
        ->selectRaw('wb_status_group, COUNT(*) as count')
        ->groupBy('wb_status_group')
        ->get();

    echo "\n   Группировка по статусам:\n";
    foreach ($statusGroups as $group) {
        echo "      {$group->wb_status_group}: {$group->count} заказов\n";
    }

    // Последние 5 заказов
    echo "\n   Последние 5 заказов:\n";
    $recentOrders = WbOrder::where('marketplace_account_id', $account->id)
        ->orderBy('created_at', 'desc')
        ->limit(5)
        ->get();

    foreach ($recentOrders as $order) {
        echo "      Order #{$order->external_order_id}: status={$order->status}, wb_status_group={$order->wb_status_group}, supply_id=" . ($order->supply_id ?: 'null') . ", updated_at={$order->updated_at}\n";
    }
} else {
    echo "   ⚠️  Заказов нет!\n";
}
echo "\n";

// Проверяем поставки
echo "📦 Поставки (Supplies):\n";
$suppliesCount = Supply::where('marketplace_account_id', $account->id)->count();
echo "   Всего поставок: {$suppliesCount}\n";

if ($suppliesCount > 0) {
    $latestSupply = Supply::where('marketplace_account_id', $account->id)
        ->orderBy('created_at', 'desc')
        ->first();
    echo "   Последняя поставка: {$latestSupply->created_at}\n";

    // Группировка по статусам
    $supplyStatusGroups = Supply::where('marketplace_account_id', $account->id)
        ->selectRaw('status, COUNT(*) as count')
        ->groupBy('status')
        ->get();

    echo "\n   Группировка по статусам:\n";
    foreach ($supplyStatusGroups as $group) {
        echo "      {$group->status}: {$group->count} поставок\n";
    }

    // Последние 5 поставок
    echo "\n   Последние 5 поставок:\n";
    $recentSupplies = Supply::where('marketplace_account_id', $account->id)
        ->orderBy('created_at', 'desc')
        ->limit(5)
        ->get();

    foreach ($recentSupplies as $supply) {
        $ordersInSupply = WbOrder::where('marketplace_account_id', $account->id)
            ->where('supply_id', $supply->external_supply_id)
            ->count();
        echo "      Supply {$supply->external_supply_id}: status={$supply->status}, orders={$ordersInSupply}, updated_at={$supply->updated_at}\n";
    }
}
echo "\n";

// Проверяем последние логи API
echo "📝 Проверка API (последние запросы):\n";
$logFile = storage_path('logs/laravel.log');

if (file_exists($logFile)) {
    // Ищем последние успешные запросы к WB API
    exec("tail -1000 {$logFile} | grep -i 'account.*{$account->id}.*WB' | tail -10", $apiLines);

    if (empty($apiLines)) {
        echo "   ⚠️  Последних запросов к WB API не найдено\n";
    } else {
        echo "   Последние запросы к WB API:\n";
        foreach ($apiLines as $line) {
            // Извлекаем timestamp и сообщение
            if (preg_match('/\[(.*?)\].*?(WB.*?)(\{|$)/', $line, $matches)) {
                echo "      [{$matches[1]}] {$matches[2]}\n";
            }
        }
    }
} else {
    echo "   ⚠️  Лог-файл не найден\n";
}
echo "\n";

// Анализ и рекомендации
echo "=== АНАЛИЗ ===\n\n";

if ($ordersCount === 0) {
    echo "❌ ПРОБЛЕМА: Заказов нет в БД\n";
    echo "\n";
    echo "РЕШЕНИЕ:\n";
    echo "1. Запустите синхронизацию заказов:\n";
    echo "   php artisan wildberries:sync-orders {$account->id}\n";
    echo "2. Проверьте API токен в настройках аккаунта\n";
    echo "\n";
} else {
    // Проверяем были ли обновления недавно
    $latestUpdate = WbOrder::where('marketplace_account_id', $account->id)
        ->max('updated_at');

    $hoursSinceUpdate = now()->diffInHours($latestUpdate);

    if ($hoursSinceUpdate > 2) {
        echo "⚠️  ПРОБЛЕМА: Заказы не обновлялись {$hoursSinceUpdate} часов\n";
        echo "   Последнее обновление: {$latestUpdate}\n";
        echo "\n";
        echo "Возможные причины:\n";
        echo "1. Мониторинг заказов не запущен или остановился\n";
        echo "2. Очередь не работает\n";
        echo "3. API токен истёк или не имеет прав\n";
        echo "\n";
        echo "РЕШЕНИЕ:\n";
        echo "1. Проверьте работу очереди:\n";
        echo "   php artisan queue:work\n";
        echo "2. Запустите мониторинг заказов:\n";
        echo "   php artisan marketplace:monitor orders {$account->id}\n";
        echo "3. Принудительно синхронизируйте заказы:\n";
        echo "   php artisan wildberries:sync-orders {$account->id}\n";
        echo "\n";
    } else {
        echo "✅ Заказы обновляются регулярно\n";
        echo "   Последнее обновление: {$latestUpdate} ({$hoursSinceUpdate}ч назад)\n";
        echo "   Всего заказов: {$ordersCount}\n";

        // Проверяем что есть заказы в разных статусах
        $hasNew = WbOrder::where('marketplace_account_id', $account->id)
            ->where('wb_status_group', 'new')
            ->exists();
        $hasAssembling = WbOrder::where('marketplace_account_id', $account->id)
            ->where('wb_status_group', 'assembling')
            ->exists();
        $hasShipping = WbOrder::where('marketplace_account_id', $account->id)
            ->where('wb_status_group', 'shipping')
            ->exists();
        $hasArchive = WbOrder::where('marketplace_account_id', $account->id)
            ->where('wb_status_group', 'archive')
            ->exists();

        echo "\n   Распределение по статусам:\n";
        echo "      Новые (new): " . ($hasNew ? '✅' : '❌') . "\n";
        echo "      На сборке (assembling): " . ($hasAssembling ? '✅' : '❌') . "\n";
        echo "      В доставке (shipping): " . ($hasShipping ? '✅' : '❌') . "\n";
        echo "      Архив (archive): " . ($hasArchive ? '✅' : '❌') . "\n";
    }
}

echo "\n=== Диагностика завершена ===\n";
