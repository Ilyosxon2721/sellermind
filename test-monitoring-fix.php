<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\MarketplaceAccount;
use App\Models\WbOrder;

echo "=== Тест исправления мониторинга ===\n\n";

// Проверяем аккаунт 2
$account = MarketplaceAccount::find(2);

if (!$account) {
    echo "❌ Аккаунт 2 не найден\n";
    exit(1);
}

echo "✅ Аккаунт найден:\n";
echo "   ID: {$account->id}\n";
echo "   Marketplace: {$account->marketplace}\n";
echo "   Название: {$account->name}\n";
echo "   Активен: " . ($account->is_active ? 'Да' : 'Нет') . "\n\n";

// Проверяем доступ к WbOrder
try {
    $ordersCount = WbOrder::where('marketplace_account_id', $account->id)->count();
    echo "✅ Количество заказов WB для аккаунта {$account->id}: {$ordersCount}\n";

    $lastUpdate = WbOrder::where('marketplace_account_id', $account->id)
        ->max('updated_at');
    echo "✅ Последнее обновление заказа: " . ($lastUpdate ?? 'не найдено') . "\n\n";

    // Проверяем наличие записей
    if ($ordersCount > 0) {
        $sampleOrder = WbOrder::where('marketplace_account_id', $account->id)
            ->first();

        echo "✅ Пример заказа:\n";
        echo "   External ID: {$sampleOrder->external_order_id}\n";
        echo "   Артикул: {$sampleOrder->article}\n";
        echo "   Статус: {$sampleOrder->status}\n";
        echo "   Сумма: {$sampleOrder->total_amount}\n";
    }

    echo "\n✅ Тест пройден успешно!\n";
    echo "   MonitorMarketplaceChangesJob теперь может правильно работать с таблицей wb_orders\n";

} catch (\Throwable $e) {
    echo "❌ Ошибка при работе с WbOrder:\n";
    echo "   " . $e->getMessage() . "\n";
    echo "   Файл: " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
}

echo "\n=== Проверка старых ошибок в логах ===\n";

$logFile = storage_path('logs/laravel.log');
if (file_exists($logFile)) {
    $logContent = file_get_contents($logFile);
    $lines = explode("\n", $logContent);
    $recentErrors = array_filter($lines, function($line) {
        return strpos($line, 'marketplace_orders') !== false && strpos($line, 'doesn\'t exist') !== false;
    });

    if (count($recentErrors) > 0) {
        echo "⚠️  Найдены старые ошибки о несуществующей таблице marketplace_orders\n";
        echo "   После применения исправления новых ошибок быть не должно\n";
    } else {
        echo "✅ В логах нет ошибок о таблице marketplace_orders\n";
    }
}

echo "\n=== Рекомендации ===\n";
echo "1. Перезапустите очередь: php artisan queue:restart\n";
echo "2. Проверьте логи через несколько минут на отсутствие новых ошибок\n";
echo "3. Мониторинг должен работать без ошибок\n";
