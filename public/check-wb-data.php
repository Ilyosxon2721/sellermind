<?php
// Check WB synchronized data
require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "📊 Проверка синхронизированных данных Wildberries\n";
echo str_repeat("=", 60) . "\n\n";

// Find WB account
$account = App\Models\MarketplaceAccount::where('marketplace', 'wb')->first();

if (!$account) {
    echo "❌ WB аккаунт не найден\n";
    exit(1);
}

echo "✅ WB Аккаунт найден\n";
echo "   ID: {$account->id}\n";
echo "   Company ID: {$account->company_id}\n";
echo "   Name: {$account->name}\n\n";

// Check orders
echo str_repeat("-", 60) . "\n";
echo "📦 ЗАКАЗЫ (marketplace_orders)\n";
echo str_repeat("-", 60) . "\n";

$orders = App\Models\MarketplaceOrder::where('marketplace_account_id', $account->id)
    ->orderBy('ordered_at', 'desc')
    ->get();

echo "Всего заказов: " . $orders->count() . "\n\n";

if ($orders->count() > 0) {
    echo "Последние 5 заказов:\n";
    foreach ($orders->take(5) as $order) {
        $article = $order->raw_payload['article'] ?? 'N/A';
        $price = number_format($order->raw_payload['finalPrice'] / 100 ?? 0, 2);
        echo "  • ID: {$order->external_order_id}\n";
        echo "    Артикул: {$article}\n";
        echo "    Цена: {$price} руб\n";
        echo "    Дата: {$order->ordered_at}\n";
        echo "    Склад: " . ($order->raw_payload['offices'][0] ?? 'N/A') . "\n\n";
    }
} else {
    echo "⚠️  Заказы не найдены.\n";
    echo "Запустите синхронизацию заказов.\n\n";
}

// Check products
echo str_repeat("-", 60) . "\n";
echo "🛍️  ТОВАРЫ (marketplace_products)\n";
echo str_repeat("-", 60) . "\n";

$products = App\Models\MarketplaceProduct::where('marketplace_account_id', $account->id)
    ->get();

echo "Всего товаров: " . $products->count() . "\n\n";

if ($products->count() > 0) {
    echo "Первые 5 товаров:\n";
    foreach ($products->take(5) as $product) {
        echo "  • ID: {$product->external_product_id}\n";
        echo "    Название: {$product->name}\n";
        echo "    Артикул: {$product->sku}\n";
        echo "    Цена: {$product->price} руб\n\n";
    }
} else {
    echo "⚠️  Товары не найдены.\n";
    echo "Примечание: Для синхронизации товаров используйте API Content.\n\n";
}

// Check sync logs
echo str_repeat("-", 60) . "\n";
echo "📋 ЛОГИ СИНХРОНИЗАЦИИ\n";
echo str_repeat("-", 60) . "\n";

$logs = App\Models\MarketplaceSyncLog::where('marketplace_account_id', $account->id)
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->get();

echo "Последние 10 операций:\n\n";
foreach ($logs as $log) {
    $icon = $log->status === 'success' ? '✅' : '❌';
    echo "{$icon} {$log->type} - {$log->status}\n";
    echo "   {$log->message}\n";
    echo "   {$log->created_at}\n\n";
}

// Recommendations
echo str_repeat("=", 60) . "\n";
echo "💡 РЕКОМЕНДАЦИИ\n";
echo str_repeat("=", 60) . "\n\n";

if ($orders->count() === 0) {
    echo "1. Синхронизация заказов:\n";
    echo "   - Откройте: Маркетплейсы → Wildberries\n";
    echo "   - Нажмите: 'Синхронизировать заказы'\n\n";
}

if ($products->count() === 0) {
    echo "2. Загрузка товаров:\n";
    echo "   - WB API не возвращает список всех товаров автоматически\n";
    echo "   - Товары появятся после обработки заказов (если они связаны)\n";
    echo "   - Или используйте Content API для загрузки каталога\n\n";
}

echo "3. Проверка данных в интерфейсе:\n";
echo "   - Откройте: http://localhost:8888/sellerMind/marketplace/{$account->id}/orders\n";
echo "   - Обновите страницу (F5 или Ctrl+R)\n";
echo "   - Проверьте фильтры (даты, статус)\n\n";

echo "✅ Проверка завершена!\n";
