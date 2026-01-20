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

        // Uzum продажи (TO_WITHDRAW или PROCESSING с date_issued)
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
                    ->selectRaw('COUNT(*) as cnt, SUM(sell_price * amount) as revenue, SUM(seller_profit) as profit')
                    ->first();

                $result['uzum'] = [
                    'orders' => (int) ($uzumSales?->cnt ?? 0),
                    'revenue' => (float) ($uzumSales?->revenue ?? 0),
                    'profit' => (float) ($uzumSales?->profit ?? 0),
                ];
            }
        } catch (\Exception $e) {}

        // WB продажи (is_realization = true)
        try {
            if (class_exists(\App\Models\WildberriesOrder::class)) {
                $wbSales = \App\Models\WildberriesOrder::whereHas('account', fn($q) => $q->where('company_id', $companyId))
                    ->where('is_realization', true)
                    ->where('is_cancel', false)
                    ->whereDate('date', '>=', $from)
                    ->whereDate('date', '<=', $to)
                    ->selectRaw('COUNT(*) as cnt, SUM(COALESCE(for_pay, finished_price, 0)) as revenue')
                    ->first();

                $revenueRub = (float) ($wbSales?->revenue ?? 0);
                $result['wb'] = [
                    'orders' => (int) ($wbSales?->cnt ?? 0),
                    'revenue' => $revenueRub * $rubToUzs,
                    'revenue_rub' => $revenueRub,
                    'profit' => 0,
                ];
            }
        } catch (\Exception $e) {}

        // Ozon продажи
        try {
            if (class_exists(\App\Models\OzonOrder::class)) {
                $ozonSales = \App\Models\OzonOrder::whereHas('account', fn($q) => $q->where('company_id', $companyId))
                    ->where('status', 'delivered')
                    ->whereDate('created_at', '>=', $from)
                    ->whereDate('created_at', '<=', $to)
                    ->selectRaw('COUNT(*) as cnt, SUM(total_price) as revenue')
                    ->first();

                $revenueRub = (float) ($ozonSales?->revenue ?? 0);
                $result['ozon'] = [
                    'orders' => (int) ($ozonSales?->cnt ?? 0),
                    'revenue' => $revenueRub * $rubToUzs,
                    'revenue_rub' => $revenueRub,
                    'profit' => 0,
                ];
            }
        } catch (\Exception $e) {}

        // Итого
        $result['total_orders'] = $result['uzum']['orders'] + $result['wb']['orders'] + $result['ozon']['orders'];
        $result['total_revenue'] = $result['uzum']['revenue'] + $result['wb']['revenue'] + $result['ozon']['revenue'];
        $result['total_profit'] = $result['uzum']['profit'] + $result['wb']['profit'] + $result['ozon']['profit'];

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

        $from = $request->from ? Carbon::parse($request->from) : now()->startOfMonth();
        $to = $request->to ? Carbon::parse($request->to) : now()->endOfMonth();

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

        // Uzum expenses
        $uzumAccounts = MarketplaceAccount::where('company_id', $companyId)
            ->where('marketplace', 'uzum')
            ->where('is_active', true)
            ->get();

        foreach ($uzumAccounts as $account) {
            try {
                $expenses = $this->uzumClient->getExpensesSummary($account, $from, $to);
                $result['uzum'] = $expenses;

                // Add to totals
                $result['total']['commission'] += $expenses['commission'] ?? 0;
                $result['total']['logistics'] += $expenses['logistics'] ?? 0;
                $result['total']['storage'] += $expenses['storage'] ?? 0;
                $result['total']['advertising'] += $expenses['advertising'] ?? 0;
                $result['total']['penalties'] += $expenses['penalties'] ?? 0;
                $result['total']['returns'] += $expenses['returns'] ?? 0;
                $result['total']['other'] += $expenses['other'] ?? 0;
                $result['total']['total'] += $expenses['total'] ?? 0;
            } catch (\Exception $e) {
                $result['uzum'] = ['error' => $e->getMessage()];
            }
        }

        // WB expenses (from detailed report / реализация)
        $financeSettings = FinanceSettings::getForCompany($companyId);
        $rubToUzs = $financeSettings->rub_rate ?? 140;

        $wbAccounts = MarketplaceAccount::where('company_id', $companyId)
            ->where('marketplace', 'wildberries')
            ->where('is_active', true)
            ->get();

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

        foreach ($wbAccounts as $account) {
            try {
                $httpClient = new WildberriesHttpClient($account);
                $financeService = new WildberriesFinanceService($httpClient);

                $reportData = $financeService->getFullDetailedReport($account, $from, $to);
                $summary = $financeService->calculateSummary($reportData);

                // Map WB fields to standard expense categories (all in RUB)
                $wbTotalExpenses['commission'] += $summary['total_commission'] ?? 0;
                $wbTotalExpenses['logistics'] += $summary['total_logistics'] ?? 0;
                $wbTotalExpenses['penalties'] += $summary['total_penalty'] ?? 0;
                $wbTotalExpenses['returns'] += $summary['total_returns'] ?? 0;

                $accountTotal = ($summary['total_commission'] ?? 0)
                    + ($summary['total_logistics'] ?? 0)
                    + ($summary['total_penalty'] ?? 0);

                $wbTotalExpenses['total'] += $accountTotal;
            } catch (\Exception $e) {
                if (!isset($result['wb']['error'])) {
                    $result['wb'] = ['error' => $e->getMessage()];
                }
            }
        }

        // Convert WB totals to UZS and add to result
        if ($wbTotalExpenses['total'] > 0 || !isset($result['wb']['error'])) {
            $wbTotalExpenses['total_uzs'] = $wbTotalExpenses['total'] * $rubToUzs;
            $result['wb'] = $wbTotalExpenses;

            // Add WB expenses to totals (convert RUB to UZS)
            $result['total']['commission'] += $wbTotalExpenses['commission'] * $rubToUzs;
            $result['total']['logistics'] += $wbTotalExpenses['logistics'] * $rubToUzs;
            $result['total']['penalties'] += $wbTotalExpenses['penalties'] * $rubToUzs;
            $result['total']['returns'] += $wbTotalExpenses['returns'] * $rubToUzs;
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
