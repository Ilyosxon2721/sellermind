<?php

declare(strict_types=1);

namespace App\Enums;

enum MarketplaceType: string
{
    case OZON = 'ozon';
    case YANDEX = 'yandex';
    case WILDBERRIES = 'wildberries';
    case UZUM = 'uzum';

    public function supportsWebhook(): bool
    {
        return match ($this) {
            self::OZON, self::YANDEX => true,
            default => false,
        };
    }

    public function supportsPolling(): bool
    {
        return match ($this) {
            self::WILDBERRIES, self::UZUM => true,
            default => false,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::OZON => 'Ozon',
            self::YANDEX => 'Yandex Market',
            self::WILDBERRIES => 'Wildberries',
            self::UZUM => 'Uzum Market',
        };
    }
}
