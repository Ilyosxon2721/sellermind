<?php

// file: app/Services/Marketplaces/MarketplaceClientFactory.php

namespace App\Services\Marketplaces;

use App\Models\MarketplaceAccount;
use App\Services\Marketplaces\YandexMarket\YandexMarketClient;
use InvalidArgumentException;

class MarketplaceClientFactory
{
    protected WildberriesClient $wildberriesClient;

    protected OzonClient $ozonClient;

    protected UzumClient $uzumClient;

    protected YandexMarketClient $yandexMarketClient;

    public function __construct(
        WildberriesClient $wildberriesClient,
        OzonClient $ozonClient,
        UzumClient $uzumClient,
        YandexMarketClient $yandexMarketClient
    ) {
        $this->wildberriesClient = $wildberriesClient;
        $this->ozonClient = $ozonClient;
        $this->uzumClient = $uzumClient;
        $this->yandexMarketClient = $yandexMarketClient;
    }

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
