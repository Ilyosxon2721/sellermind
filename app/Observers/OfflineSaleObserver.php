<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\OfflineSale;
use App\Notifications\OfflineSaleConfirmedNotification;
use App\Services\Stock\OfflineSaleStockService;
use Illuminate\Support\Facades\Log;

final class OfflineSaleObserver
{
    public function __construct(
        private readonly OfflineSaleStockService $stockService
    ) {}

    /**
     * Handle the OfflineSale "updated" event.
     * Обработать изменение статуса продажи: уведомления + списание остатков.
     */
    public function updated(OfflineSale $sale): void
    {
        // Если изменился статус - обрабатываем остатки
        if ($sale->wasChanged('status')) {
            $oldStatus = $sale->getOriginal('status');
            $newStatus = $sale->status;

            // Обработка остатков при изменении статуса
            try {
                $result = $this->stockService->processSaleStatusChange($sale, $oldStatus, $newStatus);

                if (! $result['success']) {
                    Log::error('OfflineSaleObserver: Stock processing failed', [
                        'sale_id' => $sale->id,
                        'old_status' => $oldStatus,
                        'new_status' => $newStatus,
                        'error' => $result['error'] ?? 'Unknown error',
                    ]);
                }
            } catch (\Throwable $e) {
                Log::error('OfflineSaleObserver: Exception in stock processing', [
                    'sale_id' => $sale->id,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }

            // Отправить уведомление при подтверждении
            if ($newStatus === OfflineSale::STATUS_CONFIRMED && $oldStatus === OfflineSale::STATUS_DRAFT) {
                $this->notifySaleConfirmed($sale);
            }
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
