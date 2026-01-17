<?php

namespace App\Jobs\Marketplace;

use App\Events\MarketplaceDataChanged;
use App\Models\MarketplaceAccount;
use App\Models\WbOrder;
use App\Services\Marketplaces\MarketplaceSyncService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Джоба для мониторинга заказов маркетплейса
 * Запускается каждую минуту для проверки новых и обновлённых заказов
 */
class MonitorOrdersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300; // 5 минут на выполнение
    public int $tries = 1; // Не повторять при ошибке

    protected MarketplaceAccount $account;
    protected int $checkInterval = 60; // Проверять каждую минуту

    /**
     * Уникальный ID джобы на основе аккаунта
     */
    public function uniqueId(): string
    {
        return "monitor_orders_{$this->account->id}";
    }

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
    public function handle(MarketplaceSyncService $syncService): void
    {
        if (!$this->account->is_active) {
            Log::info("Orders monitoring skipped for inactive account {$this->account->id}");
            return;
        }

        Log::info("Starting orders monitoring for account {$this->account->id} ({$this->account->marketplace})");

        try {
            $this->checkOrdersChanges($syncService);

            // Перезапускаем мониторинг через 1 минуту
            $this->rescheduleMonitoring();
        } catch (\Throwable $e) {
            Log::error("Orders monitoring error for account {$this->account->id}: " . $e->getMessage());

            // Даже при ошибке перезапускаем мониторинг
            $this->rescheduleMonitoring();
        }
    }

    /**
     * Проверка изменений в заказах
     */
    protected function checkOrdersChanges(MarketplaceSyncService $syncService): void
    {
        $cacheKey = "orders_last_check_{$this->account->id}";
        $lastCheck = Cache::get($cacheKey, now()->subMinutes(5));

        // Получаем количество заказов до синхронизации
        $ordersBefore = WbOrder::where('marketplace_account_id', $this->account->id)->count();
        $lastUpdateBefore = WbOrder::where('marketplace_account_id', $this->account->id)
            ->max('updated_at');

        try {
            // Синхронизируем заказы за последние 15 минут (для минимизации нагрузки)
            $syncService->syncOrders(
                $this->account,
                Carbon::now()->subMinutes(15),
                Carbon::now()
            );

            // Проверяем изменения после синхронизации
            $ordersAfter = WbOrder::where('marketplace_account_id', $this->account->id)->count();
            $lastUpdateAfter = WbOrder::where('marketplace_account_id', $this->account->id)
                ->max('updated_at');

            $newOrders = $ordersAfter - $ordersBefore;
            $hasUpdates = $lastUpdateAfter && $lastUpdateBefore && $lastUpdateAfter > $lastUpdateBefore;

            // Если есть изменения - отправляем событие
            if ($newOrders > 0 || $hasUpdates) {
                $changeType = $newOrders > 0 ? 'created' : 'updated';

                broadcast(new MarketplaceDataChanged(
                    $this->account->company_id,
                    $this->account->id,
                    'orders',
                    $changeType,
                    $newOrders, // affectedCount
                    null, // changes
                    [
                        'new_count' => $newOrders,
                        'total_count' => $ordersAfter,
                    ] // metadata
                ))->toOthers();

                Log::info("Orders changed for account {$this->account->id}: {$changeType}, new: {$newOrders}, total: {$ordersAfter}");
            }

            Cache::put($cacheKey, now(), now()->addMinutes(10));
        } catch (\Throwable $e) {
            Log::error("Failed to check orders for account {$this->account->id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Перезапустить мониторинг через интервал
     */
    protected function rescheduleMonitoring(): void
    {
        // Перезапускаем job через указанный интервал (1 минута)
        static::dispatch($this->account)
            ->delay(now()->addSeconds($this->checkInterval));
    }
}
