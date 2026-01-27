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

// Получаем ВСЕ заказы с status='new'
$orders = WbOrder::where('marketplace_account_id', $account->id)
    ->where('status', 'new')
    ->get();

echo "Found " . $orders->count() . " orders with status='new'\n\n";

if ($orders->count() === 0) {
    echo "No orders to update!\n";
    exit;
}

$orderIds = $orders->pluck('external_order_id')->map(fn($id) => intval($id))->toArray();

echo "Order IDs: " . implode(', ', $orderIds) . "\n\n";

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

    $order = $orders->firstWhere('external_order_id', (string) $orderId);
    if (!$order) continue;

    $oldStatus = $order->status;

    // Маппинг статусов
    $normalizedStatus = mapWbStatusToInternal($supplierStatus, $wbStatus);
    $statusGroup = mapWbStatusToGroup($supplierStatus, $wbStatus);

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

// Проверяем результат
$remaining = WbOrder::where('marketplace_account_id', $account->id)
    ->where('status', 'new')
    ->count();

echo "Remaining orders with status='new': {$remaining}\n";

function mapWbStatusToInternal(?string $supplierStatus, ?string $wbStatus): string
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
        in_array($wbStatus, ['on_way_to_client', 'on_way_from_client', 'ready_for_pickup', 'at_deliverypoint', 'at_sortcenter'])) {
        return 'in_delivery';
    }
    if ($supplierStatus === 'confirm' ||
        in_array($wbStatus, ['sorted'])) {
        return 'in_assembly';
    }
    if ($supplierStatus === 'new' || $wbStatus === 'waiting') {
        return 'new';
    }
    return 'new';
}

function mapWbStatusToGroup(?string $supplierStatus, ?string $wbStatus): string
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
        in_array($wbStatus, ['on_way_to_client', 'on_way_from_client', 'ready_for_pickup', 'at_deliverypoint', 'at_sortcenter'])) {
        return 'shipping';
    }
    if ($supplierStatus === 'confirm' ||
        in_array($wbStatus, ['sorted'])) {
        return 'assembling';
    }
    return 'new';
}
