<?php

namespace App\Services\Finance;

use App\Models\Finance\FinanceCategory;
use App\Models\Finance\FinanceSettings;
use App\Models\Finance\FinanceTransaction;
use App\Models\MarketplaceAccount;
use App\Models\OzonOrder;
use App\Models\UzumExpense;
use App\Models\UzumFinanceOrder;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Сервис для синхронизации расходов маркетплейсов в FinanceTransaction
 *
 * Поддерживает: Uzum, Wildberries, Ozon
 */
class MarketplaceExpensesSyncService
{
    protected array $categoryCache = [];

    protected ?float $rubRate = null;

    /**
     * Синхронизировать расходы всех маркетплейсов
     */
    public function syncAllMarketplaces(int $companyId, Carbon $from, Carbon $to): array
    {
        $results = [
            'uzum' => $this->syncUzumExpenses($companyId, $from, $to),
            'wb' => $this->syncWildberriesExpenses($companyId, $from, $to),
            'ozon' => $this->syncOzonExpenses($companyId, $from, $to),
        ];

        // Агрегируем итоги
        $total = [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => 0,
            'total_amount' => 0,
            'by_marketplace' => [],
        ];

        foreach ($results as $mp => $result) {
            $total['created'] += $result['created'];
            $total['updated'] += $result['updated'];
            $total['skipped'] += $result['skipped'];
            $total['errors'] += $result['errors'];
            $total['total_amount'] += $result['total_amount'];
            $total['by_marketplace'][$mp] = $result['total_amount'];
        }

        $results['total'] = $total;

        return $results;
    }

    /**
     * Синхронизировать расходы Uzum
     */
    public function syncUzumExpenses(int $companyId, Carbon $from, Carbon $to): array
    {
        $result = $this->emptyResult();

        $accounts = MarketplaceAccount::where('company_id', $companyId)
            ->where('marketplace', 'uzum')
            ->where('is_active', true)
            ->get();

        if ($accounts->isEmpty()) {
            return $result;
        }

        foreach ($accounts as $account) {
            // 1. Комиссия и логистика из заказов
            $orderResult = $this->syncUzumOrderExpenses($account, $companyId, $from, $to);
            $this->mergeResults($result, $orderResult);

            // 2. Хранение, реклама, штрафы из expenses
            $expenseResult = $this->syncUzumExpenseRecords($account, $companyId, $from, $to);
            $this->mergeResults($result, $expenseResult);
        }

        return $result;
    }

