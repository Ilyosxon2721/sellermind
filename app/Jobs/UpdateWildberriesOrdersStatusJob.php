<?php

namespace App\Jobs;

use App\Models\MarketplaceAccount;
use App\Models\WbOrder;
use App\Services\Marketplaces\Wildberries\WildberriesOrderService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class UpdateWildberriesOrdersStatusJob implements ShouldQueue
{
    use Queueable;

    protected MarketplaceAccount $account;

    /**
     * Create a new job instance.
     */
    public function __construct(MarketplaceAccount $account)
    {
        $this->account = $account;
    }

    /**
     * Execute the job.
     */
    public function handle(WildberriesOrderService $orderService): void
    {
        // Проверяем, что это Wildberries аккаунт
        if ($this->account->marketplace !== 'wb') {
            Log::warning('UpdateWildberriesOrdersStatusJob: Not a WB account', [
                'account_id' => $this->account->id,
                'marketplace' => $this->account->marketplace,
            ]);
            return;
        }

        Log::info('UpdateWildberriesOrdersStatusJob: Starting', [
            'account_id' => $this->account->id,
            'account_name' => $this->account->name,
        ]);

        try {
            // Получаем заказы, которые не в финальном статусе
            $orders = WbOrder::where('marketplace_account_id', $this->account->id)
                ->whereNotIn('status', ['completed', 'canceled'])
                ->whereNotNull('external_order_id')
                ->limit(100) // Ограничиваем количество для избежания rate limit
                ->get();

            if ($orders->isEmpty()) {
                Log::info('UpdateWildberriesOrdersStatusJob: No orders to update', [
                    'account_id' => $this->account->id,
                ]);
                return;
            }

            // Получаем ID заказов
            $orderIds = $orders->pluck('external_order_id')->toArray();

            // Получаем статусы от WB API
            $statusesData = $orderService->getOrdersStatus($this->account, $orderIds);
            $statuses = $statusesData['orders'] ?? [];

            $updated = 0;
            foreach ($statuses as $statusData) {
                $orderId = $statusData['id'] ?? null;
                $wbStatus = $statusData['wbStatus'] ?? null;
                $supplierStatus = $statusData['supplierStatus'] ?? null;

                if (!$orderId) continue;

                // Найти заказ в БД
                $order = $orders->firstWhere('external_order_id', $orderId);
                if (!$order) continue;

                // Подготовка данных для обновления
                $updateData = [];

                if ($wbStatus) {
                    $updateData['wb_status'] = $wbStatus;
                }

                if ($supplierStatus) {
                    $updateData['supplier_status'] = $supplierStatus;
                }

                // Добавляем запись в историю статусов
                $statusHistory = $order->status_history ?? [];
                $statusHistory[] = [
                    'wb_status' => $wbStatus,
                    'supplier_status' => $supplierStatus,
                    'updated_at' => now()->toIso8601String(),
                ];
                $updateData['status_history'] = $statusHistory;

                // Обновляем заказ
                if (!empty($updateData)) {
                    $order->update($updateData);
                    $updated++;
                }
            }

            Log::info('UpdateWildberriesOrdersStatusJob: Completed', [
                'account_id' => $this->account->id,
                'checked' => count($orderIds),
                'updated' => $updated,
            ]);

        } catch (\Exception $e) {
            Log::error('UpdateWildberriesOrdersStatusJob: Failed', [
                'account_id' => $this->account->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
