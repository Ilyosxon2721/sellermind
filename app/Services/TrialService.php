<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Company;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Support\Facades\Log;

/**
 * Сервис управления пробным периодом (15 дней)
 *
 * При регистрации пользователь получает trial с полным функционалом
 * на уровне тарифа «Про» (все маркетплейсы, все функции).
 */
final class TrialService
{
    /**
     * Длительность пробного периода в днях
     */
    public const TRIAL_DAYS = 15;

    /**
     * Slug тарифа для пробного периода (полный функционал)
     */
    public const TRIAL_PLAN_SLUG = 'pro';

    /**
     * Создать пробную подписку для компании
     */
    public function createTrial(Company $company): Subscription
    {
        $plan = Plan::where('slug', self::TRIAL_PLAN_SLUG)->first();

        if (! $plan) {
            Log::error('TrialService: тариф для пробного периода не найден', [
                'slug' => self::TRIAL_PLAN_SLUG,
            ]);

            throw new \RuntimeException('Тариф для пробного периода не найден. Запустите db:seed --class=PlanSeeder');
        }

        $now = now();
        $trialEndsAt = $now->copy()->addDays(self::TRIAL_DAYS);

        $subscription = Subscription::create([
            'company_id' => $company->id,
            'plan_id' => $plan->id,
            'status' => 'trial',
            'starts_at' => $now,
            'ends_at' => $trialEndsAt,
            'trial_ends_at' => $trialEndsAt,
            'amount_paid' => 0,
            'payment_method' => null,
            'current_products_count' => 0,
            'current_orders_count' => 0,
            'current_ai_requests' => 0,
            'usage_reset_at' => $now,
            'notes' => 'Пробный период 15 дней. Полный функционал тарифа «Про».',
        ]);

        Log::info('TrialService: создан пробный период', [
            'company_id' => $company->id,
            'plan_id' => $plan->id,
            'trial_ends_at' => $trialEndsAt->toIso8601String(),
        ]);

        return $subscription;
    }

    /**
     * Проверить, была ли у компании уже пробная подписка
     */
    public function hasHadTrial(Company $company): bool
    {
        return $company->subscriptions()
            ->where('status', 'trial')
            ->orWhere(function ($query) use ($company) {
                $query->where('company_id', $company->id)
                    ->whereNotNull('trial_ends_at');
            })
            ->exists();
    }

    /**
     * Получить оставшиеся дни пробного периода
     */
    public function getTrialDaysRemaining(Subscription $subscription): int
    {
        if (! $subscription->trial_ends_at) {
            return 0;
        }

        return max(0, (int) now()->diffInDays($subscription->trial_ends_at, false));
    }

    /**
     * Проверить, активен ли пробный период
     */
    public function isTrialActive(Subscription $subscription): bool
    {
        return $subscription->status === 'trial'
            && $subscription->trial_ends_at !== null
            && $subscription->trial_ends_at->isFuture();
    }
}
