<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Административный контроллер для управления тарифными планами
 */
class PlanAdminController extends Controller
{
    /**
     * Список всех тарифов (включая неактивные) с количеством подписчиков
     */
    public function index(Request $request): JsonResponse
    {
        if (! $request->user()->is_admin) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        $plans = Plan::ordered()
            ->withCount(['subscriptions as active_subscribers_count' => function ($query) {
                $query->whereIn('status', ['active', 'trial']);
            }])
            ->get();

        return response()->json([
            'plans' => $plans,
        ]);
    }

    /**
     * Создать новый тарифный план
     */
    public function store(Request $request): JsonResponse
    {
        if (! $request->user()->is_admin) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:100', 'unique:plans,slug'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'currency' => ['string', 'max:10'],
            'billing_period' => ['string', 'in:monthly,quarterly,yearly'],
            'max_marketplace_accounts' => ['required', 'integer', 'min:0'],
            'max_products' => ['required', 'integer', 'min:0'],
            'max_orders_per_month' => ['required', 'integer', 'min:0'],
            'max_users' => ['required', 'integer', 'min:0'],
            'max_warehouses' => ['required', 'integer', 'min:0'],
            'max_ai_requests' => ['required', 'integer', 'min:0'],
            'data_retention_days' => ['required', 'integer', 'min:1'],
            'has_api_access' => ['boolean'],
            'has_priority_support' => ['boolean'],
            'has_telegram_notifications' => ['boolean'],
            'has_auto_pricing' => ['boolean'],
            'has_analytics' => ['boolean'],
            'allowed_marketplaces' => ['array'],
            'allowed_marketplaces.*' => ['string', 'in:uzum,wb,ozon,yandex'],
            'features' => ['array'],
            'features.*' => ['string'],
            'sort_order' => ['integer', 'min:0'],
            'is_active' => ['boolean'],
            'is_popular' => ['boolean'],
        ]);

        $plan = Plan::create($validated);

        return response()->json([
            'plan' => $plan,
            'message' => 'Тариф успешно создан.',
        ], 201);
    }

    /**
     * Показать тариф с количеством подписчиков
     */
    public function show(Request $request, Plan $plan): JsonResponse
    {
        if (! $request->user()->is_admin) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        $plan->loadCount(['subscriptions as active_subscribers_count' => function ($query) {
            $query->whereIn('status', ['active', 'trial']);
        }]);

        return response()->json([
            'plan' => $plan,
        ]);
    }

    /**
     * Обновить тарифный план
     */
    public function update(Request $request, Plan $plan): JsonResponse
    {
        if (! $request->user()->is_admin) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:100', 'unique:plans,slug,'.$plan->id],
            'description' => ['nullable', 'string'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'currency' => ['string', 'max:10'],
            'billing_period' => ['string', 'in:monthly,quarterly,yearly'],
            'max_marketplace_accounts' => ['sometimes', 'integer', 'min:0'],
            'max_products' => ['sometimes', 'integer', 'min:0'],
            'max_orders_per_month' => ['sometimes', 'integer', 'min:0'],
            'max_users' => ['sometimes', 'integer', 'min:0'],
            'max_warehouses' => ['sometimes', 'integer', 'min:0'],
            'max_ai_requests' => ['sometimes', 'integer', 'min:0'],
            'data_retention_days' => ['sometimes', 'integer', 'min:1'],
            'has_api_access' => ['boolean'],
            'has_priority_support' => ['boolean'],
            'has_telegram_notifications' => ['boolean'],
            'has_auto_pricing' => ['boolean'],
            'has_analytics' => ['boolean'],
            'allowed_marketplaces' => ['array'],
            'allowed_marketplaces.*' => ['string', 'in:uzum,wb,ozon,yandex'],
            'features' => ['array'],
            'features.*' => ['string'],
            'sort_order' => ['integer', 'min:0'],
            'is_active' => ['boolean'],
            'is_popular' => ['boolean'],
        ]);

        $plan->update($validated);

        return response()->json([
            'plan' => $plan->fresh(),
            'message' => 'Тариф успешно обновлён.',
        ]);
    }

    /**
     * Удалить тарифный план (только если нет активных подписок)
     */
    public function destroy(Request $request, Plan $plan): JsonResponse
    {
        if (! $request->user()->is_admin) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        $activeCount = $plan->subscriptions()
            ->whereIn('status', ['active', 'trial'])
            ->count();

        if ($activeCount > 0) {
            return response()->json([
                'message' => "Невозможно удалить тариф с активными подписками ({$activeCount}).",
            ], 409);
        }

        $plan->delete();

        return response()->json(null, 204);
    }

    /**
     * Переключить активность тарифа
     */
    public function toggleActive(Request $request, Plan $plan): JsonResponse
    {
        if (! $request->user()->is_admin) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        $plan->update(['is_active' => ! $plan->is_active]);

        return response()->json([
            'plan' => $plan->fresh(),
            'message' => $plan->is_active ? 'Тариф активирован.' : 'Тариф деактивирован.',
        ]);
    }
}
