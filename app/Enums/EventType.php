<?php

declare(strict_types=1);

namespace App\Enums;

enum EventType: string
{
    case ORDER_CREATED = 'order_created';
    case ORDER_STATUS_CHANGED = 'order_status_changed';
    case ORDER_CANCELLED = 'order_cancelled';
    case ORDER_UPDATED = 'order_updated';
    case RETURN_CREATED = 'return_created';
    case RETURN_STATUS_CHANGED = 'return_status_changed';
    case CHAT_MESSAGE_CREATED = 'chat_message_created';
    case CHAT_MESSAGE_UPDATED = 'chat_message_updated';
    case CHAT_MESSAGE_READ = 'chat_message_read';
    case CHAT_CLOSED = 'chat_closed';
    case STOCK_UPDATED = 'stock_updated';
    case SALE_RECORDED = 'sale_recorded';

    public function label(): string
    {
        return match ($this) {
            self::ORDER_CREATED => 'Новый заказ',
            self::ORDER_STATUS_CHANGED => 'Статус заказа изменён',
            self::ORDER_CANCELLED => 'Заказ отменён',
            self::ORDER_UPDATED => 'Заказ обновлён',
            self::RETURN_CREATED => 'Новый возврат',
            self::RETURN_STATUS_CHANGED => 'Статус возврата изменён',
            self::CHAT_MESSAGE_CREATED => 'Новое сообщение',
            self::CHAT_MESSAGE_UPDATED => 'Сообщение обновлено',
            self::CHAT_MESSAGE_READ => 'Сообщение прочитано',
            self::CHAT_CLOSED => 'Чат закрыт',
            self::STOCK_UPDATED => 'Остатки обновлены',
            self::SALE_RECORDED => 'Продажа зафиксирована',
        };
    }
}
