<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront\Traits;

use App\Models\Store\Store;
use App\Models\Store\StoreAnalytics;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Общие методы для Storefront-контроллеров: получение магазина, трекинг аналитики
 */
trait StorefrontHelpers
{
    /**
     * Получить опубликованный магазин по slug с загрузкой темы
     *
     * @param  array<int, string>  $with  Дополнительные связи для загрузки
     */
    protected function getPublishedStore(string $slug, array $with = []): Store
    {
        return Store::where('slug', $slug)
            ->where('is_active', true)
            ->where('is_published', true)
            ->with(array_merge(['theme'], $with))
            ->firstOrFail();
    }

    /**
     * Экранирование спецсимволов LIKE
     */
    protected function escapeLike(string $value): string
    {
        return str_replace(['%', '_', '\\'], ['\\%', '\\_', '\\\\'], $value);
    }

    /**
     * Трекинг визита (visits + page_views) — один upsert вместо 3 запросов
     */
    protected function trackVisit(Store $store): void
    {
        $this->trackAnalytics($store, ['visits' => 1, 'page_views' => 1]);
    }

    /**
     * Трекинг просмотра страницы (только page_views)
     */
    protected function trackPageView(Store $store): void
    {
        $this->trackAnalytics($store, ['page_views' => 1]);
    }

    /**
     * Трекинг добавления в корзину
     */
    protected function trackCartAddition(Store $store): void
    {
        $this->trackAnalytics($store, ['cart_additions' => 1]);
    }

    /**
     * Трекинг завершённого заказа
     */
    protected function trackOrderCompleted(Store $store, float $total): void
    {
        $this->trackAnalytics($store, ['orders_completed' => 1, 'revenue' => $total]);
    }

    /**
     * Универсальный трекинг аналитики — fire-and-forget, один запрос к БД
     *
     * @param  array<string, int|float>  $increments  Поля для инкремента
     */
    private function trackAnalytics(Store $store, array $increments): void
    {
        try {
            $today = now()->toDateString();

            // Один upsert вместо updateOrCreate + N increment
            $setClauses = [];
            foreach ($increments as $field => $value) {
                $setClauses[] = "{$field} = {$field} + " . (is_float($value) ? $value : (int) $value);
            }

            DB::statement(
                'INSERT INTO store_analytics (store_id, date, ' . implode(', ', array_keys($increments)) . ')
                 VALUES (?, ?, ' . implode(', ', array_fill(0, count($increments), '?')) . ')
                 ON DUPLICATE KEY UPDATE ' . implode(', ', $setClauses),
                array_merge([$store->id, $today], array_values($increments))
            );
        } catch (\Throwable $e) {
            Log::debug('Ошибка трекинга аналитики витрины', [
                'store_id' => $store->id,
                'increments' => $increments,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
