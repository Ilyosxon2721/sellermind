<?php

namespace App\Providers;

use App\Events\StockUpdated;
use App\Listeners\SyncStockToMarketplaces;
use App\Models\ProductVariant;
use App\Models\UzumOrder;
use App\Observers\ProductVariantObserver;
use App\Observers\UzumOrderObserver;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register observers
        ProductVariant::observe(ProductVariantObserver::class);
        UzumOrder::observe(UzumOrderObserver::class);
        
        // Register event listeners
        Event::listen(StockUpdated::class, SyncStockToMarketplaces::class);
    }
}
