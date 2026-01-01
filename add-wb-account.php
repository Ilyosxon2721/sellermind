<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\MarketplaceAccount;
use App\Models\Company;

echo "=== Добавление нового аккаунта Wildberries ===\n\n";

// Проверяем аргументы
if ($argc < 4) {
    echo "Использование: php add-wb-account.php <company_id> <название> <api_token>\n\n";
    echo "Доступные компании:\n";
    $companies = Company::all();
    foreach ($companies as $company) {
        echo "   ID: {$company->id} - {$company->name}\n";
    }
    echo "\nПример:\n";
    echo "   php add-wb-account.php 1 \"FORRIS HOME 2\" \"eyJhbGc...ваш_токен\"\n";
    exit(1);
}

$companyId = $argv[1];
$name = $argv[2];
$apiToken = $argv[3];

// Проверяем компанию
$company = Company::find($companyId);
if (!$company) {
    echo "❌ Компания с ID {$companyId} не найдена!\n";
    exit(1);
}

echo "Компания: {$company->name}\n";
echo "Название аккаунта: {$name}\n";
echo "API токен: " . substr($apiToken, 0, 20) . "...\n\n";

// Создаём аккаунт
try {
    $account = MarketplaceAccount::create([
        'company_id' => $companyId,
        'marketplace' => 'wb',
        'name' => $name,
        'is_active' => true,
        'credentials' => [
            'api_token' => $apiToken,
        ],
        'connected_at' => now(),
    ]);

    echo "✅ Аккаунт успешно создан!\n";
    echo "   ID: {$account->id}\n";
    echo "   Название: {$account->name}\n";
    echo "   Маркетплейс: {$account->marketplace}\n";
    echo "   Компания: {$company->name}\n\n";

    echo "Теперь вы можете:\n";
    echo "1. Запустить синхронизацию заказов:\n";
    echo "   php artisan marketplace:sync orders {$account->id}\n\n";
    echo "2. Запустить синхронизацию товаров:\n";
    echo "   php artisan marketplace:sync products {$account->id}\n\n";
    echo "3. Запустить мониторинг:\n";
    echo "   php artisan marketplace:monitor orders {$account->id}\n\n";

} catch (\Exception $e) {
    echo "❌ Ошибка при создании аккаунта:\n";
    echo "   {$e->getMessage()}\n";
    exit(1);
}
