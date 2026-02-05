<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    /**
     * Get all active plans
     */
    public function index(Request $request): JsonResponse
    {
        $plans = Plan::active()
            ->ordered()
            ->get()
            ->map(function ($plan) {
                return [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'slug' => $plan->slug,
                    'description' => $plan->description,
                    'price' => $plan->price,
                    'formatted_price' => $plan->formatted_price,
                    'currency' => $plan->currency,
                    'billing_period' => $plan->billing_period,
                    'limits' => [
                        'marketplace_accounts' => $plan->max_marketplace_accounts,
                        'products' => $plan->max_products,
                        'orders_per_month' => $plan->max_orders_per_month,
                        'users' => $plan->max_users,
                        'warehouses' => $plan->max_warehouses,
                        'ai_requests' => $plan->max_ai_requests,
                        'data_retention_days' => $plan->data_retention_days,
                    ],
                    'features' => [
                        'api_access' => $plan->has_api_access,
                        'priority_support' => $plan->has_priority_support,
                        'telegram_notifications' => $plan->has_telegram_notifications,
                        'auto_pricing' => $plan->has_auto_pricing,
                        'analytics' => $plan->has_analytics,
                    ],
                    'allowed_marketplaces' => $plan->allowed_marketplaces,
                    'feature_list' => $plan->features,
                    'is_popular' => $plan->is_popular,
                    'sort_order' => $plan->sort_order,
                ];
            });

        return response()->json([
            'plans' => $plans,
        ]);
    }

    /**
     * Get a specific plan by slug or ID
     */
    public function show(Request $request, string $slugOrId): JsonResponse
    {
        $plan = is_numeric($slugOrId)
            ? Plan::active()->find($slugOrId)
            : Plan::active()->where('slug', $slugOrId)->first();

        if (! $plan) {
            return response()->json([
                'message' => 'Тариф не найден',
            ], 404);
        }

        return response()->json([
            'plan' => [
                'id' => $plan->id,
                'name' => $plan->name,
                'slug' => $plan->slug,
                'description' => $plan->description,
                'price' => $plan->price,
                'formatted_price' => $plan->formatted_price,
                'currency' => $plan->currency,
                'billing_period' => $plan->billing_period,
                'limits' => [
                    'marketplace_accounts' => $plan->max_marketplace_accounts,
                    'products' => $plan->max_products,
                    'orders_per_month' => $plan->max_orders_per_month,
                    'users' => $plan->max_users,
                    'warehouses' => $plan->max_warehouses,
                    'ai_requests' => $plan->max_ai_requests,
                    'data_retention_days' => $plan->data_retention_days,
                ],
                'features' => [
                    'api_access' => $plan->has_api_access,
                    'priority_support' => $plan->has_priority_support,
                    'telegram_notifications' => $plan->has_telegram_notifications,
                    'auto_pricing' => $plan->has_auto_pricing,
                    'analytics' => $plan->has_analytics,
                ],
                'allowed_marketplaces' => $plan->allowed_marketplaces,
                'feature_list' => $plan->features,
                'is_popular' => $plan->is_popular,
            ],
        ]);
    }
}
