<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Models\Finance\FinanceSettings;
use App\Models\Finance\FinanceTransaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Сервис для получения данных о продажах маркетплейсов
 */
final class MarketplaceSalesService
{
    /**
     * Получить сводку продаж по всем маркетплейсам
     */
    public function getSales(int $companyId, Carbon $from, Carbon $to, FinanceSettings $settings): array
    {
        $rubToUzs = $settings->rub_rate ?? 140;

        $result = [
            'uzum' => ['orders' => 0, 'revenue' => 0, 'profit' => 0],
            'wb' => ['orders' => 0, 'revenue' => 0, 'revenue_rub' => 0, 'profit' => 0],
            'ozon' => ['orders' => 0, 'revenue' => 0, 'revenue_rub' => 0, 'profit' => 0],
            'ym' => ['orders' => 0, 'revenue' => 0, 'revenue_rub' => 0, 'profit' => 0],
            'total_orders' => 0,
            'total_revenue' => 0,
            'total_profit' => 0,
        ];

        Log::info('MarketplaceSalesService::getSales', [
            'company_id' => $companyId,
            'from' => $from->format('Y-m-d'),
            'to' => $to->format('Y-m-d'),
            'rub_rate' => $rubToUzs,
        ]);

        $result['uzum'] = $this->getUzumSales($companyId, $from, $to);
        $result['wb'] = $this->getWbSales($companyId, $from, $to, $rubToUzs);
        $result['ozon'] = $this->getOzonSales($companyId, $from, $to, $rubToUzs);
        $result['ym'] = $this->getYmSales($companyId, $from, $to, $rubToUzs);

        // Итого
        $result['total_orders'] = $result['uzum']['orders'] + $result['wb']['orders'] + $result['ozon']['orders'] + $result['ym']['orders'];
        $result['total_revenue'] = $result['uzum']['revenue'] + $result['wb']['revenue'] + $result['ozon']['revenue'] + $result['ym']['revenue'];
        $result['total_profit'] = $result['uzum']['profit'] + $result['wb']['profit'] + $result['ozon']['profit'] + $result['ym']['profit'];

        Log::info('MarketplaceSalesService totals', [
            'total_orders' => $result['total_orders'],
            'total_revenue' => $result['total_revenue'],
            'total_profit' => $result['total_profit'],
        ]);

        return $result;
    }

    /**
     * Продажи Uzum
     */
    protected function getUzumSales(int $companyId, Carbon $from, Carbon $to): array
    {
        $default = ['orders' => 0, 'revenue' => 0, 'profit' => 0];

        try {
            $hasFinanceOrders = class_exists(\App\Models\UzumFinanceOrder::class)
                && \App\Models\UzumFinanceOrder::whereHas('account', fn ($q) => $q->where('company_id', $companyId))->exists();

            if ($hasFinanceOrders) {
                $uzumSales = \App\Models\UzumFinanceOrder::whereHas('account', fn ($q) => $q->where('company_id', $companyId))
                    ->where(function ($q) use ($from, $to) {
                        $q->where(function ($sub) use ($from, $to) {
                            $sub->where('status', 'TO_WITHDRAW')
                                ->whereDate('date_issued', '>=', $from)
                                ->whereDate('date_issued', '<=', $to);
                        })
                            ->orWhere(function ($sub) use ($from, $to) {
                                $sub->where('status', 'PROCESSING')
                                    ->whereNotNull('date_issued')
                                    ->whereDate('date_issued', '>=', $from)
                                    ->whereDate('date_issued', '<=', $to);
                            });
                    })
                    ->selectRaw('COUNT(*) as cnt, SUM(sell_price * amount) as revenue, SUM(seller_profit) as profit')
                    ->first();

                return [
                    'orders' => (int) ($uzumSales?->cnt ?? 0),
                    'revenue' => (float) ($uzumSales?->revenue ?? 0),
                    'profit' => (float) ($uzumSales?->profit ?? 0),
                ];
            }

            if (class_exists(\App\Models\UzumOrder::class)) {
                $uzumSales = \App\Models\UzumOrder::whereHas('account', fn ($q) => $q->where('company_id', $companyId))
                    ->where('status', 'issued')
                    ->whereDate('ordered_at', '>=', $from)
                    ->whereDate('ordered_at', '<=', $to)
                    ->selectRaw('COUNT(*) as cnt, SUM(total_amount) as revenue')
                    ->first();

                $revenue = (float) ($uzumSales?->revenue ?? 0);

                $uzumExpenses = FinanceTransaction::byCompany($companyId)
                    ->confirmed()
                    ->expense()
                    ->inPeriod($from, $to)
                    ->whereHas('category', fn ($q) => $q->where('code', 'like', 'MP_%'))
                    ->where('metadata->source', 'uzum_sync')
                    ->sum('amount_base');

                return [
                    'orders' => (int) ($uzumSales?->cnt ?? 0),
                    'revenue' => $revenue,
                    'profit' => $revenue - (float) $uzumExpenses,
                ];
            }
        } catch (\Exception $e) {
            Log::error('Uzum sales error', ['error' => $e->getMessage()]);
        }

        return $default;
    }

