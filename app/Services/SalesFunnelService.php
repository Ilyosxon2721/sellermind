<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\MarketplaceAccount;
use App\Models\OzonOrder;
use App\Models\SalesFunnel;
use App\Models\WildberriesOrder;
use App\Models\YandexMarketOrder;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Сервис воронки продаж
 *
 * Автоматически агрегирует данные из всех источников:
 * - Маркетплейсы: WB, Ozon, Uzum, Yandex Market
 * - Ручные продажи (Sales)
 * - Оффлайн: розница, опт, прямые (OfflineSales)
 */
final class SalesFunnelService
{
    /** Статусы отменённых заказов */
    private const CANCELLED_STATUSES = [
        'cancelled', 'canceled', 'returned',
        'CANCELLED', 'CANCELED', 'RETURNED',
        'PENDING_CANCELLATION',
    ];

    public function __construct(
        private readonly ?CurrencyConversionService $currencyService = null,
    ) {}

    /**
     * Автоматический расчёт воронки из реальных данных компании
     *
     * @param array $sourceFilter Фильтр источников ['wb','ozon','uzum','ym','manual','wholesale','retail','direct']
     */
    public function calculateAuto(
        int $companyId,
        string $period = '30days',
        array $sourceFilter = [],
    ): array {
        $dateRange = $this->getDateRange($period);
        $accountIds = MarketplaceAccount::where('company_id', $companyId)->pluck('id');

        // Настраиваем конвертацию валют
        if ($this->currencyService !== null) {
            $company = \App\Models\Company::find($companyId);
            if ($company) {
                $this->currencyService->forCompany($company);
            }
        }

        // Собираем данные по каждому источнику
        $sources = $this->getSourcesData($companyId, $accountIds, $dateRange, $sourceFilter);

        // Агрегируем
        $totalOrders = 0;
        $totalRevenue = 0.0;
        $totalCost = 0.0;
        $totalViews = 0;
        $totalCartAdds = 0;
        $sourceBreakdown = [];

        foreach ($sources as $key => $source) {
            $totalOrders += $source['orders'];
            $totalRevenue += $source['revenue'];
            $totalCost += $source['cost'];
            $totalViews += $source['views'];
            $totalCartAdds += $source['cart_adds'];

            if ($source['orders'] > 0 || $source['revenue'] > 0) {
                $sourceBreakdown[$key] = $source;
            }
        }

        $avgCheck = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;
        $netProfit = $totalRevenue - $totalCost;
        $profitMargin = $totalRevenue > 0 ? ($netProfit / $totalRevenue) * 100 : 0;

        // Конверсии воронки
        $inquiryRate = $totalViews > 0 ? ($totalCartAdds / $totalViews) * 100 : 0;
        $meetingRate = $totalCartAdds > 0 ? ($totalOrders / $totalCartAdds) * 100 : 0;

        // Строим воронку
        $funnel = [
            ['stage' => 'views', 'label' => 'Просмотры (Ko\'rdi)', 'value' => $totalViews, 'rate' => null, 'unit' => 'та мижоз'],
            ['stage' => 'inquiries', 'label' => 'Обращения / Корзина (Murojaat)', 'value' => $totalCartAdds, 'rate' => round($inquiryRate, 2), 'unit' => 'та мижоз'],
            ['stage' => 'meetings', 'label' => 'Заказы оформлены (Uchrashuvga keldi)', 'value' => $totalOrders, 'rate' => round($meetingRate, 2), 'unit' => 'та мижоз'],
            ['stage' => 'sales', 'label' => 'Продажи завершены (Sotuvlar)', 'value' => $totalOrders, 'rate' => 100.0, 'unit' => 'та'],
            ['stage' => 'average_check', 'label' => 'Средний чек (O\'rtacha chek)', 'value' => round($avgCheck, 2), 'rate' => null, 'unit' => 'UZS'],
            ['stage' => 'revenue', 'label' => 'Доход (Daromad)', 'value' => round($totalRevenue, 2), 'rate' => null, 'unit' => 'UZS'],
            ['stage' => 'net_profit', 'label' => 'Чистая прибыль (Sof foyda)', 'value' => round($netProfit, 2), 'rate' => round($profitMargin, 2), 'unit' => 'UZS'],
            ['stage' => 'bonus', 'label' => 'Бонус (Mukofot)', 'value' => 0, 'rate' => 0, 'unit' => 'UZS'],
        ];

        return [
            'funnel' => $funnel,
            'summary' => [
                'total_views' => $totalViews,
                'total_cart_adds' => $totalCartAdds,
                'total_orders' => $totalOrders,
                'total_revenue' => round($totalRevenue, 2),
                'total_cost' => round($totalCost, 2),
                'net_profit' => round($netProfit, 2),
                'average_check' => round($avgCheck, 2),
                'profit_margin' => round($profitMargin, 2),
            ],
            'sources' => $sourceBreakdown,
            'period' => [
                'from' => $dateRange['from']->toDateString(),
                'to' => $dateRange['to']->toDateString(),
            ],
        ];
    }

