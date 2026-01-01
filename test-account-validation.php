<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Тест валидации учётных данных маркетплейсов ===\n\n";

// Тест 1: Wildberries с пустыми credentials
echo "Тест 1: WB с пустыми credentials...\n";
$controller = new \App\Http\Controllers\Api\MarketplaceAccountController();
$reflection = new ReflectionClass($controller);
$method = $reflection->getMethod('validateCredentials');
$method->setAccessible(true);

$result = $method->invoke($controller, 'wb', []);
echo $result ? "❌ Ошибка: {$result}\n" : "✅ OK\n";
echo "\n";

// Тест 2: WB с корректным токеном
echo "Тест 2: WB с корректным токеном...\n";
$result = $method->invoke($controller, 'wb', [
    'api_token' => 'eyJhbGciOiJFUzI1NiIsImtpZCI6IjIwMjQwODE0djEiLCJ0eXAiOiJKV1QifQ'
]);
echo $result ? "❌ Ошибка: {$result}\n" : "✅ OK\n";
echo "\n";

// Тест 3: WB с коротким токеном
echo "Тест 3: WB с коротким токеном...\n";
$result = $method->invoke($controller, 'wb', [
    'api_token' => 'short'
]);
echo $result ? "✅ Ошибка: {$result}\n" : "❌ Должна быть ошибка\n";
echo "\n";

// Тест 4: WB с неправильным форматом
echo "Тест 4: WB с неправильным форматом токена...\n";
$result = $method->invoke($controller, 'wb', [
    'api_token' => 'токен-на-русском-языке-с-спецсимволами!@#'
]);
echo $result ? "✅ Ошибка: {$result}\n" : "❌ Должна быть ошибка\n";
echo "\n";

// Тест 5: Uzum без shop_ids
echo "Тест 5: Uzum без shop_ids...\n";
$result = $method->invoke($controller, 'uzum', [
    'api_token' => 'eyJhbGciOiJFUzI1NiIsImtpZCI6IjIwMjQwODE0djEiLCJ0eXAiOiJKV1QifQ'
]);
echo $result ? "✅ Ошибка: {$result}\n" : "❌ Должна быть ошибка\n";
echo "\n";

// Тест 6: Uzum с пустым массивом shop_ids
echo "Тест 6: Uzum с пустым массивом shop_ids...\n";
$result = $method->invoke($controller, 'uzum', [
    'api_token' => 'eyJhbGciOiJFUzI1NiIsImtpZCI6IjIwMjQwODE0djEiLCJ0eXAiOiJKV1QifQ',
    'shop_ids' => []
]);
echo $result ? "✅ Ошибка: {$result}\n" : "❌ Должна быть ошибка\n";
echo "\n";

// Тест 7: Uzum с корректными данными
echo "Тест 7: Uzum с корректными данными...\n";
$result = $method->invoke($controller, 'uzum', [
    'api_token' => 'eyJhbGciOiJFUzI1NiIsImtpZCI6IjIwMjQwODE0djEiLCJ0eXAiOiJKV1QifQ',
    'shop_ids' => [12345, 67890]
]);
echo $result ? "❌ Ошибка: {$result}\n" : "✅ OK\n";
echo "\n";

// Тест 8: Ozon без client_id
echo "Тест 8: Ozon без client_id...\n";
$result = $method->invoke($controller, 'ozon', [
    'api_key' => 'test-api-key'
]);
echo $result ? "✅ Ошибка: {$result}\n" : "❌ Должна быть ошибка\n";
echo "\n";

// Тест 9: Ozon с корректными данными
echo "Тест 9: Ozon с корректными данными...\n";
$result = $method->invoke($controller, 'ozon', [
    'client_id' => '123456',
    'api_key' => 'test-api-key-here'
]);
echo $result ? "❌ Ошибка: {$result}\n" : "✅ OK\n";
echo "\n";

echo "=== Тесты завершены ===\n";