    /**
     * Продажи Wildberries
     */
    protected function getWbSales(int $companyId, Carbon $from, Carbon $to, float $rubToUzs): array
    {
        $default = ['orders' => 0, 'revenue' => 0, 'revenue_rub' => 0, 'profit' => 0];

        try {
            if (class_exists(\App\Models\WildberriesOrder::class)) {
                $wbSales = \App\Models\WildberriesOrder::whereHas('account', fn ($q) => $q->where('company_id', $companyId))
                    ->where('is_realization', true)
                    ->where('is_cancel', false)
                    ->whereDate('last_change_date', '>=', $from)
                    ->whereDate('last_change_date', '<=', $to)
                    ->selectRaw('COUNT(*) as cnt, SUM(COALESCE(finished_price, total_price, 0)) as revenue, SUM(COALESCE(for_pay, 0)) as seller_revenue')
                    ->first();

                $revenueRub = (float) ($wbSales?->revenue ?? 0);
                $sellerRevenueRub = (float) ($wbSales?->seller_revenue ?? 0);

                return [
                    'orders' => (int) ($wbSales?->cnt ?? 0),
                    'revenue' => $revenueRub * $rubToUzs,
                    'revenue_rub' => $revenueRub,
                    'profit' => $sellerRevenueRub * $rubToUzs,
                    'profit_rub' => $sellerRevenueRub,
                ];
            }
        } catch (\Exception $e) {
            Log::error('WB sales error', ['error' => $e->getMessage()]);
        }

        return $default;
    }

    /**
     * Продажи Ozon
     */
    protected function getOzonSales(int $companyId, Carbon $from, Carbon $to, float $rubToUzs): array
    {
        $default = ['orders' => 0, 'revenue' => 0, 'revenue_rub' => 0, 'profit' => 0];

        try {
            if (class_exists(\App\Models\OzonOrder::class)) {
                $ozonSales = \App\Models\OzonOrder::whereHas('account', fn ($q) => $q->where('company_id', $companyId))
                    ->where('stock_status', 'sold')
                    ->whereNotNull('stock_sold_at')
                    ->whereDate('stock_sold_at', '>=', $from)
                    ->whereDate('stock_sold_at', '<=', $to)
                    ->selectRaw('COUNT(*) as cnt, SUM(COALESCE(total_price, 0)) as revenue')
                    ->first();

                $revenueRub = (float) ($ozonSales?->revenue ?? 0);

                $ozonExpenses = FinanceTransaction::byCompany($companyId)
                    ->confirmed()
                    ->expense()
                    ->inPeriod($from, $to)
                    ->whereHas('category', fn ($q) => $q->where('code', 'like', 'MP_%'))
                    ->where('metadata->source', 'ozon_sync')
                    ->sum('amount_base');

                $revenueUzs = $revenueRub * $rubToUzs;

                return [
                    'orders' => (int) ($ozonSales?->cnt ?? 0),
                    'revenue' => $revenueUzs,
                    'revenue_rub' => $revenueRub,
                    'profit' => $revenueUzs - (float) $ozonExpenses,
                ];
            }
        } catch (\Exception $e) {
            Log::error('Ozon sales error', ['error' => $e->getMessage()]);
        }

        return $default;
    }

    /**
     * Продажи Yandex Market
     */
    protected function getYmSales(int $companyId, Carbon $from, Carbon $to, float $rubToUzs): array
    {
        $default = ['orders' => 0, 'revenue' => 0, 'revenue_rub' => 0, 'profit' => 0];

        try {
            if (class_exists(\App\Models\YandexMarketOrder::class)) {
                $ymSales = \App\Models\YandexMarketOrder::whereHas('account', fn ($q) => $q->where('company_id', $companyId))
                    ->where('stock_status', 'sold')
                    ->whereNotNull('stock_sold_at')
                    ->whereDate('stock_sold_at', '>=', $from)
                    ->whereDate('stock_sold_at', '<=', $to)
                    ->selectRaw('COUNT(*) as cnt, SUM(COALESCE(total_price, 0)) as revenue')
                    ->first();

                $revenueRub = (float) ($ymSales?->revenue ?? 0);

                $ymExpenses = FinanceTransaction::byCompany($companyId)
                    ->confirmed()
                    ->expense()
                    ->inPeriod($from, $to)
                    ->whereHas('category', fn ($q) => $q->where('code', 'like', 'MP_%'))
                    ->where('metadata->source', 'ym_sync')
                    ->sum('amount_base');

                $revenueUzs = $revenueRub * $rubToUzs;

                return [
                    'orders' => (int) ($ymSales?->cnt ?? 0),
                    'revenue' => $revenueUzs,
                    'revenue_rub' => $revenueRub,
                    'profit' => $revenueUzs - (float) $ymExpenses,
                ];
            }
        } catch (\Exception $e) {
            Log::error('YM sales error', ['error' => $e->getMessage()]);
        }

        return $default;
    }
}