    /**
     * Расчёт воронки из ручных параметров (без обращения к БД)
     */
    public function calculateManual(array $params): array
    {
        $views = (int) ($params['views'] ?? 0);
        $inquiryRate = (float) ($params['inquiry_rate'] ?? 0);
        $meetingRate = (float) ($params['meeting_rate'] ?? 0);
        $saleRate = (float) ($params['sale_rate'] ?? 0);
        $averageCheck = (float) ($params['average_check'] ?? 0);
        $profitMargin = (float) ($params['profit_margin'] ?? 0);
        $bonusRate = (float) ($params['bonus_rate'] ?? 0);

        $inquiries = (int) round($views * ($inquiryRate / 100));
        $meetings = (int) round($inquiries * ($meetingRate / 100));
        $sales = (int) round($meetings * ($saleRate / 100));
        $revenue = round($sales * $averageCheck, 2);
        $netProfit = round($revenue * ($profitMargin / 100), 2);
        $bonus = round($netProfit * ($bonusRate / 100), 2);

        return [
            ['stage' => 'views', 'label' => 'Просмотры (Ko\'rdi)', 'value' => $views, 'rate' => null, 'unit' => 'та мижоз'],
            ['stage' => 'inquiries', 'label' => 'Обращения (Murojaat)', 'value' => $inquiries, 'rate' => $inquiryRate, 'unit' => 'та мижоз'],
            ['stage' => 'meetings', 'label' => 'Встречи (Uchrashuvga keldi)', 'value' => $meetings, 'rate' => $meetingRate, 'unit' => 'та мижоз'],
            ['stage' => 'sales', 'label' => 'Продажи (Sotuvlar)', 'value' => $sales, 'rate' => $saleRate, 'unit' => 'та'],
            ['stage' => 'average_check', 'label' => 'Средний чек (O\'rtacha chek)', 'value' => $averageCheck, 'rate' => null, 'unit' => $params['currency'] ?? 'UZS'],
            ['stage' => 'revenue', 'label' => 'Доход (Daromad)', 'value' => $revenue, 'rate' => null, 'unit' => $params['currency'] ?? 'UZS'],
            ['stage' => 'net_profit', 'label' => 'Чистая прибыль (Sof foyda)', 'value' => $netProfit, 'rate' => $profitMargin, 'unit' => $params['currency'] ?? 'UZS'],
            ['stage' => 'bonus', 'label' => 'Бонус (Mukofot)', 'value' => $bonus, 'rate' => $bonusRate, 'unit' => $params['currency'] ?? 'UZS'],
        ];
    }

    /**
     * Воронка с разбивкой по каждому источнику
     */
    public function getSourcesBreakdown(
        int $companyId,
        string $period = '30days',
    ): array {
        $dateRange = $this->getDateRange($period);
        $accountIds = MarketplaceAccount::where('company_id', $companyId)->pluck('id');

        return $this->getSourcesData($companyId, $accountIds, $dateRange, []);
    }

    // ========== Сбор данных по источникам ==========

