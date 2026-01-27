<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$sku = App\Models\Warehouse\Sku::where('sku_code', 'FH25201-S-PINK')->first();
echo "Warehouse SKU ID: {$sku->id}\n";
echo "Entries in stock_ledger:\n";

$entries = App\Models\Warehouse\StockLedger::where('sku_id', $sku->id)->orderBy('occurred_at')->get();
foreach ($entries as $e) {
    echo "  {$e->occurred_at} | qty={$e->qty_delta} | type={$e->source_type}\n";
}
echo "Total: " . $entries->sum('qty_delta') . "\n";

// Also check variant
$variant = $sku->productVariant;
if ($variant) {
    echo "\nProductVariant:\n";
    echo "  stock_default: {$variant->stock_default}\n";
}
