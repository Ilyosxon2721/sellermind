<?php

namespace App\Services\Finance;

use App\Models\Finance\FinanceCategory;
use App\Models\Finance\FinanceDebt;
use App\Models\Finance\FinanceTransaction;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FinanceReportService
{
    public function getExpensesByCategory(int $companyId, Carbon $from, Carbon $to): array
    {
        $transactions = FinanceTransaction::byCompany($companyId)
            ->confirmed()
            ->expense()
            ->inPeriod($from, $to)
            ->with('category')
            ->get();

        $byCategory = $transactions->groupBy(fn($t) => $t->category?->name ?? 'Без категории')
            ->map(fn($group) => $group->sum('amount'))
            ->sortDesc();

        return $byCategory->map(fn($amount, $name) => [
            'category' => $name,
            'amount' => $amount,
        ])->values()->toArray();
    }

    public function getProfitAndLoss(int $companyId, Carbon $from, Carbon $to): array
    {
        $transactions = FinanceTransaction::byCompany($companyId)
            ->confirmed()
            ->inPeriod($from, $to)
            ->with(['category.parent'])
            ->get();

        // Группируем доходы
        $income = $transactions->where('type', FinanceTransaction::TYPE_INCOME);
        $incomeByCategory = $this->groupByParentCategory($income);

        // Группируем расходы
        $expenses = $transactions->where('type', FinanceTransaction::TYPE_EXPENSE);
        $expensesByCategory = $this->groupByParentCategory($expenses);

        $totalIncome = $income->sum('amount');
        $totalExpenses = $expenses->sum('amount');

        return [
            'income' => [
                'total' => $totalIncome,
                'by_category' => $incomeByCategory,
            ],
            'expenses' => [
                'total' => $totalExpenses,
                'by_category' => $expensesByCategory,
            ],
            'gross_profit' => $totalIncome - $totalExpenses,
            'profit_margin' => $totalIncome > 0
                ? round(($totalIncome - $totalExpenses) / $totalIncome * 100, 2)
                : 0,
        ];
    }

    public function getCashFlow(int $companyId, Carbon $from, Carbon $to): array
    {
        $transactions = FinanceTransaction::byCompany($companyId)
            ->confirmed()
            ->inPeriod($from, $to)
            ->orderBy('transaction_date')
            ->get();

        // Группируем по дням/неделям/месяцам в зависимости от периода
        $diffDays = $from->diffInDays($to);

        if ($diffDays <= 31) {
            // По дням
            $grouped = $transactions->groupBy(fn($t) => $t->transaction_date->format('Y-m-d'));
            $format = 'd.m';
        } elseif ($diffDays <= 90) {
            // По неделям
            $grouped = $transactions->groupBy(fn($t) => $t->transaction_date->startOfWeek()->format('Y-m-d'));
            $format = 'd.m';
        } else {
            // По месяцам
            $grouped = $transactions->groupBy(fn($t) => $t->transaction_date->format('Y-m'));
            $format = 'M Y';
        }

        $cashFlow = [];
        $runningBalance = 0;

        foreach ($grouped as $period => $group) {
            $income = $group->where('type', FinanceTransaction::TYPE_INCOME)->sum('amount');
            $expense = $group->where('type', FinanceTransaction::TYPE_EXPENSE)->sum('amount');
            $net = $income - $expense;
            $runningBalance += $net;

            $date = Carbon::parse($period);
            $cashFlow[] = [
                'period' => $date->format($format),
                'date' => $period,
                'income' => $income,
                'expense' => $expense,
                'net' => $net,
                'balance' => $runningBalance,
            ];
        }

        return $cashFlow;
    }

    public function getByCategory(int $companyId, Carbon $from, Carbon $to): array
    {
        $transactions = FinanceTransaction::byCompany($companyId)
            ->confirmed()
            ->inPeriod($from, $to)
            ->with(['category.parent', 'subcategory'])
            ->get();

        $incomeCategories = $this->buildCategoryTree(
            $transactions->where('type', FinanceTransaction::TYPE_INCOME)
        );

        $expenseCategories = $this->buildCategoryTree(
            $transactions->where('type', FinanceTransaction::TYPE_EXPENSE)
        );

        return [
            'income' => $incomeCategories,
            'expense' => $expenseCategories,
        ];
    }

    public function getDebtsAging(int $companyId): array
    {
        $today = now();

        $debts = FinanceDebt::byCompany($companyId)
            ->active()
            ->with(['counterparty', 'employee'])
            ->get();

        $aging = [
            'current' => ['count' => 0, 'amount' => 0],      // не просрочено
            '1_30' => ['count' => 0, 'amount' => 0],         // 1-30 дней
            '31_60' => ['count' => 0, 'amount' => 0],        // 31-60 дней
            '61_90' => ['count' => 0, 'amount' => 0],        // 61-90 дней
            'over_90' => ['count' => 0, 'amount' => 0],      // более 90 дней
        ];

        $receivable = ['current' => 0, '1_30' => 0, '31_60' => 0, '61_90' => 0, 'over_90' => 0];
        $payable = ['current' => 0, '1_30' => 0, '31_60' => 0, '61_90' => 0, 'over_90' => 0];

        foreach ($debts as $debt) {
            $bucket = 'current';

            if ($debt->due_date && $debt->due_date->isPast()) {
                $daysOverdue = $debt->due_date->diffInDays($today);

                if ($daysOverdue <= 30) {
                    $bucket = '1_30';
                } elseif ($daysOverdue <= 60) {
                    $bucket = '31_60';
                } elseif ($daysOverdue <= 90) {
                    $bucket = '61_90';
                } else {
                    $bucket = 'over_90';
                }
            }

            $aging[$bucket]['count']++;
            $aging[$bucket]['amount'] += $debt->amount_outstanding;

            if ($debt->type === FinanceDebt::TYPE_RECEIVABLE) {
                $receivable[$bucket] += $debt->amount_outstanding;
            } else {
                $payable[$bucket] += $debt->amount_outstanding;
            }
        }

        return [
            'summary' => $aging,
            'receivable' => $receivable,
            'payable' => $payable,
            'total_receivable' => array_sum($receivable),
            'total_payable' => array_sum($payable),
        ];
    }

    protected function groupByParentCategory(Collection $transactions): array
    {
        return $transactions->groupBy(function ($t) {
            if ($t->category?->parent) {
                return $t->category->parent->name;
            }
            return $t->category?->name ?? 'Без категории';
        })->map(fn($group) => $group->sum('amount'))
            ->sortDesc()
            ->toArray();
    }

    protected function buildCategoryTree(Collection $transactions): array
    {
        $tree = [];

        $byParent = $transactions->groupBy(function ($t) {
            return $t->category?->parent_id ?? $t->category_id ?? 0;
        });

        $rootCategories = FinanceCategory::whereNull('parent_id')
            ->system()
            ->orderBy('sort_order')
            ->get();

        foreach ($rootCategories as $root) {
            $rootTransactions = $transactions->filter(function ($t) use ($root) {
                return $t->category_id === $root->id ||
                    ($t->category?->parent_id === $root->id);
            });

            if ($rootTransactions->isEmpty()) {
                continue;
            }

            $children = $rootTransactions->filter(fn($t) => $t->category?->parent_id === $root->id)
                ->groupBy(fn($t) => $t->category?->name ?? 'Другое')
                ->map(fn($group) => $group->sum('amount'))
                ->toArray();

            $tree[] = [
                'category' => $root->name,
                'total' => $rootTransactions->sum('amount'),
                'children' => $children,
            ];
        }

        return $tree;
    }
}
