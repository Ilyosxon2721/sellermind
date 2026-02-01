<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\MarketplaceAccount;
use App\Models\WbOrder;
use App\Services\Marketplaces\Wildberries\WildberriesHttpClient;
use App\Services\Marketplaces\Wildberries\WildberriesOrderService;

$accountId = $argv[1] ?? 2;

echo "=== Тест синхронизации WB заказов ===\n\n";

$account = MarketplaceAccount::find($accountId);

if (!$account || $account->marketplace !== 'wb') {
    echo "❌ WB аккаунт с ID {$accountId} не найден\n";
    exit(1);
}

echo "Аккаунт: {$account->name}\n\n";

// Получаем несколько последних заказов и их updated_at ДО синхронизации
$ordersBefore = WbOrder::where('marketplace_account_id', $accountId)
    ->orderBy('id', 'desc')
    ->limit(5)
    ->get(['id', 'external_order_id', 'status', 'updated_at']);

echo "Заказы ДО синхронизации:\n";
foreach ($ordersBefore as $order) {
    echo "  Order #{$order->external_order_id}: updated_at={$order->updated_at}\n";
}

// Ждём 2 секунды чтобы была разница во времени
echo "\n⏳ Ожидание 2 секунды...\n";
sleep(2);

// Запускаем синхронизацию через WildberriesOrderService
$httpClient = new WildberriesHttpClient($account);
$orderService = new WildberriesOrderService($httpClient);

echo "⏳ Запускаем синхронизацию заказов...\n";
$result = $orderService->fetchAllOrders($account, 50);
echo "✅ Синхронизировано: {$result['synced']}, создано: {$result['created']}, обновлено: {$result['updated']}\n\n";

// Получаем те же заказы ПОСЛЕ синхронизации
$ordersAfter = WbOrder::where('marketplace_account_id', $accountId)
    ->whereIn('id', $ordersBefore->pluck('id'))
    ->get(['id', 'external_order_id', 'status', 'updated_at']);

echo "Заказы ПОСЛЕ синхронизации:\n";
$updatedCount = 0;
foreach ($ordersAfter as $order) {
    $before = $ordersBefore->firstWhere('id', $order->id);
    $changed = $before && $before->updated_at->ne($order->updated_at);
    $status = $changed ? '✅ ОБНОВЛЁН' : '❌ НЕ ИЗМЕНИЛСЯ';

    if ($changed) {
        $updatedCount++;
        $diff = $before->updated_at->diffInSeconds($order->updated_at);
        echo "  Order #{$order->external_order_id}: updated_at={$order->updated_at} {$status} (diff: {$diff}s)\n";
    } else {
        echo "  Order #{$order->external_order_id}: updated_at={$order->updated_at} {$status}\n";
    }
}

echo "\n";
if ($updatedCount === $ordersAfter->count()) {
    echo "✅ ВСЕ заказы обновили updated_at!\n";
} elseif ($updatedCount > 0) {
    echo "⚠️  Обновлено {$updatedCount} из {$ordersAfter->count()} заказов\n";
} else {
    echo "❌ НИ ОДИН заказ не обновил updated_at\n";
}
