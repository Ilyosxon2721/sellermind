<?php

namespace App\Jobs;

use App\Jobs\Concerns\HandlesMarketplaceRateLimiting;
use App\Models\MarketplaceAccount;
use App\Models\WbOrder;
use App\Services\Marketplaces\Wildberries\WildberriesOrderService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpdateWildberriesOrdersStatusJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, HandlesMarketplaceRateLimiting;

    /**
     * Таймаут выполнения job (5 минут)
     */
    public int $timeout = 300;

    /**
     * Количество попыток
     */
    public int $tries = 3;

    /**
     * Время уникальности job
     */
    public int $uniqueFor = 600; // 10 минут

    protected int $accountId;

    /**
     * Create a new job instance.
     */
    public function __construct(MarketplaceAccount $account)
    {
        $this->accountId = $account->id;
        $this->onQueue('marketplace-sync');
    }

    /**
     * Уникальный ID для предотвращения дублирования
     */
    public function uniqueId(): string
    {
        return 'update-wb-orders-status-' . $this->accountId;
    }

    /**
     * Получить аккаунт
     */
    protected function getAccount(): ?MarketplaceAccount
    {
        return MarketplaceAccount::find($this->accountId);
    }

    /**
     * Execute the job.
     */
    public function handle(WildberriesOrderService $orderService): void
    {
        $account = $this->getAccount();

        if (!$account) {
            Log::warning('UpdateWildberriesOrdersStatusJob: Account not found', [
                'account_id' => $this->accountId,
            ]);
            return;
        }

        // Проверяем, что это Wildberries аккаунт
        if ($account->marketplace !== 'wb') {
            Log::warning('UpdateWildberriesOrdersStatusJob: Not a WB account', [
                'account_id' => $account->id,
                'marketplace' => $account->marketplace,
            ]);
            return;
        }

        if (!$account->is_active) {
            Log::info('UpdateWildberriesOrdersStatusJob: Skipped inactive account', [
                'account_id' => $account->id,
            ]);
            return;
        }

        Log::info('UpdateWildberriesOrdersStatusJob: Starting', [
            'account_id' => $account->id,
            'account_name' => $account->name,
            'attempt' => $this->attempts(),
        ]);

        try {
            // Получаем заказы, которые не в финальном статусе
            $orders = WbOrder::where('marketplace_account_id', $account->id)
                ->whereNotIn('status', ['completed', 'canceled'])
                ->whereNotNull('external_order_id')
                ->limit(100) // Ограничиваем количество для избежания rate limit
                ->get();

            if ($orders->isEmpty()) {
                Log::info('UpdateWildberriesOrdersStatusJob: No orders to update', [
                    'account_id' => $account->id,
                ]);
                return;
            }

            // Получаем ID заказов
            $orderIds = $orders->pluck('external_order_id')->toArray();

            // Получаем статусы от WB API
            $statusesData = $orderService->getOrdersStatus($account, $orderIds);
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
                'account_id' => $account->id,
                'checked' => count($orderIds),
                'updated' => $updated,
            ]);

        } catch (\Exception $e) {
            Log::error('UpdateWildberriesOrdersStatusJob: Failed', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            // Проверяем, нужен ли retry
            if ($this->shouldRetry($e)) {
                $delay = $this->getRetryAfterSeconds($e) ?? $this->backoff()[$this->attempts() - 1] ?? 60;
                $this->release($delay);
                return;
            }

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('UpdateWildberriesOrdersStatusJob: Job failed permanently', [
            'account_id' => $this->accountId,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);
    }
}
