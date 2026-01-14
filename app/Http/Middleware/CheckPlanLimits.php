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
        $user = $request->user();

        if (!$user || !$user->company_id) {
            return $next($request);
        }

        $company = $user->companies()->where('companies.id', $user->company_id)->first();

        if (!$company) {
            return $next($request);
        }

        $subscription = $company->activeSubscription;

        if (!$subscription) {
            return $this->limitExceeded($request, 'Нет активной подписки');
        }

        // If no specific limit type provided, just check if subscription is active
        if (!$limitType) {
            return $next($request);
        }

        // Check specific limit
        $canProceed = match($limitType) {
            'products' => $subscription->canAddProducts($count),
            'orders' => $subscription->canProcessOrders($count),
            'ai' => $subscription->canUseAI($count),
            'marketplace_accounts' => $this->checkMarketplaceAccounts($company, $subscription, $count),
            'users' => $this->checkUsers($company, $subscription, $count),
            'warehouses' => $this->checkWarehouses($company, $subscription, $count),
            default => true,
        };

        if (!$canProceed) {
            return $this->limitExceeded(
                $request,
                $this->getLimitMessage($limitType),
                $limitType
            );
        }

        return $next($request);
    }

    /**
     * Check marketplace accounts limit
     */
    protected function checkMarketplaceAccounts($company, $subscription, int $count): bool
    {
        if (!$subscription->plan) {
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
        if (!$subscription->plan) {
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
        if (!$subscription->plan) {
            return true; // Allow if plan not found (graceful degradation)
        }
        $current = $company->warehouses()->count();
        return ($current + $count) <= ($subscription->plan->max_warehouses ?? PHP_INT_MAX);
    }

    /**
     * Get user-friendly limit message
     */
    protected function getLimitMessage(string $limitType): string
    {
        return match($limitType) {
            'products' => 'Достигнут лимит товаров для вашего тарифного плана. Пожалуйста, обновите план.',
            'orders' => 'Достигнут месячный лимит заказов для вашего тарифного плана.',
            'ai' => 'Достигнут лимит AI-запросов для вашего тарифного плана.',
            'marketplace_accounts' => 'Достигнут лимит подключений к маркетплейсам для вашего тарифного плана.',
            'users' => 'Достигнут лимит пользователей для вашего тарифного плана.',
            'warehouses' => 'Достигнут лимит складов для вашего тарифного плана.',
            default => 'Достигнут лимит для вашего тарифного плана.',
        };
    }

    /**
     * Return limit exceeded response
     */
    protected function limitExceeded(Request $request, string $message, ?string $limitType = null): Response
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'message' => $message,
                'error' => 'plan_limit_exceeded',
                'limit_type' => $limitType,
                'upgrade_url' => route('pricing.index'),
            ], 402);
        }

        return redirect()
            ->back()
            ->with('error', $message)
            ->with('upgrade_url', route('pricing.index'));
    }
}
