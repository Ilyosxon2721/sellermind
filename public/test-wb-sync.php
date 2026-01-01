<?php
// Test WB synchronization
// Usage: php public/test-wb-sync.php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\MarketplaceAccount;
use App\Services\Marketplaces\WildberriesClient;

echo "🔍 Testing Wildberries API integration...\n\n";

// Find WB account
$account = MarketplaceAccount::where('marketplace', 'wb')->first();

if (!$account) {
    echo "❌ No Wildberries account found\n";
    exit(1);
}

echo "✅ Found WB account ID: {$account->id}\n";
echo "   Name: {$account->name}\n";
echo "   Active: " . ($account->is_active ? 'Yes' : 'No') . "\n\n";

// Check credentials
$credentials = $account->getAllCredentials();
echo "🔑 Checking credentials:\n";
echo "   api_key: " . (empty($credentials['api_key']) ? '❌ Missing' : '✅ Present') . "\n";
echo "   wb_marketplace_token: " . (empty($credentials['wb_marketplace_token']) ? '❌ Missing' : '✅ Present') . "\n";
echo "   wb_content_token: " . (empty($credentials['wb_content_token']) ? '❌ Missing' : '✅ Present') . "\n";
echo "   wb_prices_token: " . (empty($credentials['wb_prices_token']) ? '❌ Missing' : '✅ Present') . "\n";
echo "   wb_statistics_token: " . (empty($credentials['wb_statistics_token']) ? '❌ Missing' : '✅ Present') . "\n\n";

// Test WB client
$client = app(WildberriesClient::class);

echo "📡 Testing connection...\n";
$pingResult = $client->ping($account);
echo "   Result: " . json_encode($pingResult, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

if ($pingResult['success'] ?? false) {
    echo "✅ Connection successful!\n\n";

    // Test fetching orders
    echo "📦 Fetching orders (last 7 days)...\n";
    try {
        $from = new DateTime('-7 days');
        $to = new DateTime();

        echo "   Period: {$from->format('Y-m-d')} to {$to->format('Y-m-d')}\n";
        echo "   Unix timestamps: {$from->getTimestamp()} to {$to->getTimestamp()}\n";

        $orders = $client->fetchOrders($account, $from, $to);

        echo "✅ Orders fetched: " . count($orders) . "\n";

        if (!empty($orders)) {
            echo "   First order sample:\n";
            echo "   " . json_encode($orders[0], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
        }
    } catch (\Exception $e) {
        echo "❌ Error fetching orders: {$e->getMessage()}\n";
        echo "   Exception: " . get_class($e) . "\n";
        echo "   File: {$e->getFile()}:{$e->getLine()}\n";
    }
} else {
    echo "❌ Connection failed!\n";
    echo "   Check the error message above and verify your tokens.\n";
}

echo "\n✅ Test completed\n";
