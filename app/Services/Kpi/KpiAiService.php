<?php

declare(strict_types=1);

namespace App\Services\Kpi;

use App\Models\AIUsageLog;
use App\Models\Finance\Employee;
use App\Models\Kpi\KpiPlan;
use App\Models\Kpi\SalesSphere;
use App\Models\OfflineSale;
use App\Models\OfflineSaleItem;
use App\Services\AI\AiProviderService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * ИИ-аналитика для рекомендации KPI-планов на основе исторических данных
 */
final class KpiAiService
{
    public function __construct(
        private readonly AiProviderService $aiProvider,
        private readonly KpiMarginCalculator $marginCalculator,
    ) {}

    /**
     * Сгенерировать рекомендации KPI на основе исторических данных
     *
     * @return array{target_revenue: float, target_margin: float, target_orders: int, weight_revenue: int, weight_margin: int, weight_orders: int, reasoning: string}
     */
    public function suggestPlan(
        int $companyId,
        int $userId,
        int $employeeId,
        int $sphereId,
        int $year,
        int $month,
    ): array {
        $sphere = SalesSphere::byCompany($companyId)->findOrFail($sphereId);
        $employee = Employee::where('company_id', $companyId)->findOrFail($employeeId);

        // Собрать исторические данные за последние 6 месяцев
        $history = $this->collectHistory($companyId, $employeeId, $sphereId, $year, $month);

        // Собрать данные по прошлым KPI-планам
        $pastPlans = $this->collectPastPlans($companyId, $employeeId, $sphereId, $year, $month);

        $prompt = $this->buildPrompt($employee, $sphere, $history, $pastPlans, $year, $month);

        $messages = [
            [
                'role' => 'system',
                'content' => $this->getSystemPrompt($sphere),
            ],
            [
                'role' => 'user',
                'content' => $prompt,
            ],
        ];

        $options = [
            'temperature' => 0.3,
            'max_tokens' => 4096,
            'timeout' => config('ai.timeout.kpi', 90),
        ];

        // Определяем модель в зависимости от провайдера
        $provider = config('ai.provider', 'openai');
        $options['model'] = match ($provider) {
            'anthropic' => config('anthropic.models.kpi', 'claude-sonnet-4-20250514'),
            default => config('openai.models.kpi', 'gpt-4o'),
        };

        try {
            $response = $this->aiProvider->chatCompletion($messages, $options);

            // Логируем использование ИИ
            AIUsageLog::log(
                $companyId,
                $userId,
                $response['provider'].':'.$response['model'],
                $response['usage']['prompt_tokens'] ?? 0,
                $response['usage']['completion_tokens'] ?? 0
            );

            return $this->parseResponse($response['content'], $history);
        } catch (\Exception $e) {
            Log::error('KPI AI Service Error', [
                'error' => $e->getMessage(),
                'company_id' => $companyId,
                'employee_id' => $employeeId,
            ]);

            throw $e;
        }
    }

    /**
     * Собрать историю продаж за последние 6 месяцев
     */
    private function collectHistory(int $companyId, int $employeeId, int $sphereId, int $year, int $month): array
    {
        $sphere = SalesSphere::find($sphereId);
        if (! $sphere) {
            return [];
        }

        $history = [];

        for ($i = 1; $i <= 6; $i++) {
            $date = Carbon::create($year, $month, 1)->subMonths($i);
            $periodStart = $date->copy()->startOfMonth();
            $periodEnd = $date->copy()->endOfMonth();

            $monthData = [
                'year' => $date->year,
                'month' => $date->month,
                'revenue' => 0,
                'margin' => 0,
                'orders' => 0,
            ];

            // Проверяем есть ли уже рассчитанный KPI-план за этот период
            $existingPlan = KpiPlan::byCompany($companyId)
                ->forEmployee($employeeId)
                ->forPeriod($date->year, $date->month)
                ->where('kpi_sales_sphere_id', $sphereId)
                ->first();

            if ($existingPlan && $existingPlan->actual_revenue > 0) {
                $monthData['revenue'] = $existingPlan->actual_revenue;
                $monthData['margin'] = $existingPlan->actual_margin;
                $monthData['orders'] = $existingPlan->actual_orders;
            } elseif ($sphere) {
                // Агрегируем из всех привязанных источников
                if ($sphere->hasMarketplaceLink()) {
                    $accountIds = $sphere->getLinkedAccountIds();
                    $marketplaceData = $this->collectMarketplaceData($accountIds, $periodStart, $periodEnd);

                    $monthData['revenue'] += $marketplaceData['revenue'];
                    $monthData['margin'] += $marketplaceData['margin'];
                    $monthData['orders'] += $marketplaceData['orders'];
                }

                if ($sphere->hasOfflineSaleLink()) {
                    $offlineData = $this->collectOfflineSaleHistory($companyId, $sphere, $periodStart, $periodEnd);

                    $monthData['revenue'] += $offlineData['revenue'];
                    $monthData['margin'] += $offlineData['margin'];
                    $monthData['orders'] += $offlineData['orders'];
                }
            }

            $history[] = $monthData;
        }

        return $history;
    }

