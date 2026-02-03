<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscription
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // If no user, let auth middleware handle it
        if (! $user) {
            return $next($request);
        }

        $companyId = $user->company_id;

        // If no company, allow access (they need to create one first)
        if (! $companyId) {
            return $next($request);
        }

        // Get company - try direct relation first, then many-to-many if available
        $company = $user->company;

        // If company doesn't match company_id, user might have switched companies
        if (! $company || $company->id !== $companyId) {
            // Try to find in user's companies (if many-to-many exists)
            if (method_exists($user, 'companies') && $user->companies()->exists()) {
                $company = $user->companies()->where('companies.id', $companyId)->first();
            }
        }

        if (! $company) {
            return $this->handleNoSubscription($request);
        }

        $subscription = $company->activeSubscription;

        // No active subscription
        if (! $subscription) {
            return $this->handleNoSubscription($request);
        }

        // Check if subscription is expired
        if ($subscription->isExpired()) {
            return $this->handleExpiredSubscription($request, $subscription);
        }

        // Attach subscription to request for easy access in controllers
        $request->attributes->set('subscription', $subscription);
        $request->attributes->set('plan', $subscription->plan);

        return $next($request);
    }

    /**
     * Handle case when company has no subscription
     */
    protected function handleNoSubscription(Request $request): Response
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'message' => 'У вашей компании нет активной подписки. Пожалуйста, выберите тарифный план.',
                'error' => 'no_subscription',
                'redirect' => route('pricing.index'),
            ], 402);
        }

        return redirect()
            ->route('pricing.index')
            ->with('error', 'У вашей компании нет активной подписки. Пожалуйста, выберите тарифный план.');
    }

    /**
     * Handle case when subscription is expired
     */
    protected function handleExpiredSubscription(Request $request, $subscription): Response
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'message' => 'Ваша подписка истекла. Пожалуйста, продлите подписку.',
                'error' => 'subscription_expired',
                'expired_at' => $subscription->ends_at?->toIso8601String(),
                'redirect' => route('pricing.index'),
            ], 402);
        }

        return redirect()
            ->route('pricing.index')
            ->with('error', 'Ваша подписка истекла. Пожалуйста, продлите подписку.');
    }
}
