<?php

declare(strict_types=1);

namespace App\Modules\UzumAnalytics\Jobs;

use App\Modules\UzumAnalytics\Models\UzumTrackedProduct;
use App\Modules\UzumAnalytics\Repositories\AnalyticsRepository;
use App\Modules\UzumAnalytics\Services\UzumAnalyticsApiClient;
use App\Telegram\TelegramService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job для сбора снепшота одного товара Uzum (#072).
 *
 * GET /v2/product/{productId} → сохранение в uzum_products_snapshots.
 * Если цена изменилась > threshold% — Telegram алерт.
 * Retry: 3 попытки с backoff 5/15/60 минут.
 */
final class CrawlProductJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public readonly int $productId,
        public readonly ?int $companyId = null, // null = системный сбор без алертов
    ) {}

    public function backoff(): array
    {
        return [300, 900, 3600];
    }

    public function handle(
        UzumAnalyticsApiClient $apiClient,
        AnalyticsRepository $repository,
        TelegramService $telegram,
    ): void {
        $response = $apiClient->getProduct($this->productId);

        if (empty($response)) {
            Log::channel('uzum-analytics')->warning('CrawlProductJob: пустой ответ API', [
                'product_id' => $this->productId,
            ]);

            return;
        }

        $snapshot = $repository->saveProductSnapshot($response);

        Log::channel('uzum-analytics')->info('CrawlProductJob: снепшот сохранён', [
            'product_id' => $this->productId,
            'price'      => $snapshot->price,
        ]);

        // Алерт только если это отслеживаемый товар конкретной компании
        if ($this->companyId) {
            $this->checkPriceAlert($snapshot->price, $snapshot->title, $telegram);
        }
    }

    private function checkPriceAlert(float $newPrice, string $title, TelegramService $telegram): void
    {
        $tracked = UzumTrackedProduct::where('company_id', $this->companyId)
            ->where('product_id', $this->productId)
            ->where('alert_enabled', true)
            ->first();

        if (! $tracked) {
            return;
        }

        $changed = $tracked->isPriceChangedSignificantly($newPrice);

        if ($changed && $telegram->isConfigured()) {
            $pct      = $tracked->getPriceChangePct($newPrice);
            $arrow    = $pct > 0 ? '📈' : '📉';
            $oldPrice = number_format((float) $tracked->last_price, 0, '.', ' ');
            $newFmt   = number_format($newPrice, 0, '.', ' ');

            $message = "{$arrow} <b>Изменение цены конкурента</b>\n\n"
                . "🏷 {$title}\n"
                . "💰 Было: {$oldPrice} сум\n"
                . "💰 Стало: {$newFmt} сум\n"
                . "📊 Изменение: " . ($pct > 0 ? '+' : '') . "{$pct}%\n\n"
                . "🔗 <a href=\"https://uzum.uz/product/{$this->productId}\">Открыть на Uzum</a>";

            // Telegram chatId берём из настроек компании
            $chatId = $tracked->company?->settings['telegram_chat_id']
                ?? config('uzum-crawler.telegram_chat_id');

            if ($chatId) {
                $telegram->sendMessage($chatId, $message);
            }
        }

        // Обновить last_price в любом случае
        $tracked->update([
            'last_price'      => $newPrice,
            'last_scraped_at' => now(),
            'title'           => $title,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::channel('uzum-analytics')->error('CrawlProductJob: все попытки провалились', [
            'product_id' => $this->productId,
            'error'      => $exception->getMessage(),
        ]);
    }
}
