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
use App\Models\MarketplaceExpenseCache;
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

        // Расходы маркетплейсов теперь хранятся в FinanceTransaction
        // и автоматически включаются в $totalExpense

        // ========== СЕБЕСТОИМОСТЬ ПРОДАННЫХ ТОВАРОВ ==========
        $rubToUzs = $financeSettings->rub_rate ?? 140;
        $cogs = $this->calculateCogs($companyId, $from, $to, $rubToUzs);
        $totalCogs = $cogs['total'] ?? 0;

        // Чистая прибыль = Доходы - Расходы - Себестоимость
        $netProfit = $totalIncome - $totalExpense - $totalCogs;

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
                'total_cogs' => $totalCogs,
                'net_profit' => $netProfit,
            ],
            'cogs' => $cogs,
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

        // Uzum продажи из uzum_orders (issued = доставлено клиенту)
        try {
            if (class_exists(\App\Models\UzumOrder::class)) {
                $uzumSales = \App\Models\UzumOrder::whereHas('account', fn($q) => $q->where('company_id', $companyId))
                    ->where('status', 'issued')
                    ->whereDate('ordered_at', '>=', $from)
                    ->whereDate('ordered_at', '<=', $to)
                    ->selectRaw('COUNT(*) as cnt, SUM(total_amount) as revenue')
                    ->first();

                $revenue = (float) ($uzumSales?->revenue ?? 0);
                $result['uzum'] = [
                    'orders' => (int) ($uzumSales?->cnt ?? 0),
                    'revenue' => $revenue,
                    'profit' => $revenue * 0.85, // Примерная прибыль после комиссий
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
     *
     * ВАЖНО: cost_delta хранит себестоимость в UZS (уже конвертирована при записи)
     * - Поступления: cost_delta положительная (добавляется к себестоимости)
     * - Списания: cost_delta отрицательная (вычитается из себестоимости)
     *
     * Формула: SUM(cost_delta) для всех записей
     * Это автоматически учитывает поступления и списания
     */
    protected function getStockSummary(int $companyId, FinanceSettings $settings): array
    {
        try {
            // Получаем текущее количество и себестоимость
            // cost_delta уже в UZS и уже учитывает направление (+ для поступлений, - для списаний)
            $summary = StockLedger::byCompany($companyId)
                ->selectRaw('SUM(qty_delta) as total_qty, SUM(cost_delta) as total_cost')
                ->first();

            $totalQty = max(0, (float) ($summary?->total_qty ?? 0));
            $totalCost = max(0, (float) ($summary?->total_cost ?? 0));

            // Группировка по складам
            $byWarehouse = StockLedger::where('stock_ledger.company_id', $companyId)
                ->join('warehouses', 'stock_ledger.warehouse_id', '=', 'warehouses.id')
                ->selectRaw('warehouses.id, warehouses.name, SUM(stock_ledger.qty_delta) as qty, SUM(stock_ledger.cost_delta) as cost')
                ->groupBy('warehouses.id', 'warehouses.name')
                ->having('qty', '>', 0)
                ->get();

            $warehouseData = $byWarehouse->map(fn($w) => [
                'id' => $w->id,
                'name' => $w->name,
                'qty' => (float) $w->qty,
                'cost' => max(0, (float) $w->cost),
            ])->toArray();

            return [
                'total_qty' => $totalQty,
                'total_cost' => $totalCost,
                'by_warehouse' => $warehouseData,
            ];
        } catch (\Exception $e) {
            \Log::error('getStockSummary error', ['error' => $e->getMessage()]);
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

        // 1. Uzum заказы в пути (уже в UZS) - из uzum_orders
        // Статусы: waiting_pickup, accepted_uzum, in_supply, in_assembly = в пути
        try {
            if (class_exists(\App\Models\UzumOrder::class)) {
                $uzumTransit = \App\Models\UzumOrder::whereHas('account', fn($q) => $q->where('company_id', $companyId))
                    ->whereIn('status', ['waiting_pickup', 'accepted_uzum', 'in_supply', 'in_assembly'])
                    ->selectRaw('COUNT(*) as cnt, SUM(total_amount) as total')
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

    public function storeCategory(Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (!$companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:income,expense,both'],
            'parent_id' => ['nullable', 'integer', 'exists:finance_categories,id'],
        ]);

        // Check if category with same name exists for this company
        $existing = FinanceCategory::where('company_id', $companyId)
            ->where('name', $data['name'])
            ->where('type', $data['type'])
            ->first();

        if ($existing) {
            return $this->successResponse($existing);
        }

        $category = FinanceCategory::create([
            'company_id' => $companyId,
            'name' => $data['name'],
            'type' => $data['type'],
            'parent_id' => $data['parent_id'] ?? null,
            'code' => 'CUSTOM_' . strtoupper(str_replace(' ', '_', $data['name'])) . '_' . time(),
            'is_system' => false,
            'is_active' => true,
            'sort_order' => 999,
        ]);

        return $this->successResponse($category, 201);
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
     *
     * Использует кэш из таблицы marketplace_expense_cache для быстрой загрузки.
     * Кэш обновляется каждые 4 часа через команду marketplace:sync-expenses.
     * Если кэш устарел или отсутствует, данные загружаются напрямую из API.
     */
    public function marketplaceExpenses(Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (!$companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $from = $request->from ? Carbon::parse($request->from)->startOfDay() : now()->startOfMonth();
        $to = $request->to ? Carbon::parse($request->to)->endOfDay() : now()->endOfMonth();
        $useCache = $request->boolean('use_cache', true);
        $forceRefresh = $request->boolean('refresh', false);

        // Determine period type for cache lookup
        $daysDiff = $from->diffInDays($to);
        $periodType = match (true) {
            $daysDiff <= 7 => '7days',
            $daysDiff <= 30 => '30days',
            default => '90days',
        };

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
            'cache_info' => [
                'used' => false,
                'stale' => false,
            ],
        ];

        // Try to get data from cache first (if enabled and not forcing refresh)
        if ($useCache && !$forceRefresh) {
            $cachedData = $this->getExpensesFromCache($companyId, $periodType);
            if ($cachedData) {
                return $this->successResponse($cachedData);
            }
        }

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
            // 1. Получаем комиссию и логистику из uzum_finance_orders (данные о заказах)
            // Это основной источник для комиссии маркетплейса
            if (class_exists(\App\Models\UzumFinanceOrder::class)) {
                $orderExpenses = \App\Models\UzumFinanceOrder::where('marketplace_account_id', $account->id)
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

                $commission = (float) ($orderExpenses->total_commission ?? 0);
                $logisticsFromOrders = (float) ($orderExpenses->total_logistics ?? 0);

                $uzumTotalExpenses['commission'] += $commission;
                $uzumTotalExpenses['logistics'] += $logisticsFromOrders;
                $uzumTotalExpenses['total'] += $commission + $logisticsFromOrders;
                $uzumTotalExpenses['items_count'] += (int) ($orderExpenses->orders_count ?? 0);
            }

            // 2. Получаем прочие расходы из uzum_expenses (хранение, реклама, штрафы)
            // Эти данные не дублируются с комиссией из заказов
            if (class_exists(\App\Models\UzumExpense::class)) {
                $dbExpenses = \App\Models\UzumExpense::getSummaryForAccount($account->id, $from, $to);

                if ($dbExpenses['total'] > 0) {
                    // НЕ добавляем commission и logistics - они уже взяты из orders
                    $uzumTotalExpenses['storage'] += $dbExpenses['storage'] ?? 0;
                    $uzumTotalExpenses['advertising'] += $dbExpenses['advertising'] ?? 0;
                    $uzumTotalExpenses['penalties'] += $dbExpenses['penalties'] ?? 0;
                    $uzumTotalExpenses['other'] += $dbExpenses['other'] ?? 0;
                    // Добавляем только storage/advertising/penalties/other к total
                    $expensesOnlyTotal = ($dbExpenses['storage'] ?? 0) + ($dbExpenses['advertising'] ?? 0)
                        + ($dbExpenses['penalties'] ?? 0) + ($dbExpenses['other'] ?? 0);
                    $uzumTotalExpenses['total'] += $expensesOnlyTotal;
                    $uzumTotalExpenses['accounts_db']++;
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
     *
     * Возвращает 4 категории:
     * 1. Заказы - все заказы независимо от типа (FBO/FBS/DBS и т.д.)
     * 2. Продано - проданные товары (delivered/issued/completed)
     * 3. Возвраты - возвраты товаров
     * 4. Отменены - отменённые заказы (до доставки)
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

        // Инициализация итогов
        $totals = [
            'orders' => ['count' => 0, 'amount' => 0],      // Все заказы
            'sold' => ['count' => 0, 'amount' => 0],        // Проданные (delivered)
            'returns' => ['count' => 0, 'amount' => 0],     // Возвраты
            'cancelled' => ['count' => 0, 'amount' => 0],   // Отменённые
            'avg_order_value' => 0,
        ];

        $marketplaces = [];

        // ========== UZUM ==========
        try {
            if (class_exists(\App\Models\UzumOrder::class)) {
                $uzumQuery = fn() => \App\Models\UzumOrder::whereHas('account', fn($q) => $q->where('company_id', $companyId))
                    ->whereDate('ordered_at', '>=', $from)
                    ->whereDate('ordered_at', '<=', $to);

                // Все заказы (независимо от типа доставки)
                $uzumAll = $uzumQuery()
                    ->selectRaw('COUNT(*) as cnt, SUM(total_amount) as amount')
                    ->first();

                // Проданные (issued = доставлено клиенту)
                $uzumSold = $uzumQuery()
                    ->where('status', 'issued')
                    ->selectRaw('COUNT(*) as cnt, SUM(total_amount) as amount')
                    ->first();

                // Возвраты
                $uzumReturns = $uzumQuery()
                    ->where('status', 'returns')
                    ->selectRaw('COUNT(*) as cnt, SUM(total_amount) as amount')
                    ->first();

                // Отменённые
                $uzumCancelled = $uzumQuery()
                    ->where('status', 'cancelled')
                    ->selectRaw('COUNT(*) as cnt, SUM(total_amount) as amount')
                    ->first();

                $ordersCount = (int) ($uzumAll?->cnt ?? 0);
                $ordersAmount = (float) ($uzumAll?->amount ?? 0);
                $soldCount = (int) ($uzumSold?->cnt ?? 0);
                $soldAmount = (float) ($uzumSold?->amount ?? 0);
                $returnsCount = (int) ($uzumReturns?->cnt ?? 0);
                $returnsAmount = (float) ($uzumReturns?->amount ?? 0);
                $cancelledCount = (int) ($uzumCancelled?->cnt ?? 0);
                $cancelledAmount = (float) ($uzumCancelled?->amount ?? 0);

                $marketplaces['uzum'] = [
                    'orders' => ['count' => $ordersCount, 'amount' => $ordersAmount],
                    'sold' => ['count' => $soldCount, 'amount' => $soldAmount],
                    'returns' => ['count' => $returnsCount, 'amount' => $returnsAmount],
                    'cancelled' => ['count' => $cancelledCount, 'amount' => $cancelledAmount],
                    'avg_order_value' => $soldCount > 0 ? $soldAmount / $soldCount : 0,
                    'currency' => 'UZS',
                ];

                // Добавляем к итогам (Uzum уже в UZS)
                $totals['orders']['count'] += $ordersCount;
                $totals['orders']['amount'] += $ordersAmount;
                $totals['sold']['count'] += $soldCount;
                $totals['sold']['amount'] += $soldAmount;
                $totals['returns']['count'] += $returnsCount;
                $totals['returns']['amount'] += $returnsAmount;
                $totals['cancelled']['count'] += $cancelledCount;
                $totals['cancelled']['amount'] += $cancelledAmount;
            }
        } catch (\Exception $e) {
            $marketplaces['uzum'] = ['error' => $e->getMessage()];
        }

        // ========== WILDBERRIES ==========
        try {
            if (class_exists(\App\Models\WildberriesOrder::class)) {
                $wbQuery = fn() => \App\Models\WildberriesOrder::whereHas('account', fn($q) => $q->where('company_id', $companyId))
                    ->whereDate('order_date', '>=', $from)
                    ->whereDate('order_date', '<=', $to);

                // Все заказы
                $wbAll = $wbQuery()
                    ->selectRaw('COUNT(*) as cnt, SUM(COALESCE(for_pay, finished_price, total_price, 0)) as amount')
                    ->first();

                // Проданные (is_realization=true, не отменённые, не возвраты)
                $wbSold = $wbQuery()
                    ->where('is_realization', true)
                    ->where('is_cancel', false)
                    ->where('is_return', false)
                    ->selectRaw('COUNT(*) as cnt, SUM(COALESCE(for_pay, finished_price, total_price, 0)) as amount')
                    ->first();

                // Возвраты
                $wbReturns = $wbQuery()
                    ->where('is_return', true)
                    ->selectRaw('COUNT(*) as cnt, SUM(COALESCE(for_pay, finished_price, total_price, 0)) as amount')
                    ->first();

                // Отменённые
                $wbCancelled = $wbQuery()
                    ->where('is_cancel', true)
                    ->selectRaw('COUNT(*) as cnt, SUM(COALESCE(for_pay, finished_price, total_price, 0)) as amount')
                    ->first();

                $ordersCount = (int) ($wbAll?->cnt ?? 0);
                $ordersAmountRub = (float) ($wbAll?->amount ?? 0);
                $soldCount = (int) ($wbSold?->cnt ?? 0);
                $soldAmountRub = (float) ($wbSold?->amount ?? 0);
                $returnsCount = (int) ($wbReturns?->cnt ?? 0);
                $returnsAmountRub = (float) ($wbReturns?->amount ?? 0);
                $cancelledCount = (int) ($wbCancelled?->cnt ?? 0);
                $cancelledAmountRub = (float) ($wbCancelled?->amount ?? 0);

                $marketplaces['wb'] = [
                    'orders' => ['count' => $ordersCount, 'amount' => $ordersAmountRub * $rubToUzs, 'amount_rub' => $ordersAmountRub],
                    'sold' => ['count' => $soldCount, 'amount' => $soldAmountRub * $rubToUzs, 'amount_rub' => $soldAmountRub],
                    'returns' => ['count' => $returnsCount, 'amount' => $returnsAmountRub * $rubToUzs, 'amount_rub' => $returnsAmountRub],
                    'cancelled' => ['count' => $cancelledCount, 'amount' => $cancelledAmountRub * $rubToUzs, 'amount_rub' => $cancelledAmountRub],
                    'avg_order_value' => $soldCount > 0 ? ($soldAmountRub / $soldCount) * $rubToUzs : 0,
                    'avg_order_value_rub' => $soldCount > 0 ? $soldAmountRub / $soldCount : 0,
                    'currency' => 'RUB',
                    'rub_rate' => $rubToUzs,
                ];

                // Добавляем к итогам (конвертируем RUB -> UZS)
                $totals['orders']['count'] += $ordersCount;
                $totals['orders']['amount'] += $ordersAmountRub * $rubToUzs;
                $totals['sold']['count'] += $soldCount;
                $totals['sold']['amount'] += $soldAmountRub * $rubToUzs;
                $totals['returns']['count'] += $returnsCount;
                $totals['returns']['amount'] += $returnsAmountRub * $rubToUzs;
                $totals['cancelled']['count'] += $cancelledCount;
                $totals['cancelled']['amount'] += $cancelledAmountRub * $rubToUzs;
            }
        } catch (\Exception $e) {
            $marketplaces['wb'] = ['error' => $e->getMessage()];
        }

        // ========== OZON ==========
        try {
            if (class_exists(\App\Models\OzonOrder::class)) {
                $ozonQuery = fn() => \App\Models\OzonOrder::whereHas('account', fn($q) => $q->where('company_id', $companyId))
                    ->whereDate('created_at_ozon', '>=', $from)
                    ->whereDate('created_at_ozon', '<=', $to);

                // Все заказы
                $ozonAll = $ozonQuery()
                    ->selectRaw('COUNT(*) as cnt, SUM(COALESCE(total_price, 0)) as amount')
                    ->first();

                // Проданные (delivered, completed)
                $ozonSold = $ozonQuery()
                    ->whereIn('status', ['delivered', 'completed'])
                    ->selectRaw('COUNT(*) as cnt, SUM(COALESCE(total_price, 0)) as amount')
                    ->first();

                // Возвраты
                $ozonReturns = $ozonQuery()
                    ->whereIn('status', ['returned'])
                    ->selectRaw('COUNT(*) as cnt, SUM(COALESCE(total_price, 0)) as amount')
                    ->first();

                // Отменённые
                $ozonCancelled = $ozonQuery()
                    ->where('status', 'cancelled')
                    ->selectRaw('COUNT(*) as cnt, SUM(COALESCE(total_price, 0)) as amount')
                    ->first();

                $ordersCount = (int) ($ozonAll?->cnt ?? 0);
                $ordersAmountRub = (float) ($ozonAll?->amount ?? 0);
                $soldCount = (int) ($ozonSold?->cnt ?? 0);
                $soldAmountRub = (float) ($ozonSold?->amount ?? 0);
                $returnsCount = (int) ($ozonReturns?->cnt ?? 0);
                $returnsAmountRub = (float) ($ozonReturns?->amount ?? 0);
                $cancelledCount = (int) ($ozonCancelled?->cnt ?? 0);
                $cancelledAmountRub = (float) ($ozonCancelled?->amount ?? 0);

                $marketplaces['ozon'] = [
                    'orders' => ['count' => $ordersCount, 'amount' => $ordersAmountRub * $rubToUzs, 'amount_rub' => $ordersAmountRub],
                    'sold' => ['count' => $soldCount, 'amount' => $soldAmountRub * $rubToUzs, 'amount_rub' => $soldAmountRub],
                    'returns' => ['count' => $returnsCount, 'amount' => $returnsAmountRub * $rubToUzs, 'amount_rub' => $returnsAmountRub],
                    'cancelled' => ['count' => $cancelledCount, 'amount' => $cancelledAmountRub * $rubToUzs, 'amount_rub' => $cancelledAmountRub],
                    'avg_order_value' => $soldCount > 0 ? ($soldAmountRub / $soldCount) * $rubToUzs : 0,
                    'avg_order_value_rub' => $soldCount > 0 ? $soldAmountRub / $soldCount : 0,
                    'currency' => 'RUB',
                    'rub_rate' => $rubToUzs,
                ];

                // Добавляем к итогам (конвертируем RUB -> UZS)
                $totals['orders']['count'] += $ordersCount;
                $totals['orders']['amount'] += $ordersAmountRub * $rubToUzs;
                $totals['sold']['count'] += $soldCount;
                $totals['sold']['amount'] += $soldAmountRub * $rubToUzs;
                $totals['returns']['count'] += $returnsCount;
                $totals['returns']['amount'] += $returnsAmountRub * $rubToUzs;
                $totals['cancelled']['count'] += $cancelledCount;
                $totals['cancelled']['amount'] += $cancelledAmountRub * $rubToUzs;
            }
        } catch (\Exception $e) {
            $marketplaces['ozon'] = ['error' => $e->getMessage()];
        }

        // ========== YANDEX MARKET ==========
        try {
            if (class_exists(\App\Models\YandexMarketOrder::class)) {
                $ymQuery = fn() => \App\Models\YandexMarketOrder::whereHas('account', fn($q) => $q->where('company_id', $companyId))
                    ->whereDate('created_at_ym', '>=', $from)
                    ->whereDate('created_at_ym', '<=', $to);

                // Все заказы
                $ymAll = $ymQuery()
                    ->selectRaw('COUNT(*) as cnt, SUM(COALESCE(total_price, 0)) as amount')
                    ->first();

                // Проданные (DELIVERED)
                $ymSold = $ymQuery()
                    ->where('status', 'DELIVERED')
                    ->selectRaw('COUNT(*) as cnt, SUM(COALESCE(total_price, 0)) as amount')
                    ->first();

                // Возвраты (RETURNED)
                $ymReturns = $ymQuery()
                    ->where('status', 'RETURNED')
                    ->selectRaw('COUNT(*) as cnt, SUM(COALESCE(total_price, 0)) as amount')
                    ->first();

                // Отменённые (CANCELLED)
                $ymCancelled = $ymQuery()
                    ->where('status', 'CANCELLED')
                    ->selectRaw('COUNT(*) as cnt, SUM(COALESCE(total_price, 0)) as amount')
                    ->first();

                $ordersCount = (int) ($ymAll?->cnt ?? 0);
                $ordersAmountRub = (float) ($ymAll?->amount ?? 0);
                $soldCount = (int) ($ymSold?->cnt ?? 0);
                $soldAmountRub = (float) ($ymSold?->amount ?? 0);
                $returnsCount = (int) ($ymReturns?->cnt ?? 0);
                $returnsAmountRub = (float) ($ymReturns?->amount ?? 0);
                $cancelledCount = (int) ($ymCancelled?->cnt ?? 0);
                $cancelledAmountRub = (float) ($ymCancelled?->amount ?? 0);

                $marketplaces['yandex'] = [
                    'orders' => ['count' => $ordersCount, 'amount' => $ordersAmountRub * $rubToUzs, 'amount_rub' => $ordersAmountRub],
                    'sold' => ['count' => $soldCount, 'amount' => $soldAmountRub * $rubToUzs, 'amount_rub' => $soldAmountRub],
                    'returns' => ['count' => $returnsCount, 'amount' => $returnsAmountRub * $rubToUzs, 'amount_rub' => $returnsAmountRub],
                    'cancelled' => ['count' => $cancelledCount, 'amount' => $cancelledAmountRub * $rubToUzs, 'amount_rub' => $cancelledAmountRub],
                    'avg_order_value' => $soldCount > 0 ? ($soldAmountRub / $soldCount) * $rubToUzs : 0,
                    'avg_order_value_rub' => $soldCount > 0 ? $soldAmountRub / $soldCount : 0,
                    'currency' => 'RUB',
                    'rub_rate' => $rubToUzs,
                ];

                // Добавляем к итогам (конвертируем RUB -> UZS)
                $totals['orders']['count'] += $ordersCount;
                $totals['orders']['amount'] += $ordersAmountRub * $rubToUzs;
                $totals['sold']['count'] += $soldCount;
                $totals['sold']['amount'] += $soldAmountRub * $rubToUzs;
                $totals['returns']['count'] += $returnsCount;
                $totals['returns']['amount'] += $returnsAmountRub * $rubToUzs;
                $totals['cancelled']['count'] += $cancelledCount;
                $totals['cancelled']['amount'] += $cancelledAmountRub * $rubToUzs;
            }
        } catch (\Exception $e) {
            $marketplaces['yandex'] = ['error' => $e->getMessage()];
        }

        // ========== РУЧНЫЕ ПРОДАЖИ (Продажи со страницы "Продажи") ==========
        try {
            if (class_exists(\App\Models\Sale::class)) {
                // Ручные продажи (type = 'manual') за период
                $saleQuery = fn() => \App\Models\Sale::byCompany($companyId)
                    ->where('type', 'manual')
                    ->whereDate('created_at', '>=', $from)
                    ->whereDate('created_at', '<=', $to);

                // Все продажи
                $saleAll = $saleQuery()
                    ->selectRaw('COUNT(*) as cnt, SUM(total_amount) as amount')
                    ->first();

                // Завершённые (completed) - аналог проданных
                $saleSold = $saleQuery()
                    ->where('status', 'completed')
                    ->selectRaw('COUNT(*) as cnt, SUM(total_amount) as amount')
                    ->first();

                // Возвраты - в модели Sale нет отдельного статуса returned, считаем 0
                $saleReturns = (object) ['cnt' => 0, 'amount' => 0];

                // Отменённые (cancelled)
                $saleCancelled = $saleQuery()
                    ->where('status', 'cancelled')
                    ->selectRaw('COUNT(*) as cnt, SUM(total_amount) as amount')
                    ->first();

                // По источникам продаж (source: pos, web, phone, etc.)
                $bySource = $saleQuery()
                    ->where('status', 'completed')
                    ->selectRaw('COALESCE(source, "other") as source, COUNT(*) as cnt, SUM(total_amount) as amount')
                    ->groupBy('source')
                    ->get()
                    ->keyBy('source');

                $ordersCount = (int) ($saleAll?->cnt ?? 0);
                $ordersAmount = (float) ($saleAll?->amount ?? 0);
                $soldCount = (int) ($saleSold?->cnt ?? 0);
                $soldAmount = (float) ($saleSold?->amount ?? 0);
                $returnsCount = (int) ($saleReturns?->cnt ?? 0);
                $returnsAmount = (float) ($saleReturns?->amount ?? 0);
                $cancelledCount = (int) ($saleCancelled?->cnt ?? 0);
                $cancelledAmount = (float) ($saleCancelled?->amount ?? 0);

                $marketplaces['offline'] = [
                    'orders' => ['count' => $ordersCount, 'amount' => $ordersAmount],
                    'sold' => ['count' => $soldCount, 'amount' => $soldAmount],
                    'returns' => ['count' => $returnsCount, 'amount' => $returnsAmount],
                    'cancelled' => ['count' => $cancelledCount, 'amount' => $cancelledAmount],
                    'avg_order_value' => $soldCount > 0 ? $soldAmount / $soldCount : 0,
                    'currency' => 'UZS',
                    'by_source' => [
                        'pos' => ['count' => (int) ($bySource['pos']?->cnt ?? 0), 'amount' => (float) ($bySource['pos']?->amount ?? 0)],
                        'web' => ['count' => (int) ($bySource['web']?->cnt ?? 0), 'amount' => (float) ($bySource['web']?->amount ?? 0)],
                        'phone' => ['count' => (int) ($bySource['phone']?->cnt ?? 0), 'amount' => (float) ($bySource['phone']?->amount ?? 0)],
                        'other' => ['count' => (int) ($bySource['other']?->cnt ?? 0), 'amount' => (float) ($bySource['other']?->amount ?? 0)],
                    ],
                ];

                // Добавляем к итогам (Ручные продажи уже в UZS)
                $totals['orders']['count'] += $ordersCount;
                $totals['orders']['amount'] += $ordersAmount;
                $totals['sold']['count'] += $soldCount;
                $totals['sold']['amount'] += $soldAmount;
                $totals['returns']['count'] += $returnsCount;
                $totals['returns']['amount'] += $returnsAmount;
                $totals['cancelled']['count'] += $cancelledCount;
                $totals['cancelled']['amount'] += $cancelledAmount;
            }
        } catch (\Exception $e) {
            $marketplaces['offline'] = ['error' => $e->getMessage()];
        }

        // Рассчитываем средний чек по проданным
        if ($totals['sold']['count'] > 0) {
            $totals['avg_order_value'] = $totals['sold']['amount'] / $totals['sold']['count'];
        }

        // ========== СЕБЕСТОИМОСТЬ ПРОДАННЫХ ТОВАРОВ (COGS) ==========
        $cogs = $this->calculateCogs($companyId, $from, $to, $rubToUzs);

        return $this->successResponse([
            'period' => [
                'from' => $from->format('Y-m-d'),
                'to' => $to->format('Y-m-d'),
            ],
            'totals' => $totals,
            'cogs' => $cogs,
            'marketplaces' => $marketplaces,
            // Legacy fields for backward compatibility
            'total' => [
                'gross_revenue' => $totals['sold']['amount'],
                'orders_count' => $totals['orders']['count'],
                'returns_count' => $totals['returns']['count'],
                'avg_order_value' => $totals['avg_order_value'],
            ],
            'uzum' => $marketplaces['uzum'] ?? null,
            'wb' => $marketplaces['wb'] ?? null,
            'ozon' => $marketplaces['ozon'] ?? null,
            'yandex' => $marketplaces['yandex'] ?? null,
            'offline' => $marketplaces['offline'] ?? null,
        ]);
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

    /**
     * Get marketplace expenses from cache table
     *
     * Returns cached data if available and fresh (< 4 hours old).
     * Returns null if cache is stale or doesn't exist.
     */
    protected function getExpensesFromCache(int $companyId, string $periodType): ?array
    {
        $financeSettings = FinanceSettings::getForCompany($companyId);
        $rubToUzs = $financeSettings->rub_rate ?? 140;

        // Get all cached expenses for this company and period
        $cachedExpenses = MarketplaceExpenseCache::where('company_id', $companyId)
            ->where('period_type', $periodType)
            ->where('sync_status', 'success')
            ->get();

        if ($cachedExpenses->isEmpty()) {
            return null;
        }

        // Check if any cache is stale (older than 4 hours)
        $hasStale = $cachedExpenses->contains(fn($c) => $c->isStale(4));

        // Prepare result structure
        $result = [
            'period' => [
                'from' => $cachedExpenses->first()->period_from->format('Y-m-d'),
                'to' => $cachedExpenses->first()->period_to->format('Y-m-d'),
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
            'cache_info' => [
                'used' => true,
                'stale' => $hasStale,
                'synced_at' => $cachedExpenses->max('synced_at')?->toIso8601String(),
            ],
        ];

        // Aggregate by marketplace (include 'ym' as alias for yandex)
        foreach (['uzum', 'wb', 'ozon', 'yandex', 'ym'] as $marketplace) {
            $mpExpenses = $cachedExpenses->where('marketplace', $marketplace);

            if ($mpExpenses->isEmpty()) {
                // Return empty structure for marketplaces that have no cache data
                // This ensures UI shows 0 instead of hiding the section
                $displayKey = ($marketplace === 'ym') ? 'yandex' : $marketplace;
                if (!isset($result[$displayKey])) {
                    $result[$displayKey] = [
                        'commission' => 0,
                        'logistics' => 0,
                        'storage' => 0,
                        'advertising' => 0,
                        'penalties' => 0,
                        'returns' => 0,
                        'other' => 0,
                        'total' => 0,
                        'gross_revenue' => 0,
                        'orders_count' => 0,
                        'returns_count' => 0,
                        'currency' => 'UZS',
                        'total_uzs' => 0,
                        'source' => 'cache',
                    ];
                }
                continue;
            }

            $aggregated = [
                'commission' => $mpExpenses->sum('commission'),
                'logistics' => $mpExpenses->sum('logistics'),
                'storage' => $mpExpenses->sum('storage'),
                'advertising' => $mpExpenses->sum('advertising'),
                'penalties' => $mpExpenses->sum('penalties'),
                'returns' => $mpExpenses->sum('returns'),
                'other' => $mpExpenses->sum('other'),
                'total' => $mpExpenses->sum('total'),
                'gross_revenue' => $mpExpenses->sum('gross_revenue'),
                'orders_count' => $mpExpenses->sum('orders_count'),
                'returns_count' => $mpExpenses->sum('returns_count'),
                'currency' => $mpExpenses->first()->currency ?? 'UZS',
                'total_uzs' => $mpExpenses->sum('total_uzs'),
                'source' => 'cache',
            ];

            // Use 'yandex' key for 'ym' marketplace
            $displayKey = ($marketplace === 'ym') ? 'yandex' : $marketplace;
            $result[$displayKey] = $aggregated;

            // Determine conversion rate based on currency
            $isUzs = $aggregated['currency'] === 'UZS';
            $conversionRate = $isUzs ? 1 : $rubToUzs;

            // Add to totals
            $result['total']['commission'] += $aggregated['commission'] * $conversionRate;
            $result['total']['logistics'] += $aggregated['logistics'] * $conversionRate;
            $result['total']['storage'] += $aggregated['storage'] * $conversionRate;
            $result['total']['advertising'] += $aggregated['advertising'] * $conversionRate;
            $result['total']['penalties'] += $aggregated['penalties'] * $conversionRate;
            $result['total']['returns'] += $aggregated['returns'] * $conversionRate;
            $result['total']['other'] += $aggregated['other'] * $conversionRate;
            $result['total']['total'] += $aggregated['total_uzs'] ?: ($aggregated['total'] * $conversionRate);
        }

        return $result;
    }

    /**
     * Рассчитать себестоимость проданных товаров (COGS - Cost of Goods Sold)
     *
     * Приоритет получения себестоимости:
     * 1. СНАЧАЛА ищем связь с внутренним товаром (ProductVariant.purchase_price)
     * 2. Если связи нет - берём из маркетплейса (UzumFinanceOrder.purchase_price и т.д.)
     *
     * ВАЖНО: purchase_price в ProductVariant хранится в UZS!
     *
     * @param int $companyId
     * @param Carbon $from
     * @param Carbon $to
     * @param float $rubToUzs
     * @return array
     */
    protected function calculateCogs(int $companyId, Carbon $from, Carbon $to, float $rubToUzs): array
    {
        $result = [
            'total' => 0,
            'total_items' => 0,
            'by_marketplace' => [],
            'gross_margin' => 0,
            'margin_percent' => 0,
        ];

        $totalRevenue = 0;

        // ========== 1. UZUM COGS ==========
        try {
            if (class_exists(\App\Models\UzumFinanceOrder::class)) {
                $uzumOrders = \App\Models\UzumFinanceOrder::whereHas('account', fn($q) => $q->where('company_id', $companyId))
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
                    ->select('sku_id', 'offer_id', 'barcode', 'amount', 'purchase_price', 'marketplace_account_id')
                    ->get();

                $uzumCogs = 0;
                $uzumRevenue = 0;
                $uzumItemsCount = $uzumOrders->count();
                $uzumWithCogs = 0;
                $uzumFromInternal = 0;
                $uzumFromMarketplace = 0;

                foreach ($uzumOrders as $order) {
                    $revenue = (float) ($order->amount ?? 0);
                    $uzumRevenue += $revenue;

                    $purchasePrice = null;
                    $fromInternal = false;

                    // 1. СНАЧАЛА ищем связь с внутренним товаром через offer_id
                    if ($order->offer_id) {
                        $link = \App\Models\VariantMarketplaceLink::where('marketplace_account_id', $order->marketplace_account_id)
                            ->where('external_offer_id', $order->offer_id)
                            ->with('variant')
                            ->first();
                        if ($link && $link->variant && $link->variant->purchase_price) {
                            $purchasePrice = $link->variant->purchase_price;
                            $fromInternal = true;
                        }
                    }

                    // 2. Если не нашли - пробуем через barcode
                    if (!$purchasePrice && $order->barcode) {
                        $link = \App\Models\VariantMarketplaceLink::where('marketplace_account_id', $order->marketplace_account_id)
                            ->where('marketplace_barcode', $order->barcode)
                            ->with('variant')
                            ->first();
                        if ($link && $link->variant && $link->variant->purchase_price) {
                            $purchasePrice = $link->variant->purchase_price;
                            $fromInternal = true;
                        }
                    }

                    // 3. Если связи нет - берём из маркетплейса
                    if (!$purchasePrice && $order->purchase_price) {
                        $purchasePrice = (float) $order->purchase_price;
                        $fromInternal = false;
                    }

                    if ($purchasePrice) {
                        $uzumCogs += (float) $purchasePrice;
                        $uzumWithCogs++;
                        if ($fromInternal) {
                            $uzumFromInternal++;
                        } else {
                            $uzumFromMarketplace++;
                        }
                    }
                }

                if ($uzumItemsCount > 0) {
                    $result['by_marketplace']['uzum'] = [
                        'cogs' => $uzumCogs,
                        'items_count' => $uzumItemsCount,
                        'items_with_cogs' => $uzumWithCogs,
                        'from_internal' => $uzumFromInternal,
                        'from_marketplace' => $uzumFromMarketplace,
                        'revenue' => $uzumRevenue,
                        'margin' => $uzumRevenue - $uzumCogs,
                        'margin_percent' => $uzumRevenue > 0 ? round((($uzumRevenue - $uzumCogs) / $uzumRevenue) * 100, 1) : 0,
                        'currency' => 'UZS',
                        'note' => $uzumWithCogs < $uzumItemsCount ? 'Не все товары имеют закупочную цену' : null,
                    ];
                    $result['total'] += $uzumCogs;
                    $result['total_items'] += $uzumItemsCount;
                    $totalRevenue += $uzumRevenue;
                }
            }
        } catch (\Exception $e) {
            \Log::error('Uzum COGS calculation error', ['error' => $e->getMessage()]);
        }

        // ========== 2. WILDBERRIES COGS ==========
        try {
            if (class_exists(\App\Models\WildberriesOrder::class)) {
                $wbOrders = \App\Models\WildberriesOrder::whereHas('account', fn($q) => $q->where('company_id', $companyId))
                    ->where('is_realization', true)
                    ->where('is_cancel', false)
                    ->where('is_return', false)
                    ->whereDate('order_date', '>=', $from)
                    ->whereDate('order_date', '<=', $to)
                    ->select('barcode', 'nm_id', 'supplier_article', 'for_pay', 'finished_price', 'total_price', 'marketplace_account_id')
                    ->get();

                $wbCogs = 0; // В UZS (себестоимость внутренних товаров хранится в UZS)
                $wbRevenue = 0; // В RUB
                $wbItemsCount = $wbOrders->count();
                $wbWithCogs = 0;
                $wbFromInternal = 0;

                foreach ($wbOrders as $order) {
                    $revenue = (float) ($order->for_pay ?? $order->finished_price ?? $order->total_price ?? 0);
                    $wbRevenue += $revenue;

                    $purchasePrice = null;

                    // 1. Ищем связь с внутренним товаром через barcode
                    if ($order->barcode) {
                        $link = \App\Models\VariantMarketplaceLink::where('marketplace_account_id', $order->marketplace_account_id)
                            ->where('marketplace_barcode', $order->barcode)
                            ->with('variant')
                            ->first();
                        if ($link && $link->variant && $link->variant->purchase_price) {
                            $purchasePrice = $link->variant->purchase_price; // В UZS
                            $wbFromInternal++;
                        }
                    }

                    // 2. Fallback: искать по nm_id через marketplace_product
                    if (!$purchasePrice && $order->nm_id) {
                        $link = \App\Models\VariantMarketplaceLink::where('marketplace_account_id', $order->marketplace_account_id)
                            ->whereHas('marketplaceProduct', function($q) use ($order) {
                                $q->where('external_id', $order->nm_id);
                            })
                            ->with('variant')
                            ->first();
                        if ($link && $link->variant && $link->variant->purchase_price) {
                            $purchasePrice = $link->variant->purchase_price; // В UZS
                            $wbFromInternal++;
                        }
                    }

                    // 3. Fallback: искать по supplier_article (артикул продавца)
                    if (!$purchasePrice && $order->supplier_article) {
                        $variant = \App\Models\ProductVariant::where('company_id', $companyId)
                            ->where('sku', $order->supplier_article)
                            ->first();
                        if ($variant && $variant->purchase_price) {
                            $purchasePrice = $variant->purchase_price; // В UZS
                            $wbFromInternal++;
                        }
                    }

                    if ($purchasePrice) {
                        $wbCogs += (float) $purchasePrice; // В UZS
                        $wbWithCogs++;
                    }
                }

                // Выручка в RUB -> конвертируем в UZS для расчёта маржи
                $wbRevenueUzs = $wbRevenue * $rubToUzs;

                if ($wbItemsCount > 0) {
                    $result['by_marketplace']['wb'] = [
                        'cogs' => $wbCogs, // Уже в UZS
                        'items_count' => $wbItemsCount,
                        'items_with_cogs' => $wbWithCogs,
                        'from_internal' => $wbFromInternal,
                        'revenue' => $wbRevenueUzs,
                        'revenue_rub' => $wbRevenue,
                        'margin' => $wbRevenueUzs - $wbCogs,
                        'margin_percent' => $wbRevenueUzs > 0 ? round((($wbRevenueUzs - $wbCogs) / $wbRevenueUzs) * 100, 1) : 0,
                        'currency' => 'UZS',
                        'note' => $wbWithCogs < $wbItemsCount ? 'Не все товары связаны с внутренними' : null,
                    ];
                    $result['total'] += $wbCogs;
                    $result['total_items'] += $wbItemsCount;
                    $totalRevenue += $wbRevenueUzs;
                }
            }
        } catch (\Exception $e) {
            \Log::error('WB COGS calculation error', ['error' => $e->getMessage()]);
        }

        // ========== 3. OZON COGS ==========
        try {
            if (class_exists(\App\Models\OzonOrder::class)) {
                $ozonOrders = \App\Models\OzonOrder::whereHas('account', fn($q) => $q->where('company_id', $companyId))
                    ->whereIn('status', ['delivered', 'completed'])
                    ->whereDate('created_at_ozon', '>=', $from)
                    ->whereDate('created_at_ozon', '<=', $to)
                    ->select('offer_id', 'sku', 'total_price', 'marketplace_account_id')
                    ->get();

                $ozonCogs = 0; // В UZS
                $ozonRevenue = 0; // В RUB
                $ozonItemsCount = $ozonOrders->count();
                $ozonWithCogs = 0;
                $ozonFromInternal = 0;

                foreach ($ozonOrders as $order) {
                    $revenue = (float) ($order->total_price ?? 0);
                    $ozonRevenue += $revenue;

                    $purchasePrice = null;

                    // 1. Ищем связь с внутренним товаром через offer_id
                    if ($order->offer_id) {
                        $link = \App\Models\VariantMarketplaceLink::where('marketplace_account_id', $order->marketplace_account_id)
                            ->where('external_offer_id', $order->offer_id)
                            ->with('variant')
                            ->first();
                        if ($link && $link->variant && $link->variant->purchase_price) {
                            $purchasePrice = $link->variant->purchase_price; // В UZS
                            $ozonFromInternal++;
                        }
                    }

                    // 2. Fallback: искать по sku
                    if (!$purchasePrice && $order->sku) {
                        $variant = \App\Models\ProductVariant::where('company_id', $companyId)
                            ->where('sku', $order->sku)
                            ->first();
                        if ($variant && $variant->purchase_price) {
                            $purchasePrice = $variant->purchase_price; // В UZS
                            $ozonFromInternal++;
                        }
                    }

                    if ($purchasePrice) {
                        $ozonCogs += (float) $purchasePrice;
                        $ozonWithCogs++;
                    }
                }

                // Выручка в RUB -> конвертируем в UZS
                $ozonRevenueUzs = $ozonRevenue * $rubToUzs;

                if ($ozonItemsCount > 0) {
                    $result['by_marketplace']['ozon'] = [
                        'cogs' => $ozonCogs, // Уже в UZS
                        'items_count' => $ozonItemsCount,
                        'items_with_cogs' => $ozonWithCogs,
                        'from_internal' => $ozonFromInternal,
                        'revenue' => $ozonRevenueUzs,
                        'revenue_rub' => $ozonRevenue,
                        'margin' => $ozonRevenueUzs - $ozonCogs,
                        'margin_percent' => $ozonRevenueUzs > 0 ? round((($ozonRevenueUzs - $ozonCogs) / $ozonRevenueUzs) * 100, 1) : 0,
                        'currency' => 'UZS',
                        'note' => $ozonWithCogs < $ozonItemsCount ? 'Не все товары связаны с внутренними' : null,
                    ];
                    $result['total'] += $ozonCogs;
                    $result['total_items'] += $ozonItemsCount;
                    $totalRevenue += $ozonRevenueUzs;
                }
            }
        } catch (\Exception $e) {
            \Log::error('Ozon COGS calculation error', ['error' => $e->getMessage()]);
        }

        // ========== 4. РУЧНЫЕ ПРОДАЖИ (Sale) COGS ==========
        try {
            if (class_exists(\App\Models\Sale::class)) {
                $offlineSales = \App\Models\Sale::byCompany($companyId)
                    ->where('type', 'manual')
                    ->where('status', 'completed')
                    ->whereDate('created_at', '>=', $from)
                    ->whereDate('created_at', '<=', $to)
                    ->with(['items.productVariant'])
                    ->get();

                $totalOfflineCogs = 0;
                $totalOfflineRevenue = 0;
                $offlineItemsCount = 0;
                $offlineWithCogs = 0;
                $offlineFromInternal = 0;
                $offlineFromSaleItem = 0;

                foreach ($offlineSales as $sale) {
                    $totalOfflineRevenue += $sale->total_amount;
                    foreach ($sale->items as $item) {
                        $offlineItemsCount++;
                        $costPrice = null;

                        // 1. СНАЧАЛА ищем себестоимость из связанного внутреннего товара
                        if ($item->productVariant && $item->productVariant->purchase_price) {
                            $costPrice = (float) $item->productVariant->purchase_price;
                            $offlineFromInternal++;
                        }
                        // 2. Если нет - берём из SaleItem.cost_price
                        elseif ($item->cost_price) {
                            $costPrice = (float) $item->cost_price;
                            $offlineFromSaleItem++;
                        }

                        if ($costPrice) {
                            $totalOfflineCogs += $costPrice * $item->quantity;
                            $offlineWithCogs++;
                        }
                    }
                }

                if ($offlineItemsCount > 0) {
                    $result['by_marketplace']['offline'] = [
                        'cogs' => $totalOfflineCogs,
                        'items_count' => $offlineItemsCount,
                        'items_with_cogs' => $offlineWithCogs,
                        'from_internal' => $offlineFromInternal,
                        'from_sale_item' => $offlineFromSaleItem,
                        'revenue' => $totalOfflineRevenue,
                        'margin' => $totalOfflineRevenue - $totalOfflineCogs,
                        'margin_percent' => $totalOfflineRevenue > 0 ? round((($totalOfflineRevenue - $totalOfflineCogs) / $totalOfflineRevenue) * 100, 1) : 0,
                        'currency' => 'UZS',
                        'note' => $offlineWithCogs < $offlineItemsCount ? 'Не все товары имеют закупочную цену' : null,
                    ];
                    $result['total'] += $totalOfflineCogs;
                    $result['total_items'] += $offlineItemsCount;
                    $totalRevenue += $totalOfflineRevenue;
                }
            }
        } catch (\Exception $e) {
            \Log::error('Offline COGS calculation error', ['error' => $e->getMessage()]);
        }

        // Рассчитываем общую маржу
        $result['gross_margin'] = $totalRevenue - $result['total'];
        $result['margin_percent'] = $totalRevenue > 0 ? round((($totalRevenue - $result['total']) / $totalRevenue) * 100, 1) : 0;
        $result['total_revenue'] = $totalRevenue;

        return $result;
    }
}
