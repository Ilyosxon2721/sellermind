<?php

declare(strict_types=1);

namespace App\Enums;

enum EntityType: string
{
    case ORDER = 'order';
    case RETURN = 'return';
    case CHAT = 'chat';
    case POSTING = 'posting';
    case STOCK = 'stock';
    case SALE = 'sale';
}
