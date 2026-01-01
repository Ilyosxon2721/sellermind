<?php
// file: app/Models/MarketplaceAutomationRule.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceAutomationRule extends Model
{
    // Event types
    public const EVENT_LOW_STOCK = 'low_stock';
    public const EVENT_NO_SALES = 'no_sales';
    public const EVENT_HIGH_RETURN_RATE = 'high_return_rate';
    public const EVENT_COMPETITOR_PRICE_DROP = 'competitor_price_drop';
    public const EVENT_ORDER_CREATED = 'order_created';
    public const EVENT_ORDER_DELIVERED = 'order_delivered';
    public const EVENT_ORDER_CANCELED = 'order_canceled';
    public const EVENT_RETURN_CREATED = 'return_created';
    public const EVENT_PAYOUT_RECEIVED = 'payout_received';

    // Action types
    public const ACTION_NOTIFY = 'notify';
    public const ACTION_ADJUST_PRICE = 'adjust_price';
    public const ACTION_CREATE_AGENT_TASK = 'create_agent_task';
    public const ACTION_SYNC_STOCKS = 'sync_stocks';
    public const ACTION_DISABLE_PRODUCT = 'disable_product';

    protected $fillable = [
        'marketplace_account_id',
        'name',
        'event_type',
        'conditions_json',
        'action_type',
        'action_params_json',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'conditions_json' => 'array',
            'action_params_json' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(MarketplaceAccount::class, 'marketplace_account_id');
    }

    /**
     * Get event type label
     */
    public function getEventTypeLabel(): string
    {
        return match ($this->event_type) {
            self::EVENT_LOW_STOCK => 'Низкий остаток',
            self::EVENT_NO_SALES => 'Нет продаж',
            self::EVENT_HIGH_RETURN_RATE => 'Высокий % возвратов',
            self::EVENT_COMPETITOR_PRICE_DROP => 'Снижение цены конкурента',
            self::EVENT_ORDER_CREATED => 'Новый заказ',
            self::EVENT_ORDER_DELIVERED => 'Заказ доставлен',
            self::EVENT_ORDER_CANCELED => 'Заказ отменён',
            self::EVENT_RETURN_CREATED => 'Новый возврат',
            self::EVENT_PAYOUT_RECEIVED => 'Получена выплата',
            default => $this->event_type,
        };
    }

    /**
     * Get action type label
     */
    public function getActionTypeLabel(): string
    {
        return match ($this->action_type) {
            self::ACTION_NOTIFY => 'Уведомить',
            self::ACTION_ADJUST_PRICE => 'Изменить цену',
            self::ACTION_CREATE_AGENT_TASK => 'Создать задачу агенту',
            self::ACTION_SYNC_STOCKS => 'Синхронизировать остатки',
            self::ACTION_DISABLE_PRODUCT => 'Отключить товар',
            default => $this->action_type,
        };
    }

    /**
     * Check if conditions are met
     */
    public function checkConditions(array $context): bool
    {
        $conditions = $this->conditions_json ?? [];

        foreach ($conditions as $key => $value) {
            if (!isset($context[$key])) {
                continue;
            }

            // Handle different comparison types
            if (is_array($value)) {
                if (isset($value['min']) && $context[$key] < $value['min']) {
                    return false;
                }
                if (isset($value['max']) && $context[$key] > $value['max']) {
                    return false;
                }
                if (isset($value['equals']) && $context[$key] !== $value['equals']) {
                    return false;
                }
            } else {
                if ($context[$key] !== $value) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Get available event types
     */
    public static function getEventTypes(): array
    {
        return [
            self::EVENT_LOW_STOCK => 'Низкий остаток',
            self::EVENT_NO_SALES => 'Нет продаж',
            self::EVENT_HIGH_RETURN_RATE => 'Высокий % возвратов',
            self::EVENT_COMPETITOR_PRICE_DROP => 'Снижение цены конкурента',
            self::EVENT_ORDER_CREATED => 'Новый заказ',
            self::EVENT_ORDER_DELIVERED => 'Заказ доставлен',
            self::EVENT_ORDER_CANCELED => 'Заказ отменён',
            self::EVENT_RETURN_CREATED => 'Новый возврат',
            self::EVENT_PAYOUT_RECEIVED => 'Получена выплата',
        ];
    }

    /**
     * Get available action types
     */
    public static function getActionTypes(): array
    {
        return [
            self::ACTION_NOTIFY => 'Уведомить',
            self::ACTION_ADJUST_PRICE => 'Изменить цену',
            self::ACTION_CREATE_AGENT_TASK => 'Создать задачу агенту',
            self::ACTION_SYNC_STOCKS => 'Синхронизировать остатки',
            self::ACTION_DISABLE_PRODUCT => 'Отключить товар',
        ];
    }
}
