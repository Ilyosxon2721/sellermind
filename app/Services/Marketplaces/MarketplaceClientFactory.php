<?php

declare(strict_types=1);

namespace App\Services\Marketplaces;

use App\Models\MarketplaceAccount;
use App\Services\Marketplaces\YandexMarket\YandexMarketClient;
use InvalidArgumentException;

/**
 * Фабрика для получения клиента маркетплейса
 *
 * Поддерживаемые маркетплейсы:
 * - wb (Wildberries)
 * - ozon (Ozon)
 * - uzum (Uzum Market)
 * - ym (Yandex Market)
 */
final class MarketplaceClientFactory
{
    public function __construct(
        private readonly WildberriesClient $wildberriesClient,
        private readonly OzonClient $ozonClient,
        private readonly UzumClient $uzumClient,
        private readonly YandexMarketClient $yandexMarketClient,
    ) {}

    /**
     * Get marketplace client for account
     */
    public function forAccount(MarketplaceAccount $account): MarketplaceClientInterface
    {
        return $this->forMarketplace($account->marketplace);
    }

    /**
     * Get marketplace client by marketplace code
     */
    public function forMarketplace(string $marketplace): MarketplaceClientInterface
    {
        return match ($marketplace) {
            'wb', 'wildberries' => $this->wildberriesClient,
            'ozon' => $this->ozonClient,
            'uzum' => $this->uzumClient,
            'ym', 'yandex_market' => $this->yandexMarketClient,
            default => throw new InvalidArgumentException("Unknown marketplace: {$marketplace}"),
        };
    }

    /**
     * Get all supported marketplace codes
     */
    public function getSupportedMarketplaces(): array
    {
        return ['wb', 'ozon', 'uzum', 'ym'];
    }

    /**
     * Get marketplace label
     */
    public function getMarketplaceLabel(string $marketplace): string
    {
        return match ($marketplace) {
            'wb', 'wildberries' => 'Wildberries',
            'ozon' => 'Ozon',
            'uzum' => 'Uzum Market',
            'ym', 'yandex_market' => 'Yandex Market',
            default => $marketplace,
        };
    }
}
