<?php

declare(strict_types=1);

namespace App\Services\Products\Publishers;

use App\Models\Product;

class OzonProductPublisher
{
    /**
     * Публикация товара на Ozon — в разработке
     */
    public function publish(Product $product): void
    {
        throw new \RuntimeException('Публикация на Ozon ещё не реализована. Функция будет доступна в следующем обновлении.');
    }
}
