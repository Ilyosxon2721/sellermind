<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    /**
     * Get company subscription status and limits
     */
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();
        $companyId = $user->company_id;

        if (!$companyId) {
            return response()->json([
                'message' => 'Компания не найдена',
                'has_subscription' => false,
            ], 404);
        }

        $company = Company::with(['activeSubscription.plan'])->find($companyId);

        if (!$company) {
            return response()->json([
                'message' => 'Компания не найдена',
                'has_subscription' => false,
            ], 404);
        }

        $subscription = $company->activeSubscription;

        if (!$subscription) {
            return response()->json([
                'has_subscription' => false,
                'message' => 'Нет активной подписки',
            ]);
        }

        $plan = $subscription->plan;

        return response()->json([
            'has_subscription' => true,
            'subscription' => [
                'id' => $subscription->id,
                'status' => $subscription->status,
                'starts_at' => $subscription->starts_at?->toIso8601String(),
                'ends_at' => $subscription->ends_at?->toIso8601String(),
                'trial_ends_at' => $subscription->trial_ends_at?->toIso8601String(),
                'days_remaining' => $subscription->daysRemaining(),
                'is_trial' => $subscription->isTrial(),
                'is_expired' => $subscription->isExpired(),
            ],
            'plan' => [
                'id' => $plan->id,
                'name' => $plan->name,
                'slug' => $plan->slug,
                'price' => $plan->price,
                'formatted_price' => $plan->formatted_price,
                'currency' => $plan->currency,
                'billing_period' => $plan->billing_period,
            ],
            'usage' => [
                'products' => [
                    'current' => $subscription->current_products_count,
                    'max' => $plan->max_products,
                    'percentage' => $plan->max_products > 0
                        ? round(($subscription->current_products_count / $plan->max_products) * 100, 1)
                        : 0,
                ],
                'orders' => [
                    'current' => $subscription->current_orders_count,
                    'max' => $plan->max_orders_per_month,
                    'percentage' => $plan->max_orders_per_month > 0
                        ? round(($subscription->current_orders_count / $plan->max_orders_per_month) * 100, 1)
                        : 0,
                ],
                'ai_requests' => [
                    'current' => $subscription->current_ai_requests,
                    'max' => $plan->max_ai_requests,
                    'percentage' => $plan->max_ai_requests > 0
                        ? round(($subscription->current_ai_requests / $plan->max_ai_requests) * 100, 1)
                        : 0,
                ],
                'marketplace_accounts' => [
                    'current' => $company->marketplaceAccounts()->count(),
                    'max' => $plan->max_marketplace_accounts,
                ],
                'users' => [
                    'current' => $company->users()->count(),
                    'max' => $plan->max_users,
                ],
                'warehouses' => [
                    'current' => $company->warehouses()->count(),
                    'max' => $plan->max_warehouses,
                ],
            ],
            'limits' => [
                'marketplace_accounts' => $plan->max_marketplace_accounts,
                'products' => $plan->max_products,
                'orders_per_month' => $plan->max_orders_per_month,
                'users' => $plan->max_users,
                'warehouses' => $plan->max_warehouses,
                'ai_requests' => $plan->max_ai_requests,
                'data_retention_days' => $plan->data_retention_days,
            ],
        ]);
    }

    /**
     * Subscribe to a plan
     */
    public function subscribe(Request $request): JsonResponse
    {
        $request->validate([
            'plan_id' => ['required', 'exists:plans,id'],
            'billing_period' => ['required', 'in:monthly,quarterly,yearly'],
        ]);

        $user = $request->user();
        $companyId = $user->company_id;

        if (!$companyId) {
            return response()->json([
                'message' => 'Компания не найдена',
            ], 404);
        }

        $company = Company::find($companyId);

        if (!$company) {
            return response()->json([
                'message' => 'Компания не найдена',
            ], 404);
        }

        // Check if user is owner
        if (!$user->isOwnerOf($companyId)) {
            return response()->json([
                'message' => 'Только владелец компании может изменять подписку',
            ], 403);
        }

        $plan = Plan::active()->find($request->plan_id);

        if (!$plan) {
            return response()->json([
                'message' => 'Тариф не найден',
            ], 404);
        }

        // Cancel existing active subscription
        $existingSubscription = $company->activeSubscription;
        if ($existingSubscription) {
            $existingSubscription->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);
        }

        // Calculate end date based on billing period
        $endsAt = match($request->billing_period) {
            'monthly' => now()->addMonth(),
            'quarterly' => now()->addMonths(3),
            'yearly' => now()->addYear(),
        };

        // Create new subscription
        $subscription = Subscription::create([
            'company_id' => $companyId,
            'plan_id' => $plan->id,
            'status' => 'pending', // Will be activated after payment
            'starts_at' => now(),
            'ends_at' => $endsAt,
            'amount_paid' => 0,
            'payment_method' => null,
            'current_products_count' => $company->products()->count(),
            'current_orders_count' => 0,
            'current_ai_requests' => 0,
            'usage_reset_at' => now(),
        ]);

        return response()->json([
            'message' => 'Подписка создана. Перейдите к оплате.',
            'subscription' => [
                'id' => $subscription->id,
                'plan' => $plan->name,
                'amount' => $plan->price,
                'currency' => $plan->currency,
                'starts_at' => $subscription->starts_at->toIso8601String(),
                'ends_at' => $subscription->ends_at->toIso8601String(),
            ],
            'payment_required' => true,
            'payment_url' => route('payment.initiate', ['subscription' => $subscription->id]),
        ], 201);
    }

    /**
     * Cancel subscription
     */
    public function cancel(Request $request): JsonResponse
    {
        $user = $request->user();
        $companyId = $user->company_id;

        if (!$companyId) {
            return response()->json([
                'message' => 'Компания не найдена',
            ], 404);
        }

        // Check if user is owner
        if (!$user->isOwnerOf($companyId)) {
            return response()->json([
                'message' => 'Только владелец компании может отменять подписку',
            ], 403);
        }

        $company = Company::find($companyId);
        $subscription = $company->activeSubscription;

        if (!$subscription) {
            return response()->json([
                'message' => 'Нет активной подписки',
            ], 404);
        }

        $subscription->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        return response()->json([
            'message' => 'Подписка отменена',
        ]);
    }

    /**
     * Extend/renew subscription
     */
    public function renew(Request $request): JsonResponse
    {
        $request->validate([
            'billing_period' => ['required', 'in:monthly,quarterly,yearly'],
        ]);

        $user = $request->user();
        $companyId = $user->company_id;

        if (!$companyId) {
            return response()->json([
                'message' => 'Компания не найдена',
            ], 404);
        }

        // Check if user is owner
        if (!$user->isOwnerOf($companyId)) {
            return response()->json([
                'message' => 'Только владелец компании может продлевать подписку',
            ], 403);
        }

        $company = Company::find($companyId);
        $subscription = $company->activeSubscription;

        if (!$subscription) {
            return response()->json([
                'message' => 'Нет активной подписки для продления',
            ], 404);
        }

        $plan = $subscription->plan;

        // Calculate new end date
        $currentEnd = $subscription->ends_at ?? now();
        $newEndsAt = match($request->billing_period) {
            'monthly' => $currentEnd->addMonth(),
            'quarterly' => $currentEnd->addMonths(3),
            'yearly' => $currentEnd->addYear(),
        };

        // Create payment intent (status stays active, ends_at extended after payment)
        return response()->json([
            'message' => 'Перейдите к оплате для продления подписки',
            'subscription' => [
                'id' => $subscription->id,
                'plan' => $plan->name,
                'amount' => $plan->price,
                'currency' => $plan->currency,
                'current_ends_at' => $currentEnd->toIso8601String(),
                'new_ends_at' => $newEndsAt->toIso8601String(),
            ],
            'payment_required' => true,
            'payment_url' => route('payment.renew', ['subscription' => $subscription->id]),
        ]);
    }

    /**
     * Get subscription history
     */
    public function history(Request $request): JsonResponse
    {
        $user = $request->user();
        $companyId = $user->company_id;

        if (!$companyId) {
            return response()->json([
                'message' => 'Компания не найдена',
            ], 404);
        }

        $company = Company::find($companyId);
        $subscriptions = $company->subscriptions()
            ->with('plan')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($subscription) {
                return [
                    'id' => $subscription->id,
                    'plan' => $subscription->plan->name,
                    'status' => $subscription->status,
                    'amount_paid' => $subscription->amount_paid,
                    'payment_method' => $subscription->payment_method,
                    'starts_at' => $subscription->starts_at?->toIso8601String(),
                    'ends_at' => $subscription->ends_at?->toIso8601String(),
                    'cancelled_at' => $subscription->cancelled_at?->toIso8601String(),
                ];
            });

        return response()->json([
            'subscriptions' => $subscriptions,
        ]);
    }
}
