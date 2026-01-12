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

/*
|--------------------------------------------------------------------------
| Quick Wins Scheduled Tasks
|--------------------------------------------------------------------------
|
| Automated tasks for Smart Promotions, Review Responses, and Analytics
|
*/

// Smart Promotions: Создание автоматических промо для неликвида (каждый понедельник)
Schedule::call(function () {
    $companies = \App\Models\Company::where('is_active', true)->get();

    foreach ($companies as $company) {
        \App\Jobs\ProcessAutoPromotionsJob::dispatch($company->id, [
            'min_days_no_sale' => 30,
            'min_stock' => 5,
            'min_price' => 100,
        ]);
    }
})->weekly()
    ->mondays()
    ->at('09:00')
    ->name('create-auto-promotions')
    ->onSuccess(function () {
        \Log::info('Smart Promotions: Auto promotion jobs dispatched');
    })
    ->onFailure(function () {
        \Log::error('Smart Promotions: Failed to dispatch auto promotion jobs');
    });

// Smart Promotions: Уведомления об истекающих акциях (каждый день)
Schedule::call(function () {
    $companies = \App\Models\Company::where('is_active', true)->get();

    foreach ($companies as $company) {
        \App\Jobs\SendPromotionExpiringNotificationsJob::dispatch($company->id, 3);
    }
})->daily()
    ->at('10:00')
    ->name('notify-expiring-promotions')
    ->onSuccess(function () {
        \Log::info('Smart Promotions: Expiring notification jobs dispatched');
    })
    ->onFailure(function () {
        \Log::error('Smart Promotions: Failed to dispatch expiring notification jobs');
    });

// Sales Analytics: Обновление кэша аналитики (каждый час)
Schedule::call(function () {
    // Предварительный расчет аналитики для всех компаний
    $companies = \App\Models\Company::where('is_active', true)->get();

    foreach ($companies as $company) {
        try {
            $analyticsService = app(\App\Services\SalesAnalyticsService::class);

            // Кэшируем аналитику на час
            \Cache::put(
                "sales_analytics_{$company->id}_30days",
                $analyticsService->getOverview($company->id, '30days'),
                now()->addHour()
            );
        } catch (\Exception $e) {
            \Log::error("Analytics cache failed for company {$company->id}: " . $e->getMessage());
        }
    }
})->hourly()
    ->name('cache-sales-analytics')
    ->onFailure(function () {
        \Log::error('Failed to cache sales analytics');
    });

// Review Responses: Синхронизация отзывов с маркетплейсов (каждые 30 минут)
// TODO: Реализовать команду для автоматического импорта отзывов с WB/Ozon/Yandex
// Schedule::command('reviews:sync-from-marketplaces')
//     ->everyThirtyMinutes()
//     ->withoutOverlapping(15)
//     ->appendOutputTo(storage_path('logs/reviews.log'));
