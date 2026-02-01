<?php

declare(strict_types=1);

namespace App\Services\Products\Publishers;

use App\Models\Product;

class WbProductPublisher
{
    /**
     * Публикация товара на Wildberries — в разработке
     */
    public function publish(Product $product): void
    {
        throw new \RuntimeException('Публикация на Wildberries ещё не реализована. Функция будет доступна в следующем обновлении.');
    }
}
