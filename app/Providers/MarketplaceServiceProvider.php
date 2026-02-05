<?php

// file: app/Providers/MarketplaceServiceProvider.php

namespace App\Providers;

use App\Services\Marketplaces\MarketplaceHttpClient;
use App\Services\Marketplaces\MarketplaceRegistry;
use App\Services\Marketplaces\MarketplaceSyncService;
use App\Services\Marketplaces\OzonClient;
use App\Services\Marketplaces\UzumClient;
use App\Services\Marketplaces\WildberriesClient;
use App\Services\Marketplaces\YandexMarket\YandexMarketClient;
use App\Services\Marketplaces\YandexMarket\YandexMarketHttpClient;
use Illuminate\Support\ServiceProvider;

class MarketplaceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register HTTP client as singleton
        $this->app->singleton(MarketplaceHttpClient::class, function ($app) {
            return new MarketplaceHttpClient;
        });

        // Register marketplace clients
        $this->app->singleton(WildberriesClient::class, function ($app) {
            return new WildberriesClient($app->make(MarketplaceHttpClient::class));
        });

        $this->app->singleton(OzonClient::class, function ($app) {
            return new OzonClient($app->make(MarketplaceHttpClient::class));
        });

        $this->app->singleton(UzumClient::class, function ($app) {
            return new UzumClient(
                $app->make(MarketplaceHttpClient::class),
                $app->make(\App\Services\Marketplaces\IssueDetectorService::class)
            );
        });

        // Register YandexMarket HTTP client
        $this->app->singleton(YandexMarketHttpClient::class, function ($app) {
            return new YandexMarketHttpClient;
        });

        $this->app->singleton(YandexMarketClient::class, function ($app) {
            return new YandexMarketClient($app->make(YandexMarketHttpClient::class));
        });

        // Register marketplace registry
        $this->app->singleton(MarketplaceRegistry::class, function ($app) {
            $registry = new MarketplaceRegistry;

            // Register all marketplace clients
            $registry->registerClient('wb', $app->make(WildberriesClient::class));
            $registry->registerClient('ozon', $app->make(OzonClient::class));
            $registry->registerClient('uzum', $app->make(UzumClient::class));
            $registry->registerClient('ym', $app->make(YandexMarketClient::class));

            return $registry;
        });

        // Register sync service
        $this->app->singleton(MarketplaceSyncService::class, function ($app) {
            return new MarketplaceSyncService($app->make(MarketplaceRegistry::class));
        });
    }

    public function boot(): void
    {
        //
    }
}
