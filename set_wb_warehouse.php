<?php
/**
 * Helper script to set warehouse_id for Wildberries account
 * 
 * Usage:
 * php set_wb_warehouse.php <account_id> <warehouse_id>
 * 
 * Example:
 * php set_wb_warehouse.php 2 506019
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

if ($argc < 3) {
    echo "❌ Usage: php set_wb_warehouse.php <account_id> <warehouse_id>\n";
    echo "Example: php set_wb_warehouse.php 2 506019\n";
    exit(1);
}

$accountId = (int) $argv[1];
$warehouseId = (int) $argv[2];

try {
    $account = \App\Models\MarketplaceAccount::findOrFail($accountId);
    
    if ($account->marketplace !== 'wb') {
        echo "❌ Error: Account {$accountId} is not a Wildberries account (marketplace: {$account->marketplace})\n";
        exit(1);
    }
    
    echo "📦 Setting warehouse_id for WB account {$accountId}...\n";
    echo "   Warehouse ID: {$warehouseId}\n\n";
    
    // Get current credentials_json
    $currentCredentials = $account->credentials_json ?? [];
    $oldWarehouseId = $currentCredentials['warehouse_id'] ?? null;
    
    if ($oldWarehouseId) {
        echo "ℹ️  Previous warehouse_id: {$oldWarehouseId}\n";
    }
    
    // Set new warehouse_id in credentials_json
    $currentCredentials['warehouse_id'] = $warehouseId;
    $account->credentials_json = $currentCredentials;
    $account->save();
    
    echo "✅ Successfully set warehouse_id = {$warehouseId} for account {$accountId}\n\n";
    
    // Verify
    $account->refresh();
    $savedCredentials = $account->credentials_json ?? [];
    $savedWarehouseId = $savedCredentials['warehouse_id'] ?? null;
    
    if ($savedWarehouseId == $warehouseId) {
        echo "✅ Verified: warehouse_id is correctly saved\n";
    } else {
        echo "⚠️  Warning: warehouse_id verification failed\n";
    }
    
} catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
    echo "❌ Error: Account with ID {$accountId} not found\n";
    exit(1);
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
