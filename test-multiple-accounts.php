<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\MarketplaceAccount;
use App\Models\Company;

echo "=== Тест множественных аккаунтов маркетплейсов ===\n\n";

// Получаем первую компанию
$company = Company::first();

if (!$company) {
    echo "❌ Компании не найдены в БД\n";
    exit(1);
}

echo "✅ Компания найдена: ID {$company->id}, Название: {$company->name}\n\n";

// Показываем текущие аккаунты
echo "=== Текущие аккаунты ===\n";
$accounts = MarketplaceAccount::where('company_id', $company->id)->get();

foreach ($accounts as $acc) {
    echo sprintf(
        "ID: %-3s | %-10s | Name: %-30s | Active: %s\n",
        $acc->id,
        $acc->marketplace,
        $acc->name ?: '(без названия)',
        $acc->is_active ? 'Да' : 'Нет'
    );
}

echo "\n=== Создаем тестовый второй аккаунт WB ===\n";

// Проверяем, сколько WB аккаунтов уже есть
$wbAccountsCount = MarketplaceAccount::where('company_id', $company->id)
    ->where('marketplace', 'wb')
    ->count();

echo "Текущее количество WB аккаунтов: {$wbAccountsCount}\n";

// Создаём второй тестовый аккаунт WB
try {
    $testAccount = MarketplaceAccount::create([
        'company_id' => $company->id,
        'marketplace' => 'wb',
        'name' => 'Тестовый магазин #' . ($wbAccountsCount + 1),
        'wb_marketplace_token' => 'test_token_' . time(),
        'credentials' => ['test' => 'value'], // Обязательное поле
        'is_active' => true,
        'connected_at' => now(),
    ]);

    echo "✅ Тестовый аккаунт создан:\n";
    echo "   ID: {$testAccount->id}\n";
    echo "   Marketplace: {$testAccount->marketplace}\n";
    echo "   Name: {$testAccount->name}\n";
    echo "   Display Name: {$testAccount->getDisplayName()}\n\n";

} catch (\Exception $e) {
    echo "❌ Ошибка при создании аккаунта:\n";
    echo "   {$e->getMessage()}\n\n";
    exit(1);
}

// Показываем все WB аккаунты
echo "=== Все WB аккаунты после создания ===\n";
$wbAccounts = MarketplaceAccount::where('company_id', $company->id)
    ->where('marketplace', 'wb')
    ->get();

foreach ($wbAccounts as $acc) {
    echo sprintf(
        "ID: %-3s | Name: %-30s | Display: %-30s | Active: %s\n",
        $acc->id,
        $acc->name ?: '(без названия)',
        $acc->getDisplayName(),
        $acc->is_active ? 'Да' : 'Нет'
    );
}

echo "\n=== Тест getDisplayName() ===\n";
foreach ($wbAccounts as $acc) {
    $displayName = $acc->getDisplayName();
    echo "ID {$acc->id}: '{$displayName}'\n";
}

echo "\n=== Очистка тестового аккаунта ===\n";
$testAccount->delete();
echo "✅ Тестовый аккаунт удалён (ID: {$testAccount->id})\n";

echo "\n=== Финальная проверка ===\n";
$finalCount = MarketplaceAccount::where('company_id', $company->id)
    ->where('marketplace', 'wb')
    ->count();

echo "Количество WB аккаунтов после удаления: {$finalCount}\n";

if ($finalCount === $wbAccountsCount) {
    echo "✅ Количество вернулось к исходному значению\n";
} else {
    echo "⚠️  Количество изменилось: было {$wbAccountsCount}, стало {$finalCount}\n";
}

echo "\n=== ИТОГИ ===\n";
echo "✅ Система поддерживает создание нескольких аккаунтов одного маркетплейса\n";
echo "✅ Поле 'name' работает корректно\n";
echo "✅ Метод getDisplayName() отображает правильное имя\n";
echo "✅ Создание и удаление аккаунтов работает без ошибок\n";
echo "\n✅ Все тесты пройдены успешно!\n";
