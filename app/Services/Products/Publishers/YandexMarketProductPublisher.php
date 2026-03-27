<?php

declare(strict_types=1);

namespace App\Services\Products\Publishers;

use App\Models\MarketplaceAccount;
use App\Models\MarketplaceProduct;
use App\Models\Product;
use App\Services\Marketplaces\YandexMarket\YandexMarketClient;
use App\Services\Marketplaces\YandexMarket\YandexMarketHttpClient;
use Illuminate\Support\Facades\Log;

final class YandexMarketProductPublisher
{
    public function __construct(
        private readonly YandexMarketHttpClient $httpClient,
    ) {}

    /**
     * Публикация товара на Yandex Market
     *
     * Находит все активные YM-аккаунты компании товара и публикует/обновляет
     * оффер через Partner API (/businesses/{id}/offer-mappings/update).
     */
    public function publish(Product $product): void
    {
        $accounts = MarketplaceAccount::where('company_id', $product->company_id)
            ->where('marketplace', 'ym')
            ->where('is_active', true)
            ->get();

        if ($accounts->isEmpty()) {
            Log::info('YM publish: нет активных аккаунтов Yandex Market для компании', [
                'product_id' => $product->id,
                'company_id' => $product->company_id,
            ]);

            return;
        }

        $client = new YandexMarketClient($this->httpClient);

        foreach ($accounts as $account) {
            $this->publishToAccount($client, $account, $product);
        }
    }

    /**
     * Публикация товара в конкретный аккаунт YM
     */
    private function publishToAccount(YandexMarketClient $client, MarketplaceAccount $account, Product $product): void
    {
        try {
            // Получаем или создаём запись MarketplaceProduct для связки
            $offerId = $product->article ?? $product->sku ?? 'SKU-' . $product->id;

            $marketplaceProduct = MarketplaceProduct::firstOrCreate(
                [
                    'marketplace_account_id' => $account->id,
                    'product_id' => $product->id,
                ],
                [
                    'external_offer_id' => $offerId,
                    'title' => $product->name,
                    'status' => MarketplaceProduct::STATUS_PENDING,
                ]
            );

            // Обновляем title если изменился
            if ($marketplaceProduct->title !== $product->name) {
                $marketplaceProduct->update(['title' => $product->name]);
            }

            // Вызываем syncProducts — он сам маппит, отправляет и обновляет статус
            $client->syncProducts($account, [$marketplaceProduct]);

            Log::info('YM publish: товар отправлен на публикацию', [
                'product_id' => $product->id,
                'account_id' => $account->id,
                'offer_id' => $offerId,
                'status' => $marketplaceProduct->fresh()->status ?? 'unknown',
            ]);
        } catch (\Throwable $e) {
            Log::error('YM publish: ошибка публикации товара', [
                'product_id' => $product->id,
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);

            // Помечаем MarketplaceProduct как неудачный, если запись была создана
            if (isset($marketplaceProduct)) {
                $marketplaceProduct->markAsFailed('Publish error: ' . $e->getMessage());
            }
        }
    }
}
