<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\MarketplaceAccount;
use App\Services\Marketplaces\Wildberries\WildberriesHttpClient;

// Получаем активный WB аккаунт
$account = MarketplaceAccount::where('marketplace', 'wb')
    ->where('is_active', true)
    ->first();

if (!$account) {
    echo "No active WB account found!\n";
    exit(1);
}

echo "Using account: {$account->id} ({$account->name})\n\n";

$httpClient = new WildberriesHttpClient($account);

// 1. Получаем новые заказы через /api/v3/orders/new
echo "=== /api/v3/orders/new ===\n";
try {
    $newOrders = $httpClient->get('marketplace', '/api/v3/orders/new');
    $orders = $newOrders['orders'] ?? [];
    echo "Found " . count($orders) . " new orders\n";

    if (!empty($orders)) {
        echo "\nFirst 3 orders structure:\n";
        foreach (array_slice($orders, 0, 3) as $order) {
            echo "---\n";
            echo "ID: " . ($order['id'] ?? 'N/A') . "\n";
            echo "supplierStatus: " . ($order['supplierStatus'] ?? 'N/A') . "\n";
            echo "wbStatus: " . ($order['wbStatus'] ?? 'N/A') . "\n";
            echo "supplyId: " . ($order['supplyId'] ?? 'N/A') . "\n";
            echo "createdAt: " . ($order['createdAt'] ?? 'N/A') . "\n";
            // print_r($order);
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// 2. Получаем все заказы через /api/v3/orders
echo "\n\n=== /api/v3/orders (limit=100) ===\n";
try {
    $allOrders = $httpClient->get('marketplace', '/api/v3/orders', [
        'limit' => 100,
        'next' => 0,
    ]);
    $orders = $allOrders['orders'] ?? [];
    echo "Found " . count($orders) . " orders\n";

    if (!empty($orders)) {
        echo "\nFirst 5 orders with statuses:\n";
        foreach (array_slice($orders, 0, 5) as $order) {
            echo "---\n";
            echo "ID: " . ($order['id'] ?? 'N/A') . "\n";
            echo "supplierStatus: " . ($order['supplierStatus'] ?? 'N/A') . "\n";
            echo "wbStatus: " . ($order['wbStatus'] ?? 'N/A') . "\n";
            echo "supplyId: " . ($order['supplyId'] ?? 'N/A') . "\n";
            echo "createdAt: " . ($order['createdAt'] ?? 'N/A') . "\n";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// 3. Проверяем статусы конкретных заказов через /api/v3/orders/status
echo "\n\n=== /api/v3/orders/status (for specific orders) ===\n";
$specificOrderIds = [4521405432, 4521404669]; // Заказы из скриншота

try {
    $statusResponse = $httpClient->post('marketplace', '/api/v3/orders/status', [
        'orders' => $specificOrderIds,
    ]);

    $statuses = $statusResponse['orders'] ?? [];
    echo "Status response for " . count($statuses) . " orders:\n";

    foreach ($statuses as $status) {
        echo "---\n";
        echo "ID: " . ($status['id'] ?? 'N/A') . "\n";
        echo "supplierStatus: " . ($status['supplierStatus'] ?? 'N/A') . "\n";
        echo "wbStatus: " . ($status['wbStatus'] ?? 'N/A') . "\n";
        print_r($status);
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n\nDone!\n";
