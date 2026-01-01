<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Marketplace Scheduled Tasks
|--------------------------------------------------------------------------
|
| Schedule marketplace sync and health-check tasks.
|
| To run scheduler in development:
|   php artisan schedule:work
|
| To set up cron on production (run as web server user):
|   * * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
|
*/

// Синхронизация заказов каждые 10 минут (новые заказы должны обрабатываться быстро)
Schedule::command('marketplace:sync-orders --days=7')
    ->everyTenMinutes()
    ->withoutOverlapping(10) // Prevent overlapping, timeout after 10 minutes
    ->onFailure(function () {
        \Log::error('Marketplace orders sync failed');
    })
    ->appendOutputTo(storage_path('logs/marketplace-sync.log'));

// Полный цикл синхронизации остатков WB (pull+push)
Schedule::command('wb:full-stock-sync')
    ->hourly()
    ->withoutOverlapping(30)
    ->appendOutputTo(storage_path('logs/marketplace-sync.log'));

// Синхронизация поставок WB каждые 30 минут
Schedule::command('wb:sync-supplies')
    ->everyThirtyMinutes()
    ->withoutOverlapping(15)
    ->onFailure(function () {
        \Log::error('WB supplies sync failed');
    })
    ->appendOutputTo(storage_path('logs/marketplace-sync.log'));

// Синхронизация карточек товаров WB раз в час (pull из Content API)
Schedule::command('wb:sync-products all')
    ->hourly()
    ->withoutOverlapping(45)
    ->onFailure(function () {
        \Log::error('WB products sync failed');
    })
    ->appendOutputTo(storage_path('logs/marketplace-sync.log'));

// Получение новых FBS заказов от Wildberries каждые 5 минут
Schedule::call(function () {
    $wbAccounts = \App\Models\MarketplaceAccount::where('marketplace', 'wb')
        ->where('is_active', true)
        ->get();

    foreach ($wbAccounts as $account) {
        \App\Jobs\SyncNewWildberriesOrdersJob::dispatch($account);
    }
})->everyFiveMinutes()
    ->name('wb-sync-new-orders')
    ->onFailure(function () {
        \Log::error('WB new orders sync failed');
    });

// Обновление статусов активных заказов каждые 10 минут
Schedule::call(function () {
    $wbAccounts = \App\Models\MarketplaceAccount::where('marketplace', 'wb')
        ->where('is_active', true)
        ->get();

    foreach ($wbAccounts as $account) {
        \App\Jobs\UpdateWildberriesOrdersStatusJob::dispatch($account);
    }
})->everyTenMinutes()
    ->name('wb-update-orders-status')
    ->onFailure(function () {
        \Log::error('WB orders status update failed');
    });

// OZON: Синхронизация остатков каждый час
Schedule::command('ozon:full-stock-sync')
    ->hourly()
    ->withoutOverlapping(30)
    ->onFailure(function () {
        \Log::error('OZON stock sync failed');
    })
    ->appendOutputTo(storage_path('logs/marketplace-sync.log'));

// OZON: Синхронизация каталога каждые 6 часов
Schedule::command('ozon:sync-catalog')
    ->everySixHours()
    ->withoutOverlapping(60)
    ->onFailure(function () {
        \Log::error('OZON catalog sync failed');
    })
    ->appendOutputTo(storage_path('logs/marketplace-sync.log'));

// OZON: Синхронизация заказов каждые 15 минут
Schedule::command('ozon:sync-orders')
    ->everyFifteenMinutes()
    ->withoutOverlapping(10)
    ->onFailure(function () {
        \Log::error('OZON orders sync failed');
    })
    ->appendOutputTo(storage_path('logs/marketplace-sync.log'));

// Uzum Market: Синхронизация товаров каждый час
Schedule::call(function () {
    $uzumAccounts = \App\Models\MarketplaceAccount::where('marketplace', 'uzum')
        ->where('is_active', true)
        ->get();

    foreach ($uzumAccounts as $account) {
        \Illuminate\Support\Facades\Artisan::call('uzum:pull-products', ['accountId' => $account->id]);
    }
})->hourly()
    ->name('uzum-sync-products')
    ->onFailure(function () {
        \Log::error('Uzum products sync failed');
    });

// Uzum Market: Синхронизация остатков каждый час
Schedule::command('uzum:sync-stocks')
    ->hourly()
    ->withoutOverlapping(30)
    ->onFailure(function () {
        \Log::error('Uzum stocks sync failed');
    })
    ->appendOutputTo(storage_path('logs/marketplace-sync.log'));

// Uzum Market: Синхронизация заказов каждые 15 минут
Schedule::command('uzum:sync-orders')
    ->everyFifteenMinutes()
    ->withoutOverlapping(10)
    ->onFailure(function () {
        \Log::error('Uzum orders sync failed');
    })
    ->appendOutputTo(storage_path('logs/marketplace-sync.log'));
