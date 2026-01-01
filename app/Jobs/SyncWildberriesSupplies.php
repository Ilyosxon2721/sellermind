<?php

namespace App\Jobs;

use App\Events\MarketplaceDataChanged;
use App\Events\MarketplaceSyncProgress;
use App\Models\MarketplaceAccount;
use App\Models\Supply;
use App\Services\Marketplaces\Wildberries\WildberriesHttpClient;
use App\Services\Marketplaces\Wildberries\WildberriesOrderService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncWildberriesSupplies implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected MarketplaceAccount $account;

    public function __construct(MarketplaceAccount $account)
    {
        $this->account = $account;
    }

    public function handle(): void
    {
        Log::info('Starting WB supplies sync', ['account_id' => $this->account->id]);

        // Создаём HTTP клиент и сервис заказов
        $httpClient = new WildberriesHttpClient($this->account);
        $orderService = new WildberriesOrderService($httpClient);

        // Отправляем событие о начале синхронизации
        event(new MarketplaceSyncProgress(
            $this->account->company_id,
            $this->account->id,
            'started',
            'Начата синхронизация поставок',
            0
        ));

        try {
            $synced = 0;
            $created = 0;
            $updated = 0;
            $deleted = 0;
            $next = 0;
            $wbSupplyIds = []; // Собираем все ID поставок из WB

            do {
                // Получаем список поставок из WB
                $response = $orderService->getSupplies($this->account, 1000, $next);
                $wbSupplies = $response['supplies'] ?? [];

                foreach ($wbSupplies as $wbSupply) {
                    try {
                        $externalSupplyId = $wbSupply['id'] ?? null;

                        if (!$externalSupplyId) {
                            Log::warning('WB supply without ID, skipping', ['supply' => $wbSupply]);
                            continue;
                        }

                        // Сохраняем ID для последующей проверки
                        $wbSupplyIds[] = $externalSupplyId;

                        // Ищем поставку в нашей системе
                        $supply = Supply::where('marketplace_account_id', $this->account->id)
                            ->where('external_supply_id', $externalSupplyId)
                            ->first();

                        $statusData = $this->mapWbStatusData($wbSupply);

                        $data = [
                            'marketplace_account_id' => $this->account->id,
                            'external_supply_id' => $externalSupplyId,
                            'name' => $wbSupply['name'] ?? "WB Supply {$externalSupplyId}",
                            'status' => $statusData['status'],
                            'closed_at' => $statusData['closed_at'],
                            'sent_at' => $statusData['sent_at'],
                            'delivered_at' => $statusData['delivered_at'],
                            'delivery_started_at' => $statusData['delivery_started_at'],
                            'boxes_count' => $wbSupply['placesCount'] ?? $wbSupply['boxesCount'] ?? 0,
                            'cargo_type' => $wbSupply['cargoType'] ?? null,
                            'metadata' => $wbSupply,
                        ];

                        if (!$supply) {
                            // Создаём новую поставку
                            $supply = Supply::create($data);
                            $created++;
                            Log::info('Created new supply from WB', [
                                'supply_id' => $supply->id,
                                'external_supply_id' => $externalSupplyId,
                            ]);
                        } else {
                            // Обновляем существующую
                            $supply->update($data);
                            $updated++;
                        }

                        // Синхронизируем заказы в поставке
                        $this->syncSupplyOrders($orderService, $supply, $externalSupplyId);

                        // Синхронизируем короба (tares) поставки
                        $this->syncSupplyTares($orderService, $supply, $externalSupplyId);

                        $synced++;
                    } catch (\Exception $e) {
                        Log::error('Failed to sync WB supply', [
                            'account_id' => $this->account->id,
                            'supply' => $wbSupply,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                // Проверяем, есть ли ещё данные для пагинации
                $next = $response['next'] ?? 0;
            } while ($next > 0);

            // Удаляем поставки, которых нет в WB (только те, что с external_supply_id)
            $suppliesToDelete = Supply::where('marketplace_account_id', $this->account->id)
                ->whereNotNull('external_supply_id')
                ->whereNotIn('external_supply_id', $wbSupplyIds)
                ->get();

            foreach ($suppliesToDelete as $supply) {
                Log::info('Deleting supply not found in WB', [
                    'supply_id' => $supply->id,
                    'external_supply_id' => $supply->external_supply_id,
                ]);
                $supply->delete();
                $deleted++;
            }

            // После синхронизации поставок пересчитываем статусы заказов по статусу поставки
            $ordersUpdatedBySupplies = $orderService->refreshOrdersStatusFromSupplies($this->account);

            Log::info('WB supplies sync completed', [
                'account_id' => $this->account->id,
                'synced' => $synced,
                'created' => $created,
                'updated' => $updated,
                'deleted' => $deleted,
                'orders_updated_by_supplies' => $ordersUpdatedBySupplies['updated'] ?? 0,
            ]);

            // Отправляем событие об успешном завершении
            event(new MarketplaceSyncProgress(
                $this->account->company_id,
                $this->account->id,
                'completed',
                'Синхронизация поставок завершена',
                100,
                [
                    'synced' => $synced,
                    'created' => $created,
                    'updated' => $updated,
                    'deleted' => $deleted,
                    'orders_updated' => $ordersUpdatedBySupplies['updated'] ?? 0,
                ]
            ));

            // Сообщаем фронту об изменениях поставок
            event(new MarketplaceDataChanged(
                $this->account->company_id,
                $this->account->id,
                'supplies',
                'updated',
                $synced,
                null,
                ['created' => $created, 'updated' => $updated, 'deleted' => $deleted]
            ));

            // И об изменённых заказах (изменение статуса из-за поставок)
            if (($ordersUpdatedBySupplies['updated'] ?? 0) > 0) {
                event(new MarketplaceDataChanged(
                    $this->account->company_id,
                    $this->account->id,
                    'orders',
                    'updated',
                    $ordersUpdatedBySupplies['updated'],
                    null,
                    ['source' => 'supplies_sync']
                ));
            }

        } catch (\Exception $e) {
            Log::error('WB supplies sync failed', [
                'account_id' => $this->account->id,
                'error' => $e->getMessage(),
            ]);

            // Отправляем событие об ошибке
            event(new MarketplaceSyncProgress(
                $this->account->company_id,
                $this->account->id,
                'error',
                'Ошибка синхронизации: ' . $e->getMessage(),
                null
            ));

            throw $e;
        }
    }

    /**
     * Sync orders within a supply from WB API
     */
    protected function syncSupplyOrders(WildberriesOrderService $orderService, Supply $supply, string $externalSupplyId): void
    {
        try {
            Log::info('Syncing orders for supply', [
                'supply_id' => $supply->id,
                'external_supply_id' => $externalSupplyId,
            ]);

            $result = $orderService->syncSupplyOrders($this->account, $externalSupplyId);

            Log::info('Supply orders sync completed', [
                'supply_id' => $supply->id,
                'external_supply_id' => $externalSupplyId,
                'synced' => $result['synced'],
                'created' => $result['created'],
                'updated' => $result['updated'],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to sync supply orders', [
                'supply_id' => $supply->id,
                'external_supply_id' => $externalSupplyId,
                'error' => $e->getMessage(),
            ]);
            // Don't throw - continue syncing other supplies
        }
    }

    /**
     * Sync tares (boxes) for a supply from WB API
     */
    protected function syncSupplyTares(WildberriesOrderService $orderService, Supply $supply, string $externalSupplyId): void
    {
        try {
            Log::info('Syncing tares for supply', [
                'supply_id' => $supply->id,
                'external_supply_id' => $externalSupplyId,
            ]);

            // Получаем короба из WB API
            $wbTares = $orderService->getTares($this->account, $externalSupplyId);

            $synced = 0;
            $created = 0;
            $updated = 0;

            foreach ($wbTares as $wbTare) {
                try {
                    $externalTareId = $wbTare['id'] ?? null;

                    if (!$externalTareId) {
                        continue;
                    }

                    // Ищем короб в нашей системе
                    $tare = \App\Models\Tare::where('supply_id', $supply->id)
                        ->where('external_tare_id', $externalTareId)
                        ->first();

                    $ordersCount = count($wbTare['orderIds'] ?? []);

                    $data = [
                        'supply_id' => $supply->id,
                        'external_tare_id' => $externalTareId,
                        'barcode' => $wbTare['barcodes'][0] ?? $externalTareId,
                        'orders_count' => $ordersCount,
                    ];

                    if (!$tare) {
                        \App\Models\Tare::create($data);
                        $created++;
                    } else {
                        $tare->update($data);
                        $updated++;
                    }

                    $synced++;
                } catch (\Exception $e) {
                    Log::error('Failed to sync tare', [
                        'supply_id' => $supply->id,
                        'tare' => $wbTare,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            Log::info('Supply tares sync completed', [
                'supply_id' => $supply->id,
                'external_supply_id' => $externalSupplyId,
                'synced' => $synced,
                'created' => $created,
                'updated' => $updated,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to sync supply tares', [
                'supply_id' => $supply->id,
                'external_supply_id' => $externalSupplyId,
                'error' => $e->getMessage(),
            ]);
            // Don't throw - continue syncing other supplies
        }
    }

    /**
     * Map WB supply status to our status
     */
    protected function mapWbStatusData(array $wbSupply): array
    {
        $rawStatus = strtolower($wbSupply['status'] ?? '');
        $closedAt = $wbSupply['closedAt'] ?? null;
        $done = (bool)($wbSupply['done'] ?? false);
        $cancelled = (bool)($wbSupply['isCancelled'] ?? false);
        $deliveryStarted = $wbSupply['deliveryPlannedAt'] ?? $wbSupply['deliveryDate'] ?? null;
        $scanDt = $wbSupply['scanDt'] ?? null;

        $status = Supply::STATUS_DRAFT;
        $sentAt = null;
        $deliveredAt = null;
        $closedAtTs = $closedAt ? Carbon::parse($closedAt) : null;
        $deliveryStartedAt = $deliveryStarted ? Carbon::parse($deliveryStarted) : null;

        if ($cancelled || $rawStatus === 'cancelled' || $rawStatus === 'canceled') {
            $status = Supply::STATUS_CANCELLED;
        } elseif ($rawStatus === 'delivered') {
            $status = Supply::STATUS_DELIVERED;
            $deliveredAt = $closedAtTs ?? ($scanDt ? Carbon::parse($scanDt) : now());
        } elseif ($done || in_array($rawStatus, ['done', 'shipped', 'sent', 'in_delivery', 'delivery', 'ready_for_pickup'])) {
            // На стороне WB "done" означает закрыт/передан, для нас это "Отправлена"
            $status = Supply::STATUS_SENT;
            $sentAt = $closedAtTs ?? ($scanDt ? Carbon::parse($scanDt) : now());
        } elseif (in_array($rawStatus, ['ready', 'ready_for_packages', 'collected'])) {
            $status = Supply::STATUS_READY;
        } elseif (in_array($rawStatus, ['in_work', 'collecting', 'assembling', 'created', 'creating'])) {
            $status = Supply::STATUS_IN_ASSEMBLY;
        } elseif ($closedAtTs) {
            $status = Supply::STATUS_SENT;
            $sentAt = $closedAtTs;
        }

        return [
            'status' => $status,
            'closed_at' => $closedAtTs,
            'sent_at' => $sentAt,
            'delivered_at' => $deliveredAt,
            'delivery_started_at' => $deliveryStartedAt,
        ];
    }
}
