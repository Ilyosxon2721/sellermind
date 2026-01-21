<?php

namespace App\Providers;

use App\Events\StockUpdated;
use App\Listeners\SyncStockToMarketplaces;
use App\Models\OzonOrder;
use App\Models\ProductVariant;
use App\Models\UzumOrder;
use App\Models\WbOrder;
use App\Observers\OzonOrderObserver;
use App\Observers\ProductVariantObserver;
use App\Observers\UzumOrderObserver;
use App\Observers\WbOrderObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
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
        // Configure API rate limiting for Sanctum
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Register observers
        ProductVariant::observe(ProductVariantObserver::class);
        UzumOrder::observe(UzumOrderObserver::class);
        WbOrder::observe(WbOrderObserver::class);
        OzonOrder::observe(OzonOrderObserver::class);

        // Register event listeners
        Event::listen(StockUpdated::class, SyncStockToMarketplaces::class);
    }
}
