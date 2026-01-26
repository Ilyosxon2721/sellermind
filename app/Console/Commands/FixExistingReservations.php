<?php

namespace App\Console\Commands;

use App\Models\UzumOrder;
use App\Models\Warehouse\StockReservation;
use App\Services\Stock\OrderStockService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FixExistingReservations extends Command
{
    protected $signature = 'reservations:fix-uzum
                            {--dry-run : Показать что будет изменено без применения}
                            {--delete-only : Только удалить неправильные резервы}';

    protected $description = 'Исправить существующие Uzum резервы с неправильными вариантами';

    protected OrderStockService $orderStockService;

    public function __construct(OrderStockService $orderStockService)
    {
        parent::__construct();
        $this->orderStockService = $orderStockService;
    }

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $deleteOnly = $this->option('delete-only');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - изменения не будут применены');
        }

        $this->info('Поиск Uzum резервов для исправления...');

        // Найти все активные Uzum резервы
        $reservations = StockReservation::query()
            ->where('marketplace_code', 'uzum')
            ->where('status', 'active')
            ->with(['order', 'variant'])
            ->get();

        $this->info("Найдено активных Uzum резервов: {$reservations->count()}");

        if ($reservations->isEmpty()) {
            $this->info('Нет резервов для исправления');
            return self::SUCCESS;
        }

        // Группируем по заказам
        $orderIds = $reservations->pluck('reservable_id')->unique();
        $this->info("Уникальных заказов: {$orderIds->count()}");

        $deleted = 0;
        $recreated = 0;
        $failed = 0;

        foreach ($orderIds as $orderId) {
            $order = UzumOrder::with(['account', 'items'])->find($orderId);

            if (!$order) {
                $this->warn("  Заказ #{$orderId} не найден, пропускаем");
                continue;
            }

            $orderReservations = $reservations->where('reservable_id', $orderId);

            $this->line("  Заказ #{$order->external_order_id}: {$orderReservations->count()} резервов");

            if (!$dryRun) {
                // 1. Удаляем старые резервы
                foreach ($orderReservations as $reservation) {
                    // Возвращаем остатки
                    if ($reservation->variant) {
                        $reservation->variant->incrementStock($reservation->quantity);
                    }
                    $reservation->delete();
                    $deleted++;
                }

                // Сбрасываем статус обработки заказа
                $order->update(['stock_status' => 'none']);

                if (!$deleteOnly) {
                    // 2. Пересоздаём резервы с правильными вариантами
                    try {
                        $items = $this->orderStockService->getOrderItems($order, 'uzum');

                        if (!empty($items)) {
                            $result = $this->orderStockService->processOrderStatusChange(
                                $order->account,
                                $order,
                                null,
                                $order->status,
                                $items
                            );

                            if ($result['success'] && $result['action'] === 'reserve') {
                                $recreated += $result['items_processed'] ?? 0;
                                $this->info("    -> Создано новых резервов: " . ($result['items_processed'] ?? 0));
                            }
                        }
                    } catch (\Throwable $e) {
                        $failed++;
                        $this->error("    -> Ошибка: {$e->getMessage()}");
                        Log::error('FixExistingReservations: Failed', [
                            'order_id' => $order->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            } else {
                $this->line("    [DRY] Будет удалено: {$orderReservations->count()} резервов");
            }
        }

        $this->newLine();
        $this->info('Результаты:');
        $this->table(
            ['Метрика', 'Значение'],
            [
                ['Удалено резервов', $deleted],
                ['Создано новых', $recreated],
                ['Ошибок', $failed],
            ]
        );

        if ($dryRun) {
            $this->newLine();
            $this->warn('Запустите без --dry-run для применения изменений');
        }

        return self::SUCCESS;
    }
}
