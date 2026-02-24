<?php

declare(strict_types=1);

namespace App\Observers\Traits;

use App\Notifications\NewMarketplaceOrderNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

trait NotifiesMarketplaceOrder
{
    /**
     * Отправить уведомление о новом заказе с маркетплейса.
     */
    protected function notifyNewMarketplaceOrder(
        Model $order,
        string $marketplace,
        string $orderNumber,
        float $totalAmount,
        string $currency,
    ): void {
        try {
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
}