    /**
     * Агрегация данных из всех источников
     */
    private function getSourcesData(
        int $companyId,
        Collection $accountIds,
        array $dateRange,
        array $sourceFilter,
    ): array {
        $sources = [];
        $allSources = empty($sourceFilter);

        // Маркетплейсы
        if ($accountIds->isNotEmpty()) {
            if ($allSources || in_array('wb', $sourceFilter)) {
                $sources['wb'] = $this->getWildberriesData($accountIds, $dateRange);
            }
            if ($allSources || in_array('ozon', $sourceFilter)) {
                $sources['ozon'] = $this->getOzonData($accountIds, $dateRange);
            }
            if ($allSources || in_array('uzum', $sourceFilter)) {
                $sources['uzum'] = $this->getUzumData($accountIds, $dateRange);
            }
            if ($allSources || in_array('ym', $sourceFilter)) {
                $sources['ym'] = $this->getYandexMarketData($accountIds, $dateRange);
            }
        }

        // Ручные продажи
        if ($allSources || in_array('manual', $sourceFilter)) {
            $sources['manual'] = $this->getManualSalesData($companyId, $dateRange);
        }

        // Оффлайн: розница, опт, прямые
        if ($allSources || in_array('retail', $sourceFilter)) {
            $sources['retail'] = $this->getOfflineSalesData($companyId, $dateRange, 'retail');
        }
        if ($allSources || in_array('wholesale', $sourceFilter)) {
            $sources['wholesale'] = $this->getOfflineSalesData($companyId, $dateRange, 'wholesale');
        }
        if ($allSources || in_array('direct', $sourceFilter)) {
            $sources['direct'] = $this->getOfflineSalesData($companyId, $dateRange, 'direct');
        }

        return $sources;
    }

    /**
     * Данные Wildberries
     */
    private function getWildberriesData(Collection $accountIds, array $dateRange): array
    {
        $row = WildberriesOrder::whereIn('marketplace_account_id', $accountIds)
            ->whereBetween('order_date', [$dateRange['from'], $dateRange['to']])
            ->where('is_cancel', false)
            ->where('is_return', false)
            ->selectRaw('COUNT(*) as orders, SUM(for_pay) as revenue')
            ->first();

        $cost = DB::table('wildberries_orders')
            ->join('sale_items', function ($join) {
                $join->on('wildberries_orders.nm_id', '=', 'sale_items.sku');
            })
            ->whereIn('wildberries_orders.marketplace_account_id', $accountIds)
            ->whereBetween('wildberries_orders.order_date', [$dateRange['from'], $dateRange['to']])
            ->where('wildberries_orders.is_cancel', false)
            ->sum('sale_items.cost_price');

        return [
            'name' => 'Wildberries',
            'orders' => (int) ($row->orders ?? 0),
            'revenue' => $this->convertToDisplay((float) ($row->revenue ?? 0), 'RUB'),
            'cost' => (float) ($cost ?? 0),
            'views' => 0,
            'cart_adds' => 0,
        ];
    }

    /**
     * Данные Ozon
     */
    private function getOzonData(Collection $accountIds, array $dateRange): array
    {
        $row = OzonOrder::whereIn('marketplace_account_id', $accountIds)
            ->whereBetween('created_at_ozon', [$dateRange['from'], $dateRange['to']])
            ->whereNotIn('status', self::CANCELLED_STATUSES)
            ->selectRaw('COUNT(*) as orders, SUM(total_price) as revenue')
            ->first();

        return [
            'name' => 'Ozon',
            'orders' => (int) ($row->orders ?? 0),
            'revenue' => $this->convertToDisplay((float) ($row->revenue ?? 0), 'RUB'),
            'cost' => 0,
            'views' => 0,
            'cart_adds' => 0,
        ];
    }

