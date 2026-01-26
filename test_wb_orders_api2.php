<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\MarketplaceAccount;
use App\Services\Marketplaces\Wildberries\WildberriesHttpClient;

$account = MarketplaceAccount::where('marketplace', 'wb')
    ->where('is_active', true)
    ->first();

echo "Using account: {$account->id} ({$account->name})\n\n";

$httpClient = new WildberriesHttpClient($account);

// 1. Получаем заказы через /api/v3/orders
echo "=== Getting orders from /api/v3/orders ===\n";
$allOrders = $httpClient->get('marketplace', '/api/v3/orders', [
    'limit' => 20,
    'next' => 0,
]);
$orders = $allOrders['orders'] ?? [];
echo "Found " . count($orders) . " orders\n\n";

// Показываем полную структуру первого заказа
if (!empty($orders)) {
    echo "=== Full structure of first order ===\n";
    print_r($orders[0]);

    // Берем ID первых 5 заказов для проверки статуса
    $orderIds = array_map(fn($o) => intval($o['id']), array_slice($orders, 0, 5));

    echo "\n=== Getting statuses for orders: " . implode(', ', $orderIds) . " ===\n";

    $statusResponse = $httpClient->post('marketplace', '/api/v3/orders/status', [
        'orders' => $orderIds,
    ]);

    $statuses = $statusResponse['orders'] ?? [];
    echo "Status response for " . count($statuses) . " orders:\n\n";

    foreach ($statuses as $status) {
        echo "ID: {$status['id']}\n";
        echo "  supplierStatus: " . ($status['supplierStatus'] ?? 'N/A') . "\n";
        echo "  wbStatus: " . ($status['wbStatus'] ?? 'N/A') . "\n";
        echo "\n";
    }
}

// Проверим заказы со статусом new в БД
echo "\n=== Orders with status 'new' in DB ===\n";
$dbOrders = \App\Models\WbOrder::where('marketplace_account_id', $account->id)
    ->where('status', 'new')
    ->orderBy('ordered_at', 'desc')
    ->limit(10)
    ->get(['external_order_id', 'status', 'wb_status', 'wb_supplier_status', 'ordered_at']);

foreach ($dbOrders as $o) {
    echo "{$o->external_order_id}: wb_status={$o->wb_status}, supplier={$o->wb_supplier_status}, date={$o->ordered_at}\n";
}

if ($dbOrders->count() > 0) {
    $dbOrderIds = $dbOrders->pluck('external_order_id')->map(fn($id) => intval($id))->toArray();

    echo "\n=== Getting WB API statuses for these DB orders ===\n";

    $statusResponse = $httpClient->post('marketplace', '/api/v3/orders/status', [
        'orders' => $dbOrderIds,
    ]);

    $statuses = $statusResponse['orders'] ?? [];
    echo "Got " . count($statuses) . " statuses from API\n\n";

    foreach ($statuses as $status) {
        echo "ID: {$status['id']}\n";
        echo "  supplierStatus: " . ($status['supplierStatus'] ?? 'null') . "\n";
        echo "  wbStatus: " . ($status['wbStatus'] ?? 'null') . "\n";
        echo "\n";
    }
}

echo "\nDone!\n";
