<?php

declare(strict_types=1);

namespace App\Services\Products\Publishers;

use App\Models\MarketplaceAccount;
use App\Models\MarketplaceProduct;
use App\Models\Product;
use App\Models\ProductChannelSetting;
use App\Services\Uzum\Api\UzumApiManager;
use Illuminate\Support\Facades\Log;

/**
 * Публикация товара на Uzum Market через PRODUCT_IMPORT / PRODUCT_UPDATE
 */
final class UzumProductPublisher
{
    /**
     * Публикация товара на Uzum
     *
     * Находит все активные Uzum-аккаунты компании и публикует товар в каждый.
     */
    public function publish(Product $product): void
    {
        $product->loadMissing(['variants', 'images', 'channelSettings']);

        $accounts = MarketplaceAccount::where('company_id', $product->company_id)
            ->where('marketplace', 'uzum')
            ->where('is_active', true)
            ->get();

        if ($accounts->isEmpty()) {
            throw new \RuntimeException('Не найден активный аккаунт Uzum для этой компании.');
        }

        // Настройки канала Uzum (категория, оверрайды)
        $channelSetting = $this->getChannelSetting($product);

        foreach ($accounts as $account) {
            $this->publishToAccount($product, $account, $channelSetting);
        }
    }

    /**
     * Публикация товара в конкретный аккаунт Uzum
     */
    private function publishToAccount(
        Product $product,
        MarketplaceAccount $account,
        ?ProductChannelSetting $channelSetting
    ): void {
        $uzum = new UzumApiManager($account);

        // Определяем shopId
        $shopIds = $uzum->shops()->ids();
        if (empty($shopIds)) {
            throw new \RuntimeException("Магазин не найден для аккаунта Uzum #{$account->id}.");
        }
        $shopId = $shopIds[0];

        // Маппим товар в формат Uzum API
        $uzumData = $this->mapToUzumFormat($product, $shopId, $channelSetting);

        // Ищем существующий MarketplaceProduct
        $mpProduct = MarketplaceProduct::where('marketplace_account_id', $account->id)
            ->where('product_id', $product->id)
            ->first();

        try {
            if ($mpProduct && $mpProduct->external_product_id) {
                // Обновляем существующий товар
                $response = $uzum->products()->update(
                    (int) $mpProduct->external_product_id,
                    $uzumData
                );

                Log::info('Uzum product updated', [
                    'product_id' => $product->id,
                    'external_product_id' => $mpProduct->external_product_id,
                ]);
            } else {
                // Создаём новый товар
                $response = $uzum->products()->import($uzumData);

                $externalId = $response['payload']['productId']
                    ?? $response['productId']
                    ?? null;

                // Создаём или обновляем MarketplaceProduct
                $mpProduct = MarketplaceProduct::updateOrCreate(
                    [
                        'marketplace_account_id' => $account->id,
                        'product_id' => $product->id,
                    ],
                    [
                        'external_product_id' => $externalId ? (string) $externalId : null,
                        'shop_id' => (string) $shopId,
                        'title' => $product->name,
                        'status' => MarketplaceProduct::STATUS_PENDING,
                    ]
                );

                Log::info('Uzum product imported', [
                    'product_id' => $product->id,
                    'external_product_id' => $externalId,
                ]);
            }

            $mpProduct->markAsSynced();

            // Обновляем channel setting
            if ($channelSetting) {
                $channelSetting->update([
                    'external_product_id' => $mpProduct->external_product_id,
                    'status' => 'synced',
                    'last_synced_at' => now(),
                    'last_sync_status_message' => null,
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Uzum product publish failed', [
                'product_id' => $product->id,
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);

            $mpProduct?->markAsFailed($e->getMessage());

            if ($channelSetting) {
                $channelSetting->update([
                    'status' => 'error',
                    'last_synced_at' => now(),
                    'last_sync_status_message' => $e->getMessage(),
                ]);
            }

            throw $e;
        }
    }

    /**
     * Маппинг Product в формат Uzum API
     */
    private function mapToUzumFormat(
        Product $product,
        int $shopId,
        ?ProductChannelSetting $channelSetting
    ): array {
        $categoryId = $channelSetting?->category_external_id;
        if (! $categoryId) {
            throw new \RuntimeException(
                'Категория Uzum не указана. Укажите категорию в настройках канала перед публикацией.'
            );
        }

        $name = $channelSetting?->name_override ?: $product->name;
        $description = $channelSetting?->description_override
            ?: strip_tags($product->description_full ?? $product->description_short ?? '');
        $brandName = $channelSetting?->brand_external_name ?: ($product->brand_name ?? '');
        $sku = $product->article ?? 'SKU-' . $product->id;

        // Собираем SKU-список из вариантов
        $skuList = [];
        $variants = $product->variants;

        if ($variants->isNotEmpty()) {
            foreach ($variants as $variant) {
                $skuList[] = [
                    'skuTitle' => $variant->name ?? $name,
                    'vendorCode' => $variant->sku ?? $sku,
                    'barcode' => $variant->barcode ?? $variant->sku ?? $sku,
                    'price' => (int) ($variant->price ?? 0),
                    'quantityFbs' => (int) ($variant->getCurrentStock()),
                    'characteristics' => $this->buildCharacteristics($product),
                ];
            }
        } else {
            // Товар без вариантов — один SKU
            $skuList[] = [
                'skuTitle' => $name,
                'vendorCode' => $sku,
                'barcode' => $product->article ?? $sku,
                'price' => (int) ($channelSetting?->extra['price'] ?? 0),
                'quantityFbs' => 0,
                'characteristics' => $this->buildCharacteristics($product),
            ];
        }

        // Собираем изображения
        $images = [];
        foreach ($product->images as $i => $img) {
            $url = $img->url ?? $img->path ?? null;
            if ($url) {
                $images[] = ['url' => $url, 'main' => $img->is_main ?? ($i === 0)];
            }
        }

        return [
            'shopId' => $shopId,
            'categoryId' => (int) $categoryId,
            'title' => $name,
            'description' => $description,
            'brand' => $brandName,
            'vendorCode' => $sku,
            'skuList' => $skuList,
            'images' => array_slice($images, 0, 10),
            'weight' => (int) ($product->package_weight_g ?? 100),
            'dimensions' => [
                'length' => (int) ($product->package_length_mm ?? 100),
                'width' => (int) ($product->package_width_mm ?? 100),
                'height' => (int) ($product->package_height_mm ?? 100),
            ],
        ];
    }

    /**
     * Сборка характеристик товара для Uzum
     */
    private function buildCharacteristics(Product $product): array
    {
        $characteristics = [];

        if (! empty($product->composition)) {
            $characteristics[] = ['title' => 'Состав', 'value' => $product->composition];
        }
        if (! empty($product->country_of_origin)) {
            $characteristics[] = ['title' => 'Страна производства', 'value' => $product->country_of_origin];
        }
        if (! empty($product->manufacturer)) {
            $characteristics[] = ['title' => 'Производитель', 'value' => $product->manufacturer];
        }

        return $characteristics;
    }

    /**
     * Получить настройки канала Uzum для товара
     */
    private function getChannelSetting(Product $product): ?ProductChannelSetting
    {
        return $product->channelSettings
            ->first(function (ProductChannelSetting $setting) {
                return $setting->channel
                    && strtolower($setting->channel->code) === 'uzum'
                    && $setting->is_enabled;
            });
    }
}
