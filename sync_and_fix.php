<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\MarketplaceAccount;
use App\Services\Marketplaces\Wildberries\WildberriesHttpClient;
use App\Services\Marketplaces\Wildberries\WildberriesOrderService;
use App\Models\WbOrder;

$account = MarketplaceAccount::where('marketplace', 'wb')
    ->where('is_active', true)
    ->first();

echo "Using account: {$account->id} ({$account->name})\n\n";

$httpClient = new WildberriesHttpClient($account);
$orderService = new WildberriesOrderService();

// 1. Получаем новые заказы из WB API
echo "=== Fetching new orders from /api/v3/orders/new ===\n";
$newOrdersResponse = $httpClient->get('marketplace', '/api/v3/orders/new');
$newOrders = $newOrdersResponse['orders'] ?? [];
echo "Found " . count($newOrders) . " new orders in WB API\n\n";

if (!empty($newOrders)) {
    echo "New orders from API:\n";
    foreach ($newOrders as $order) {
        echo "  ID: {$order['id']}, created: {$order['createdAt']}\n";
    }
    echo "\n";
}

// 2. Проверяем статусы этих заказов
if (!empty($newOrders)) {
    $newOrderIds = array_map(fn($o) => intval($o['id']), $newOrders);

    echo "=== Getting statuses for new orders ===\n";
    $statusResponse = $httpClient->post('marketplace', '/api/v3/orders/status', [
        'orders' => $newOrderIds,
    ]);

    $statuses = $statusResponse['orders'] ?? [];
    foreach ($statuses as $s) {
        echo "  ID: {$s['id']}, supplierStatus: " . ($s['supplierStatus'] ?? 'null') . ", wbStatus: " . ($s['wbStatus'] ?? 'null') . "\n";
    }
    echo "\n";
}

// 3. Проверяем заказы в нашей БД со статусом new
echo "=== Orders with status='new' in DB ===\n";
$dbNewOrders = WbOrder::where('marketplace_account_id', $account->id)
    ->where(function($q) {
        $q->where('status', 'new')
          ->orWhere('wb_status_group', 'new');
    })
    ->get();

echo "Found " . $dbNewOrders->count() . " orders with status='new' in DB\n\n";

if ($dbNewOrders->count() > 0) {
    $dbOrderIds = $dbNewOrders->pluck('external_order_id')->map(fn($id) => intval($id))->toArray();

    echo "Getting WB API statuses for these orders...\n";
    $statusResponse = $orderService->getOrdersStatus($account, $dbOrderIds);
    $statuses = $statusResponse['orders'] ?? [];

    $updated = 0;
    foreach ($statuses as $statusData) {
        $orderId = $statusData['id'] ?? null;
        $wbStatus = $statusData['wbStatus'] ?? null;
        $supplierStatus = $statusData['supplierStatus'] ?? null;

        if (!$orderId) continue;

        $order = $dbNewOrders->firstWhere('external_order_id', (string) $orderId);
        if (!$order) continue;

        // Маппинг статусов
        $normalizedStatus = mapStatus($supplierStatus, $wbStatus);
        $statusGroup = mapGroup($supplierStatus, $wbStatus);

        if ($order->status !== $normalizedStatus || $order->wb_status_group !== $statusGroup) {
            $order->update([
                'wb_status' => $wbStatus,
                'wb_supplier_status' => $supplierStatus,
                'status' => $normalizedStatus,
                'status_normalized' => $normalizedStatus,
                'wb_status_group' => $statusGroup,
            ]);
            echo "  Updated {$orderId}: {$order->status} -> {$normalizedStatus}\n";
            $updated++;
        }
    }
    echo "\nUpdated: {$updated} orders\n";
}

// 4. Итоговая статистика
echo "\n=== Final Status Counts ===\n";
$stats = WbOrder::where('marketplace_account_id', $account->id)
    ->selectRaw('status, count(*) as cnt')
    ->groupBy('status')
    ->get();

foreach ($stats as $s) {
    echo "{$s->status}: {$s->cnt}\n";
}

function mapStatus(?string $supplierStatus, ?string $wbStatus): string
{
    if (in_array($supplierStatus, ['cancel', 'reject']) ||
        in_array($wbStatus, ['canceled', 'canceled_by_client', 'declined_by_client', 'defect'])) {
        return 'cancelled';
    }
    if (in_array($wbStatus, ['delivered', 'sold_from_store', 'sold']) ||
        $supplierStatus === 'receive') {
        return 'completed';
    }
    if ($supplierStatus === 'complete' ||
        in_array($wbStatus, ['on_way_to_client', 'on_way_from_client', 'ready_for_pickup', 'at_deliverypoint', 'at_sortcenter', 'sorted'])) {
        return 'in_delivery';
    }
    if ($supplierStatus === 'confirm') {
        return 'in_assembly';
    }
    return 'new';
}

function mapGroup(?string $supplierStatus, ?string $wbStatus): string
{
    if (in_array($supplierStatus, ['cancel', 'reject']) ||
        in_array($wbStatus, ['canceled', 'canceled_by_client', 'declined_by_client', 'defect'])) {
        return 'canceled';
    }
    if (in_array($wbStatus, ['delivered', 'sold_from_store', 'sold']) ||
        $supplierStatus === 'receive') {
        return 'archive';
    }
    if ($supplierStatus === 'complete' ||
        in_array($wbStatus, ['on_way_to_client', 'on_way_from_client', 'ready_for_pickup', 'at_deliverypoint', 'at_sortcenter', 'sorted'])) {
        return 'shipping';
    }
    if ($supplierStatus === 'confirm') {
        return 'assembling';
    }
    return 'new';
}