    /**
     * Данные Uzum
     */
    private function getUzumData(Collection $accountIds, array $dateRange): array
    {
        $row = DB::table('uzum_orders')
            ->whereIn('marketplace_account_id', $accountIds)
            ->whereBetween('ordered_at', [$dateRange['from'], $dateRange['to']])
            ->whereNotIn('status_normalized', self::CANCELLED_STATUSES)
            ->selectRaw('COUNT(*) as orders, SUM(total_amount) as revenue')
            ->first();

        return [
            'name' => 'Uzum',
            'orders' => (int) ($row->orders ?? 0),
            'revenue' => $this->convertToDisplay((float) ($row->revenue ?? 0), 'UZS'),
            'cost' => 0,
            'views' => 0,
            'cart_adds' => 0,
        ];
    }

    /**
     * Данные Yandex Market
     */
    private function getYandexMarketData(Collection $accountIds, array $dateRange): array
    {
        $row = YandexMarketOrder::whereIn('marketplace_account_id', $accountIds)
            ->whereBetween('created_at_ym', [$dateRange['from'], $dateRange['to']])
            ->whereNotIn('status_normalized', self::CANCELLED_STATUSES)
            ->selectRaw('COUNT(*) as orders, SUM(total_price) as revenue')
            ->first();

        return [
            'name' => 'Yandex Market',
            'orders' => (int) ($row->orders ?? 0),
            'revenue' => $this->convertToDisplay((float) ($row->revenue ?? 0), 'RUB'),
            'cost' => 0,
            'views' => 0,
            'cart_adds' => 0,
        ];
    }

    /**
     * Данные ручных продаж (из таблицы sales)
     */
    private function getManualSalesData(int $companyId, array $dateRange): array
    {
        $row = DB::table('sales')
            ->where('company_id', $companyId)
            ->whereBetween('created_at', [$dateRange['from'], $dateRange['to']])
            ->where('status', '!=', 'cancelled')
            ->whereNull('deleted_at')
            ->selectRaw('COUNT(*) as orders, SUM(total_amount) as revenue')
            ->first();

        $cost = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->where('sales.company_id', $companyId)
            ->whereBetween('sales.created_at', [$dateRange['from'], $dateRange['to']])
            ->where('sales.status', '!=', 'cancelled')
            ->whereNull('sales.deleted_at')
            ->selectRaw('SUM(sale_items.cost_price * sale_items.quantity) as total_cost')
            ->first();

        return [
            'name' => 'Ручные продажи',
            'orders' => (int) ($row->orders ?? 0),
            'revenue' => (float) ($row->revenue ?? 0),
            'cost' => (float) ($cost->total_cost ?? 0),
            'views' => 0,
            'cart_adds' => (int) ($row->orders ?? 0),
        ];
    }

    /**
     * Данные оффлайн продаж (розница / опт / прямые)
     */
    private function getOfflineSalesData(int $companyId, array $dateRange, string $saleType): array
    {
        $labels = [
            'retail' => 'Розница',
            'wholesale' => 'Опт',
            'direct' => 'Прямые продажи',
        ];

        $row = DB::table('offline_sales')
            ->where('company_id', $companyId)
            ->where('sale_type', $saleType)
            ->whereBetween('sale_date', [$dateRange['from'], $dateRange['to']])
            ->whereNotIn('status', ['cancelled', 'returned'])
            ->whereNull('deleted_at')
            ->selectRaw('COUNT(*) as orders, SUM(total_amount) as revenue')
            ->first();

        $cost = DB::table('offline_sale_items')
            ->join('offline_sales', 'offline_sale_items.offline_sale_id', '=', 'offline_sales.id')
            ->where('offline_sales.company_id', $companyId)
            ->where('offline_sales.sale_type', $saleType)
            ->whereBetween('offline_sales.sale_date', [$dateRange['from'], $dateRange['to']])
            ->whereNotIn('offline_sales.status', ['cancelled', 'returned'])
            ->whereNull('offline_sales.deleted_at')
            ->selectRaw('SUM(offline_sale_items.cost_price * offline_sale_items.quantity) as total_cost')
            ->first();

        return [
            'name' => $labels[$saleType] ?? $saleType,
            'orders' => (int) ($row->orders ?? 0),
            'revenue' => (float) ($row->revenue ?? 0),
            'cost' => (float) ($cost->total_cost ?? 0),
            'views' => 0,
            'cart_adds' => (int) ($row->orders ?? 0),
        ];
    }

