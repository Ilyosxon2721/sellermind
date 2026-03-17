<?php

declare(strict_types=1);

namespace App\Observers\Traits;

use App\Jobs\SendTelegramOrderNotification;
use App\Models\TelegramSubscription;
use App\Notifications\NewMarketplaceOrderNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

trait NotifiesMarketplaceOrder
{
    /**
     * Отправить уведомление о новом заказе с маркетплейса (старая система через $user->notify).
     * Используется только для записи в БД уведомлений — Telegram отправляется через notifySubscribers.
     */
    protected function notifyNewMarketplaceOrder(
        Model $order,
        string $marketplace,
        string $orderNumber,
        float $totalAmount,
        string $currency,
    ): void {
        try {
            // Дедупликация: не отправлять повторно для того же заказа (24 часа кэш)
            $cacheKey = "notify:new:{$marketplace}:{$orderNumber}";
            if (Cache::has($cacheKey)) {
                return;
            }
            Cache::put($cacheKey, true, 86400);

            $account = $order->account;
            if (! $account) {
                return;
            }

            $company = $account->company;
            if (! $company) {
                return;
            }

            $users = $company->users()
                ->whereNotNull('telegram_id')
                ->with('notificationSettings')
                ->get();

            foreach ($users as $user) {
                $user->notify(new NewMarketplaceOrderNotification(
                    marketplace: $marketplace,
                    accountName: $account->name ?? $marketplace,
                    orderNumber: $orderNumber,
                    totalAmount: $totalAmount,
                    currency: $currency,
                    order: $order,
                ));
            }
        } catch (\Exception $e) {
            Log::error('Failed to dispatch marketplace order notification', [
                'marketplace' => $marketplace,
                'order_number' => $orderNumber,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Отправить уведомления подписчикам TelegramSubscription (новая система).
     * Включает статус в дедупликацию чтобы не спамить одним статусом.
     */
    protected function notifySubscribers(Model $order, string $marketplace, string $status): void
    {
        try {
            // Дедупликация: не отправлять повторно для того же заказа+статуса (24 часа кэш)
            $cacheKey = "notify:sub:{$marketplace}:{$order->id}:{$status}";
            if (Cache::has($cacheKey)) {
                return;
            }
            Cache::put($cacheKey, true, 86400);

            $account = $order->account;
            if (! $account) {
                return;
            }

            $company = $account->company;
            if (! $company) {
                return;
            }

            // Получить всех пользователей компании
            $userIds = $company->users()->pluck('users.id');

            // Найти подходящие подписки
            $subscriptions = TelegramSubscription::query()
                ->active()
                ->whereIn('user_id', $userIds)
                ->forMarketplace($marketplace)
                ->forAccount($account->id)
                ->get();

            foreach ($subscriptions as $subscription) {
                if (! $subscription->shouldNotifyForStatus($status)) {
                    continue;
                }

                SendTelegramOrderNotification::dispatch($order, $subscription->chat_id, $status);
            }
        } catch (\Exception $e) {
            Log::error('Failed to notify subscribers', [
                'marketplace' => $marketplace,
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
