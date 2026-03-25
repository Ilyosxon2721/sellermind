<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\CreateFinanceCategoryRequest;
use App\Http\Requests\Finance\UpdateFinanceSettingsRequest;
use App\Models\AP\SupplierInvoice;
use App\Models\Company;
use App\Models\Finance\FinanceCategory;
use App\Models\Finance\FinanceDebt;
use App\Models\Finance\FinanceSettings;
use App\Models\Finance\FinanceTransaction;
use App\Models\Finance\SalaryCalculation;
use App\Models\Finance\TaxCalculation;
use App\Models\MarketplaceAccount;
use App\Models\MarketplaceExpenseCache;
use App\Models\Warehouse\StockLedger;
use App\Services\CurrencyConversionService;
use App\Services\Finance\FinanceReportService;
use App\Services\Finance\InventoryValuationService;
use App\Services\Finance\MarketplaceSalesService;
use App\Services\Marketplaces\OzonClient;
use App\Services\Marketplaces\UzumClient;
use App\Services\Marketplaces\Wildberries\WildberriesFinanceService;
use App\Services\Marketplaces\Wildberries\WildberriesHttpClient;
use App\Support\ApiResponder;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FinanceController extends Controller
{
    use ApiResponder;

    public function __construct(
        protected FinanceReportService $reportService,
        protected UzumClient $uzumClient,
        protected OzonClient $ozonClient,
        protected CurrencyConversionService $currencyService,
        protected MarketplaceSalesService $marketplaceSalesService,
        protected InventoryValuationService $inventoryValuationService,
    ) {}

    public function overview(Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (! $companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $from = $request->from ? Carbon::parse($request->from) : now()->startOfMonth();
        $to = $request->to ? Carbon::parse($request->to) : now()->endOfMonth();

        // Получаем настройки финансов для курсов валют
        $financeSettings = FinanceSettings::getForCompany($companyId);

        // ========== ВАЛЮТА ОТОБРАЖЕНИЯ ==========
        $company = Company::find($companyId);
        $currencyService = $this->currencyService->forCompany($company);
        $displayCurrency = $currencyService->getDisplayCurrency();
        $baseCurrency = 'UZS'; // Все данные хранятся в UZS

        $transactions = FinanceTransaction::byCompany($companyId)
            ->confirmed()
            ->inPeriod($from, $to);

        // Суммы в базовой валюте (UZS)
        $totalIncomeBase = (clone $transactions)->income()->sum('amount_base');
        $totalExpenseBase = (clone $transactions)->expense()->sum('amount_base');

        // ========== ПРОДАЖИ МАРКЕТПЛЕЙСОВ ==========
        $marketplaceSalesBase = $this->getMarketplaceSales($companyId, $from, $to, $financeSettings);
        $totalIncomeBase += $marketplaceSalesBase['total_revenue'];

        // Конвертируем продажи в выбранную валюту для отображения
        $marketplaceSales = $this->convertMarketplaceSales($marketplaceSalesBase, $currencyService, $baseCurrency, $displayCurrency);

        // Расходы маркетплейсов теперь хранятся в FinanceTransaction
        // и автоматически включаются в $totalExpense

        // ========== СЕБЕСТОИМОСТЬ ПРОДАННЫХ ТОВАРОВ ==========
        $rubToUzs = $financeSettings->rub_rate ?? 140;
        $cogs = $this->calculateCogs($companyId, $from, $to, $rubToUzs);
        $totalCogsBase = $cogs['total'] ?? 0;

        // Чистая прибыль = Доходы - Расходы - Себестоимость (в базовой валюте)
        $netProfitBase = $totalIncomeBase - $totalExpenseBase - $totalCogsBase;

        // ========== КОНВЕРТАЦИЯ В ВЫБРАННУЮ ВАЛЮТУ ==========
        $totalIncome = $currencyService->convert($totalIncomeBase, $baseCurrency, $displayCurrency);
        $totalExpense = $currencyService->convert($totalExpenseBase, $baseCurrency, $displayCurrency);
        $totalCogs = $currencyService->convert($totalCogsBase, $baseCurrency, $displayCurrency);
        $netProfit = $currencyService->convert($netProfitBase, $baseCurrency, $displayCurrency);

        // ========== ДОЛГИ (в оригинальных валютах) ==========
        // Группируем долги по валютам, чтобы показывать в оригинальной валюте
        $debtsReceivableByCurrency = FinanceDebt::byCompany($companyId)
            ->receivable()
            ->active()
            ->selectRaw('currency_code, SUM(amount_outstanding) as total')
            ->groupBy('currency_code')
            ->get()
            ->mapWithKeys(fn ($d) => [$d->currency_code ?? 'UZS' => (float) $d->total])
            ->toArray();

        $debtsPayableByCurrency = FinanceDebt::byCompany($companyId)
            ->payable()
            ->active()
            ->selectRaw('currency_code, SUM(amount_outstanding) as total')
            ->groupBy('currency_code')
            ->get()
            ->mapWithKeys(fn ($d) => [$d->currency_code ?? 'UZS' => (float) $d->total])
            ->toArray();

        $overdueReceivableByCurrency = FinanceDebt::byCompany($companyId)
            ->receivable()
            ->overdue()
            ->selectRaw('currency_code, SUM(amount_outstanding) as total')
            ->groupBy('currency_code')
            ->get()
            ->mapWithKeys(fn ($d) => [$d->currency_code ?? 'UZS' => (float) $d->total])
            ->toArray();

        $overduePayableByCurrency = FinanceDebt::byCompany($companyId)
            ->payable()
            ->overdue()
            ->selectRaw('currency_code, SUM(amount_outstanding) as total')
            ->groupBy('currency_code')
            ->get()
            ->mapWithKeys(fn ($d) => [$d->currency_code ?? 'UZS' => (float) $d->total])
            ->toArray();

        // Также считаем общие суммы в displayCurrency для баланса
        $debtsReceivableConverted = 0;
        $debtsPayableConverted = 0;
        foreach ($debtsReceivableByCurrency as $cur => $amount) {
            $debtsReceivableConverted += $currencyService->convert($amount, $cur, $displayCurrency);
        }
        foreach ($debtsPayableByCurrency as $cur => $amount) {
            $debtsPayableConverted += $currencyService->convert($amount, $cur, $displayCurrency);
        }

        // ========== ОСТАТКИ НА СКЛАДАХ ==========
        $stockData = $this->getStockSummary($companyId, $financeSettings, $currencyService, $displayCurrency);

        // ========== ТОВАРЫ В ТРАНЗИТАХ ==========
        $transitData = $this->getTransitSummary($companyId, $financeSettings, $currencyService, $displayCurrency);

        // Зарплата текущего месяца
        $currentSalary = SalaryCalculation::byCompany($companyId)
            ->forPeriod(now()->year, now()->month)
            ->first();
        $currentSalaryNet = $currentSalary ? $currencyService->convert($currentSalary->total_net ?? 0, $baseCurrency, $displayCurrency) : 0;

        // Налоги (в базовой валюте)
        $unpaidTaxesBase = TaxCalculation::byCompany($companyId)
            ->unpaid()
            ->sum('calculated_amount');
        $unpaidTaxes = $currencyService->convert($unpaidTaxesBase, $baseCurrency, $displayCurrency);

        // Расходы по категориям (конвертируем каждую категорию)
        $expensesByCategoryBase = $this->reportService->getExpensesByCategory($companyId, $from, $to);
        $expensesByCategory = $this->convertExpensesByCategory($expensesByCategoryBase, $currencyService, $baseCurrency, $displayCurrency);

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
        $cashBalance = $this->getCashBalance($companyId, $currencyService, $displayCurrency);

        // ========== ИТОГОВЫЙ БАЛАНС КОМПАНИИ ==========
        $balance = $this->calculateCompanyBalance(
            $stockData,
            $transitData,
            $debtsReceivableConverted,
            $debtsPayableConverted,
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
                'display' => $displayCurrency,
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
                // Долги в оригинальных валютах
                'receivable_by_currency' => $debtsReceivableByCurrency,
                'payable_by_currency' => $debtsPayableByCurrency,
                'overdue_receivable_by_currency' => $overdueReceivableByCurrency,
                'overdue_payable_by_currency' => $overduePayableByCurrency,
                // Конвертированные суммы для итогов
                'receivable_converted' => $debtsReceivableConverted,
                'payable_converted' => $debtsPayableConverted,
                'net_converted' => $debtsReceivableConverted - $debtsPayableConverted,
            ],
            'salary' => [
                'current_period' => $currentSalary?->period_label,
                'total_net' => $currentSalaryNet,
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
     *
     * ВАЖНО: Используем ту же логику, что и SalesController для единообразия данных.
     *
     * Uzum: Продажа = TO_WITHDRAW или (PROCESSING + date_issued NOT NULL)
     *       Фильтрация по date_issued (дата выкупа) для продаж
     *
     * WB: Продажа = is_realization=true AND is_cancel=false
     *     Фильтрация по last_change_date для продаж
     *
     * Ozon: Продажа = stock_status='sold' AND stock_sold_at NOT NULL
     *       Фильтрация по stock_sold_at (дата продажи)
     *
     * YM: Продажа = stock_status='sold' AND stock_sold_at NOT NULL
     *     Фильтрация по stock_sold_at (дата продажи)
     */
    protected function getMarketplaceSales(int $companyId, Carbon $from, Carbon $to, FinanceSettings $settings): array
    {
        return $this->marketplaceSalesService->getSales($companyId, $from, $to, $settings);
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
    protected function getStockSummary(int $companyId, FinanceSettings $settings, $currencyService = null, string $displayCurrency = 'UZS'): array
    {
        return $this->inventoryValuationService->getStockSummary($companyId, $settings, $currencyService, $displayCurrency);
    }


    /**
     * Получить сводку по товарам в транзитах
     * Все суммы конвертируются в выбранную валюту
     */
    protected function getTransitSummary(int $companyId, FinanceSettings $settings, $currencyService = null, string $displayCurrency = 'UZS'): array
    {
        return $this->inventoryValuationService->getTransitSummary($companyId, $settings, $currencyService, $displayCurrency);
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
     * Конвертировать расходы по категориям в выбранную валюту
     */
    protected function convertExpensesByCategory($expensesByCategory, $currencyService, string $baseCurrency, string $displayCurrency): array
    {
        if (! $currencyService || $displayCurrency === $baseCurrency) {
            return $expensesByCategory instanceof \Illuminate\Support\Collection
                ? $expensesByCategory->toArray()
                : (array) $expensesByCategory;
        }

        $result = [];
        $items = $expensesByCategory instanceof \Illuminate\Support\Collection
            ? $expensesByCategory->toArray()
            : (array) $expensesByCategory;

        foreach ($items as $item) {
            $amount = $item['amount'] ?? $item['total'] ?? 0;
            $converted = [
                'category' => $item['category'] ?? $item['category_name'] ?? $item['name'] ?? '',
                'amount' => $currencyService->convert($amount, $baseCurrency, $displayCurrency),
            ];
            $result[] = $converted;
        }

        return $result;
    }

    /**
     * Конвертировать продажи маркетплейсов в выбранную валюту
     */
    protected function convertMarketplaceSales(array $sales, $currencyService, string $baseCurrency, string $displayCurrency): array
    {
        if (! $currencyService || $displayCurrency === $baseCurrency) {
            return $sales;
        }

        $result = $sales;

        // Конвертируем Uzum (уже в UZS)
        $result['uzum']['revenue'] = $currencyService->convert($sales['uzum']['revenue'] ?? 0, $baseCurrency, $displayCurrency);
        $result['uzum']['profit'] = $currencyService->convert($sales['uzum']['profit'] ?? 0, $baseCurrency, $displayCurrency);

        // Конвертируем WB (конвертировано из RUB в UZS)
        $result['wb']['revenue'] = $currencyService->convert($sales['wb']['revenue'] ?? 0, $baseCurrency, $displayCurrency);
        $result['wb']['profit'] = $currencyService->convert($sales['wb']['profit'] ?? 0, $baseCurrency, $displayCurrency);

        // Конвертируем Ozon (конвертировано из RUB в UZS)
        $result['ozon']['revenue'] = $currencyService->convert($sales['ozon']['revenue'] ?? 0, $baseCurrency, $displayCurrency);
        $result['ozon']['profit'] = $currencyService->convert($sales['ozon']['profit'] ?? 0, $baseCurrency, $displayCurrency);

        // Конвертируем YM (конвертировано из RUB в UZS)
        if (isset($sales['ym'])) {
            $result['ym']['revenue'] = $currencyService->convert($sales['ym']['revenue'] ?? 0, $baseCurrency, $displayCurrency);
            $result['ym']['profit'] = $currencyService->convert($sales['ym']['profit'] ?? 0, $baseCurrency, $displayCurrency);
        }

        // Конвертируем итоги
        $result['total_revenue'] = $currencyService->convert($sales['total_revenue'] ?? 0, $baseCurrency, $displayCurrency);
        $result['total_profit'] = $currencyService->convert($sales['total_profit'] ?? 0, $baseCurrency, $displayCurrency);

        return $result;
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
            ->sum('amount_base');

        $transactionExpense = FinanceTransaction::byCompany($companyId)
            ->confirmed()
            ->inPeriod($from, $to)
            ->expense()
            ->sum('amount_base');

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
     * Каждый счёт показывается в своей валюте, total конвертируется в displayCurrency
     */
    protected function getCashBalance(int $companyId, $currencyService = null, string $displayCurrency = 'UZS'): array
    {
        try {
            // Проверяем есть ли модель CashAccount
            if (! class_exists(\App\Models\Finance\CashAccount::class)) {
                return ['total' => 0, 'accounts' => []];
            }

            $accounts = \App\Models\Finance\CashAccount::where('company_id', $companyId)
                ->where('is_active', true)
                ->get();

            // Каждый счёт показываем в своей валюте
            $accountsData = $accounts->map(fn ($a) => [
                'id' => $a->id,
                'name' => $a->name,
                'type' => $a->type,
                'balance' => $a->balance,
                'currency_code' => $a->currency_code,
                'marketplace' => $a->marketplace,
            ])->toArray();

            // Total конвертируем в выбранную валюту
            $totalConverted = 0;
            foreach ($accounts as $account) {
                $accountCurrency = $account->currency_code ?? 'UZS';
                if ($currencyService) {
                    $totalConverted += $currencyService->convert($account->balance, $accountCurrency, $displayCurrency);
                } else {
                    $totalConverted += $account->balance;
                }
            }

            return [
                'total' => $totalConverted,
                'accounts' => $accountsData,
            ];
        } catch (\Exception $e) {
            Log::error('Ошибка получения баланса денежных счетов', ['company_id' => $companyId, 'error' => $e->getMessage()]);

            return ['total' => 0, 'accounts' => []];
        }
    }

    public function categories(Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (! $companyId) {
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
        if (! $companyId) {
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

    public function storeCategory(CreateFinanceCategoryRequest $request)
    {
        $companyId = Auth::user()?->company_id;
        if (! $companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $data = $request->validated();

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
            'code' => 'CUSTOM_'.strtoupper(str_replace(' ', '_', $data['name'])).'_'.time(),
            'is_system' => false,
            'is_active' => true,
            'sort_order' => 999,
        ]);

        return $this->successResponse($category, 201);
    }

    public function settings(Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (! $companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $settings = FinanceSettings::getForCompany($companyId);

        return $this->successResponse($settings);
    }

    public function updateSettings(UpdateFinanceSettingsRequest $request)
    {
        $companyId = Auth::user()?->company_id;
        if (! $companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $data = $request->validated();

        $settings = FinanceSettings::getForCompany($companyId);

        // If currency rates are being updated, set the rates_updated_at timestamp
        if (isset($data['usd_rate']) || isset($data['rub_rate']) || isset($data['eur_rate'])) {
            $data['rates_updated_at'] = now();
        }

        $settings->update(array_filter($data, fn ($v) => $v !== null));

        return $this->successResponse($settings->fresh());
    }

    public function reports(Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (! $companyId) {
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
        if (! $companyId) {
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
        if ($useCache && ! $forceRefresh) {
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
                    ->where(function ($q) use ($from, $to) {
                        $q->where(function ($sub) use ($from, $to) {
                            $sub->whereNotNull('date_issued')
                                ->whereDate('date_issued', '>=', $from)
                                ->whereDate('date_issued', '<=', $to);
                        })
                            ->orWhere(function ($sub) use ($from, $to) {
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
        if ($uzumTotalExpenses['total'] > 0 || ! empty($uzumAccounts)) {
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
                Log::error('Ошибка получения расходов Ozon', ['account_id' => $account->id, 'error' => $e->getMessage()]);
                if (! isset($result['ozon']['error'])) {
                    $result['ozon'] = ['error' => $e->getMessage()];
                }
            }
        }

        // Convert Ozon totals to UZS and add to result
        if ($ozonTotalExpenses['total'] > 0 || ! isset($result['ozon']['error'])) {
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
        if (! $companyId) {
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
        // Используем ту же логику что и SalesController:
        // 1. Приоритет — UzumFinanceOrder (FBO/FBS/DBS/EDBS — полные данные)
        // 2. Fallback — UzumOrder (FBS — оперативные), исключая дубликаты
        //
        // Статусы UzumFinanceOrder:
        //   TO_WITHDRAW / (PROCESSING + date_issued) → sold (продано)
        //   PROCESSING + date_issued NULL → transit (в пути)
        //   CANCELED + date_issued → returned (возврат после выкупа)
        //   CANCELED + date_issued NULL → cancelled (отмена до выкупа)
        try {
            $ordersCount = 0;
            $ordersAmount = 0;
            $soldCount = 0;
            $soldAmount = 0;
            $returnsCount = 0;
            $returnsAmount = 0;
            $cancelledCount = 0;
            $cancelledAmount = 0;
            $financeOrderNumbers = [];

            // 1. UzumFinanceOrder — основной источник
            $hasFinanceOrders = class_exists(\App\Models\UzumFinanceOrder::class)
                && \App\Models\UzumFinanceOrder::whereHas('account', fn ($q) => $q->where('company_id', $companyId))->exists();

            if ($hasFinanceOrders) {
                // Базовый запрос с фильтрацией по дате (как в SalesController)
                $finQuery = fn () => \App\Models\UzumFinanceOrder::whereHas('account', fn ($q) => $q->where('company_id', $companyId))
                    ->where(function ($q) use ($from, $to) {
                        // Заказы с date_issued — фильтруем по date_issued
                        $q->where(function ($sub) use ($from, $to) {
                            $sub->whereNotNull('date_issued')
                                ->whereDate('date_issued', '>=', $from)
                                ->whereDate('date_issued', '<=', $to);
                        })
                        // Заказы без date_issued — фильтруем по order_date
                            ->orWhere(function ($sub) use ($from, $to) {
                                $sub->whereNull('date_issued')
                                    ->whereDate('order_date', '>=', $from)
                                    ->whereDate('order_date', '<=', $to);
                            });
                    });

                // Все заказы
                $finAll = $finQuery()
                    ->selectRaw('COUNT(*) as cnt, SUM(sell_price * amount) as total_amount')
                    ->first();
                $ordersCount += (int) ($finAll?->cnt ?? 0);
                $ordersAmount += (float) ($finAll?->total_amount ?? 0);

                // Продано: TO_WITHDRAW или (PROCESSING + date_issued NOT NULL)
                $finSold = $finQuery()
                    ->where(function ($q) {
                        $q->where('status', 'TO_WITHDRAW')
                            ->orWhere(function ($sub) {
                                $sub->where('status', 'PROCESSING')
                                    ->whereNotNull('date_issued');
                            });
                    })
                    ->selectRaw('COUNT(*) as cnt, SUM(sell_price * amount) as total_amount')
                    ->first();
                $soldCount += (int) ($finSold?->cnt ?? 0);
                $soldAmount += (float) ($finSold?->total_amount ?? 0);

                // Возвраты: CANCELED + date_issued NOT NULL (возврат после выкупа)
                $finReturns = $finQuery()
                    ->where('status', 'CANCELED')
                    ->whereNotNull('date_issued')
                    ->selectRaw('COUNT(*) as cnt, SUM(sell_price * amount) as total_amount')
                    ->first();
                $returnsCount += (int) ($finReturns?->cnt ?? 0);
                $returnsAmount += (float) ($finReturns?->total_amount ?? 0);

                // Отменённые: CANCELED + date_issued NULL (отмена до выкупа)
                $finCancelled = $finQuery()
                    ->where('status', 'CANCELED')
                    ->whereNull('date_issued')
                    ->selectRaw('COUNT(*) as cnt, SUM(sell_price * amount) as total_amount')
                    ->first();
                $cancelledCount += (int) ($finCancelled?->cnt ?? 0);
                $cancelledAmount += (float) ($finCancelled?->total_amount ?? 0);

                // Собираем order_number для дедупликации с UzumOrder
                $financeOrderNumbers = \App\Models\UzumFinanceOrder::whereHas('account', fn ($q) => $q->where('company_id', $companyId))
                    ->where(function ($q) use ($from, $to) {
                        $q->where(function ($sub) use ($from, $to) {
                            $sub->whereNotNull('date_issued')
                                ->whereDate('date_issued', '>=', $from)
                                ->whereDate('date_issued', '<=', $to);
                        })
                            ->orWhere(function ($sub) use ($from, $to) {
                                $sub->whereNull('date_issued')
                                    ->whereDate('order_date', '>=', $from)
                                    ->whereDate('order_date', '<=', $to);
                            });
                    })
                    ->pluck('order_number')
                    ->filter()
                    ->toArray();
            }

            // 2. UzumOrder — дополнительный источник (исключая дубликаты)
            if (class_exists(\App\Models\UzumOrder::class)) {
                $uzumQuery = fn () => \App\Models\UzumOrder::whereHas('account', fn ($q) => $q->where('company_id', $companyId))
                    ->whereDate('ordered_at', '>=', $from)
                    ->whereDate('ordered_at', '<=', $to)
                    ->when(! empty($financeOrderNumbers), fn ($q) => $q->whereNotIn('external_order_id', $financeOrderNumbers));

                $uzAll = $uzumQuery()->selectRaw('COUNT(*) as cnt, SUM(total_amount) as amount')->first();
                $ordersCount += (int) ($uzAll?->cnt ?? 0);
                $ordersAmount += (float) ($uzAll?->amount ?? 0);

                $uzSold = $uzumQuery()->where('status', 'issued')
                    ->selectRaw('COUNT(*) as cnt, SUM(total_amount) as amount')->first();
                $soldCount += (int) ($uzSold?->cnt ?? 0);
                $soldAmount += (float) ($uzSold?->amount ?? 0);

                $uzReturns = $uzumQuery()->where('status', 'returns')
                    ->selectRaw('COUNT(*) as cnt, SUM(total_amount) as amount')->first();
                $returnsCount += (int) ($uzReturns?->cnt ?? 0);
                $returnsAmount += (float) ($uzReturns?->amount ?? 0);

                $uzCancelled = $uzumQuery()->where('status', 'cancelled')
                    ->selectRaw('COUNT(*) as cnt, SUM(total_amount) as amount')->first();
                $cancelledCount += (int) ($uzCancelled?->cnt ?? 0);
                $cancelledAmount += (float) ($uzCancelled?->amount ?? 0);
            }

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
        } catch (\Exception $e) {
            \Log::error('marketplaceIncome Uzum error', ['error' => $e->getMessage()]);
            $marketplaces['uzum'] = ['error' => $e->getMessage()];
        }

        // ========== WILDBERRIES ==========
        // Используем ту же логику что и SalesController:
        // Продажи (is_realization=true, is_cancel=false) → фильтр по last_change_date
        // Возвраты (is_realization=true, is_cancel=true) → фильтр по last_change_date
        // В транзите (is_realization=false, is_cancel=false) → фильтр по order_date
        // Отмены (is_realization=false, is_cancel=true) → фильтр по order_date
        try {
            if (class_exists(\App\Models\WildberriesOrder::class)) {
                $amountExpr = 'COALESCE(for_pay, finished_price, total_price, 0)';
                $wbBase = fn () => \App\Models\WildberriesOrder::whereHas('account', fn ($q) => $q->where('company_id', $companyId));

                // Все заказы (с учётом разных дат по статусу)
                $wbAll = $wbBase()
                    ->where(function ($q) use ($from, $to) {
                        // Продажи/возвраты → last_change_date
                        $q->where(function ($sub) use ($from, $to) {
                            $sub->where('is_realization', true)
                                ->whereDate('last_change_date', '>=', $from)
                                ->whereDate('last_change_date', '<=', $to);
                        })
                        // В транзите/отмены → order_date
                            ->orWhere(function ($sub) use ($from, $to) {
                                $sub->where('is_realization', false)
                                    ->whereDate('order_date', '>=', $from)
                                    ->whereDate('order_date', '<=', $to);
                            });
                    })
                    ->selectRaw("COUNT(*) as cnt, SUM({$amountExpr}) as amount")
                    ->first();

                // Проданные: is_realization=true AND is_cancel=false → last_change_date
                $wbSold = $wbBase()
                    ->where('is_realization', true)
                    ->where('is_cancel', false)
                    ->whereDate('last_change_date', '>=', $from)
                    ->whereDate('last_change_date', '<=', $to)
                    ->selectRaw("COUNT(*) as cnt, SUM({$amountExpr}) as amount")
                    ->first();

                // Возвраты: is_realization=true AND is_cancel=true → last_change_date
                $wbReturns = $wbBase()
                    ->where('is_realization', true)
                    ->where('is_cancel', true)
                    ->whereDate('last_change_date', '>=', $from)
                    ->whereDate('last_change_date', '<=', $to)
                    ->selectRaw("COUNT(*) as cnt, SUM({$amountExpr}) as amount")
                    ->first();

                // Отменённые: is_realization=false AND is_cancel=true → order_date
                $wbCancelled = $wbBase()
                    ->where('is_realization', false)
                    ->where('is_cancel', true)
                    ->whereDate('order_date', '>=', $from)
                    ->whereDate('order_date', '<=', $to)
                    ->selectRaw("COUNT(*) as cnt, SUM({$amountExpr}) as amount")
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
            Log::error('Ошибка получения доходов WB', ['company_id' => $companyId, 'error' => $e->getMessage()]);
            $marketplaces['wb'] = ['error' => $e->getMessage()];
        }

        // ========== OZON ==========
        try {
            if (class_exists(\App\Models\OzonOrder::class)) {
                $ozonQuery = fn () => \App\Models\OzonOrder::whereHas('account', fn ($q) => $q->where('company_id', $companyId))
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
            Log::error('Ошибка получения доходов Ozon', ['company_id' => $companyId, 'error' => $e->getMessage()]);
            $marketplaces['ozon'] = ['error' => $e->getMessage()];
        }

        // ========== YANDEX MARKET ==========
        try {
            if (class_exists(\App\Models\YandexMarketOrder::class)) {
                $ymQuery = fn () => \App\Models\YandexMarketOrder::whereHas('account', fn ($q) => $q->where('company_id', $companyId))
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
            Log::error('Ошибка получения доходов Yandex Market', ['company_id' => $companyId, 'error' => $e->getMessage()]);
            $marketplaces['yandex'] = ['error' => $e->getMessage()];
        }

        // ========== РУЧНЫЕ ПРОДАЖИ (Продажи со страницы "Продажи") ==========
        try {
            if (class_exists(\App\Models\Sale::class)) {
                // Ручные продажи (type = 'manual') за период
                $saleQuery = fn () => \App\Models\Sale::byCompany($companyId)
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
            Log::error('Ошибка получения данных ручных продаж', ['company_id' => $companyId, 'error' => $e->getMessage()]);
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
        if (! $companyId) {
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
                Log::error('Ошибка синхронизации расходов Uzum', ['account_id' => $account->id, 'error' => $e->getMessage()]);
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
        $hasStale = $cachedExpenses->contains(fn ($c) => $c->isStale(4));

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
                if (! isset($result[$displayKey])) {
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
     * ВАЖНО: purchase_price в ProductVariant конвертируется в UZS через getPurchasePriceInBase()
     */
    protected function calculateCogs(int $companyId, Carbon $from, Carbon $to, float $rubToUzs): array
    {
        return $this->inventoryValuationService->calculateCogs($companyId, $from, $to, $rubToUzs);
    }


    /**
     * Получить список доступных валют и текущих курсов
     *
     * Возвращает:
     * - Базовую валюту
     * - Список поддерживаемых валют с текущими курсами
     * - Возможность обновить курсы
     */
    public function currencies(Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (! $companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $financeSettings = FinanceSettings::getForCompany($companyId);

        // Список поддерживаемых валют
        $currencies = [
            [
                'code' => 'UZS',
                'name' => 'Узбекский сум',
                'symbol' => 'сўм',
                'rate' => 1.0,
                'is_base' => $financeSettings->base_currency_code === 'UZS',
            ],
            [
                'code' => 'USD',
                'name' => 'Доллар США',
                'symbol' => '$',
                'rate' => $financeSettings->usd_rate ?? 12700,
                'is_base' => false,
            ],
            [
                'code' => 'RUB',
                'name' => 'Российский рубль',
                'symbol' => '₽',
                'rate' => $financeSettings->rub_rate ?? 140,
                'is_base' => false,
            ],
            [
                'code' => 'EUR',
                'name' => 'Евро',
                'symbol' => '€',
                'rate' => $financeSettings->eur_rate ?? 13800,
                'is_base' => false,
            ],
            [
                'code' => 'KZT',
                'name' => 'Казахстанский тенге',
                'symbol' => '₸',
                'rate' => 27, // Примерный курс KZT к UZS
                'is_base' => false,
            ],
        ];

        return $this->successResponse([
            'base_currency' => $financeSettings->base_currency_code ?? 'UZS',
            'currencies' => $currencies,
            'rates_updated_at' => $financeSettings->rates_updated_at?->toIso8601String(),
        ]);
    }

    /**
     * Обновить курсы валют для компании
     */
    public function updateRates(Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (! $companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $data = $request->validate([
            'usd_rate' => ['nullable', 'numeric', 'min:0.01'],
            'rub_rate' => ['nullable', 'numeric', 'min:0.01'],
            'eur_rate' => ['nullable', 'numeric', 'min:0.01'],
        ]);

        $financeSettings = FinanceSettings::getForCompany($companyId);

        $updateData = ['rates_updated_at' => now()];

        if (isset($data['usd_rate'])) {
            $updateData['usd_rate'] = $data['usd_rate'];
        }
        if (isset($data['rub_rate'])) {
            $updateData['rub_rate'] = $data['rub_rate'];
        }
        if (isset($data['eur_rate'])) {
            $updateData['eur_rate'] = $data['eur_rate'];
        }

        $financeSettings->update($updateData);

        Log::info('Currency rates updated', [
            'company_id' => $companyId,
            'user_id' => Auth::id(),
            'rates' => $updateData,
        ]);

        return $this->successResponse([
            'message' => 'Курсы валют обновлены',
            'settings' => $financeSettings->fresh(),
        ]);
    }
}
