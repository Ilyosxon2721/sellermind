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
use App\Support\ApiResponder;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FinanceController extends Controller
{
    use ApiResponder;

    public function __construct(protected FinanceReportService $reportService)
    {
    }

    public function overview(Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (!$companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $from = $request->from ? Carbon::parse($request->from) : now()->startOfMonth();
        $to = $request->to ? Carbon::parse($request->to) : now()->endOfMonth();

        $transactions = FinanceTransaction::byCompany($companyId)
            ->confirmed()
            ->inPeriod($from, $to);

        $totalIncome = (clone $transactions)->income()->sum('amount');
        $totalExpense = (clone $transactions)->expense()->sum('amount');
        $netProfit = $totalIncome - $totalExpense;

        // Долги
        $debtsReceivable = FinanceDebt::byCompany($companyId)->receivable()->active()->sum('amount_outstanding');
        $debtsPayable = FinanceDebt::byCompany($companyId)->payable()->active()->sum('amount_outstanding');

        // Просроченные долги
        $overdueReceivable = FinanceDebt::byCompany($companyId)->receivable()->overdue()->sum('amount_outstanding');
        $overduePayable = FinanceDebt::byCompany($companyId)->payable()->overdue()->sum('amount_outstanding');

        // ========== ОСТАТКИ НА СКЛАДАХ ==========
        $stockData = $this->getStockSummary($companyId);

        // ========== ТОВАРЫ В ТРАНЗИТАХ ==========
        $transitData = $this->getTransitSummary($companyId);

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

        // ========== ИТОГОВЫЙ БАЛАНС КОМПАНИИ ==========
        $balance = $this->calculateCompanyBalance(
            $stockData,
            $transitData,
            $debtsReceivable,
            $debtsPayable,
            $unpaidTaxes,
            $currentSalary
        );

        return $this->successResponse([
            'period' => [
                'from' => $from->format('Y-m-d'),
                'to' => $to->format('Y-m-d'),
            ],
            'summary' => [
                'total_income' => $totalIncome,
                'total_expense' => $totalExpense,
                'net_profit' => $netProfit,
            ],
            'balance' => $balance,
            'stock' => $stockData,
            'transit' => $transitData,
            'debts' => [
                'receivable' => $debtsReceivable,
                'payable' => $debtsPayable,
                'overdue_receivable' => $overdueReceivable,
                'overdue_payable' => $overduePayable,
                'net' => $debtsReceivable - $debtsPayable, // Чистая позиция по долгам
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
     * Получить сводку по остаткам на складах
     */
    protected function getStockSummary(int $companyId): array
    {
        // Суммируем все движения по складам для получения текущих остатков
        try {
            $stockSummary = StockLedger::byCompany($companyId)
                ->selectRaw('SUM(qty_delta) as total_qty, SUM(cost_delta) as total_cost')
                ->first();

            // Группировка по складам
            $byWarehouse = StockLedger::byCompany($companyId)
                ->join('warehouses', 'stock_ledger.warehouse_id', '=', 'warehouses.id')
                ->selectRaw('warehouses.id, warehouses.name, SUM(qty_delta) as qty, SUM(cost_delta) as cost')
                ->groupBy('warehouses.id', 'warehouses.name')
                ->having('qty', '>', 0)
                ->get();

            return [
                'total_qty' => (float) ($stockSummary?->total_qty ?? 0),
                'total_cost' => (float) ($stockSummary?->total_cost ?? 0),
                'by_warehouse' => $byWarehouse->map(fn($w) => [
                    'id' => $w->id,
                    'name' => $w->name,
                    'qty' => (float) $w->qty,
                    'cost' => (float) $w->cost,
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
     */
    protected function getTransitSummary(int $companyId): array
    {
        $result = [
            'orders_in_transit' => [
                'count' => 0,
                'amount' => 0,
            ],
            'purchases_in_transit' => [
                'count' => 0,
                'amount' => 0,
            ],
            'total_amount' => 0,
        ];

        // 1. Заказы в пути на маркетплейсах (PROCESSING без date_issued для Uzum, и т.п.)
        try {
            // Uzum заказы в пути
            if (class_exists(\App\Models\UzumFinanceOrder::class)) {
                $uzumTransit = \App\Models\UzumFinanceOrder::whereHas('account', fn($q) => $q->where('company_id', $companyId))
                    ->where('status', 'PROCESSING')
                    ->whereNull('date_issued')
                    ->selectRaw('COUNT(*) as cnt, SUM(sell_price * amount) as total')
                    ->first();

                $result['orders_in_transit']['count'] += (int) ($uzumTransit?->cnt ?? 0);
                $result['orders_in_transit']['amount'] += (float) ($uzumTransit?->total ?? 0);
            }

            // WB заказы в пути
            if (class_exists(\App\Models\WildberriesOrder::class)) {
                $wbTransit = \App\Models\WildberriesOrder::whereHas('account', fn($q) => $q->where('company_id', $companyId))
                    ->where('is_realization', false)
                    ->where('is_cancel', false)
                    ->selectRaw('COUNT(*) as cnt, SUM(COALESCE(for_pay, finished_price, total_price, 0)) as total')
                    ->first();

                $result['orders_in_transit']['count'] += (int) ($wbTransit?->cnt ?? 0);
                $result['orders_in_transit']['amount'] += (float) ($wbTransit?->total ?? 0);
            }

            // Ozon заказы в пути
            if (class_exists(\App\Models\OzonOrder::class)) {
                $ozonTransit = \App\Models\OzonOrder::whereHas('account', fn($q) => $q->where('company_id', $companyId))
                    ->inTransit()
                    ->selectRaw('COUNT(*) as cnt, SUM(COALESCE(total_price, 0)) as total')
                    ->first();

                $result['orders_in_transit']['count'] += (int) ($ozonTransit?->cnt ?? 0);
                $result['orders_in_transit']['amount'] += (float) ($ozonTransit?->total ?? 0);
            }
        } catch (\Exception $e) {
            // Ignore errors if models don't exist
        }

        // 2. Закупки в пути (неоплаченные/частично оплаченные инвойсы)
        try {
            if (class_exists(SupplierInvoice::class)) {
                $purchaseTransit = SupplierInvoice::byCompany($companyId)
                    ->whereIn('status', ['confirmed', 'partially_paid'])
                    ->selectRaw('COUNT(*) as cnt, SUM(total_amount - amount_paid) as total')
                    ->first();

                $result['purchases_in_transit']['count'] = (int) ($purchaseTransit?->cnt ?? 0);
                $result['purchases_in_transit']['amount'] = (float) ($purchaseTransit?->total ?? 0);
            }
        } catch (\Exception $e) {
            // Ignore errors if table doesn't exist
        }

        $result['total_amount'] = $result['orders_in_transit']['amount'] + $result['purchases_in_transit']['amount'];

        return $result;
    }

    /**
     * Рассчитать итоговый баланс компании
     *
     * Активы = Остатки на складах + Товары в пути + Дебиторка
     * Пассивы = Кредиторка + Неоплаченные налоги + Неоплаченная зарплата
     * Чистый баланс = Активы - Пассивы
     */
    protected function calculateCompanyBalance(
        array $stockData,
        array $transitData,
        float $debtsReceivable,
        float $debtsPayable,
        float $unpaidTaxes,
        ?SalaryCalculation $currentSalary
    ): array {
        // Активы
        $stockValue = $stockData['total_cost'] ?? 0;
        $transitValue = $transitData['total_amount'] ?? 0;
        $totalAssets = $stockValue + $transitValue + $debtsReceivable;

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
                'stock_value' => $stockValue,
                'transit_value' => $transitValue,
                'receivables' => $debtsReceivable,
                'total' => $totalAssets,
            ],
            'liabilities' => [
                'payables' => $debtsPayable,
                'unpaid_taxes' => $unpaidTaxes,
                'unpaid_salary' => $unpaidSalary,
                'total' => $totalLiabilities,
            ],
            'net_balance' => $netBalance,
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
            'tax_system' => ['nullable', 'in:simplified,general,both'],
            'vat_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'income_tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'social_tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'auto_import_marketplace_fees' => ['nullable', 'boolean'],
        ]);

        $settings = FinanceSettings::getForCompany($companyId);
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
}
