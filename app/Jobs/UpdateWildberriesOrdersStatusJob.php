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
     * Таймаут выполнения job (10 минут для обработки всех заказов)
     */
    public int $timeout = 600;

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
     * Получить общее количество заказов для обновления
     */
    protected function getTotalOrdersCount(MarketplaceAccount $account): int
    {
        return WbOrder::where('marketplace_account_id', $account->id)
            ->whereNotIn('status', ['completed', 'canceled', 'cancelled'])
            ->whereNotNull('external_order_id')
            ->count();
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
            // Получаем общее количество заказов для обновления
            $totalOrders = WbOrder::where('marketplace_account_id', $account->id)
                ->whereNotIn('status', ['completed', 'canceled', 'cancelled'])
                ->whereNotNull('external_order_id')
                ->count();

            if ($totalOrders === 0) {
                Log::info('UpdateWildberriesOrdersStatusJob: No orders to update', [
                    'account_id' => $account->id,
                ]);
                return;
            }

            Log::info('UpdateWildberriesOrdersStatusJob: Found orders to update', [
                'account_id' => $account->id,
                'total_orders' => $totalOrders,
            ]);

            $totalUpdated = 0;
            $totalChecked = 0;

            // Обрабатываем заказы батчами по 1000 (лимит WB API)
            WbOrder::where('marketplace_account_id', $account->id)
                ->whereNotIn('status', ['completed', 'canceled', 'cancelled'])
                ->whereNotNull('external_order_id')
                ->orderBy('ordered_at', 'desc') // Сначала новые заказы
                ->chunk(1000, function ($orders) use ($account, $orderService, &$totalUpdated, &$totalChecked) {
                    // Получаем ID заказов
                    $orderIds = $orders->pluck('external_order_id')->toArray();
                    $totalChecked += count($orderIds);

                    // Получаем статусы от WB API
                    $statusesData = $orderService->getOrdersStatus($account, $orderIds);
                    $statuses = $statusesData['orders'] ?? [];

                    foreach ($statuses as $statusData) {
                        $orderId = $statusData['id'] ?? null;
                        $wbStatus = $statusData['wbStatus'] ?? null;
                        $supplierStatus = $statusData['supplierStatus'] ?? null;

                        if (!$orderId) continue;

                        // Найти заказ в БД (конвертируем в строку для сравнения)
                        $order = $orders->firstWhere('external_order_id', (string) $orderId);
                        if (!$order) continue;

                        // Подготовка данных для обновления
                        $updateData = [];

                        if ($wbStatus) {
                            $updateData['wb_status'] = $wbStatus;
                        }

                        if ($supplierStatus) {
                            $updateData['wb_supplier_status'] = $supplierStatus;
                        }

                        // Нормализуем статус на основе WB статусов
                        $normalizedStatus = $this->mapWbStatusToInternal($supplierStatus, $wbStatus);
                        $statusGroup = $this->mapWbStatusToGroup($supplierStatus, $wbStatus);

                        $updateData['status'] = $normalizedStatus;
                        $updateData['status_normalized'] = $normalizedStatus;
                        $updateData['wb_status_group'] = $statusGroup;

                        // Добавляем запись в историю статусов
                        $statusHistory = $order->status_history ?? [];
                        $statusHistory[] = [
                            'wb_status' => $wbStatus,
                            'supplier_status' => $supplierStatus,
                            'status' => $normalizedStatus,
                            'updated_at' => now()->toIso8601String(),
                        ];
                        $updateData['status_history'] = $statusHistory;

                        // Обновляем заказ
                        if (!empty($updateData)) {
                            $order->update($updateData);
                            $totalUpdated++;
                        }
                    }

                    // Небольшая пауза между батчами для избежания rate limit
                    if ($totalChecked < $this->getTotalOrdersCount($account)) {
                        usleep(500000); // 0.5 секунды
                    }
                });

            Log::info('UpdateWildberriesOrdersStatusJob: Completed', [
                'account_id' => $account->id,
                'checked' => $totalChecked,
                'updated' => $totalUpdated,
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

    /**
     * Маппинг WB статусов в нормализованный внутренний статус
     */
    protected function mapWbStatusToInternal(?string $supplierStatus, ?string $wbStatus): string
    {
        // 1. CANCELLED (высший приоритет)
        if (in_array($supplierStatus, ['cancel', 'reject']) ||
            in_array($wbStatus, ['canceled', 'canceled_by_client', 'declined_by_client', 'defect'])) {
            return 'cancelled';
        }

        // 2. COMPLETED (доставлен клиенту)
        if (in_array($wbStatus, ['delivered', 'sold_from_store', 'sold']) ||
            $supplierStatus === 'receive') {
            return 'completed';
        }

        // 3. IN_DELIVERY (продавец сделал, WB доставляет)
        if ($supplierStatus === 'complete' ||
            in_array($wbStatus, ['on_way_to_client', 'on_way_from_client', 'ready_for_pickup', 'at_deliverypoint', 'at_sortcenter'])) {
            return 'in_delivery';
        }

        // 4. IN_ASSEMBLY (продавец подтвердил и собирает)
        if ($supplierStatus === 'confirm' ||
            in_array($wbStatus, ['sorted'])) {
            return 'in_assembly';
        }

        // 5. NEW (ожидает подтверждения)
        if ($supplierStatus === 'new' || $wbStatus === 'waiting') {
            return 'new';
        }

        return 'new'; // По умолчанию
    }

    /**
     * Маппинг WB статусов в группу статусов
     */
    protected function mapWbStatusToGroup(?string $supplierStatus, ?string $wbStatus): string
    {
        // 1. Cancelled
        if (in_array($supplierStatus, ['cancel', 'reject']) ||
            in_array($wbStatus, ['canceled', 'canceled_by_client', 'declined_by_client', 'defect'])) {
            return 'canceled';
        }

        // 2. Archive/Completed
        if (in_array($wbStatus, ['delivered', 'sold_from_store', 'sold']) ||
            $supplierStatus === 'receive') {
            return 'archive';
        }

        // 3. Shipping
        if ($supplierStatus === 'complete' ||
            in_array($wbStatus, ['on_way_to_client', 'on_way_from_client', 'ready_for_pickup', 'at_deliverypoint', 'at_sortcenter'])) {
            return 'shipping';
        }

        // 4. Assembling
        if ($supplierStatus === 'confirm' ||
            in_array($wbStatus, ['sorted'])) {
            return 'assembling';
        }

        // 5. New
        return 'new';
    }
}
