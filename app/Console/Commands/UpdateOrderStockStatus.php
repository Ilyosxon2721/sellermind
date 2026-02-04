<?php

namespace App\Console\Commands;

use App\Models\OzonOrder;
use App\Models\YandexMarketOrder;
use App\Services\Stock\OrderStockService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Команда для массового обновления stock_status существующих заказов
 * Используется для ретроспективного обновления заказов после добавления поддержки stock tracking
 */
class UpdateOrderStockStatus extends Command
{
    protected $signature = 'orders:update-stock-status
                            {--marketplace= : Маркетплейс (ozon, ym, all)}
                            {--account= : ID аккаунта}
                            {--force : Принудительно обновить все заказы, включая уже обработанные}';

    protected $description = 'Обновить stock_status для существующих заказов Ozon и YM на основе их текущего статуса';

    protected OrderStockService $stockService;

    public function __construct()
    {
        parent::__construct();
        $this->stockService = new OrderStockService;
    }

    public function handle(): int
    {
        $marketplace = $this->option('marketplace') ?? 'all';
        $accountId = $this->option('account');
        $force = $this->option('force');

        $this->info('Обновление stock_status для заказов...');
        $this->info("Маркетплейс: {$marketplace}");
        $this->info('Режим: '.($force ? 'принудительное обновление всех' : 'только необработанные'));

        if ($marketplace === 'all' || $marketplace === 'ozon') {
            $this->updateOzonOrders($accountId, $force);
        }

        if ($marketplace === 'all' || $marketplace === 'ym') {
            $this->updateYmOrders($accountId, $force);
        }

        $this->info("\n✓ Обновление завершено");

        return self::SUCCESS;
    }

