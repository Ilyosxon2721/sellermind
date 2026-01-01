<?php

require __DIR__ . '/vendor/autoload.php';

use App\Helpers\CurrencyHelper;

echo "=== Тест CurrencyHelper ===\n\n";

// Тестовые данные из реального заказа
$testCases = [
    [
        'name' => 'Заказ с BYN и UZS',
        'currency_code' => 933,
        'converted_currency_code' => 860,
        'price' => 10333,
        'converted_price' => 42223132,
    ],
    [
        'name' => 'Заказ с RUB',
        'currency_code' => 643,
        'converted_currency_code' => null,
        'price' => 150000,
        'converted_price' => null,
    ],
    [
        'name' => 'Заказ с KZT',
        'currency_code' => 398,
        'converted_currency_code' => null,
        'price' => 500000,
        'converted_price' => null,
    ],
];

foreach ($testCases as $test) {
    echo "--- {$test['name']} ---\n";

    // Основная валюта
    $currencyName = CurrencyHelper::getCurrencyName($test['currency_code']);
    $currencyCode = CurrencyHelper::getCurrencyCode($test['currency_code']);
    $currencySymbol = CurrencyHelper::getCurrencySymbol($test['currency_code']);

    echo "Валюта: {$currencyName} ({$currencyCode})\n";
    echo "Символ: {$currencySymbol}\n";

    if ($test['price']) {
        $price = CurrencyHelper::fromKopecks($test['price']);
        $formattedPrice = CurrencyHelper::formatPrice($price, $test['currency_code']);
        echo "Цена: {$formattedPrice}\n";
    }

    // Конвертированная валюта
    if ($test['converted_currency_code']) {
        $convertedName = CurrencyHelper::getCurrencyName($test['converted_currency_code']);
        $convertedCode = CurrencyHelper::getCurrencyCode($test['converted_currency_code']);
        $convertedSymbol = CurrencyHelper::getCurrencySymbol($test['converted_currency_code']);

        echo "Конвертированная валюта: {$convertedName} ({$convertedCode})\n";
        echo "Символ конвертированной валюты: {$convertedSymbol}\n";

        if ($test['converted_price']) {
            $convertedPrice = CurrencyHelper::fromKopecks($test['converted_price']);
            $formattedConvertedPrice = CurrencyHelper::formatPrice($convertedPrice, $test['converted_currency_code']);
            echo "Конвертированная цена: {$formattedConvertedPrice}\n";
        }
    }

    echo "\n";
}

echo "=== Тест всех поддерживаемых валют ===\n\n";

$allCurrencies = [
    '643' => 'RUB',
    '933' => 'BYN',
    '860' => 'UZS',
    '398' => 'KZT',
    '417' => 'KGS',
    '051' => 'AMD',
    '944' => 'AZN',
    '840' => 'USD',
    '978' => 'EUR',
];

foreach ($allCurrencies as $code => $expectedCode) {
    $name = CurrencyHelper::getCurrencyName($code);
    $symbol = CurrencyHelper::getCurrencySymbol($code);
    $actualCode = CurrencyHelper::getCurrencyCode($code);

    echo sprintf(
        "%-3s | %-25s | %-5s | Пример: %s\n",
        $code,
        $name,
        $symbol,
        CurrencyHelper::formatPrice(1234.56, $code)
    );

    if ($actualCode !== $expectedCode) {
        echo "  ❌ ОШИБКА: ожидался код {$expectedCode}, получен {$actualCode}\n";
    }
}

echo "\n=== Тест неизвестной валюты ===\n";
$unknownCode = '999';
echo "Код: {$unknownCode}\n";
echo "Название: " . CurrencyHelper::getCurrencyName($unknownCode) . "\n";
echo "Код валюты: " . CurrencyHelper::getCurrencyCode($unknownCode) . "\n";
echo "Символ: '" . CurrencyHelper::getCurrencySymbol($unknownCode) . "'\n";

echo "\n✅ Тест завершён\n";
