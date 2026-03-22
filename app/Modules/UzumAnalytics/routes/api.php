<?php

declare(strict_types=1);

use App\Modules\UzumAnalytics\Http\Controllers\AnalyticsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Uzum Analytics API Routes
|--------------------------------------------------------------------------
|
| API маршруты для модуля аналитики Uzum Market
| Все маршруты требуют авторизации и company scope
|
*/

Route::middleware(['auth:sanctum', 'verified'])
    ->prefix('api/analytics/uzum')
    ->name('api.analytics.uzum.')
    ->group(function () {
        // Список категорий с метриками
        Route::get('/categories', [AnalyticsController::class, 'categories'])
            ->name('categories');

        // Товары категории с сортировкой
        Route::get('/category/{id}/products', [AnalyticsController::class, 'categoryProducts'])
            ->name('category.products');

        // Данные конкурирующего магазина
        Route::get('/competitor/{slug}', [AnalyticsController::class, 'competitor'])
            ->name('competitor');

        // История цен товара
        Route::get('/price-history/{productId}', [AnalyticsController::class, 'priceHistory'])
            ->name('price-history');

        // Сводка по отслеживаемым позициям
        Route::get('/market-overview', [AnalyticsController::class, 'marketOverview'])
            ->name('market-overview');

        // Управление отслеживаемыми товарами (#073-#075)
        Route::get('/tracked', [AnalyticsController::class, 'tracked'])->name('tracked.index');
        Route::post('/tracked', [AnalyticsController::class, 'addTracked'])->name('tracked.store');
        Route::delete('/tracked/{productId}', [AnalyticsController::class, 'removeTracked'])->name('tracked.destroy');
    });
