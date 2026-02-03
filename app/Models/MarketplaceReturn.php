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

    public function order(): BelongsTo
    {
        return $this->belongsTo(MarketplaceOrder::class, 'marketplace_order_id');
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(MarketplaceOrderItem::class, 'marketplace_order_item_id');
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
