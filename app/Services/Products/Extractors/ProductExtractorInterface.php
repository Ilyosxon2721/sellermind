<?php

declare(strict_types=1);

namespace App\Services\Products\Extractors;

use App\Services\Products\DTO\ProductCardDTO;
use Illuminate\Database\Eloquent\Model;

/**
 * Интерфейс извлечения данных товара из источника в единый DTO
 */
interface ProductExtractorInterface
{
    /**
     * Проверяет, поддерживает ли данный тип маркетплейса
     */
    public function supports(string $marketplace): bool;

    /**
     * Извлечь данные товара в DTO
     */
    public function extract(Model $source): ProductCardDTO;
}
