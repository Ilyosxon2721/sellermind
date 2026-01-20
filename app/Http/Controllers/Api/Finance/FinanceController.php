<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\FinanceCategory;
use App\Models\Finance\FinanceDebt;
use App\Models\Finance\FinanceSettings;
use App\Models\Finance\FinanceTransaction;
use App\Models\Finance\SalaryCalculation;
use App\Models\Finance\TaxCalculation;
use App\Models\Warehouse\StockLedger;
use App\Models\Warehouse\InventoryDocument;
use App\Models\AP\SupplierInvoice;
use App\Services\Finance\FinanceReportService;
use App\Services\Marketplaces\UzumClient;
use App\Services\Marketplaces\OzonClient;
use App\Services\Marketplaces\Wildberries\WildberriesHttpClient;
use App\Services\Marketplaces\Wildberries\WildberriesFinanceService;
use App\Models\MarketplaceAccount;
use App\Support\ApiResponder;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FinanceController extends Controller
{
    use ApiResponder;

    public function __construct(
        protected FinanceReportService $reportService,
        protected UzumClient $uzumClient,
        protected OzonClient $ozonClient
    ) {
    }

    public function overview(Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (!$companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $from = $request->from ? Carbon::parse($request->from) : now()->startOfMonth();
        $to = $request->to ? Carbon::parse($request->to) : now()->endOfMonth();

        // Получаем настройки финансов для курсов валют
        $financeSettings = FinanceSettings::getForCompany($companyId);

        $transactions = FinanceTransaction::byCompany($companyId)
            ->confirmed()
            ->inPeriod($from, $to);

        $totalIncome = (clone $transactions)->income()->sum('amount');
        $totalExpense = (clone $transactions)->expense()->sum('amount');

        // ========== ПРОДАЖИ МАРКЕТПЛЕЙСОВ ==========
        $marketplaceSales = $this->getMarketplaceSales($companyId, $from, $to, $financeSettings);
        $totalIncome += $marketplaceSales['total_revenue'];

        $netProfit = $totalIncome - $totalExpense;

        // Долги
        $debtsReceivable = FinanceDebt::byCompany($companyId)->receivable()->active()->sum('amount_outstanding');
        $debtsPayable = FinanceDebt::byCompany($companyId)->payable()->active()->sum('amount_outstanding');

        // Просроченные долги
        $overdueReceivable = FinanceDebt::byCompany($companyId)->receivable()->overdue()->sum('amount_outstanding');
        $overduePayable = FinanceDebt::byCompany($companyId)->payable()->overdue()->sum('amount_outstanding');

        // ========== ОСТАТКИ НА СКЛАДАХ ==========
        $stockData = $this->getStockSummary($companyId, $financeSettings);

        // ========== ТОВАРЫ В ТРАНЗИТАХ ==========
        $transitData = $this->getTransitSummary($companyId, $financeSettings);

        // Зарплата текущего месяца
        $currentSalary = SalaryCalculation::byCompany($companyId)
            ->forPeriod(now()->year, now()->month)
            ->first();

        // Налоги
        $unpaidTaxes = TaxCalculation::byCompany($companyId)
            ->unpaid()
            ->sum('calculated_amount');

        // Расходы по категориям
        $expensesByCategory = $this->reportService->getExpensesByCategory($companyId, $from, $to);

        // Последние транзакции
        $recentTransactions = FinanceTransaction::byCompany($companyId)
            ->with(['category', 'counterparty', 'employee'])
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        // ========== ПРИБЫЛЬ ЗА ПЕРИОД ==========
        $periodProfit = $this->getPeriodProfit($companyId, $from, $to, $financeSettings);

        \Log::info('Finance overview period profit', [
            'company_id' => $companyId,
            'from' => $from->format('Y-m-d'),
            'to' => $to->format('Y-m-d'),
            'period_profit' => $periodProfit,
            'marketplace_sales_from_overview' => $marketplaceSales,
        ]);

        // ========== ОСТАТКИ НА СЧЕТАХ (касса, банк) ==========
        $cashBalance = $this->getCashBalance($companyId);

        // ========== ИТОГОВЫЙ БАЛАНС КОМПАНИИ ==========
        $balance = $this->calculateCompanyBalance(
            $stockData,
            $transitData,
            $debtsReceivable,
            $debtsPayable,
            $unpaidTaxes,
            $currentSalary,
            $periodProfit,
            $cashBalance
        );

        return $this->successResponse([
            'period' => [
                'from' => $from->format('Y-m-d'),
                'to' => $to->format('Y-m-d'),
            ],
            'currency' => [
                'base' => $financeSettings->base_currency_code ?? 'UZS',
                'rates' => [
                    'USD' => $financeSettings->usd_rate,
                    'RUB' => $financeSettings->rub_rate,
                    'EUR' => $financeSettings->eur_rate,
                ],
                'rates_updated_at' => $financeSettings->rates_updated_at?->format('Y-m-d H:i:s'),
            ],
            'summary' => [
                'total_income' => $totalIncome,
                'total_expense' => $totalExpense,
                'net_profit' => $netProfit,
            ],
            'marketplace_sales' => $marketplaceSales,
            'balance' => $balance,
            'stock' => $stockData,
            'transit' => $transitData,
            'debts' => [
                'receivable' => $debtsReceivable,
                'payable' => $debtsPayable,
                'overdue_receivable' => $overdueReceivable,
                'overdue_payable' => $overduePayable,
                'net' => $debtsReceivable - $debtsPayable,
            ],
            'salary' => [
                'current_period' => $currentSalary?->period_label,
                'total_net' => $currentSalary?->total_net ?? 0,
                'status' => $currentSalary?->status ?? 'none',
            ],
            'taxes' => [
                'unpaid_total' => $unpaidTaxes,
            ],
            'expenses_by_category' => $expensesByCategory,
            'recent_transactions' => $recentTransactions,
        ]);
    }

    /**
     * Получить продажи маркетплейсов за период
     */
    protected function getMarketplaceSales(int $companyId, Carbon $from, Carbon $to, FinanceSettings $settings): array
    {
        $rubToUzs = $settings->rub_rate ?? 140;

        $result = [
            'uzum' => ['orders' => 0, 'revenue' => 0, 'profit' => 0],
            'wb' => ['orders' => 0, 'revenue' => 0, 'revenue_rub' => 0, 'profit' => 0],
            'ozon' => ['orders' => 0, 'revenue' => 0, 'revenue_rub' => 0, 'profit' => 0],
            'total_orders' => 0,
            'total_revenue' => 0,
            'total_profit' => 0,
        ];

        \Log::info('getMarketplaceSales called', [
            'company_id' => $companyId,
            'from' => $from->format('Y-m-d'),
            'to' => $to->format('Y-m-d'),
            'rub_rate' => $rubToUzs,
        ]);

        // Uzum продажи (TO_WITHDRAW, COMPLETED, или PROCESSING с датой в периоде)
        // TO_WITHDRAW = деньги выведены, COMPLETED = доставлено, PROCESSING = в процессе
        try {
            if (class_exists(\App\Models\UzumFinanceOrder::class)) {
                // Считаем завершённые продажи (TO_WITHDRAW, COMPLETED) по date_issued или order_date
                $uzumSales = \App\Models\UzumFinanceOrder::whereHas('account', fn($q) => $q->where('company_id', $companyId))
                    ->whereIn('status', ['TO_WITHDRAW', 'COMPLETED', 'PROCESSING'])
                    ->where('status', '!=', 'CANCELED')
                    ->where(function($q) use ($from, $to) {
                        // Используем date_issued если есть, иначе order_date
                        $q->where(function($sub) use ($from, $to) {
                            $sub->whereNotNull('date_issued')
                                ->whereDate('date_issued', '>=', $from)
                                ->whereDate('date_issued', '<=', $to);
                        })
                        ->orWhere(function($sub) use ($from, $to) {
                            $sub->whereNull('date_issued')
                                ->whereDate('order_date', '>=', $from)
                                ->whereDate('order_date', '<=', $to);
                        });
                    })
                    ->selectRaw('COUNT(*) as cnt, SUM(sell_price * amount) as revenue, SUM(seller_profit) as profit')
                    ->first();

                $result['uzum'] = [
                    'orders' => (int) ($uzumSales?->cnt ?? 0),
                    'revenue' => (float) ($uzumSales?->revenue ?? 0),
                    'profit' => (float) ($uzumSales?->profit ?? 0),
                ];

                \Log::info('Uzum sales fetched', $result['uzum']);
            }
        } catch (\Exception $e) {
            \Log::error('Uzum sales error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
        }

        // WB продажи (is_realization = true, не отменённые, не возвраты)
        try {
            if (class_exists(\App\Models\WildberriesOrder::class)) {
                $wbSales = \App\Models\WildberriesOrder::whereHas('account', fn($q) => $q->where('company_id', $companyId))
                    ->where('is_realization', true)
                    ->where('is_cancel', false)
                    ->where('is_return', false)
                    ->whereDate('order_date', '>=', $from)
                    ->whereDate('order_date', '<=', $to)
                    ->selectRaw('COUNT(*) as cnt, SUM(COALESCE(for_pay, finished_price, total_price, 0)) as revenue')
                    ->first();

                $revenueRub = (float) ($wbSales?->revenue ?? 0);
                $result['wb'] = [
                    'orders' => (int) ($wbSales?->cnt ?? 0),
                    'revenue' => $revenueRub * $rubToUzs,
                    'revenue_rub' => $revenueRub,
                    'profit' => 0,
                ];

                \Log::info('WB sales fetched', $result['wb']);
            }
        } catch (\Exception $e) {
            \Log::error('WB sales error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
        }

        // Ozon продажи (статусы delivered, completed = завершённые продажи)
        try {
            if (class_exists(\App\Models\OzonOrder::class)) {
                $ozonSales = \App\Models\OzonOrder::whereHas('account', fn($q) => $q->where('company_id', $companyId))
                    ->whereIn('status', ['delivered', 'completed'])
                    ->whereDate('created_at_ozon', '>=', $from)
                    ->whereDate('created_at_ozon', '<=', $to)
                    ->selectRaw('COUNT(*) as cnt, SUM(COALESCE(total_price, 0)) as revenue')
                    ->first();

                $revenueRub = (float) ($ozonSales?->revenue ?? 0);
                $result['ozon'] = [
                    'orders' => (int) ($ozonSales?->cnt ?? 0),
                    'revenue' => $revenueRub * $rubToUzs,
                    'revenue_rub' => $revenueRub,
                    'profit' => 0,
                ];

                \Log::info('Ozon sales fetched', $result['ozon']);
            }
        } catch (\Exception $e) {
            \Log::error('Ozon sales error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
        }

        // Итого
        $result['total_orders'] = $result['uzum']['orders'] + $result['wb']['orders'] + $result['ozon']['orders'];
        $result['total_revenue'] = $result['uzum']['revenue'] + $result['wb']['revenue'] + $result['ozon']['revenue'];
        $result['total_profit'] = $result['uzum']['profit'] + $result['wb']['profit'] + $result['ozon']['profit'];

        \Log::info('getMarketplaceSales totals', [
            'total_orders' => $result['total_orders'],
            'total_revenue' => $result['total_revenue'],
            'total_profit' => $result['total_profit'],
        ]);

        return $result;
    }

    /**
     * Получить сводку по остаткам на складах
     * cost_delta хранит закупочную цену за единицу в USD
     * Себестоимость рассчитывается: qty × cost_delta × usd_rate
     */
    protected function getStockSummary(int $companyId, FinanceSettings $settings): array
    {
        $usdRate = $settings->usd_rate ?? 12700;

        // Суммируем все движения по складам для получения текущих остатков
        // cost_delta - это закупочная цена за единицу в USD
        // Себестоимость = SUM(qty_delta * cost_delta) * usd_rate
        try {
            $stockSummary = StockLedger::byCompany($companyId)
                ->selectRaw('SUM(qty_delta) as total_qty, SUM(qty_delta * cost_delta) as total_cost_usd')
                ->first();

            $totalCostUzs = ((float) ($stockSummary?->total_cost_usd ?? 0)) * $usdRate;

            // Группировка по складам (явно указываем таблицу для company_id)
            $byWarehouse = StockLedger::where('stock_ledger.company_id', $companyId)
                ->join('warehouses', 'stock_ledger.warehouse_id', '=', 'warehouses.id')
                ->selectRaw('warehouses.id, warehouses.name, SUM(stock_ledger.qty_delta) as qty, SUM(stock_ledger.qty_delta * stock_ledger.cost_delta) as cost_usd')
                ->groupBy('warehouses.id', 'warehouses.name')
                ->having('qty', '>', 0)
                ->get();

            return [
                'total_qty' => (float) ($stockSummary?->total_qty ?? 0),
                'total_cost' => $totalCostUzs,
                'by_warehouse' => $byWarehouse->map(fn($w) => [
                    'id' => $w->id,
                    'name' => $w->name,
                    'qty' => (float) $w->qty,
                    'cost' => ((float) $w->cost_usd) * $usdRate,
                ])->toArray(),
            ];
        } catch (\Exception $e) {
            return [
                'total_qty' => 0,
                'total_cost' => 0,
                'by_warehouse' => [],
            ];
        }
    }

    /**
     * Получить сводку по товарам в транзитах
     * Все суммы конвертируются в UZS
     */
    protected function getTransitSummary(int $companyId, FinanceSettings $settings): array
    {
        $rubToUzs = $settings->rub_rate ?? 140;

        $result = [
            'orders_in_transit' => [
                'count' => 0,
                'amount' => 0,
                'by_marketplace' => [],
            ],
            'purchases_in_transit' => [
                'count' => 0,
                'amount' => 0,
            ],
            'total_amount' => 0,
        ];

        // 1. Uzum заказы в пути (уже в UZS)
        try {
            if (class_exists(\App\Models\UzumFinanceOrder::class)) {
                $uzumTransit = \App\Models\UzumFinanceOrder::whereHas('account', fn($q) => $q->where('company_id', $companyId))
                    ->where('status', 'PROCESSING')
                    ->whereNull('date_issued')
                    ->selectRaw('COUNT(*) as cnt, SUM(sell_price * amount) as total')
                    ->first();

                $uzumCount = (int) ($uzumTransit?->cnt ?? 0);
                $uzumAmount = (float) ($uzumTransit?->total ?? 0);

                $result['orders_in_transit']['count'] += $uzumCount;
                $result['orders_in_transit']['amount'] += $uzumAmount;
                if ($uzumCount > 0) {
                    $result['orders_in_transit']['by_marketplace']['uzum'] = [
                        'count' => $uzumCount,
                        'amount' => $uzumAmount,
                        'currency' => 'UZS',
                    ];
                }
            }
        } catch (\Exception $e) {}

        // 2. WB заказы в пути (RUB -> UZS)
        try {
            if (class_exists(\App\Models\WildberriesOrder::class)) {
                $wbTransit = \App\Models\WildberriesOrder::whereHas('account', fn($q) => $q->where('company_id', $companyId))
                    ->where('is_realization', false)
                    ->where('is_cancel', false)
                    ->selectRaw('COUNT(*) as cnt, SUM(COALESCE(for_pay, finished_price, total_price, 0)) as total_rub')
                    ->first();

                $wbCount = (int) ($wbTransit?->cnt ?? 0);
                $wbAmountRub = (float) ($wbTransit?->total_rub ?? 0);
                $wbAmountUzs = $wbAmountRub * $rubToUzs;

                $result['orders_in_transit']['count'] += $wbCount;
                $result['orders_in_transit']['amount'] += $wbAmountUzs;
                if ($wbCount > 0) {
                    $result['orders_in_transit']['by_marketplace']['wb'] = [
                        'count' => $wbCount,
                        'amount' => $wbAmountUzs,
                        'amount_original' => $wbAmountRub,
                        'currency' => 'RUB',
                    ];
                }
            }
        } catch (\Exception $e) {}

        // 3. Ozon заказы в пути (RUB -> UZS)
        try {
            if (class_exists(\App\Models\OzonOrder::class)) {
                $ozonTransit = \App\Models\OzonOrder::whereHas('account', fn($q) => $q->where('company_id', $companyId))
                    ->inTransit()
                    ->selectRaw('COUNT(*) as cnt, SUM(COALESCE(total_price, 0)) as total_rub')
                    ->first();

                $ozonCount = (int) ($ozonTransit?->cnt ?? 0);
                $ozonAmountRub = (float) ($ozonTransit?->total_rub ?? 0);
                $ozonAmountUzs = $ozonAmountRub * $rubToUzs;

                $result['orders_in_transit']['count'] += $ozonCount;
                $result['orders_in_transit']['amount'] += $ozonAmountUzs;
                if ($ozonCount > 0) {
                    $result['orders_in_transit']['by_marketplace']['ozon'] = [
                        'count' => $ozonCount,
                        'amount' => $ozonAmountUzs,
                        'amount_original' => $ozonAmountRub,
                        'currency' => 'RUB',
                    ];
                }
            }
        } catch (\Exception $e) {}

        // 4. Закупки в пути
        try {
            if (class_exists(SupplierInvoice::class)) {
                $purchaseTransit = SupplierInvoice::byCompany($companyId)
                    ->whereIn('status', ['confirmed', 'partially_paid'])
                    ->selectRaw('COUNT(*) as cnt, SUM(total_amount - amount_paid) as total')
                    ->first();

                $result['purchases_in_transit']['count'] = (int) ($purchaseTransit?->cnt ?? 0);
                $result['purchases_in_transit']['amount'] = (float) ($purchaseTransit?->total ?? 0);
            }
        } catch (\Exception $e) {}

        $result['total_amount'] = $result['orders_in_transit']['amount'] + $result['purchases_in_transit']['amount'];

        return $result;
    }

    /**
     * Рассчитать итоговый баланс компании
     *
     * ВАЖНО: Товары в транзите НЕ включаются в активы!
     * Транзит — это потенциальный доход, не гарантированный.
     * Клиент может отказаться, не забрать или вернуть товар.
     *
     * Активы = Остатки на складах + Дебиторка (подтверждённые)
     * Пассивы = Кредиторка + Неоплаченные налоги + Неоплаченная зарплата
     * Чистый баланс = Активы - Пассивы
     *
     * Транзит показывается отдельно как "ожидаемые поступления"
     */
    protected function calculateCompanyBalance(
        array $stockData,
        array $transitData,
        float $debtsReceivable,
        float $debtsPayable,
        float $unpaidTaxes,
        ?SalaryCalculation $currentSalary,
        array $accumulatedProfit,
        array $cashBalance
    ): array {
        // Активы
        $stockValue = $stockData['total_cost'] ?? 0;
        $transitValue = $transitData['total_amount'] ?? 0;
        $cashTotal = $cashBalance['total'] ?? 0;
        $totalAssets = $stockValue + $debtsReceivable + $cashTotal;

        // Пассивы
        $unpaidSalary = 0;
        if ($currentSalary && in_array($currentSalary->status, ['calculated', 'approved'])) {
            $unpaidSalary = $currentSalary->total_net;
        }
        $totalLiabilities = $debtsPayable + $unpaidTaxes + $unpaidSalary;

        // Чистый баланс
        $netBalance = $totalAssets - $totalLiabilities;

        return [
            'assets' => [
                'cash' => $cashTotal,
                'stock_value' => $stockValue,
                'receivables' => $debtsReceivable,
                'total' => $totalAssets,
            ],
            'liabilities' => [
                'payables' => $debtsPayable,
                'unpaid_taxes' => $unpaidTaxes,
                'unpaid_salary' => $unpaidSalary,
                'total' => $totalLiabilities,
            ],
            'cash_accounts' => $cashBalance['accounts'] ?? [],
            'period_profit' => $accumulatedProfit,
            'net_balance' => $netBalance,
            // Транзит показываем отдельно — это потенциальный, не гарантированный доход
            'pending_income' => [
                'transit_orders' => $transitValue,
                'note' => 'Ожидаемые поступления (не гарантированы)',
            ],
            'health' => $this->getBalanceHealth($netBalance, $totalAssets, $totalLiabilities),
        ];
    }

    /**
     * Определить "здоровье" баланса
     */
    protected function getBalanceHealth(float $netBalance, float $assets, float $liabilities): array
    {
        $ratio = $liabilities > 0 ? $assets / $liabilities : ($assets > 0 ? 999 : 1);

        $status = 'good';
        $message = 'Финансовое состояние стабильное';

        if ($ratio < 1) {
            $status = 'critical';
            $message = 'Обязательства превышают активы!';
        } elseif ($ratio < 1.5) {
            $status = 'warning';
            $message = 'Низкий запас ликвидности';
        } elseif ($ratio > 3) {
            $status = 'excellent';
            $message = 'Отличное финансовое состояние';
        }

        return [
            'status' => $status,
            'ratio' => round($ratio, 2),
            'message' => $message,
        ];
    }

    /**
     * Получить прибыль за указанный период
     * Включает: транзакции + продажи маркетплейсов
     */
    protected function getPeriodProfit(int $companyId, Carbon $from, Carbon $to, FinanceSettings $settings): array
    {
        // Прибыль из транзакций за период
        $transactionIncome = FinanceTransaction::byCompany($companyId)
            ->confirmed()
            ->inPeriod($from, $to)
            ->income()
            ->sum('amount');

        $transactionExpense = FinanceTransaction::byCompany($companyId)
            ->confirmed()
            ->inPeriod($from, $to)
            ->expense()
            ->sum('amount');

        // Продажи маркетплейсов за период (уже передаётся в overview)
        $marketplaceSales = $this->getMarketplaceSales($companyId, $from, $to, $settings);

        $totalIncome = $transactionIncome + $marketplaceSales['total_revenue'];
        $totalExpense = $transactionExpense;
        $netProfit = $totalIncome - $totalExpense;

        return [
            'total_income' => $totalIncome,
            'total_expense' => $totalExpense,
            'transaction_income' => $transactionIncome,
            'transaction_expense' => $transactionExpense,
            'marketplace_revenue' => $marketplaceSales['total_revenue'],
            'net_profit' => $netProfit,
        ];
    }

    /**
     * Получить остатки на денежных счетах (касса, банк)
     */
    protected function getCashBalance(int $companyId): array
    {
        try {
            // Проверяем есть ли модель CashAccount
            if (!class_exists(\App\Models\Finance\CashAccount::class)) {
                return ['total' => 0, 'accounts' => []];
            }

            $accounts = \App\Models\Finance\CashAccount::where('company_id', $companyId)
                ->where('is_active', true)
                ->get();

            $total = $accounts->sum('balance');

            return [
                'total' => $total,
                'accounts' => $accounts->map(fn($a) => [
                    'id' => $a->id,
                    'name' => $a->name,
                    'type' => $a->type,
                    'balance' => $a->balance,
                    'currency_code' => $a->currency_code,
                ])->toArray(),
            ];
        } catch (\Exception $e) {
            return ['total' => 0, 'accounts' => []];
        }
    }

    public function categories(Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (!$companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $type = $request->type; // income, expense, or null for all

        $query = FinanceCategory::byCompany($companyId)
            ->active()
            ->roots()
            ->with('children')
            ->orderBy('sort_order');

        if ($type === 'income') {
            $query->income();
        } elseif ($type === 'expense') {
            $query->expense();
        }

        return $this->successResponse($query->get());
    }

    public function allCategories(Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (!$companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $query = FinanceCategory::byCompany($companyId)
            ->active()
            ->orderBy('sort_order');

        if ($type = $request->type) {
            if ($type === 'income') {
                $query->income();
            } else {
                $query->expense();
            }
        }

        return $this->successResponse($query->get());
    }

    public function settings(Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (!$companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $settings = FinanceSettings::getForCompany($companyId);
        return $this->successResponse($settings);
    }

    public function updateSettings(Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (!$companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $data = $request->validate([
            'base_currency_code' => ['nullable', 'string', 'max:8'],
            'usd_rate' => ['nullable', 'numeric', 'min:0'],
            'rub_rate' => ['nullable', 'numeric', 'min:0'],
            'eur_rate' => ['nullable', 'numeric', 'min:0'],
            'tax_system' => ['nullable', 'in:simplified,general,both'],
            'vat_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'income_tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'social_tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'auto_import_marketplace_fees' => ['nullable', 'boolean'],
        ]);

        $settings = FinanceSettings::getForCompany($companyId);

        // If currency rates are being updated, set the rates_updated_at timestamp
        if (isset($data['usd_rate']) || isset($data['rub_rate']) || isset($data['eur_rate'])) {
            $data['rates_updated_at'] = now();
        }

        $settings->update(array_filter($data, fn($v) => $v !== null));

        return $this->successResponse($settings->fresh());
    }

    public function reports(Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (!$companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $reportType = $request->type ?? 'pnl'; // pnl, cash_flow, by_category, debts_aging
        $from = $request->from ? Carbon::parse($request->from) : now()->startOfYear();
        $to = $request->to ? Carbon::parse($request->to) : now()->endOfMonth();

        $data = match ($reportType) {
            'pnl' => $this->reportService->getProfitAndLoss($companyId, $from, $to),
            'cash_flow' => $this->reportService->getCashFlow($companyId, $from, $to),
            'by_category' => $this->reportService->getByCategory($companyId, $from, $to),
            'debts_aging' => $this->reportService->getDebtsAging($companyId),
            default => [],
        };

        return $this->successResponse([
            'report_type' => $reportType,
            'period' => [
                'from' => $from->format('Y-m-d'),
                'to' => $to->format('Y-m-d'),
            ],
            'data' => $data,
        ]);
    }

    /**
     * Получить расходы маркетплейсов (Uzum/WB/Ozon)
     */
    public function marketplaceExpenses(Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (!$companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $from = $request->from ? Carbon::parse($request->from)->startOfDay() : now()->startOfMonth();
        $to = $request->to ? Carbon::parse($request->to)->endOfDay() : now()->endOfMonth();

        $result = [
            'period' => [
                'from' => $from->format('Y-m-d'),
                'to' => $to->format('Y-m-d'),
            ],
            'uzum' => null,
            'wb' => null,
            'ozon' => null,
            'total' => [
                'commission' => 0,
                'logistics' => 0,
                'storage' => 0,
                'advertising' => 0,
                'penalties' => 0,
                'returns' => 0,
                'other' => 0,
                'total' => 0,
            ],
        ];

        // Uzum expenses - first try local DB (uzum_expenses), fallback to API
        // API returns detailed expense breakdown by source: commission, logistics, storage, advertising, etc.
        $uzumAccounts = MarketplaceAccount::where('company_id', $companyId)
            ->where('marketplace', 'uzum')
            ->where('is_active', true)
            ->get();

        $uzumTotalExpenses = [
            'commission' => 0,
            'logistics' => 0,
            'storage' => 0,
            'advertising' => 0,
            'penalties' => 0,
            'returns' => 0,
            'other' => 0,
            'total' => 0,
            'currency' => 'UZS',
            'items_count' => 0,
            'source' => 'none',
            'accounts_db' => 0,
            'accounts_api' => 0,
        ];

        foreach ($uzumAccounts as $account) {
            // First try: local DB (UzumExpense table - synced data)
            if (class_exists(\App\Models\UzumExpense::class)) {
                $dbExpenses = \App\Models\UzumExpense::getSummaryForAccount($account->id, $from, $to);

                if ($dbExpenses['total'] > 0) {
                    $uzumTotalExpenses['commission'] += $dbExpenses['commission'] ?? 0;
                    $uzumTotalExpenses['logistics'] += $dbExpenses['logistics'] ?? 0;
                    $uzumTotalExpenses['storage'] += $dbExpenses['storage'] ?? 0;
                    $uzumTotalExpenses['advertising'] += $dbExpenses['advertising'] ?? 0;
                    $uzumTotalExpenses['penalties'] += $dbExpenses['penalties'] ?? 0;
                    $uzumTotalExpenses['other'] += $dbExpenses['other'] ?? 0;
                    $uzumTotalExpenses['total'] += $dbExpenses['total'] ?? 0;
                    $uzumTotalExpenses['items_count'] += $dbExpenses['items_count'] ?? 0;
                    $uzumTotalExpenses['accounts_db']++;
                    continue;
                }
            }

            // Second try: API (if DB is empty)
            try {
                $expenses = $this->uzumClient->getExpensesSummary($account, $from, $to);

                // Accumulate all expense categories
                $uzumTotalExpenses['commission'] += $expenses['commission'] ?? 0;
                $uzumTotalExpenses['logistics'] += $expenses['logistics'] ?? 0;
                $uzumTotalExpenses['storage'] += $expenses['storage'] ?? 0;
                $uzumTotalExpenses['advertising'] += $expenses['advertising'] ?? 0;
                $uzumTotalExpenses['penalties'] += $expenses['penalties'] ?? 0;
                $uzumTotalExpenses['returns'] += $expenses['returns'] ?? 0;
                $uzumTotalExpenses['other'] += $expenses['other'] ?? 0;
                $uzumTotalExpenses['total'] += $expenses['total'] ?? 0;
                $uzumTotalExpenses['items_count'] += $expenses['items_count'] ?? 0;
                $uzumTotalExpenses['accounts_api']++;
            } catch (\Exception $e) {
                \Log::warning('Uzum expenses API failed, falling back to orders calculation', [
                    'account_id' => $account->id,
                    'error' => $e->getMessage(),
                ]);

                // Third fallback: calculate from local orders if API fails
                if (class_exists(\App\Models\UzumFinanceOrder::class)) {
                    $uzumExpenses = \App\Models\UzumFinanceOrder::where('marketplace_account_id', $account->id)
                        ->whereIn('status', ['TO_WITHDRAW', 'COMPLETED', 'PROCESSING'])
                        ->where('status', '!=', 'CANCELED')
                        ->where(function($q) use ($from, $to) {
                            $q->where(function($sub) use ($from, $to) {
                                $sub->whereNotNull('date_issued')
                                    ->whereDate('date_issued', '>=', $from)
                                    ->whereDate('date_issued', '<=', $to);
                            })
                            ->orWhere(function($sub) use ($from, $to) {
                                $sub->whereNull('date_issued')
                                    ->whereDate('order_date', '>=', $from)
                                    ->whereDate('order_date', '<=', $to);
                            });
                        })
                        ->selectRaw('
                            SUM(commission) as total_commission,
                            SUM(logistic_delivery_fee) as total_logistics,
                            COUNT(*) as orders_count
                        ')
                        ->first();

                    $commission = (float) ($uzumExpenses->total_commission ?? 0);
                    $logistics = (float) ($uzumExpenses->total_logistics ?? 0);

                    $uzumTotalExpenses['commission'] += $commission;
                    $uzumTotalExpenses['logistics'] += $logistics;
                    $uzumTotalExpenses['total'] += $commission + $logistics;
                    $uzumTotalExpenses['items_count'] += (int) ($uzumExpenses->orders_count ?? 0);
                }
            }
        }

        // Set source indicator based on which sources were used
        if ($uzumTotalExpenses['accounts_db'] > 0 && $uzumTotalExpenses['accounts_api'] === 0) {
            $uzumTotalExpenses['source'] = 'database';
        } elseif ($uzumTotalExpenses['accounts_db'] === 0 && $uzumTotalExpenses['accounts_api'] > 0) {
            $uzumTotalExpenses['source'] = 'api';
        } elseif ($uzumTotalExpenses['accounts_db'] > 0 && $uzumTotalExpenses['accounts_api'] > 0) {
            $uzumTotalExpenses['source'] = 'mixed';
        }
        unset($uzumTotalExpenses['accounts_db'], $uzumTotalExpenses['accounts_api']);

        // Add Uzum expenses to result and totals
        if ($uzumTotalExpenses['total'] > 0 || !empty($uzumAccounts)) {
            $result['uzum'] = $uzumTotalExpenses;

            // Add Uzum expenses to totals (already in UZS)
            $result['total']['commission'] += $uzumTotalExpenses['commission'];
            $result['total']['logistics'] += $uzumTotalExpenses['logistics'];
            $result['total']['storage'] += $uzumTotalExpenses['storage'];
            $result['total']['advertising'] += $uzumTotalExpenses['advertising'];
            $result['total']['penalties'] += $uzumTotalExpenses['penalties'];
            $result['total']['returns'] += $uzumTotalExpenses['returns'];
            $result['total']['other'] += $uzumTotalExpenses['other'];
            $result['total']['total'] += $uzumTotalExpenses['total'];
        }

        // WB expenses (from detailed realization report API - accurate breakdown)
        $financeSettings = FinanceSettings::getForCompany($companyId);
        $rubToUzs = $financeSettings->rub_rate ?? 140;

        $wbTotalExpenses = [
            'commission' => 0,
            'logistics' => 0,
            'storage' => 0,
            'advertising' => 0,
            'penalties' => 0,
            'returns' => 0,
            'other' => 0,
            'total' => 0,
            'currency' => 'RUB',
            'total_uzs' => 0,
        ];

        $wbAccounts = MarketplaceAccount::where('company_id', $companyId)
            ->where('marketplace', 'wb')
            ->where('is_active', true)
            ->get();

        foreach ($wbAccounts as $account) {
            try {
                $httpClient = new WildberriesHttpClient($account);
                $financeService = new WildberriesFinanceService($httpClient);

                // Get full expense summary including storage fees
                $expenses = $financeService->getExpensesSummary($account, $from, $to);

                // IMPORTANT: WB API returns amounts in seller's account currency
                // For Uzbekistan sellers, currency is UZS (not RUB despite field names)
                $expenseCurrency = $expenses['currency'] ?? 'RUB';
                if ($expenseCurrency === 'UZS') {
                    $wbTotalExpenses['currency'] = 'UZS';
                }

                // Accumulate all expense categories
                $wbTotalExpenses['commission'] += $expenses['commission'] ?? 0;
                $wbTotalExpenses['logistics'] += $expenses['logistics'] ?? 0;
                $wbTotalExpenses['storage'] += $expenses['storage'] ?? 0;
                $wbTotalExpenses['penalties'] += $expenses['penalties'] ?? 0;
                $wbTotalExpenses['returns'] += $expenses['returns'] ?? 0;
                $wbTotalExpenses['total'] += $expenses['total'] ?? 0;
                $wbTotalExpenses['orders_count'] = ($wbTotalExpenses['orders_count'] ?? 0) + ($expenses['orders_count'] ?? 0);
                $wbTotalExpenses['gross_revenue'] = ($wbTotalExpenses['gross_revenue'] ?? 0) + ($expenses['gross_revenue'] ?? 0);

            } catch (\Exception $e) {
                \Log::warning('WB expenses API failed, falling back to DB calculation', [
                    'account_id' => $account->id,
                    'error' => $e->getMessage(),
                ]);

                // Fallback: calculate from local orders if API fails
                if (class_exists(\App\Models\WildberriesOrder::class)) {
                    $wbExpenses = \App\Models\WildberriesOrder::where('marketplace_account_id', $account->id)
                        ->where('is_realization', true)
                        ->where('is_cancel', false)
                        ->where('is_return', false)
                        ->whereDate('order_date', '>=', $from)
                        ->whereDate('order_date', '<=', $to)
                        ->selectRaw('
                            COUNT(*) as orders_count,
                            SUM(COALESCE(total_price, 0)) as gross_revenue,
                            SUM(COALESCE(for_pay, finished_price, 0)) as net_payout
                        ')
                        ->first();

                    $grossRevenue = (float) ($wbExpenses->gross_revenue ?? 0);
                    $netPayout = (float) ($wbExpenses->net_payout ?? 0);
                    $totalFees = $grossRevenue - $netPayout;

                    // Estimated split when using DB fallback
                    $wbTotalExpenses['commission'] += $totalFees * 0.4;
                    $wbTotalExpenses['logistics'] += $totalFees * 0.6;
                    $wbTotalExpenses['total'] += $totalFees;
                    $wbTotalExpenses['orders_count'] = ($wbTotalExpenses['orders_count'] ?? 0) + (int) ($wbExpenses->orders_count ?? 0);
                    $wbTotalExpenses['gross_revenue'] = ($wbTotalExpenses['gross_revenue'] ?? 0) + $grossRevenue;
                    $wbTotalExpenses['fallback'] = true;
                }
            }
        }

        // Convert and add WB expenses to result and totals
        if ($wbTotalExpenses['total'] > 0) {
            // Check if amounts are already in UZS (for Uzbekistan sellers)
            $isUzs = ($wbTotalExpenses['currency'] ?? 'RUB') === 'UZS';
            $conversionRate = $isUzs ? 1 : $rubToUzs;

            $wbTotalExpenses['total_uzs'] = $wbTotalExpenses['total'] * $conversionRate;
            $result['wb'] = $wbTotalExpenses;

            // Add WB expenses to totals (convert only if in RUB)
            $result['total']['commission'] += $wbTotalExpenses['commission'] * $conversionRate;
            $result['total']['logistics'] += $wbTotalExpenses['logistics'] * $conversionRate;
            $result['total']['storage'] += $wbTotalExpenses['storage'] * $conversionRate;
            $result['total']['penalties'] += $wbTotalExpenses['penalties'] * $conversionRate;
            $result['total']['returns'] += $wbTotalExpenses['returns'] * $conversionRate;
            $result['total']['total'] += $wbTotalExpenses['total_uzs'];
        }

        // Ozon expenses (from finance transaction API)
        $ozonAccounts = MarketplaceAccount::where('company_id', $companyId)
            ->where('marketplace', 'ozon')
            ->where('is_active', true)
            ->get();

        $ozonTotalExpenses = [
            'commission' => 0,
            'logistics' => 0,
            'storage' => 0,
            'advertising' => 0,
            'penalties' => 0,
            'returns' => 0,
            'other' => 0,
            'total' => 0,
            'currency' => 'RUB',
            'total_uzs' => 0,
        ];

        foreach ($ozonAccounts as $account) {
            try {
                $expenses = $this->ozonClient->getExpensesSummary($account, $from, $to);

                // Accumulate expenses (all in RUB)
                $ozonTotalExpenses['commission'] += $expenses['commission'] ?? 0;
                $ozonTotalExpenses['logistics'] += $expenses['logistics'] ?? 0;
                $ozonTotalExpenses['storage'] += $expenses['storage'] ?? 0;
                $ozonTotalExpenses['advertising'] += $expenses['advertising'] ?? 0;
                $ozonTotalExpenses['penalties'] += $expenses['penalties'] ?? 0;
                $ozonTotalExpenses['returns'] += $expenses['returns'] ?? 0;
                $ozonTotalExpenses['other'] += $expenses['other'] ?? 0;
                $ozonTotalExpenses['total'] += $expenses['total'] ?? 0;
            } catch (\Exception $e) {
                if (!isset($result['ozon']['error'])) {
                    $result['ozon'] = ['error' => $e->getMessage()];
                }
            }
        }

        // Convert Ozon totals to UZS and add to result
        if ($ozonTotalExpenses['total'] > 0 || !isset($result['ozon']['error'])) {
            $ozonTotalExpenses['total_uzs'] = $ozonTotalExpenses['total'] * $rubToUzs;
            $result['ozon'] = $ozonTotalExpenses;

            // Add Ozon expenses to totals (convert RUB to UZS)
            $result['total']['commission'] += $ozonTotalExpenses['commission'] * $rubToUzs;
            $result['total']['logistics'] += $ozonTotalExpenses['logistics'] * $rubToUzs;
            $result['total']['storage'] += $ozonTotalExpenses['storage'] * $rubToUzs;
            $result['total']['advertising'] += $ozonTotalExpenses['advertising'] * $rubToUzs;
            $result['total']['penalties'] += $ozonTotalExpenses['penalties'] * $rubToUzs;
            $result['total']['returns'] += $ozonTotalExpenses['returns'] * $rubToUzs;
            $result['total']['other'] += $ozonTotalExpenses['other'] * $rubToUzs;
            $result['total']['total'] += $ozonTotalExpenses['total_uzs'];
        }

        return $this->successResponse($result);
    }

    /**
     * Получить детальную информацию о доходах с маркетплейсов
     */
    public function marketplaceIncome(Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (!$companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $from = $request->from ? Carbon::parse($request->from) : now()->startOfMonth();
        $to = $request->to ? Carbon::parse($request->to) : now()->endOfMonth();

        $financeSettings = FinanceSettings::getForCompany($companyId);
        $rubToUzs = $financeSettings->rub_rate ?? 140;

        $result = [
            'period' => [
                'from' => $from->format('Y-m-d'),
                'to' => $to->format('Y-m-d'),
            ],
            'uzum' => null,
            'wb' => null,
            'ozon' => null,
            'total' => [
                'gross_revenue' => 0,
                'net_payout' => 0,
                'orders_count' => 0,
                'returns_count' => 0,
                'avg_order_value' => 0,
            ],
        ];

        // Uzum income (UZS)
        try {
            if (class_exists(\App\Models\UzumFinanceOrder::class)) {
                $uzumSales = \App\Models\UzumFinanceOrder::whereHas('account', fn($q) => $q->where('company_id', $companyId))
                    ->where(function($q) use ($from, $to) {
                        $q->where(function($sub) use ($from, $to) {
                            $sub->where('status', 'TO_WITHDRAW')
                                ->whereDate('date_issued', '>=', $from)
                                ->whereDate('date_issued', '<=', $to);
                        })
                        ->orWhere(function($sub) use ($from, $to) {
                            $sub->where('status', 'PROCESSING')
                                ->whereNotNull('date_issued')
                                ->whereDate('date_issued', '>=', $from)
                                ->whereDate('date_issued', '<=', $to);
                        });
                    })
                    ->selectRaw('
                        COUNT(*) as orders_count,
                        SUM(sell_price * amount) as gross_revenue,
                        SUM(seller_profit) as net_payout,
                        SUM(CASE WHEN status = "RETURNED" THEN 1 ELSE 0 END) as returns_count
                    ')
                    ->first();

                // Возвраты отдельно
                $uzumReturns = \App\Models\UzumFinanceOrder::whereHas('account', fn($q) => $q->where('company_id', $companyId))
                    ->where('status', 'RETURNED')
                    ->whereDate('date_issued', '>=', $from)
                    ->whereDate('date_issued', '<=', $to)
                    ->selectRaw('COUNT(*) as cnt, SUM(sell_price * amount) as amount')
                    ->first();

                $ordersCount = (int) ($uzumSales?->orders_count ?? 0);
                $grossRevenue = (float) ($uzumSales?->gross_revenue ?? 0);
                $netPayout = (float) ($uzumSales?->net_payout ?? 0);
                $returnsCount = (int) ($uzumReturns?->cnt ?? 0);
                $returnsAmount = (float) ($uzumReturns?->amount ?? 0);

                $result['uzum'] = [
                    'orders_count' => $ordersCount,
                    'gross_revenue' => $grossRevenue,
                    'net_payout' => $netPayout,
                    'returns_count' => $returnsCount,
                    'returns_amount' => $returnsAmount,
                    'avg_order_value' => $ordersCount > 0 ? $grossRevenue / $ordersCount : 0,
                    'profit_margin' => $grossRevenue > 0 ? round(($netPayout / $grossRevenue) * 100, 1) : 0,
                    'currency' => 'UZS',
                ];

                $result['total']['gross_revenue'] += $grossRevenue;
                $result['total']['net_payout'] += $netPayout;
                $result['total']['orders_count'] += $ordersCount;
                $result['total']['returns_count'] += $returnsCount;
            }
        } catch (\Exception $e) {
            $result['uzum'] = ['error' => $e->getMessage()];
        }

        // WB income (RUB -> UZS)
        try {
            if (class_exists(\App\Models\WildberriesOrder::class)) {
                $wbSales = \App\Models\WildberriesOrder::whereHas('account', fn($q) => $q->where('company_id', $companyId))
                    ->where('is_realization', true)
                    ->where('is_cancel', false)
                    ->whereDate('order_date', '>=', $from)
                    ->whereDate('order_date', '<=', $to)
                    ->selectRaw('
                        COUNT(*) as orders_count,
                        SUM(COALESCE(total_price, 0)) as gross_revenue,
                        SUM(COALESCE(for_pay, finished_price, 0)) as net_payout
                    ')
                    ->first();

                // Возвраты / отмены
                $wbReturns = \App\Models\WildberriesOrder::whereHas('account', fn($q) => $q->where('company_id', $companyId))
                    ->where('is_cancel', true)
                    ->whereDate('order_date', '>=', $from)
                    ->whereDate('order_date', '<=', $to)
                    ->selectRaw('COUNT(*) as cnt, SUM(COALESCE(total_price, 0)) as amount')
                    ->first();

                $ordersCount = (int) ($wbSales?->orders_count ?? 0);
                $grossRevenueRub = (float) ($wbSales?->gross_revenue ?? 0);
                $netPayoutRub = (float) ($wbSales?->net_payout ?? 0);
                $returnsCount = (int) ($wbReturns?->cnt ?? 0);
                $returnsAmountRub = (float) ($wbReturns?->amount ?? 0);

                $result['wb'] = [
                    'orders_count' => $ordersCount,
                    'gross_revenue' => $grossRevenueRub * $rubToUzs,
                    'gross_revenue_rub' => $grossRevenueRub,
                    'net_payout' => $netPayoutRub * $rubToUzs,
                    'net_payout_rub' => $netPayoutRub,
                    'returns_count' => $returnsCount,
                    'returns_amount' => $returnsAmountRub * $rubToUzs,
                    'returns_amount_rub' => $returnsAmountRub,
                    'avg_order_value' => $ordersCount > 0 ? ($grossRevenueRub / $ordersCount) * $rubToUzs : 0,
                    'avg_order_value_rub' => $ordersCount > 0 ? $grossRevenueRub / $ordersCount : 0,
                    'profit_margin' => $grossRevenueRub > 0 ? round(($netPayoutRub / $grossRevenueRub) * 100, 1) : 0,
                    'currency' => 'RUB',
                    'rub_rate' => $rubToUzs,
                ];

                $result['total']['gross_revenue'] += $grossRevenueRub * $rubToUzs;
                $result['total']['net_payout'] += $netPayoutRub * $rubToUzs;
                $result['total']['orders_count'] += $ordersCount;
                $result['total']['returns_count'] += $returnsCount;
            }
        } catch (\Exception $e) {
            $result['wb'] = ['error' => $e->getMessage()];
        }

        // Ozon income (RUB -> UZS)
        try {
            if (class_exists(\App\Models\OzonOrder::class)) {
                $ozonSales = \App\Models\OzonOrder::whereHas('account', fn($q) => $q->where('company_id', $companyId))
                    ->whereIn('status', ['delivered', 'completed'])
                    ->whereDate('created_at_ozon', '>=', $from)
                    ->whereDate('created_at_ozon', '<=', $to)
                    ->selectRaw('
                        COUNT(*) as orders_count,
                        SUM(COALESCE(total_price, 0)) as gross_revenue
                    ')
                    ->first();

                // Возвраты / отмены
                $ozonReturns = \App\Models\OzonOrder::whereHas('account', fn($q) => $q->where('company_id', $companyId))
                    ->whereIn('status', ['cancelled', 'returned'])
                    ->whereDate('created_at_ozon', '>=', $from)
                    ->whereDate('created_at_ozon', '<=', $to)
                    ->selectRaw('COUNT(*) as cnt, SUM(COALESCE(total_price, 0)) as amount')
                    ->first();

                $ordersCount = (int) ($ozonSales?->orders_count ?? 0);
                $grossRevenueRub = (float) ($ozonSales?->gross_revenue ?? 0);
                // Для Ozon net_payout примерно = gross - комиссия (берём из expenses или оценка ~85%)
                $netPayoutRub = $grossRevenueRub * 0.85; // Приблизительно
                $returnsCount = (int) ($ozonReturns?->cnt ?? 0);
                $returnsAmountRub = (float) ($ozonReturns?->amount ?? 0);

                $result['ozon'] = [
                    'orders_count' => $ordersCount,
                    'gross_revenue' => $grossRevenueRub * $rubToUzs,
                    'gross_revenue_rub' => $grossRevenueRub,
                    'net_payout' => $netPayoutRub * $rubToUzs,
                    'net_payout_rub' => $netPayoutRub,
                    'returns_count' => $returnsCount,
                    'returns_amount' => $returnsAmountRub * $rubToUzs,
                    'returns_amount_rub' => $returnsAmountRub,
                    'avg_order_value' => $ordersCount > 0 ? ($grossRevenueRub / $ordersCount) * $rubToUzs : 0,
                    'avg_order_value_rub' => $ordersCount > 0 ? $grossRevenueRub / $ordersCount : 0,
                    'profit_margin' => $grossRevenueRub > 0 ? round(($netPayoutRub / $grossRevenueRub) * 100, 1) : 0,
                    'currency' => 'RUB',
                    'rub_rate' => $rubToUzs,
                    'note' => 'Net payout estimated at 85% of gross',
                ];

                $result['total']['gross_revenue'] += $grossRevenueRub * $rubToUzs;
                $result['total']['net_payout'] += $netPayoutRub * $rubToUzs;
                $result['total']['orders_count'] += $ordersCount;
                $result['total']['returns_count'] += $returnsCount;
            }
        } catch (\Exception $e) {
            $result['ozon'] = ['error' => $e->getMessage()];
        }

        // Calculate total avg order value
        if ($result['total']['orders_count'] > 0) {
            $result['total']['avg_order_value'] = $result['total']['gross_revenue'] / $result['total']['orders_count'];
        }

        return $this->successResponse($result);
    }

    /**
     * Синхронизировать расходы Uzum за период
     */
    public function syncUzumExpenses(Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (!$companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $from = $request->from ? Carbon::parse($request->from) : now()->startOfMonth();
        $to = $request->to ? Carbon::parse($request->to) : now()->endOfMonth();

        $uzumAccounts = MarketplaceAccount::where('company_id', $companyId)
            ->where('marketplace', 'uzum')
            ->where('is_active', true)
            ->get();

        $results = [];
        foreach ($uzumAccounts as $account) {
            try {
                $dateFromMs = $from->getTimestamp() * 1000;
                $dateToMs = $to->getTimestamp() * 1000;

                $expenses = $this->uzumClient->fetchAllFinanceExpenses($account, [], $dateFromMs, $dateToMs);
                $summary = $this->uzumClient->getExpensesSummary($account, $from, $to);

                $results[] = [
                    'account_id' => $account->id,
                    'account_name' => $account->name,
                    'expenses_count' => count($expenses),
                    'summary' => $summary,
                ];
            } catch (\Exception $e) {
                $results[] = [
                    'account_id' => $account->id,
                    'account_name' => $account->name,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $this->successResponse([
            'period' => [
                'from' => $from->format('Y-m-d'),
                'to' => $to->format('Y-m-d'),
            ],
            'results' => $results,
        ]);
    }
}
