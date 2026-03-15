<?php

declare(strict_types=1);

namespace App\Services\Uzum\Api\Plugins;

use App\Services\Uzum\Api\UzumApi;
use App\Services\Uzum\Api\UzumEndpoints;

/**
 * Плагин: Финансы (заказы + расходы)
 */
final class FinancePlugin
{
    public function __construct(
        private readonly UzumApi $api,
    ) {}

    /**
     * Получить финансовые заказы (одна страница)
     */
    public function orders(string $shopIds, int $page = 0, int $size = 50, array $extra = []): array
    {
        $query = array_merge([
            'shopIds' => $shopIds,
            'page' => $page,
            'size' => $size,
            'group' => 'PRODUCT',
        ], $extra);

        return $this->api->call(UzumEndpoints::FINANCE_ORDERS, query: $query);
    }

    /**
     * Получить ВСЕ финансовые заказы (с пагинацией)
     */
    public function allOrders(string $shopIds, ?int $dateFrom = null, ?int $dateTo = null): array
    {
        $query = ['shopIds' => $shopIds, 'group' => 'PRODUCT'];
        if ($dateFrom) {
            $query['dateFrom'] = $dateFrom;
        }
        if ($dateTo) {
            $query['dateTo'] = $dateTo;
        }

        return $this->api->paginate(
            UzumEndpoints::FINANCE_ORDERS,
            query: $query,
            dataKey: 'financeOrders',
            pageSize: 100,
        );
    }

    /**
     * Получить расходы продавца (одна страница)
     */
    public function expenses(string $shopIds, int $page = 0, int $size = 50, array $extra = []): array
    {
        $query = array_merge([
            'shopIds' => $shopIds,
            'page' => $page,
            'size' => $size,
        ], $extra);

        return $this->api->call(UzumEndpoints::FINANCE_EXPENSES, query: $query);
    }

    /**
     * Получить ВСЕ расходы (с пагинацией)
     */
    public function allExpenses(string $shopIds, ?int $dateFrom = null, ?int $dateTo = null): array
    {
        $query = ['shopIds' => $shopIds];
        if ($dateFrom) {
            $query['dateFrom'] = $dateFrom;
        }
        if ($dateTo) {
            $query['dateTo'] = $dateTo;
        }

        return $this->api->paginate(
            UzumEndpoints::FINANCE_EXPENSES,
            query: $query,
            dataKey: 'expenseList',
            pageSize: 100,
        );
    }

    /**
     * Сводка расходов по категориям
     */
    public function expensesSummary(string $shopIds, ?int $dateFrom = null, ?int $dateTo = null): array
    {
        $expenses = $this->allExpenses($shopIds, $dateFrom, $dateTo);

        $categories = [
            'commission' => 0.0,
            'logistics' => 0.0,
            'storage' => 0.0,
            'advertising' => 0.0,
            'penalties' => 0.0,
            'returns' => 0.0,
            'other' => 0.0,
        ];

        foreach ($expenses as $expense) {
            $source = strtolower($expense['source'] ?? '');
            $amount = (float) ($expense['amount'] ?? 0);

            $category = match (true) {
                str_contains($source, 'commission') => 'commission',
                str_contains($source, 'logistic') || str_contains($source, 'delivery') => 'logistics',
                str_contains($source, 'storage') || str_contains($source, 'warehouse') => 'storage',
                str_contains($source, 'advert') || str_contains($source, 'promotion') => 'advertising',
                str_contains($source, 'penal') || str_contains($source, 'fine') => 'penalties',
                str_contains($source, 'return') => 'returns',
                default => 'other',
            };

            $categories[$category] += $amount;
        }

        return [
            'total' => array_sum($categories),
            'categories' => $categories,
            'count' => count($expenses),
        ];
    }
}
