<?php

// file: app/Models/MarketplacePayoutItem.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplacePayoutItem extends Model
{
    public const TYPE_SALE = 'sale';

    public const TYPE_RETURN = 'return';

    public const TYPE_COMMISSION = 'commission';

    public const TYPE_LOGISTICS = 'logistics';

    public const TYPE_STORAGE = 'storage';

    public const TYPE_ADV = 'adv';

    public const TYPE_PENALTY = 'penalty';

    public const TYPE_OTHER = 'other';

    protected $fillable = [
        'marketplace_payout_id',
        'marketplace_order_id',
        'marketplace_order_item_id',
        'operation_type',
        'amount',
        'currency',
        'description',
        'raw_payload',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'float',
            'raw_payload' => 'array',
        ];
    }

    public function payout(): BelongsTo
    {
        return $this->belongsTo(MarketplacePayout::class, 'marketplace_payout_id');
    }

    /**
     * Получить заказ из соответствующей таблицы маркетплейса
     */
    public function findOrder(): ?object
    {
        if (! $this->marketplace_order_id) {
            return null;
        }

        $account = $this->payout?->account;
        if (! $account) {
            return null;
        }

        $modelClass = match ($account->marketplace) {
            'wb' => WbOrder::class,
            'uzum' => UzumOrder::class,
            'ozon' => OzonOrder::class,
            'ym' => YandexMarketOrder::class,
            default => null,
        };

        return $modelClass ? $modelClass::find($this->marketplace_order_id) : null;
    }

    /**
     * Get operation type label
     */
    public function getTypeLabel(): string
    {
        return match ($this->operation_type) {
            self::TYPE_SALE => 'Продажа',
            self::TYPE_RETURN => 'Возврат',
            self::TYPE_COMMISSION => 'Комиссия',
            self::TYPE_LOGISTICS => 'Логистика',
            self::TYPE_STORAGE => 'Хранение',
            self::TYPE_ADV => 'Реклама',
            self::TYPE_PENALTY => 'Штраф',
            self::TYPE_OTHER => 'Прочее',
            default => $this->operation_type,
        };
    }

    /**
     * Get operation type color for UI
     */
    public function getTypeColor(): string
    {
        return match ($this->operation_type) {
            self::TYPE_SALE => 'green',
            self::TYPE_RETURN => 'orange',
            self::TYPE_COMMISSION => 'blue',
            self::TYPE_LOGISTICS => 'purple',
            self::TYPE_STORAGE => 'indigo',
            self::TYPE_ADV => 'cyan',
            self::TYPE_PENALTY => 'red',
            default => 'gray',
        };
    }

    /**
     * Check if operation is a deduction
     */
    public function isDeduction(): bool
    {
        return in_array($this->operation_type, [
            self::TYPE_RETURN,
            self::TYPE_COMMISSION,
            self::TYPE_LOGISTICS,
            self::TYPE_STORAGE,
            self::TYPE_ADV,
            self::TYPE_PENALTY,
        ]);
    }
}
