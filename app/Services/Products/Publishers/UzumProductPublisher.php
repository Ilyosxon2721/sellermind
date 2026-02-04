<?php

declare(strict_types=1);

namespace App\Services\Products\Publishers;

use App\Models\Product;

class UzumProductPublisher
{
    /**
     * Публикация товара на Uzum — в разработке
     */
    public function publish(Product $product): void
    {
        throw new \RuntimeException('Публикация на Uzum ещё не реализована. Функция будет доступна в следующем обновлении.');
    }
}
