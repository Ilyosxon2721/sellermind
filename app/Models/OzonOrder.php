<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OzonOrder extends Model
{
    protected $table = 'ozon_orders';

    protected $fillable = [
        'marketplace_account_id',
        'order_id',
        'posting_number',
        'status',
        'substatus',
        'order_data',
        'products',
        'total_price',
        'currency',
        'customer_name',
        'customer_phone',
        'delivery_address',
        'delivery_method',
        'warehouse_id',
        'tracking_number',
        'cancellation_reason',
        'cancelled_at',
        'shipment_date',
        'in_process_at',
        'created_at_ozon',
        // Stock tracking fields
        'stock_status',
        'stock_reserved_at',
        'stock_sold_at',
        'stock_released_at',
    ];

    protected function casts(): array
    {
        return [
            'order_data' => 'array',
            'products' => 'array',
            'total_price' => 'decimal:2',
            'created_at_ozon' => 'datetime',
            'cancelled_at' => 'datetime',
            'shipment_date' => 'datetime',
            'in_process_at' => 'datetime',
            'stock_reserved_at' => 'datetime',
            'stock_sold_at' => 'datetime',
            'stock_released_at' => 'datetime',
        ];
    }

    public function marketplaceAccount(): BelongsTo
    {
        return $this->belongsTo(MarketplaceAccount::class);
    }

    /**
     * Получить список товаров в заказе
     */
    public function getProductsList(): array
    {
        if (!empty($this->products) && is_array($this->products)) {
            return $this->products;
        }

        // Если в products пусто, попробовать извлечь из order_data
        if (!empty($this->order_data['products'])) {
            return $this->order_data['products'];
        }

        return [];
    }

    /**
     * Проверить, можно ли отменить заказ
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['awaiting_packaging', 'awaiting_deliver']);
    }

    /**
     * Проверить, можно ли отправить заказ
     */
    public function canBeShipped(): bool
    {
        return $this->status === 'awaiting_packaging';
    }

    /**
     * Получить человекочитаемое название статуса
     */
    public function getStatusLabel(): string
    {
        $statusLabels = [
            'awaiting_packaging' => 'Ожидает упаковки',
            'awaiting_deliver' => 'Ожидает отгрузки',
            'arbitration' => 'Арбитраж',
            'client_arbitration' => 'Арбитраж клиента',
            'delivering' => 'Доставляется',
            'driver_pickup' => 'У водителя',
            'delivered' => 'Доставлен',
            'cancelled' => 'Отменён',
            'not_accepted' => 'Не принят',
            'pending' => 'В ожидании',
            'processing' => 'В обработке',
            'shipped' => 'Отправлен',
            'completed' => 'Завершён',
        ];

        return $statusLabels[$this->status] ?? $this->status;
    }

    /**
     * Проверить, связан ли заказ с внутренними товарами
     */
    public function isLinked(): bool
    {
        $products = $this->getProductsList();

        foreach ($products as $product) {
            $sku = $product['sku'] ?? $product['offer_id'] ?? null;
            if ($sku) {
                $link = VariantMarketplaceLink::where('external_sku', $sku)
                    ->where('marketplace_account_id', $this->marketplace_account_id)
                    ->where('is_active', true)
                    ->first();

                if ($link) {
                    return true;
                }
            }
        }

        return false;
    }
}