    /**
     * Собрать данные продаж напрямую из таблиц маркетплейсов
     *
     * @return array{revenue: float, margin: float, orders: int}
     */
    private function collectMarketplaceData(array $accountIds, Carbon $periodStart, Carbon $periodEnd): array
    {
        $totalRevenue = 0;
        $totalMargin = 0.0;
        $totalOrders = 0;

        // Предзагрузка всех аккаунтов одним запросом вместо N+1
        $accountsMap = \App\Models\MarketplaceAccount::whereIn('id', $accountIds)->get()->keyBy('id');

        foreach ($accountIds as $accountId) {
            $account = $accountsMap[$accountId] ?? null;
            if (! $account) {
                continue;
            }

            // Определяем тип маркетплейса и читаем из соответствующей таблицы
            $data = match ($account->marketplace) {
                'uzum' => $this->collectUzumData($accountId, $periodStart, $periodEnd),
                'wb', 'wildberries' => $this->collectWildberriesData($accountId, $periodStart, $periodEnd),
                'ozon' => $this->collectOzonData($accountId, $periodStart, $periodEnd),
                'ym', 'yandex_market' => $this->collectYandexMarketData($accountId, $periodStart, $periodEnd),
                default => ['revenue' => 0, 'margin' => 0, 'orders' => 0],
            };

            $totalRevenue += $data['revenue'];
            $totalOrders += $data['orders'];

            // Расчёт маржи через себестоимость товаров
            $totalMargin += $this->marginCalculator->calculateMargin(
                $account->marketplace,
                [$accountId],
                $periodStart,
                $periodEnd,
                $account->company_id ?? 0,
            );
        }

        return [
            'revenue' => $totalRevenue,
            'margin' => $totalMargin,
            'orders' => $totalOrders,
        ];
    }

