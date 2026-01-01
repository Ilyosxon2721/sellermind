<?php

namespace App\Services\Products;

use App\Models\Product;
use App\Services\Products\Publishers\OzonProductPublisher;
use App\Services\Products\Publishers\UzumProductPublisher;
use App\Services\Products\Publishers\WbProductPublisher;
use App\Services\Products\Publishers\YandexMarketProductPublisher;

class ProductPublishService
{
    public function __construct(
        protected WbProductPublisher $wbPublisher,
        protected OzonProductPublisher $ozonPublisher,
        protected YandexMarketProductPublisher $yandexMarketPublisher,
        protected UzumProductPublisher $uzumPublisher
    ) {
    }

    public function publish(Product $product, array $channels): array
    {
        $channels = collect($channels)->filter()->unique()->values();
        $results = [];

        foreach ($channels as $channel) {
            $channel = strtolower((string) $channel);
            switch ($channel) {
                case 'wb':
                    $this->wbPublisher->publish($product);
                    $results[$channel] = 'queued';
                    break;
                case 'ozon':
                    $this->ozonPublisher->publish($product);
                    $results[$channel] = 'queued';
                    break;
                case 'ym':
                case 'yandex':
                case 'yandexmarket':
                    $this->yandexMarketPublisher->publish($product);
                    $results['ym'] = 'queued';
                    break;
                case 'uzum':
                    $this->uzumPublisher->publish($product);
                    $results[$channel] = 'queued';
                    break;
                default:
                    $results[$channel] = 'unknown_channel';
                    break;
            }
        }

        return $results;
    }
}
