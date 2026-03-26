<?php

// file: app/Models/MarketplaceReturn.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceReturn extends Model
{
    protected $fillable = [
        'marketplace_order_id',
        'marketplace_order_item_id',
        'external_return_id',
        'reason_code',
        'reason_text',
        'quantity',
        'amount',
        'currency',
        'returned_at',
        'raw_payload',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'amount' => 'float',
            'returned_at' => 'datetime',
            'raw_payload' => 'array',
        ];
    }

    /**
     * Получить заказ — таблица определяется по аккаунту маркетплейса
     * @deprecated Таблица marketplace_orders удалена, используйте findOrder()
     */
    public function order(): BelongsTo
    {
        // Заглушка: таблица marketplace_orders удалена
        return $this->belongsTo(self::class, 'marketplace_order_id')->whereRaw('1 = 0');
    }

    /**
     * Позиция заказа
     * @deprecated Таблица marketplace_order_items удалена
     */
    public function orderItem(): BelongsTo
    {
        // Заглушка: таблица marketplace_order_items удалена
        return $this->belongsTo(self::class, 'marketplace_order_item_id')->whereRaw('1 = 0');
    }

    /**
     * Get formatted amount
     */
    public function getFormattedAmount(): string
    {
        $currency = $this->currency ?? 'UZS';

        return number_format($this->amount ?? 0, 0, '.', ' ').' '.$currency;
    }

    /**
     * Get reason label based on common reason codes
     */
    public function getReasonLabel(): string
    {
        $reasons = [
            'defect' => 'Брак / Дефект',
            'wrong_item' => 'Неверный товар',
            'wrong_size' => 'Неподходящий размер',
            'quality' => 'Низкое качество',
            'damaged' => 'Повреждён при доставке',
            'not_as_described' => 'Не соответствует описанию',
            'changed_mind' => 'Передумал покупать',
            'late_delivery' => 'Задержка доставки',
            'other' => 'Другое',
        ];

        return $reasons[$this->reason_code] ?? $this->reason_text ?? $this->reason_code ?? 'Неизвестно';
    }
}
