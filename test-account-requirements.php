<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Http\Controllers\Api\MarketplaceAccountController;
use Illuminate\Http\Request;

echo "=== Тест API требований для добавления аккаунтов ===\n\n";

$controller = new MarketplaceAccountController();
$reflection = new ReflectionClass($controller);
$method = $reflection->getMethod('getMarketplaceRequirements');
$method->setAccessible(true);

$marketplaces = ['wb', 'uzum', 'ozon', 'ym'];

foreach ($marketplaces as $marketplace) {
    echo "========================================\n";
    echo "Маркетплейс: " . strtoupper($marketplace) . "\n";
    echo "========================================\n\n";

    $requirements = $method->invoke($controller, $marketplace);

    if (!$requirements) {
        echo "❌ Нет данных для этого маркетплейса\n\n";
        continue;
    }

    echo "Название: {$requirements['name']}\n";
    echo "Описание: {$requirements['description']}\n\n";

    echo "Поля для заполнения:\n";
    echo "-------------------\n";
    foreach ($requirements['fields'] as $field) {
        $required = $field['required'] ? '(обязательное)' : '(необязательное)';
        echo "• {$field['label']} {$required}\n";
        echo "  Тип: {$field['type']}\n";
        echo "  Подсказка: {$field['help']}\n";
        if (!empty($field['placeholder'])) {
            echo "  Пример: {$field['placeholder']}\n";
        }
        echo "\n";
    }

    echo "\nИнструкция:\n";
    echo "-----------\n";
    echo "{$requirements['instructions']['title']}\n";
    foreach ($requirements['instructions']['steps'] as $i => $step) {
        $num = $i + 1;
        echo "{$num}. {$step}\n";
    }

    echo "\nПримечания:\n";
    echo "-----------\n";
    foreach ($requirements['instructions']['notes'] as $note) {
        echo "• {$note}\n";
    }

    echo "\n\n";
}

echo "========================================\n";
echo "Пример использования в API:\n";
echo "========================================\n\n";

echo "GET /api/marketplace/accounts/requirements?marketplace=wb\n";
echo "Authorization: Bearer YOUR_TOKEN\n\n";

echo "Ответ будет содержать JSON с полной информацией о требуемых полях,\n";
echo "инструкциями по получению токенов и примечаниями.\n\n";

echo "Frontend может использовать этот endpoint для:\n";
echo "1. Динамического отображения полей формы\n";
echo "2. Показа подсказок пользователю\n";
echo "3. Отображения пошаговых инструкций\n";
echo "4. Валидации формата полей\n\n";