    protected function updateOzonOrders(?int $accountId, bool $force): void
    {
        $this->newLine();
        $this->info('=== OZON заказы ===');

        $query = OzonOrder::query();

        if ($accountId) {
            $query->where('marketplace_account_id', $accountId);
        }

        if (! $force) {
            // Только заказы с stock_status = 'none' или null
            $query->where(function ($q) {
                $q->where('stock_status', 'none')
                    ->orWhereNull('stock_status');
            });
        }

        $total = $query->count();
        $this->line("Найдено заказов для обработки: {$total}");

        if ($total === 0) {
            $this->info('Нет заказов для обновления');

            return;
        }

        $processed = 0;
        $updated = 0;
        $errors = 0;

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $query->chunk(100, function ($orders) use (&$processed, &$updated, &$errors, $bar) {
            foreach ($orders as $order) {
                try {
                    $newStockStatus = $this->determineOzonStockStatus($order);

                    if ($newStockStatus !== $order->stock_status) {
                        $updateData = ['stock_status' => $newStockStatus];

                        // Установить временные метки в зависимости от статуса
                        if ($newStockStatus === 'sold' && ! $order->stock_sold_at) {
                            // Используем дату изменения статуса или текущую дату
                            $updateData['stock_sold_at'] = $order->shipment_date ?? $order->in_process_at ?? now();
                        }
                        if ($newStockStatus === 'reserved' && ! $order->stock_reserved_at) {
                            $updateData['stock_reserved_at'] = $order->in_process_at ?? $order->created_at_ozon ?? now();
                        }
                        if ($newStockStatus === 'released' && ! $order->stock_released_at) {
                            $updateData['stock_released_at'] = $order->cancelled_at ?? now();
                        }

                        $order->update($updateData);
                        $updated++;
                    }

                    $processed++;
                } catch (\Exception $e) {
                    $errors++;
                    Log::error('Failed to update OZON order stock status', [
                        'order_id' => $order->id,
                        'error' => $e->getMessage(),
                    ]);
                }

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info("Обработано: {$processed}, Обновлено: {$updated}, Ошибок: {$errors}");
    }

    protected function updateYmOrders(?int $accountId, bool $force): void
    {
        $this->newLine();
        $this->info('=== Yandex Market заказы ===');

        $query = YandexMarketOrder::query();

        if ($accountId) {
            $query->where('marketplace_account_id', $accountId);
        }

        if (! $force) {
            // Только заказы с stock_status = 'none' или null
            $query->where(function ($q) {
                $q->where('stock_status', 'none')
                    ->orWhereNull('stock_status');
            });
        }

        $total = $query->count();
        $this->line("Найдено заказов для обработки: {$total}");

        if ($total === 0) {
            $this->info('Нет заказов для обновления');

            return;
        }

        $processed = 0;
        $updated = 0;
        $errors = 0;

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $query->chunk(100, function ($orders) use (&$processed, &$updated, &$errors, $bar) {
            foreach ($orders as $order) {
                try {
                    $newStockStatus = $this->determineYmStockStatus($order);

                    if ($newStockStatus !== $order->stock_status) {
                        $updateData = ['stock_status' => $newStockStatus];

                        // Установить временные метки в зависимости от статуса
                        if ($newStockStatus === 'sold' && ! $order->stock_sold_at) {
                            $updateData['stock_sold_at'] = $order->updated_at_ym ?? now();
                        }
                        if ($newStockStatus === 'reserved' && ! $order->stock_reserved_at) {
                            $updateData['stock_reserved_at'] = $order->created_at_ym ?? now();
                        }
                        if ($newStockStatus === 'released' && ! $order->stock_released_at) {
                            $updateData['stock_released_at'] = now();
                        }

                        $order->update($updateData);
                        $updated++;
                    }

                    $processed++;
                } catch (\Exception $e) {
                    $errors++;
                    Log::error('Failed to update YM order stock status', [
                        'order_id' => $order->id,
                        'error' => $e->getMessage(),
                    ]);
                }

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info("Обработано: {$processed}, Обновлено: {$updated}, Ошибок: {$errors}");
    }

    /**
     * Определить stock_status на основе текущего статуса заказа OZON
     */
    protected function determineOzonStockStatus(OzonOrder $order): string
    {
        $status = strtolower($order->status ?? '');

        // Проверяем статусы продажи (доставка/доставлено)
        $soldStatuses = array_map('strtolower', OrderStockService::SOLD_STATUSES['ozon'] ?? []);
        if (in_array($status, $soldStatuses)) {
            return 'sold';
        }

        // Проверяем статусы отмены
        $cancelledStatuses = array_map('strtolower', OrderStockService::CANCELLED_STATUSES['ozon'] ?? []);
        if (in_array($status, $cancelledStatuses)) {
            return 'released';
        }

        // Проверяем статусы возврата
        $returnedStatuses = array_map('strtolower', OrderStockService::RETURNED_STATUSES['ozon'] ?? []);
        if (in_array($status, $returnedStatuses)) {
            return 'returned';
        }

        // Проверяем статусы резерва (в обработке)
        $reserveStatuses = array_map('strtolower', OrderStockService::RESERVE_STATUSES['ozon'] ?? []);
        if (in_array($status, $reserveStatuses)) {
            return 'reserved';
        }

        // По умолчанию - не обработано
        return 'none';
    }

    /**
     * Определить stock_status на основе текущего статуса заказа YM
     */
    protected function determineYmStockStatus(YandexMarketOrder $order): string
    {
        $status = strtoupper($order->status ?? '');

        // Проверяем статусы продажи (доставлен)
        $soldStatuses = array_map('strtoupper', OrderStockService::SOLD_STATUSES['ym'] ?? []);
        if (in_array($status, $soldStatuses)) {
            return 'sold';
        }

        // Проверяем статусы отмены
        $cancelledStatuses = array_map('strtoupper', OrderStockService::CANCELLED_STATUSES['ym'] ?? []);
        if (in_array($status, $cancelledStatuses)) {
            return 'released';
        }

        // Проверяем статусы возврата
        $returnedStatuses = array_map('strtoupper', OrderStockService::RETURNED_STATUSES['ym'] ?? []);
        if (in_array($status, $returnedStatuses)) {
            return 'returned';
        }

        // Проверяем статусы резерва (в обработке)
        $reserveStatuses = array_map('strtoupper', OrderStockService::RESERVE_STATUSES['ym'] ?? []);
        if (in_array($status, $reserveStatuses)) {
            return 'reserved';
        }

        // По умолчанию - не обработано
        return 'none';
    }
}
