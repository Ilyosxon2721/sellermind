<?php

// file: app/Providers/UzumAnalyticsServiceProvider.php

declare(strict_types=1);

namespace App\Providers;

use App\Modules\UzumAnalytics\Console\AddTokenCommand;
use App\Modules\UzumAnalytics\Console\SyncCategoriesCommand;
use App\Modules\UzumAnalytics\Services\CircuitBreaker;
use App\Modules\UzumAnalytics\Services\RateLimiter;
use App\Modules\UzumAnalytics\Services\TokenRefreshService;
use App\Modules\UzumAnalytics\Services\UzumAnalyticsApiClient;
use Illuminate\Support\ServiceProvider;

final class UzumAnalyticsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(base_path('config/uzum-crawler.php'), 'uzum-crawler');

        $this->app->singleton(CircuitBreaker::class, fn () => new CircuitBreaker);

        $this->app->singleton(RateLimiter::class, fn () => new RateLimiter);

        $this->app->singleton(TokenRefreshService::class, fn () => new TokenRefreshService);

        $this->app->singleton(UzumAnalyticsApiClient::class, fn ($app) => new UzumAnalyticsApiClient(
            $app->make(TokenRefreshService::class),
            $app->make(RateLimiter::class),
            $app->make(CircuitBreaker::class),
        ));
    }

    public function boot(): void
    {
        // Загрузка миграций из поддиректории
        $this->loadMigrationsFrom(database_path('migrations/uzum_analytics'));

        // Загрузка API маршрутов модуля
        $this->loadRoutesFrom(base_path('app/Modules/UzumAnalytics/routes/api.php'));

        $this->publishes([
            base_path('config/uzum-crawler.php') => config_path('uzum-crawler.php'),
        ], 'uzum-crawler-config');

        if ($this->app->runningInConsole()) {
            $this->commands([AddTokenCommand::class, SyncCategoriesCommand::class]);
        }
    }
}
