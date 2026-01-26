<?php

namespace App\Console\Commands;

use App\Models\UzumOrder;
use App\Models\Warehouse\StockReservation;
use App\Services\Stock\OrderStockService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ConsumeInSupplyReservations extends Command
{
    protected $signature = 'reservations:consume-sold
                            {--dry-run : Показать что будет изменено без реального изменения}';

    protected $description = 'Списать резервы для заказов со статусом in_supply/issued и других sold статусов';

    protected OrderStockService $orderStockService;

    public function __construct(OrderStockService $orderStockService)
    {
        parent::__construct();
        $this->orderStockService = $orderStockService;
    }

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $this->info('Поиск активных резервов для заказов со статусом sold...');

        // Найти все активные резервы от маркетплейс заказов
        $reservations = StockReservation::where('status', StockReservation::STATUS_ACTIVE)
            ->where('source_type', 'marketplace_order')
            ->whereNotNull('source_id')
            ->get();

        $this->line("Найдено активных резервов: {$reservations->count()}");

        $consumed = 0;
        $skipped = 0;

        foreach ($reservations as $reservation) {
            // Найти заказ по source_id
            $order = $this->findOrder($reservation);

            if (!$order) {
                $this->warn("  Резерв #{$reservation->id}: заказ не найден (source_id: {$reservation->source_id})");
                $skipped++;
                continue;
            }

            $status = $order->status_normalized ?? $order->status ?? null;
            $marketplace = $this->extractMarketplace($reservation->reason);

            // Проверить является ли статус sold
            if ($this->orderStockService->isSoldStatus($marketplace, $status)) {
                $this->info("  Резерв #{$reservation->id}: заказ #{$order->id} статус '{$status}' - СПИСАНИЕ");

                if (!$dryRun) {
                    $reservation->update(['status' => StockReservation::STATUS_CONSUMED]);

                    // Обновить stock_status заказа если не sold
                    if ($order->stock_status !== 'sold') {
                        $order->update([
                            'stock_status' => 'sold',
                            'stock_sold_at' => now(),
                        ]);
                    }
                }

                $consumed++;
            } else {
                $this->line("  Резерв #{$reservation->id}: заказ #{$order->id} статус '{$status}' - пропуск");
                $skipped++;
            }
        }

        $this->newLine();
        if ($dryRun) {
            $this->info("[DRY-RUN] Будет списано резервов: {$consumed}");
        } else {
            $this->info("Списано резервов: {$consumed}");
        }
        $this->line("Пропущено: {$skipped}");

        Log::info('ConsumeInSupplyReservations completed', [
            'consumed' => $consumed,
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
