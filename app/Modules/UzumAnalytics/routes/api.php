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

Route::middleware(['web', 'auth:sanctum'])
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

        // Healthcheck краулера (#086)
        Route::get('/health', [AnalyticsController::class, 'healthcheck'])->name('health');

        // AI-анализ рынка
        Route::get('/ai-insights', [AnalyticsController::class, 'aiInsights'])->name('ai-insights');

        // Синхронизация категорий вручную
        Route::post('/sync-categories', [AnalyticsController::class, 'syncCategories'])->name('sync-categories');

        // Экспорт CSV (#081)
        Route::get('/export', [AnalyticsController::class, 'export'])->name('export');

        // Анализ конкурентов
        Route::get('/our-categories', [AnalyticsController::class, 'ourCategories'])->name('our-categories');
        Route::get('/category/{id}/rankings', [AnalyticsController::class, 'categoryRankings'])->name('category.rankings');
    });
