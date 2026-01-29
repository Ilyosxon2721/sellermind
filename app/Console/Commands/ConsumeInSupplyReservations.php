<?php

namespace App\Console\Commands;

use App\Models\UzumOrder;
use App\Models\Warehouse\StockLedger;
use App\Models\Warehouse\StockReservation;
use App\Services\Stock\OrderStockService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ConsumeInSupplyReservations extends Command
{
    protected $signature = 'reservations:consume-sold
                            {--dry-run : Показать что будет изменено без реального изменения}';

    protected $description = 'Обработать резервы: списать для sold заказов, отменить и вернуть остаток для cancelled заказов';

    protected OrderStockService $orderStockService;

    public function __construct(OrderStockService $orderStockService)
    {
        parent::__construct();
        $this->orderStockService = $orderStockService;
    }

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $this->info('Поиск активных резервов для маркетплейс заказов...');

        if ($dryRun) {
            $this->warn('DRY RUN - изменения не будут применены');
        }

        // Найти все активные резервы от маркетплейс заказов
        $reservations = StockReservation::where('status', StockReservation::STATUS_ACTIVE)
            ->where('source_type', 'marketplace_order')
            ->whereNotNull('source_id')
            ->with('sku.productVariant')
            ->get();

        $this->line("Найдено активных резервов: {$reservations->count()}");

        $consumed = 0;
        $cancelled = 0;
        $skipped = 0;

        foreach ($reservations as $reservation) {
            $order = $this->findOrder($reservation);

            if (!$order) {
                $this->warn("  Резерв #{$reservation->id}: заказ не найден (source_id: {$reservation->source_id})");
                $skipped++;
                continue;
            }

            $status = $order->status_normalized ?? $order->status ?? null;
            $marketplace = $this->extractMarketplace($reservation->reason);

            // 1. Sold статус → consume (списать резерв)
            if ($this->orderStockService->isSoldStatus($marketplace, $status)) {
                $this->info("  Резерв #{$reservation->id}: заказ #{$order->id} статус '{$status}' → СПИСАНИЕ");

                if (!$dryRun) {
                    $reservation->update(['status' => StockReservation::STATUS_CONSUMED]);

                    if ($order->stock_status !== 'sold') {
                        $order->update([
                            'stock_status' => 'sold',
                            'stock_sold_at' => now(),
                        ]);
                    }
                }

                $consumed++;
                continue;
            }

            // 2. Cancelled статус → cancel резерв + вернуть остаток на склад
            if ($this->orderStockService->isCancelledStatus($marketplace, $status)) {
                $variant = $reservation->sku?->productVariant;
                $qty = (int) $reservation->qty;

                $this->info("  Резерв #{$reservation->id}: заказ #{$order->id} статус '{$status}' → ОТМЕНА + ВОЗВРАТ {$qty} шт");

                if (!$dryRun) {
                    DB::beginTransaction();
                    try {
                        // Отменяем резерв
                        $reservation->update(['status' => StockReservation::STATUS_CANCELLED]);

                        // Возвращаем остаток
                        if ($variant) {
                            $variant->incrementStock($qty);

                            // Запись в журнал склада
                            StockLedger::create([
                                'company_id' => $reservation->company_id,
                                'occurred_at' => now(),
                                'warehouse_id' => $reservation->warehouse_id,
                                'sku_id' => $reservation->sku_id,
                                'qty_delta' => $qty,
                                'cost_delta' => 0,
                                'currency_code' => 'UZS',
                                'source_type' => 'marketplace_order_cancel',
                                'source_id' => $order->id,
                            ]);

                            $this->line("    Возвращено {$qty} шт для {$variant->sku} (остаток: {$variant->fresh()->stock_default})");
                        }

                        // Обновляем статус заказа
                        if (!in_array($order->stock_status, ['released'])) {
                            $order->update([
                                'stock_status' => 'released',
                                'stock_released_at' => now(),
                            ]);
                        }

                        DB::commit();
                    } catch (\Throwable $e) {
                        DB::rollBack();
                        $this->error("    Ошибка: {$e->getMessage()}");
                        Log::error('ConsumeInSupplyReservations: Failed to cancel reservation', [
                            'reservation_id' => $reservation->id,
                            'order_id' => $order->id,
                            'error' => $e->getMessage(),
                        ]);
                        $skipped++;
                        continue;
                    }
                }

                $cancelled++;
                continue;
            }

            $this->line("  Резерв #{$reservation->id}: заказ #{$order->id} статус '{$status}' - пропуск");
            $skipped++;
        }

        $this->newLine();
        $prefix = $dryRun ? '[DRY-RUN] ' : '';
        $this->info("{$prefix}Результат:");
        $this->table(
            ['Действие', 'Количество'],
            [
                ['Списано (sold)', $consumed],
                ['Отменено + возврат (cancelled)', $cancelled],
                ['Пропущено', $skipped],
            ]
        );

        Log::info('ConsumeInSupplyReservations completed', [
            'consumed' => $consumed,
            'cancelled' => $cancelled,
            'skipped' => $skipped,
            'dry_run' => $dryRun,
        ]);

        return self::SUCCESS;
    }

    protected function findOrder(StockReservation $reservation)
    {
        $marketplace = $this->extractMarketplace($reservation->reason);

        return match ($marketplace) {
            'uzum' => UzumOrder::find($reservation->source_id),
            'wb' => \App\Models\WbOrder::find($reservation->source_id),
            'ozon' => \App\Models\OzonOrder::find($reservation->source_id),
            'ym' => \App\Models\YandexMarketOrder::find($reservation->source_id),
            default => null,
        };
    }

    protected function extractMarketplace(string $reason): string
    {
        if (stripos($reason, 'uzum') !== false) return 'uzum';
        if (stripos($reason, 'wildberries') !== false || stripos($reason, 'wb') !== false) return 'wb';
        if (stripos($reason, 'ozon') !== false) return 'ozon';
        if (stripos($reason, 'yandex') !== false || stripos($reason, 'ym') !== false) return 'ym';
        return 'unknown';
    }
}