    /**
     * Собрать данные из Uzum заказов
     */
    private function collectUzumData(int $accountId, Carbon $periodStart, Carbon $periodEnd): array
    {
        $orders = \App\Models\UzumOrder::where('marketplace_account_id', $accountId)
            ->whereNotIn('status_normalized', KpiPlan::CANCELLED_ORDER_STATUSES)
            ->whereBetween('ordered_at', [$periodStart, $periodEnd])
            ->selectRaw('
                COUNT(*) as total_orders,
                COALESCE(SUM(total_amount), 0) as total_revenue
            ')
            ->first();

        return [
            'revenue' => (float) ($orders->total_revenue ?? 0),
            'margin' => 0,
            'orders' => (int) ($orders->total_orders ?? 0),
        ];
    }

    /**
     * Собрать данные из Wildberries заказов
     */
    private function collectWildberriesData(int $accountId, Carbon $periodStart, Carbon $periodEnd): array
    {
        $orders = \App\Models\WildberriesOrder::where('marketplace_account_id', $accountId)
            ->whereNotIn('status_normalized', KpiPlan::CANCELLED_ORDER_STATUSES)
            ->whereBetween('order_date', [$periodStart, $periodEnd])
            ->selectRaw('
                COUNT(DISTINCT order_id) as total_orders,
                COALESCE(SUM(total_price), 0) as total_revenue
            ')
            ->first();

        return [
            'revenue' => (float) ($orders->total_revenue ?? 0),
            'margin' => 0,
            'orders' => (int) ($orders->total_orders ?? 0),
        ];
    }

    /**
     * Собрать данные из Ozon заказов
     */
    private function collectOzonData(int $accountId, Carbon $periodStart, Carbon $periodEnd): array
    {
        $orders = \App\Models\OzonOrder::where('marketplace_account_id', $accountId)
            ->whereNotIn('status', KpiPlan::CANCELLED_ORDER_STATUSES)
            ->whereBetween('created_at_ozon', [$periodStart, $periodEnd])
            ->selectRaw('
                COUNT(*) as total_orders,
                COALESCE(SUM(total_price), 0) as total_revenue
            ')
            ->first();

        return [
            'revenue' => (float) ($orders->total_revenue ?? 0),
            'margin' => 0,
            'orders' => (int) ($orders->total_orders ?? 0),
        ];
    }

    /**
     * Собрать данные из Yandex Market заказов
     */
    private function collectYandexMarketData(int $accountId, Carbon $periodStart, Carbon $periodEnd): array
    {
        $orders = \App\Models\YandexMarketOrder::where('marketplace_account_id', $accountId)
            ->whereNotIn('status_normalized', KpiPlan::CANCELLED_ORDER_STATUSES)
            ->whereBetween('created_at_ym', [$periodStart, $periodEnd])
            ->selectRaw('
                COUNT(*) as total_orders,
                COALESCE(SUM(total_price), 0) as total_revenue
            ')
            ->first();

        return [
            'revenue' => (float) ($orders->total_revenue ?? 0),
            'margin' => 0,
            'orders' => (int) ($orders->total_orders ?? 0),
        ];
    }

    /**
     * Собрать исторические данные из ручных продаж за период
     *
     * @return array{revenue: float, margin: float, orders: int}
     */
    private function collectOfflineSaleHistory(int $companyId, SalesSphere $sphere, Carbon $periodStart, Carbon $periodEnd): array
    {
        $saleTypes = $sphere->getOfflineSaleTypes();

        $salesData = OfflineSale::byCompany($companyId)
            ->whereIn('sale_type', $saleTypes)
            ->whereIn('status', [OfflineSale::STATUS_CONFIRMED, OfflineSale::STATUS_SHIPPED, OfflineSale::STATUS_DELIVERED])
            ->whereBetween('sale_date', [$periodStart, $periodEnd])
            ->selectRaw('COALESCE(SUM(total_amount), 0) as total_revenue, COUNT(*) as total_orders')
            ->first();

        $revenue = (float) ($salesData->total_revenue ?? 0);
        $orders = (int) ($salesData->total_orders ?? 0);

        // Маржа: выручка - себестоимость из OfflineSaleItem
        $costData = OfflineSaleItem::whereHas('sale', function ($q) use ($companyId, $saleTypes, $periodStart, $periodEnd) {
            $q->where('company_id', $companyId)
                ->whereIn('sale_type', $saleTypes)
                ->whereIn('status', [OfflineSale::STATUS_CONFIRMED, OfflineSale::STATUS_SHIPPED, OfflineSale::STATUS_DELIVERED])
                ->whereBetween('sale_date', [$periodStart, $periodEnd]);
        })
            ->selectRaw('COALESCE(SUM(line_total), 0) as total_sales, COALESCE(SUM(unit_cost * quantity), 0) as total_cost')
            ->first();

        $margin = (float) (($costData->total_sales ?? 0) - ($costData->total_cost ?? 0));

        return [
            'revenue' => $revenue,
            'margin' => max(0, $margin),
            'orders' => $orders,
        ];
    }

    /**
     * Собрать прошлые KPI-планы для контекста
     */
    private function collectPastPlans(int $companyId, int $employeeId, int $sphereId, int $year, int $month): array
    {
        $plans = KpiPlan::byCompany($companyId)
            ->forEmployee($employeeId)
            ->where('kpi_sales_sphere_id', $sphereId)
            ->whereIn('status', [KpiPlan::STATUS_CALCULATED, KpiPlan::STATUS_APPROVED])
            ->orderByDesc('period_year')
            ->orderByDesc('period_month')
            ->limit(6)
            ->get();

        return $plans->map(fn (KpiPlan $p) => [
            'year' => $p->period_year,
            'month' => $p->period_month,
            'target_revenue' => $p->target_revenue,
            'target_margin' => $p->target_margin,
            'target_orders' => $p->target_orders,
            'actual_revenue' => $p->actual_revenue,
            'actual_margin' => $p->actual_margin,
            'actual_orders' => $p->actual_orders,
            'achievement_percent' => $p->achievement_percent,
            'weight_revenue' => $p->weight_revenue,
            'weight_margin' => $p->weight_margin,
            'weight_orders' => $p->weight_orders,
        ])->toArray();
    }

    private function getSystemPrompt(SalesSphere $sphere): string
    {
        $label1 = $sphere->getMetricLabel(1);
        $label2 = $sphere->getMetricLabel(2);
        $label3 = $sphere->getMetricLabel(3);
        $isManual = $sphere->isManual();

        $roleDescription = $isManual
            ? 'Ты — ИИ-аналитик KPI для различных отделов компании (склад, доставка, поддержка, маркетинг и т.п.).'
            : 'Ты — продвинутый ИИ-аналитик KPI для платформы управления продажами на маркетплейсах СНГ (Wildberries, Ozon, Uzum, Yandex Market).';

        $metricsDescription = $isManual
            ? "Метрики для этой сферы:\n- Метрика 1 \"{$label1}\" → поле target_revenue / actual_revenue\n- Метрика 2 \"{$label2}\" → поле target_margin / actual_margin\n- Метрика 3 \"{$label3}\" → поле target_orders / actual_orders\n\nДля ручных сфер метрики могут быть нечисловыми по смыслу (штуки, минуты, проценты и т.п.) — анализируй контекст названия метрики."
            : "Метрики: Оборот (target_revenue), Маржа (target_margin), Заказы (target_orders).\nОборот обычно имеет наибольший вес (40-60%), маржа (25-40%), заказы (10-25%).";

        return <<<PROMPT
{$roleDescription}

ВАЖНО: Компания работает в Узбекистане. Для денежных метрик используй узбекские сумы (сум, UZS).

Твоя задача — на основе анализа исторических данных рекомендовать оптимальные KPI-планы.

{$metricsDescription}

Правила анализа:
- ОБЯЗАТЕЛЬНО анализируй предоставленные исторические данные (если есть)
- Если исторических данных НЕТ — дай консервативные стартовые цели
- Если данные ЕСТЬ — анализируй тренды (рост/падение/стагнация)
- Учитывай сезонность (праздники, Рамадан)
- Целевые показатели должны быть амбициозными но достижимыми (рост 5-20%)
- Веса метрик должны в сумме давать 100
- Учитывай специфику должности и профиль сотрудника

Ответ строго в формате JSON:
{
    "target_revenue": число (целевое значение метрики 1 "{$label1}"),
    "target_margin": число (целевое значение метрики 2 "{$label2}"),
    "target_orders": число (целевое значение метрики 3 "{$label3}"),
    "weight_revenue": число (0-100, вес метрики "{$label1}"),
    "weight_margin": число (0-100, вес метрики "{$label2}"),
    "weight_orders": число (0-100, вес метрики "{$label3}"),
    "reasoning": "Подробное объяснение на русском языке (3-5 предложений)"
}
PROMPT;
    }

    private function buildPrompt(Employee $employee, SalesSphere $sphere, array $history, array $pastPlans, int $year, int $month): string
    {
        $monthNames = [1 => 'Январь', 2 => 'Февраль', 3 => 'Март', 4 => 'Апрель', 5 => 'Май', 6 => 'Июнь', 7 => 'Июль', 8 => 'Август', 9 => 'Сентябрь', 10 => 'Октябрь', 11 => 'Ноябрь', 12 => 'Декабрь'];

        $label1 = $sphere->getMetricLabel(1);
        $label2 = $sphere->getMetricLabel(2);
        $label3 = $sphere->getMetricLabel(3);
        $isManual = $sphere->isManual();

        $prompt = "Создай KPI-план для сотрудника в Узбекистане.\n\n";

        if (! $isManual) {
            $prompt .= "ВАЛЮТА: Узбекский сум (сум, UZS) — все суммы указывай только в сумах!\n\n";
        }

        $prompt .= "Сотрудник: {$employee->full_name}\n";
        $prompt .= "Должность: " . ($employee->position ?? 'не указана') . "\n";
        $prompt .= "Сфера: {$sphere->name}" . ($sphere->description ? " ({$sphere->description})" : '') . "\n";
        $prompt .= 'Тип: ' . ($isManual ? 'Ручной (не продажи — склад, доставка, поддержка и т.п.)' : 'Автоматический (продажи на маркетплейсах)') . "\n";
        $prompt .= "Метрики: 1) {$label1}, 2) {$label2}, 3) {$label3}\n";
        $prompt .= "Период: {$monthNames[$month]} {$year}\n\n";

        // Историческая статистика
        $prompt .= "Историческая статистика (последние 6 месяцев):\n";
        $hasData = false;
        foreach (array_reverse($history) as $h) {
            $mName = $monthNames[$h['month']] ?? $h['month'];
            if ($h['revenue'] > 0 || $h['orders'] > 0 || $h['margin'] > 0) {
                $hasData = true;
                $prompt .= "- {$mName} {$h['year']}: {$label1}=" . number_format($h['revenue'], 0, '.', ' ') . ", {$label2}=" . number_format($h['margin'], 0, '.', ' ') . ", {$label3}={$h['orders']}\n";
            } else {
                $prompt .= "- {$mName} {$h['year']}: нет данных\n";
            }
        }

        if (! $hasData) {
            $prompt .= "\n⚠️ ВАЖНО: Исторических данных НЕТ!\n";
            $prompt .= "Это новый сотрудник/сфера без истории.\n";
            $prompt .= "Предложи начальные консервативные цели.\n\n";
        }

        // Прошлые KPI-планы
        if (! empty($pastPlans)) {
            $prompt .= "\nПрошлые KPI-планы:\n";
            foreach ($pastPlans as $p) {
                $mName = $monthNames[$p['month']] ?? $p['month'];
                $prompt .= "- {$mName} {$p['year']}: план({$label1}=" . number_format($p['target_revenue'], 0, '.', ' ') . ", {$label2}=" . number_format($p['target_margin'], 0, '.', ' ') . ", {$label3}={$p['target_orders']}) → факт({$label1}=" . number_format($p['actual_revenue'], 0, '.', ' ') . ", {$label2}=" . number_format($p['actual_margin'], 0, '.', ' ') . ", {$label3}={$p['actual_orders']}) → выполнение={$p['achievement_percent']}%\n";
            }
        }

        $prompt .= "\nОтветь ТОЛЬКО в формате JSON, без лишнего текста.";

        return $prompt;
    }

    /**
     * Распарсить ответ ИИ в структурированный массив
     */
    private function parseResponse(string $content, array $history): array
    {
        $defaults = $this->calculateDefaults($history);

        if (preg_match('/\{[\s\S]*\}/', $content, $matches)) {
            $decoded = json_decode($matches[0], true);
            if ($decoded) {
                return [
                    'target_revenue' => round((float) ($decoded['target_revenue'] ?? $defaults['target_revenue']), 0),
                    'target_margin' => round((float) ($decoded['target_margin'] ?? $defaults['target_margin']), 0),
                    'target_orders' => (int) ($decoded['target_orders'] ?? $defaults['target_orders']),
                    'weight_revenue' => (int) ($decoded['weight_revenue'] ?? 40),
                    'weight_margin' => (int) ($decoded['weight_margin'] ?? 40),
                    'weight_orders' => (int) ($decoded['weight_orders'] ?? 20),
                    'reasoning' => $decoded['reasoning'] ?? 'Рекомендация на основе анализа исторических данных.',
                ];
            }
        }

        // Fallback — используем средние за последние 3 месяца + 10%
        return array_merge($defaults, [
            'reasoning' => 'Не удалось получить ИИ-рекомендацию. Показаны средние значения за последние месяцы с ростом 10%.',
        ]);
    }

    /**
     * Рассчитать дефолтные значения на основе средних
     */
    private function calculateDefaults(array $history): array
    {
        $withData = array_filter($history, fn ($h) => $h['revenue'] > 0 || $h['orders'] > 0);

        if (empty($withData)) {
            return [
                'target_revenue' => 0,
                'target_margin' => 0,
                'target_orders' => 0,
                'weight_revenue' => 40,
                'weight_margin' => 40,
                'weight_orders' => 20,
            ];
        }

        $count = count($withData);
        $avgRevenue = array_sum(array_column($withData, 'revenue')) / $count;
        $avgMargin = array_sum(array_column($withData, 'margin')) / $count;
        $avgOrders = array_sum(array_column($withData, 'orders')) / $count;

        return [
            'target_revenue' => round($avgRevenue * 1.1, 0),
            'target_margin' => round($avgMargin * 1.1, 0),
            'target_orders' => (int) round($avgOrders * 1.1),
            'weight_revenue' => 40,
            'weight_margin' => 40,
            'weight_orders' => 20,
        ];
    }
}
