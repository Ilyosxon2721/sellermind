<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\MarketplaceAccount;
use App\Services\Marketplaces\Wildberries\WildberriesOrderService;
use App\Models\WbOrder;

$account = MarketplaceAccount::where('marketplace', 'wb')
    ->where('is_active', true)
    ->first();

echo "Using account: {$account->id} ({$account->name})\n\n";

$orderService = new WildberriesOrderService();

// Получаем заказы с status='new' для обновления
$orders = WbOrder::where('marketplace_account_id', $account->id)
    ->whereNotIn('status', ['completed', 'canceled', 'cancelled'])
    ->whereNotNull('external_order_id')
    ->orderBy('ordered_at', 'desc')
    ->limit(50)
    ->get();

echo "Found " . $orders->count() . " orders to update\n\n";

if ($orders->count() === 0) {
    echo "No orders to update!\n";
    exit;
}

// Получаем ID заказов
$orderIds = $orders->pluck('external_order_id')->map(fn($id) => intval($id))->toArray();

echo "Fetching statuses from WB API for orders: " . implode(', ', array_slice($orderIds, 0, 10)) . "...\n\n";

// Получаем статусы от WB API
$statusesData = $orderService->getOrdersStatus($account, $orderIds);
$statuses = $statusesData['orders'] ?? [];

echo "Got " . count($statuses) . " statuses from API\n\n";

$updated = 0;

foreach ($statuses as $statusData) {
    $orderId = $statusData['id'] ?? null;
    $wbStatus = $statusData['wbStatus'] ?? null;
    $supplierStatus = $statusData['supplierStatus'] ?? null;

    if (!$orderId) continue;

    // Найти заказ в БД
    $order = $orders->firstWhere('external_order_id', (string) $orderId);
    if (!$order) continue;

    $oldStatus = $order->status;

    // Маппинг статусов (из UpdateWildberriesOrdersStatusJob)
    $normalizedStatus = mapWbStatusToInternal($supplierStatus, $wbStatus);
    $statusGroup = mapWbStatusToGroup($supplierStatus, $wbStatus);

    // Обновляем заказ
    $order->update([
        'wb_status' => $wbStatus,
        'wb_supplier_status' => $supplierStatus,
        'status' => $normalizedStatus,
        'status_normalized' => $normalizedStatus,
        'wb_status_group' => $statusGroup,
    ]);

    echo "Order {$orderId}: {$oldStatus} -> {$normalizedStatus} (wbStatus={$wbStatus}, supplier={$supplierStatus})\n";
    $updated++;
}

echo "\n\nUpdated: {$updated} orders\n";

// Функции маппинга из job
function mapWbStatusToInternal(?string $supplierStatus, ?string $wbStatus): string
{
    // 1. CANCELLED
    if (in_array($supplierStatus, ['cancel', 'reject']) ||
        in_array($wbStatus, ['canceled', 'canceled_by_client', 'declined_by_client', 'defect'])) {
        return 'cancelled';
    }

    // 2. COMPLETED
    if (in_array($wbStatus, ['delivered', 'sold_from_store', 'sold']) ||
        $supplierStatus === 'receive') {
        return 'completed';
    }

    // 3. IN_DELIVERY
    if ($supplierStatus === 'complete' ||
        in_array($wbStatus, ['on_way_to_client', 'on_way_from_client', 'ready_for_pickup', 'at_deliverypoint', 'at_sortcenter'])) {
        return 'in_delivery';
    }

    // 4. IN_ASSEMBLY
    if ($supplierStatus === 'confirm' ||
        in_array($wbStatus, ['sorted'])) {
        return 'in_assembly';
    }

    // 5. NEW
    if ($supplierStatus === 'new' || $wbStatus === 'waiting') {
        return 'new';
    }

    return 'new';
}

function mapWbStatusToGroup(?string $supplierStatus, ?string $wbStatus): string
{
    // 1. Cancelled
    if (in_array($supplierStatus, ['cancel', 'reject']) ||
        in_array($wbStatus, ['canceled', 'canceled_by_client', 'declined_by_client', 'defect'])) {
        return 'canceled';
    }

    // 2. Archive/Completed
    if (in_array($wbStatus, ['delivered', 'sold_from_store', 'sold']) ||
        $supplierStatus === 'receive') {
        return 'archive';
    }

    // 3. Shipping
    if ($supplierStatus === 'complete' ||
        in_array($wbStatus, ['on_way_to_client', 'on_way_from_client', 'ready_for_pickup', 'at_deliverypoint', 'at_sortcenter'])) {
        return 'shipping';
    }

    // 4. Assembling
    if ($supplierStatus === 'confirm' ||
        in_array($wbStatus, ['sorted'])) {
        return 'assembling';
    }

    // 5. New
    return 'new';
}
