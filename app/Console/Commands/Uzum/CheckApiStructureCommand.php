<?php

declare(strict_types=1);

namespace App\Console\Commands\Uzum;

use App\Modules\UzumAnalytics\Models\UzumTrackedProduct;
use App\Modules\UzumAnalytics\Services\UzumAnalyticsApiClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Мониторинг структуры публичного API Uzum (#084).
 *
 * Проверяет что ключевые поля ответа /v2/product/{id} на месте.
 * При изменении структуры — алерт в Telegram разработчику.
 *
 * Запуск: php artisan uzum:check-api-structure
 * Scheduler: Daily at 09:00
 */
final class CheckApiStructureCommand extends Command
{
    protected $signature   = 'uzum:check-api-structure {--product-id= : ID тестового товара}';
    protected $description = 'Проверить структуру ответа Uzum API и сообщить об изменениях';

    // Поля, которые должны быть в ответе продукта
    private const REQUIRED_PRODUCT_FIELDS = [
        'id',
        'title',
        'minSellPrice',
        'rating',
        'reviewsAmount',
        'ordersAmount',
    ];

    // Поля, которые должны быть в магазине
    private const REQUIRED_SHOP_FIELDS = [
        'slug',
    ];

    public function handle(UzumAnalyticsApiClient $apiClient): int
    {
        $productId = (int) ($this->option('product-id') ?? $this->getTestProductId());

        if (! $productId) {
            $this->error('Нет доступных товаров для тестирования. Добавьте товары в отслеживание.');

            return self::FAILURE;
        }

        $this->info("Проверка структуры API Uzum для товара #{$productId}...");

        try {
            $response = $apiClient->getProduct($productId);
        } catch (\Throwable $e) {
            $this->error('Ошибка запроса к API: ' . $e->getMessage());
            $this->sendAlert("❌ Uzum API недоступен: " . $e->getMessage());
            Log::channel('uzum-analytics')->error('CheckApiStructure: ошибка запроса', [
                'error' => $e->getMessage(),
            ]);

            return self::FAILURE;
        }

        if (empty($response)) {
            $this->error('API вернул пустой ответ.');
            $this->sendAlert("❌ Uzum API: пустой ответ для /v2/product/{$productId}");

            return self::FAILURE;
        }

        $product = $response['product'] ?? $response;
        $missing = $this->checkFields($product, self::REQUIRED_PRODUCT_FIELDS, 'product');

        $shop = $product['shop'] ?? [];
        if (! empty($shop)) {
            $missing = array_merge($missing, $this->checkFields($shop, self::REQUIRED_SHOP_FIELDS, 'shop'));
        }

        if (! empty($missing)) {
            $message = "⚠️ Uzum API: изменилась структура ответа\n\nОтсутствующие поля:\n" . implode("\n", array_map(fn ($f) => "  • {$f}", $missing));
            $this->warn($message);
            $this->sendAlert($message);
            Log::channel('uzum-analytics')->warning('CheckApiStructure: структура изменилась', compact('missing'));

            return self::FAILURE;
        }

        $this->info('✅ Структура API в порядке. Все ожидаемые поля на месте.');
        Log::channel('uzum-analytics')->info('CheckApiStructure: OK', ['product_id' => $productId]);

        return self::SUCCESS;
    }

    private function checkFields(array $data, array $requiredFields, string $context): array
    {
        $missing = [];
        foreach ($requiredFields as $field) {
            if (! array_key_exists($field, $data)) {
                $missing[] = "{$context}.{$field}";
                $this->line("  ❌ Отсутствует: {$context}.{$field}");
            } else {
                $this->line("  ✓ {$context}.{$field}");
            }
        }

        return $missing;
    }

    private function getTestProductId(): ?int
    {
        return UzumTrackedProduct::orderByDesc('last_scraped_at')
            ->value('product_id');
    }

    private function sendAlert(string $message): void
    {
        $chatId = config('uzum-crawler.monitoring.telegram_dev_chat_id')
            ?? config('uzum-crawler.telegram_chat_id');

        if (! $chatId) {
            return;
        }

        $token = config('telegram.bot_token');
        if (! $token) {
            return;
        }

        try {
            Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId,
                'text'    => $message,
            ]);
        } catch (\Throwable) {
            // Не падаем из-за ошибки уведомления
        }
    }
}
