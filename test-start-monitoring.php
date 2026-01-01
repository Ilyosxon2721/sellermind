<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\MarketplaceAccount;
use App\Jobs\Marketplace\MonitorMarketplaceChangesJob;

echo "=== Запуск мониторинга маркетплейсов ===\n\n";

// Получаем все активные аккаунты
$accounts = MarketplaceAccount::where('is_active', true)->get();

echo "Найдено активных аккаунтов: " . $accounts->count() . "\n\n";

foreach ($accounts as $account) {
    echo "Запускаем мониторинг для:\n";
    echo "  ID: {$account->id}\n";
    echo "  Маркетплейс: {$account->marketplace}\n";
    echo "  Название: " . ($account->name ?: $account->getDisplayName()) . "\n";

    // Запускаем джобу мониторинга
    MonitorMarketplaceChangesJob::dispatch($account);

    echo "  ✅ Джоба добавлена в очередь\n\n";
}

// Проверяем очередь
$queueCount = DB::table('jobs')->count();
echo "Всего джоб в очереди: {$queueCount}\n";

echo "\n✅ Мониторинг запущен!\n";
echo "Следите за прогрессом: tail -f storage/logs/laravel.log | grep -i monitoring\n";
