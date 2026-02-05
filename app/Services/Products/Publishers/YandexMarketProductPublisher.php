<?php

declare(strict_types=1);

namespace App\Services\Products\Publishers;

use App\Models\Product;

class YandexMarketProductPublisher
{
    /**
     * Публикация товара на Yandex Market — в разработке
     */
    public function publish(Product $product): void
    {
        throw new \RuntimeException('Публикация на Yandex Market ещё не реализована. Функция будет доступна в следующем обновлении.');
    }
}
