<?php

namespace App\Events;

use App\Models\ProductVariant;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Событие обновления остатка товара
 */
class StockUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public ProductVariant $variant,
        public int $oldStock,
        public int $newStock,
        public ?int $sourceLinkId = null // ID связи (VariantMarketplaceLink) откуда пришло изменение (исключаем из синхронизации)
    ) {}

    /**
     * Изменение остатка
     */
    public function getStockDelta(): int
    {
        return $this->newStock - $this->oldStock;
    }
}