    /**
     * Синхронизировать расходы Wildberries
     *
     * Использует reportDetailByPeriod API для получения данных по:
     * - Комиссии (ppvz_sales_commission)
     * - Логистике (delivery_rub)
     * - Хранению (storage_fee)
     * - Штрафам (penalty)
     * - Обработке товара / приёмке (acceptance)
     *
     * ВАЖНО: WB API для продавцов из Узбекистана возвращает суммы в UZS.
     *
     * ОГРАНИЧЕНИЕ: WB API reportDetailByPeriod возвращает данные только
     * за закрытые отчётные периоды (недели). Данные за текущую неделю
     * будут недоступны до закрытия периода (каждый понедельник).
     */
    public function syncWildberriesExpenses(int $companyId, Carbon $from, Carbon $to): array
    {
        $result = $this->emptyResult();

        $accounts = MarketplaceAccount::where('company_id', $companyId)
            ->where('marketplace', 'wb')
            ->where('is_active', true)
            ->get();

        if ($accounts->isEmpty()) {
            return $result;
        }

        foreach ($accounts as $account) {
            try {
                // Используем WildberriesFinanceService для получения данных из API
                $httpClient = new \App\Services\Marketplaces\Wildberries\WildberriesHttpClient($account);
                $financeService = new \App\Services\Marketplaces\Wildberries\WildberriesFinanceService($httpClient);

                // Получаем детальный отчёт для расчёта всех категорий расходов
                $report = $financeService->getFullDetailedReport($account, $from, $to);

                // Считаем расходы по категориям из отчёта
                $expenses = $this->calculateWbExpensesFromReport($report);

                // Валюта определяется из отчёта (UZS для узбекских продавцов)
                $currency = $expenses['currency'];
                $needsConversion = ($currency === 'RUB');
                $rubRate = $needsConversion ? $this->getRubRate($companyId) : 1;

                Log::info('WB expenses calculated from report', [
                    'account_id' => $account->id,
                    'currency' => $currency,
                    'records_count' => count($report),
                    'commission' => $expenses['commission'],
                    'logistics' => $expenses['logistics'],
                    'storage' => $expenses['storage'],
                    'acceptance' => $expenses['acceptance'],
                    'penalties' => $expenses['penalties'],
                    'retentions' => $expenses['retentions'],
                ]);

                // Синхронизируем комиссию
                if (($expenses['commission'] ?? 0) > 0) {
                    $amount = $expenses['commission'] * $rubRate;
                    $txResult = $this->createOrUpdateTransaction(
                        $companyId,
                        FinanceCategory::CODE_MP_COMMISSION,
                        $amount,
                        $to,
                        "Комиссия WB за период {$from->format('d.m.Y')} - {$to->format('d.m.Y')}",
                        MarketplaceAccount::class,
                        $account->id,
                        'commission_period',
                        'wb',
                        $currency,
                        $rubRate
                    );
                    $result[$txResult]++;
                    if ($txResult !== 'skipped') {
                        $result['total_amount'] += $amount;
                        $result['by_category']['commission'] = ($result['by_category']['commission'] ?? 0) + $amount;
                    }
                }

                // Синхронизируем логистику
                if (($expenses['logistics'] ?? 0) > 0) {
                    $amount = $expenses['logistics'] * $rubRate;
                    $txResult = $this->createOrUpdateTransaction(
                        $companyId,
                        FinanceCategory::CODE_MP_LOGISTICS,
                        $amount,
                        $to,
                        "Логистика WB за период {$from->format('d.m.Y')} - {$to->format('d.m.Y')}",
                        MarketplaceAccount::class,
                        $account->id,
                        'logistics_period',
                        'wb',
                        $currency,
                        $rubRate
                    );
                    $result[$txResult]++;
                    if ($txResult !== 'skipped') {
                        $result['total_amount'] += $amount;
                        $result['by_category']['logistics'] = ($result['by_category']['logistics'] ?? 0) + $amount;
                    }
                }

                // Синхронизируем хранение
                if (($expenses['storage'] ?? 0) > 0) {
                    $amount = $expenses['storage'] * $rubRate;
                    $txResult = $this->createOrUpdateTransaction(
                        $companyId,
                        FinanceCategory::CODE_MP_STORAGE,
                        $amount,
                        $to,
                        "Хранение WB за период {$from->format('d.m.Y')} - {$to->format('d.m.Y')}",
                        MarketplaceAccount::class,
                        $account->id,
                        'storage_period',
                        'wb',
                        $currency,
                        $rubRate
                    );
                    $result[$txResult]++;
                    if ($txResult !== 'skipped') {
                        $result['total_amount'] += $amount;
                        $result['by_category']['storage'] = ($result['by_category']['storage'] ?? 0) + $amount;
                    }
                }

                // Синхронизируем операции при приёмке
                if (($expenses['acceptance'] ?? 0) > 0) {
                    $amount = $expenses['acceptance'] * $rubRate;
                    $txResult = $this->createOrUpdateTransaction(
                        $companyId,
                        FinanceCategory::CODE_MP_LOGISTICS, // Приёмка относится к логистике
                        $amount,
                        $to,
                        "Операции при приёмке WB за период {$from->format('d.m.Y')} - {$to->format('d.m.Y')}",
                        MarketplaceAccount::class,
                        $account->id,
                        'acceptance_period',
                        'wb',
                        $currency,
                        $rubRate
                    );
                    $result[$txResult]++;
                    if ($txResult !== 'skipped') {
                        $result['total_amount'] += $amount;
                        $result['by_category']['acceptance'] = ($result['by_category']['acceptance'] ?? 0) + $amount;
                    }
                }

                // Синхронизируем штрафы
                if (($expenses['penalties'] ?? 0) > 0) {
                    $amount = $expenses['penalties'] * $rubRate;
                    $txResult = $this->createOrUpdateTransaction(
                        $companyId,
                        FinanceCategory::CODE_MP_PENALTIES,
                        $amount,
                        $to,
                        "Штрафы WB за период {$from->format('d.m.Y')} - {$to->format('d.m.Y')}",
                        MarketplaceAccount::class,
                        $account->id,
                        'penalties_period',
                        'wb',
                        $currency,
                        $rubRate
                    );
                    $result[$txResult]++;
                    if ($txResult !== 'skipped') {
                        $result['total_amount'] += $amount;
                        $result['by_category']['penalties'] = ($result['by_category']['penalties'] ?? 0) + $amount;
                    }
                }

                // Синхронизируем удержания
                if (($expenses['retentions'] ?? 0) > 0) {
                    $amount = $expenses['retentions'] * $rubRate;
                    $txResult = $this->createOrUpdateTransaction(
                        $companyId,
                        FinanceCategory::CODE_MP_PENALTIES, // Удержания относятся к штрафам
                        $amount,
                        $to,
                        "Удержания WB за период {$from->format('d.m.Y')} - {$to->format('d.m.Y')}",
                        MarketplaceAccount::class,
                        $account->id,
                        'retentions_period',
                        'wb',
                        $currency,
                        $rubRate
                    );
                    $result[$txResult]++;
                    if ($txResult !== 'skipped') {
                        $result['total_amount'] += $amount;
                        $result['by_category']['retentions'] = ($result['by_category']['retentions'] ?? 0) + $amount;
                    }
                }

            } catch (\Exception $e) {
                $result['errors']++;
                Log::error('MarketplaceExpensesSyncService: WB expenses sync failed', [
                    'account_id' => $account->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $result;
    }

    /**
     * Рассчитать расходы WB из детального отчёта reportDetailByPeriod
     *
     * Поля для каждого типа операции:
     * - Продажа/Возврат: ppvz_sales_commission (комиссия)
     * - Логистика: delivery_rub
     * - Хранение: storage_fee
     * - Обработка товара: acceptance (приёмка)
     * - Штраф: penalty
     * - Возмещение издержек: rebill_logistic_cost (удержания)
     */
    protected function calculateWbExpensesFromReport(array $report): array
    {
        $expenses = [
            'commission' => 0,
            'logistics' => 0,
            'storage' => 0,
            'acceptance' => 0,
            'penalties' => 0,
            'retentions' => 0,
            'sales' => 0,
            'refunds' => 0,
            'currency' => 'RUB',
        ];

        if (empty($report)) {
            return $expenses;
        }

        // Определяем валюту из первой записи
        $firstRecord = $report[0] ?? null;
        if ($firstRecord && isset($firstRecord['currency_name'])) {
            $expenses['currency'] = $firstRecord['currency_name'];
        }

        foreach ($report as $r) {
            $type = $r['supplier_oper_name'] ?? '';

            // Продажа - комиссия в поле ppvz_sales_commission
            if ($type === 'Продажа') {
                $expenses['sales'] += abs($r['retail_amount'] ?? 0);
                $expenses['commission'] += abs($r['ppvz_sales_commission'] ?? 0);
            }

            // Возврат - комиссия возвращается
            if ($type === 'Возврат') {
                $expenses['refunds'] += abs($r['retail_amount'] ?? 0);
                $expenses['commission'] -= abs($r['ppvz_sales_commission'] ?? 0);
            }

            // Логистика - сумма в поле delivery_rub
            if ($type === 'Логистика') {
                $expenses['logistics'] += abs($r['delivery_rub'] ?? 0);
            }

            // Хранение - сумма в поле storage_fee
            if ($type === 'Хранение') {
                $expenses['storage'] += abs($r['storage_fee'] ?? 0);
            }

            // Обработка товара (операции при приёмке) - сумма в поле acceptance
            if ($type === 'Обработка товара') {
                $expenses['acceptance'] += abs($r['acceptance'] ?? 0);
            }

            // Штраф - сумма в поле penalty
            if ($type === 'Штраф') {
                $expenses['penalties'] += abs($r['penalty'] ?? 0);
            }

            // Возмещение издержек (удержания) - сумма в поле rebill_logistic_cost
            if (str_contains($type, 'Возмещение издержек')) {
                $expenses['retentions'] += abs($r['rebill_logistic_cost'] ?? 0);
            }
        }

        // Комиссия не может быть отрицательной
        if ($expenses['commission'] < 0) {
            $expenses['commission'] = 0;
        }

        return $expenses;
    }

    /**
     * Синхронизировать расходы Ozon
     */
    public function syncOzonExpenses(int $companyId, Carbon $from, Carbon $to): array
    {
        $result = $this->emptyResult();

        if (! class_exists(OzonOrder::class)) {
            return $result;
        }

        $accounts = MarketplaceAccount::where('company_id', $companyId)
            ->where('marketplace', 'ozon')
            ->where('is_active', true)
            ->get();

        if ($accounts->isEmpty()) {
            return $result;
        }

        // Получаем курс рубля для конвертации
        $rubRate = $this->getRubRate($companyId);

        foreach ($accounts as $account) {
            // Получаем завершённые заказы
            $orders = OzonOrder::where('marketplace_account_id', $account->id)
                ->whereIn('status', ['delivered', 'completed'])
                ->whereDate('created_at_ozon', '>=', $from)
                ->whereDate('created_at_ozon', '<=', $to)
                ->get();

            foreach ($orders as $order) {
                // Примерная комиссия Ozon ~15% от цены
                $totalPrice = (float) ($order->total_price ?? 0);
                $commission = $totalPrice * 0.15;

                if ($commission > 0) {
                    try {
                        // Конвертируем в UZS
                        $commissionUzs = $commission * $rubRate;

                        $txResult = $this->createOrUpdateTransaction(
                            $companyId,
                            FinanceCategory::CODE_MP_COMMISSION,
                            $commissionUzs,
                            $order->created_at_ozon,
                            "Комиссия Ozon: заказ #{$order->ozon_order_id}",
                            OzonOrder::class,
                            $order->id,
                            'commission',
                            'ozon',
                            'RUB',
                            $rubRate
                        );

                        $result[$txResult]++;
                        if ($txResult !== 'skipped') {
                            $result['total_amount'] += $commissionUzs;
                            $result['by_category']['commission'] = ($result['by_category']['commission'] ?? 0) + $commissionUzs;
                        }
                    } catch (\Exception $e) {
                        $result['errors']++;
                        Log::warning('MarketplaceExpensesSyncService: Ozon commission failed', [
                            'order_id' => $order->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Синхронизировать комиссию и логистику из UzumFinanceOrder
     */
    protected function syncUzumOrderExpenses(MarketplaceAccount $account, int $companyId, Carbon $from, Carbon $to): array
    {
        $result = $this->emptyResult();

        if (! class_exists(UzumFinanceOrder::class)) {
            return $result;
        }

        $orders = UzumFinanceOrder::where('marketplace_account_id', $account->id)
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
            ->where(function ($q) {
                $q->where('commission', '>', 0)
                    ->orWhere('logistic_delivery_fee', '>', 0);
            })
            ->get();

        foreach ($orders as $order) {
            // Комиссия
            if ($order->commission > 0) {
                try {
                    $txResult = $this->createOrUpdateTransaction(
                        $companyId,
                        FinanceCategory::CODE_MP_COMMISSION,
                        $order->commission,
                        $order->date_issued ?? $order->order_date,
                        "Комиссия Uzum: заказ #{$order->order_id}",
                        UzumFinanceOrder::class,
                        $order->id,
                        'commission',
                        'uzum'
                    );

                    $result[$txResult]++;
                    if ($txResult !== 'skipped') {
                        $result['total_amount'] += $order->commission;
                        $result['by_category']['commission'] = ($result['by_category']['commission'] ?? 0) + $order->commission;
                    }
                } catch (\Exception $e) {
                    $result['errors']++;
                }
            }

            // Логистика
            if ($order->logistic_delivery_fee > 0) {
                try {
                    $txResult = $this->createOrUpdateTransaction(
                        $companyId,
                        FinanceCategory::CODE_MP_LOGISTICS,
                        $order->logistic_delivery_fee,
                        $order->date_issued ?? $order->order_date,
                        "Логистика Uzum: заказ #{$order->order_id}",
                        UzumFinanceOrder::class,
                        $order->id,
                        'logistics',
                        'uzum'
                    );

                    $result[$txResult]++;
                    if ($txResult !== 'skipped') {
                        $result['total_amount'] += $order->logistic_delivery_fee;
                        $result['by_category']['logistics'] = ($result['by_category']['logistics'] ?? 0) + $order->logistic_delivery_fee;
                    }
                } catch (\Exception $e) {
                    $result['errors']++;
                }
            }
        }

        return $result;
    }

    /**
     * Синхронизировать расходы из UzumExpense
     */
    protected function syncUzumExpenseRecords(MarketplaceAccount $account, int $companyId, Carbon $from, Carbon $to): array
    {
        $result = $this->emptyResult();

        if (! class_exists(UzumExpense::class)) {
            return $result;
        }

        $expenses = UzumExpense::where('marketplace_account_id', $account->id)
            ->where(function ($q) use ($from, $to) {
                $q->whereDate('date_service', '>=', $from)
                    ->whereDate('date_service', '<=', $to);
            })
            ->where('payment_price', '>', 0)
            ->get();

        foreach ($expenses as $expense) {
            try {
                $categoryCode = $this->mapExpenseCategoryCode($expense->source_normalized);
                $amount = abs($expense->payment_price);

                $txResult = $this->createOrUpdateTransaction(
                    $companyId,
                    $categoryCode,
                    $amount,
                    $expense->date_service ?? $expense->date_created,
                    "Uzum: {$expense->name}",
                    UzumExpense::class,
                    $expense->id,
                    null,
                    'uzum'
                );

                $result[$txResult]++;
                if ($txResult !== 'skipped') {
                    $result['total_amount'] += $amount;
                    $catKey = $expense->source_normalized ?? 'other';
                    $result['by_category'][$catKey] = ($result['by_category'][$catKey] ?? 0) + $amount;
                }
            } catch (\Exception $e) {
                $result['errors']++;
            }
        }

        return $result;
    }

    /**
     * Создать или обновить транзакцию
     */
    protected function createOrUpdateTransaction(
        int $companyId,
        string $categoryCode,
        float $amount,
        $transactionDate,
        string $description,
        string $sourceType,
        int $sourceId,
        ?string $subType = null,
        string $marketplace = 'uzum',
        string $currency = 'UZS',
        float $exchangeRate = 1
    ): string {
        $categoryId = $this->getCategoryId($companyId, $categoryCode);

        if (! $categoryId) {
            throw new \Exception("Category not found: {$categoryCode}");
        }

        // Уникальный reference
        $reference = $subType
            ? "{$marketplace}:{$sourceType}:{$sourceId}:{$subType}"
            : "{$marketplace}:{$sourceType}:{$sourceId}";

        $existing = FinanceTransaction::where('company_id', $companyId)
            ->where('reference', $reference)
            ->first();

        if ($existing) {
            if (abs($existing->amount - $amount) < 0.01) {
                return 'skipped';
            }

            $existing->update([
                'amount' => $amount,
                'amount_base' => $amount,
                'description' => $description,
            ]);

            return 'updated';
        }

        FinanceTransaction::create([
            'company_id' => $companyId,
            'type' => FinanceTransaction::TYPE_EXPENSE,
            'category_id' => $categoryId,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'amount' => $amount,
            'currency_code' => 'UZS',
            'exchange_rate' => $exchangeRate,
            'amount_base' => $amount,
            'description' => $description,
            'transaction_date' => $transactionDate,
            'reference' => $reference,
            'status' => FinanceTransaction::STATUS_CONFIRMED,
            'confirmed_at' => now(),
            'metadata' => [
                'source' => "{$marketplace}_sync",
                'original_currency' => $currency,
                'exchange_rate' => $exchangeRate,
                'synced_at' => now()->toIso8601String(),
            ],
        ]);

        return 'created';
    }

    /**
     * Получить курс рубля
     */
    protected function getRubRate(int $companyId): float
    {
        if ($this->rubRate !== null) {
            return $this->rubRate;
        }

        $settings = FinanceSettings::getForCompany($companyId);
        $this->rubRate = $settings->rub_rate ?? 140;

        return $this->rubRate;
    }

    /**
     * Получить ID категории по коду
     */
    protected function getCategoryId(int $companyId, string $code): ?int
    {
        $cacheKey = "{$companyId}:{$code}";

        if (isset($this->categoryCache[$cacheKey])) {
            return $this->categoryCache[$cacheKey];
        }

        $category = FinanceCategory::byCompany($companyId)
            ->where('code', $code)
            ->first();

        $this->categoryCache[$cacheKey] = $category?->id;

        return $this->categoryCache[$cacheKey];
    }

    /**
     * Маппинг source_normalized -> код категории
     */
    protected function mapExpenseCategoryCode(string $sourceNormalized): string
    {
        return match ($sourceNormalized) {
            'commission' => FinanceCategory::CODE_MP_COMMISSION,
            'logistics' => FinanceCategory::CODE_MP_LOGISTICS,
            'storage' => FinanceCategory::CODE_MP_STORAGE,
            'advertising' => FinanceCategory::CODE_MP_ADS,
            'penalties' => FinanceCategory::CODE_MP_PENALTIES,
            'returns' => FinanceCategory::CODE_MP_RETURNS,
            default => FinanceCategory::CODE_OTHER_EXPENSE,
        };
    }

    protected function emptyResult(): array
    {
        return [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => 0,
            'total_amount' => 0,
            'by_category' => [],
        ];
    }

    protected function mergeResults(array &$target, array $source): void
    {
        $target['created'] += $source['created'];
        $target['updated'] += $source['updated'];
        $target['skipped'] += $source['skipped'];
        $target['errors'] += $source['errors'];
        $target['total_amount'] += $source['total_amount'];

        foreach ($source['by_category'] as $cat => $amount) {
            $target['by_category'][$cat] = ($target['by_category'][$cat] ?? 0) + $amount;
        }
    }
}