    // ========== CRUD операции ==========

    /**
     * Сохранить воронку
     */
    public function save(int $companyId, array $data): SalesFunnel
    {
        $funnel = SalesFunnel::create([
            'company_id' => $companyId,
            'name' => $data['name'],
            'views' => $data['views'] ?? 0,
            'inquiry_rate' => $data['inquiry_rate'] ?? 0,
            'meeting_rate' => $data['meeting_rate'] ?? 0,
            'sale_rate' => $data['sale_rate'] ?? 0,
            'average_check' => $data['average_check'] ?? 0,
            'profit_margin' => $data['profit_margin'] ?? 0,
            'bonus_rate' => $data['bonus_rate'] ?? 0,
            'currency' => $data['currency'] ?? 'UZS',
            'is_auto' => $data['is_auto'] ?? false,
            'source_filter' => $data['source_filter'] ?? null,
            'period_from' => $data['period_from'] ?? null,
            'period_to' => $data['period_to'] ?? null,
            'auto_snapshot' => $data['auto_snapshot'] ?? null,
            'metadata' => $data['metadata'] ?? null,
            'created_by' => auth()->id(),
        ]);

        return $funnel;
    }

    /**
     * Обновить воронку
     */
    public function update(SalesFunnel $funnel, array $data): SalesFunnel
    {
        $funnel->update($data);

        return $funnel->fresh();
    }

    /**
     * Пересчитать авто-воронку (обновить snapshot)
     */
    public function refreshAutoFunnel(SalesFunnel $funnel): array
    {
        if (! $funnel->is_auto) {
            return $funnel->calculateFunnel();
        }

        $period = 'custom';
        $dateRange = [
            'from' => $funnel->period_from ?? now()->subDays(30),
            'to' => $funnel->period_to ?? now(),
        ];

        $accountIds = MarketplaceAccount::where('company_id', $funnel->company_id)->pluck('id');
        $sources = $this->getSourcesData(
            $funnel->company_id,
            $accountIds,
            $dateRange,
            $funnel->source_filter ?? [],
        );

        $totalOrders = 0;
        $totalRevenue = 0.0;
        $totalCost = 0.0;

        foreach ($sources as $source) {
            $totalOrders += $source['orders'];
            $totalRevenue += $source['revenue'];
            $totalCost += $source['cost'];
        }

        $avgCheck = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;
        $netProfit = $totalRevenue - $totalCost;
        $profitMargin = $totalRevenue > 0 ? ($netProfit / $totalRevenue) * 100 : 0;

        // Обновляем snapshot
        $snapshot = [
            'total_orders' => $totalOrders,
            'total_revenue' => round($totalRevenue, 2),
            'average_check' => round($avgCheck, 2),
            'profit_margin' => round($profitMargin, 2),
            'sources' => $sources,
            'updated_at' => now()->toDateTimeString(),
        ];

        $funnel->update([
            'average_check' => round($avgCheck, 2),
            'profit_margin' => round($profitMargin, 3),
            'auto_snapshot' => $snapshot,
        ]);

        return $snapshot;
    }

    // ========== Вспомогательные методы ==========

    /**
     * Получить диапазон дат по периоду
     */
    private function getDateRange(string $period): array
    {
        $to = now();
        $from = match ($period) {
            'today' => now()->startOfDay(),
            '7days' => now()->subDays(7),
            '30days' => now()->subDays(30),
            '90days' => now()->subDays(90),
            '365days' => now()->subDays(365),
            'month' => now()->startOfMonth(),
            'year' => now()->startOfYear(),
            'all' => now()->subYears(10),
            default => now()->subDays(30),
        };

        return ['from' => $from, 'to' => $to];
    }

    /**
     * Конвертировать валюту в валюту отображения
     */
    private function convertToDisplay(float $amount, string $fromCurrency): float
    {
        if ($this->currencyService === null || $amount == 0.0) {
            return $amount;
        }

        return $this->currencyService->convertToDisplay($amount, $fromCurrency);
    }
}
