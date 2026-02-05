<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPlanLimits
{
    /**
     * Handle an incoming request.
     *
     * This middleware checks if the company has reached its plan limits.
     * Usage: Route::middleware('plan.limits:products,1')
     */
    public function handle(Request $request, Closure $next, ?string $limitType = null, int $count = 1): Response
    {
        try {
            $user = $request->user();

            if (! $user || ! $user->company_id) {
                return $next($request);
            }

            $company = $user->companies()->where('companies.id', $user->company_id)->first();

            if (! $company) {
                return $next($request);
            }

            $subscription = $company->activeSubscription;

            if (! $subscription) {
                return $this->limitExceeded($request, 'Нет активной подписки. Пожалуйста, выберите тарифный план.');
            }

            // If no specific limit type provided, just check if subscription is active
            if (! $limitType) {
                return $next($request);
            }

            // Check specific limit
            $canProceed = match ($limitType) {
                'products' => $subscription->canAddProducts($count),
                'orders' => $subscription->canProcessOrders($count),
                'ai' => $subscription->canUseAI($count),
                'marketplace_accounts' => $this->checkMarketplaceAccounts($company, $subscription, $count),
                'users' => $this->checkUsers($company, $subscription, $count),
                'warehouses' => $this->checkWarehouses($company, $subscription, $count),
                default => true,
            };

            if (! $canProceed) {
                return $this->limitExceeded(
                    $request,
                    $this->getLimitMessage($limitType, $company, $subscription),
                    $limitType
                );
            }

            return $next($request);
        } catch (\Exception $e) {
            \Log::error('CheckPlanLimits middleware error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'limit_type' => $limitType,
            ]);

            // В случае ошибки - пропускаем проверку лимита (graceful degradation)
            // Лучше разрешить действие, чем заблокировать пользователя из-за технической ошибки
            return $next($request);
        }
    }

    /**
     * Check marketplace accounts limit
     */
    protected function checkMarketplaceAccounts($company, $subscription, int $count): bool
    {
        if (! $subscription->plan) {
            return true; // Allow if plan not found (graceful degradation)
        }
        $current = $company->marketplaceAccounts()->count();

        return ($current + $count) <= ($subscription->plan->max_marketplace_accounts ?? PHP_INT_MAX);
    }

    /**
     * Check users limit
     */
    protected function checkUsers($company, $subscription, int $count): bool
    {
        if (! $subscription->plan) {
            return true; // Allow if plan not found (graceful degradation)
        }
        $current = $company->users()->count();

        return ($current + $count) <= ($subscription->plan->max_users ?? PHP_INT_MAX);
    }

    /**
     * Check warehouses limit
     */
    protected function checkWarehouses($company, $subscription, int $count): bool
    {
        if (! $subscription->plan) {
            return true; // Allow if plan not found (graceful degradation)
        }
        $current = $company->warehouses()->count();

        return ($current + $count) <= ($subscription->plan->max_warehouses ?? PHP_INT_MAX);
    }

    /**
     * Get user-friendly limit message with current usage info
     */
    protected function getLimitMessage(string $limitType, $company = null, $subscription = null): string
    {
        $plan = $subscription?->plan;

        // Получаем информацию об использовании для более информативного сообщения
        $usageInfo = '';
        if ($company && $plan) {
            $usageInfo = match ($limitType) {
                'marketplace_accounts' => sprintf(
                    ' (используется %d из %d)',
                    $company->marketplaceAccounts()->count(),
                    $plan->max_marketplace_accounts ?? 0
                ),
                'products' => sprintf(
                    ' (используется %d из %d)',
                    $company->products()->count(),
                    $plan->max_products ?? 0
                ),
                'users' => sprintf(
                    ' (используется %d из %d)',
                    $company->users()->count(),
                    $plan->max_users ?? 0
                ),
                'warehouses' => sprintf(
                    ' (используется %d из %d)',
                    $company->warehouses()->count(),
                    $plan->max_warehouses ?? 0
                ),
                default => '',
            };
        }

        $baseMessage = match ($limitType) {
            'products' => 'Достигнут лимит товаров для вашего тарифного плана.',
            'orders' => 'Достигнут месячный лимит заказов для вашего тарифного плана.',
            'ai' => 'Достигнут лимит AI-запросов для вашего тарифного плана.',
            'marketplace_accounts' => 'Достигнут лимит подключений к маркетплейсам для вашего тарифного плана.',
            'users' => 'Достигнут лимит пользователей для вашего тарифного плана.',
            'warehouses' => 'Достигнут лимит складов для вашего тарифного плана.',
            default => 'Достигнут лимит для вашего тарифного плана.',
        };

        return $baseMessage.$usageInfo.' Обновите план для увеличения лимитов.';
    }

    /**
     * Return limit exceeded response
     */
    protected function limitExceeded(Request $request, string $message, ?string $limitType = null): Response
    {
        // Безопасно генерируем URL (может не существовать на некоторых конфигурациях)
        $upgradeUrl = '/pricing';
        try {
            $upgradeUrl = route('pricing.index');
        } catch (\Exception $e) {
            // Fallback to direct URL if route not found
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'message' => $message,
                'error' => 'plan_limit_exceeded',
                'limit_type' => $limitType,
                'upgrade_url' => $upgradeUrl,
            ], 402);
        }

        return redirect()
            ->back()
            ->with('error', $message)
            ->with('upgrade_url', $upgradeUrl);
    }
}
