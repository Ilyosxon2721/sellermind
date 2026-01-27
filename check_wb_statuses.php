<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

// Check specific orders from WB screenshot
echo "=== Checking specific orders 4521405432, 4521404669 ===\n";
$specific = DB::table('wb_orders')
    ->whereIn('external_order_id', ['4521405432', '4521404669'])
    ->select('external_order_id', 'status', 'wb_supplier_status', 'wb_status_group', 'ordered_at')
    ->get();
foreach ($specific as $o) {
    echo "{$o->external_order_id}: status={$o->status}, supplier={$o->wb_supplier_status}, group={$o->wb_status_group}\n";
}
if ($specific->isEmpty()) {
    echo "Orders not found in database!\n";
}

echo "\n=== Orders from 2026-01-24 ===\n";
$recent = DB::table('wb_orders')
    ->whereDate('ordered_at', '>=', '2026-01-24')
    ->select('external_order_id', 'status', 'wb_supplier_status', 'wb_status_group', 'ordered_at')
    ->orderBy('ordered_at', 'desc')
    ->limit(20)
    ->get();
foreach ($recent as $o) {
    echo "{$o->external_order_id}: status={$o->status}, supplier={$o->wb_supplier_status}, ordered={$o->ordered_at}\n";
}

// Get WB orders with their statuses
$orders = DB::table('wb_orders')
    ->select('external_order_id', 'status', 'status_normalized', 'wb_status', 'wb_supplier_status', 'wb_status_group')
    ->whereIn('status', ['new', 'in_assembly', 'confirm', 'pending'])
    ->orWhereIn('status_normalized', ['new', 'in_assembly'])
    ->orderBy('id', 'desc')
    ->limit(30)
    ->get();

echo "=== WB Orders Status Analysis ===\n\n";
echo sprintf("%-15s | %-15s | %-15s | %-15s | %-15s | %-15s\n",
    'Order ID', 'status', 'status_norm', 'wb_status', 'supplier_st', 'status_group');
echo str_repeat('-', 105) . "\n";

foreach ($orders as $o) {
    echo sprintf("%-15s | %-15s | %-15s | %-15s | %-15s | %-15s\n",
        $o->external_order_id,
        $o->status ?? '-',
        $o->status_normalized ?? '-',
        $o->wb_status ?? '-',
        $o->wb_supplier_status ?? '-',
        $o->wb_status_group ?? '-'
    );
}

echo "\n=== Status Counts ===\n";
$counts = DB::table('wb_orders')
    ->select('status', DB::raw('count(*) as cnt'))
    ->whereNotIn('status', ['cancelled', 'completed'])
    ->groupBy('status')
    ->get();

foreach ($counts as $c) {
    echo "status='{$c->status}': {$c->cnt}\n";
}

echo "\n=== wb_status_group Counts ===\n";
$groupCounts = DB::table('wb_orders')
    ->select('wb_status_group', DB::raw('count(*) as cnt'))
    ->whereNotIn('wb_status_group', ['canceled', 'archive'])
    ->whereNotNull('wb_status_group')
    ->groupBy('wb_status_group')
    ->get();

foreach ($groupCounts as $c) {
    echo "wb_status_group='{$c->wb_status_group}': {$c->cnt}\n";
}
