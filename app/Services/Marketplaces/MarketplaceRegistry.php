<?php

// file: app/Services/Marketplaces/MarketplaceRegistry.php

namespace App\Services\Marketplaces;

use App\Models\MarketplaceAccount;
use InvalidArgumentException;

class MarketplaceRegistry
{
    /**
     * @var array<string, MarketplaceClientInterface>
     */
    protected array $clients = [];

    /**
     * Register a marketplace client
     */
    public function registerClient(string $marketplaceCode, MarketplaceClientInterface $client): void
    {
        $this->clients[$marketplaceCode] = $client;
    }

    /**
     * Get client for marketplace account
     */
    public function getClientForAccount(MarketplaceAccount $account): MarketplaceClientInterface
    {
        $code = $account->marketplace;

        if (! isset($this->clients[$code])) {
            throw new InvalidArgumentException("No marketplace client registered for [{$code}]");
        }

        return $this->clients[$code];
    }

    /**
     * Get client by marketplace code
     */
    public function getClient(string $marketplaceCode): MarketplaceClientInterface
    {
        if (! isset($this->clients[$marketplaceCode])) {
            throw new InvalidArgumentException("No marketplace client registered for [{$marketplaceCode}]");
        }

        return $this->clients[$marketplaceCode];
    }

    /**
     * Check if client is registered
     */
    public function hasClient(string $marketplaceCode): bool
    {
        return isset($this->clients[$marketplaceCode]);
    }

    /**
     * Get all registered marketplace codes
     */
    public function getRegisteredMarketplaces(): array
    {
        return array_keys($this->clients);
    }

    /**
     * Get all registered clients
     */
    public function getAllClients(): array
    {
        return $this->clients;
    }
}
