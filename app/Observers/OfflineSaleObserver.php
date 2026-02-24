<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\OfflineSale;
use App\Notifications\OfflineSaleConfirmedNotification;
use Illuminate\Support\Facades\Log;

final class OfflineSaleObserver
{
    /**
     * Handle the OfflineSale "updated" event.
     * Отправить уведомление при подтверждении продажи (draft -> confirmed).
     */
    public function updated(OfflineSale $sale): void
    {
        if ($sale->wasChanged('status') &&
            $sale->status === OfflineSale::STATUS_CONFIRMED &&
            $sale->getOriginal('status') === OfflineSale::STATUS_DRAFT) {
            $this->notifySaleConfirmed($sale);
        }
    }

    /**
     * Уведомить пользователей компании о подтверждённой продаже.
     */
    protected function notifySaleConfirmed(OfflineSale $sale): void
    {
        try {
            $company = $sale->company;
            if (! $company) {
                return;
            }

            $users = $company->users()
                ->whereNotNull('telegram_id')
                ->with('notificationSettings')
                ->get();

            foreach ($users as $user) {
                $user->notify(new OfflineSaleConfirmedNotification($sale));
            }
        } catch (\Exception $e) {
            Log::error('Failed to send OfflineSale notification', [
                'sale_id' => $sale->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
