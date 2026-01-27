<?php

namespace App\Console\Commands;

use App\Models\OzonOrder;
use App\Models\UzumOrder;
use App\Models\WbOrder;
use App\Models\YandexMarketOrder;
use App\Models\Warehouse\StockReservation;
use App\Services\Stock\OrderStockService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ConsumeReservedSoldOrders extends Command
{
    protected $signature = 'orders:consume-sold
                            {--marketplace= : Filter by marketplace (wb, uzum, ozon, ym)}
                            {--dry-run : Show what would be done without making changes}';

    protected $description = 'Consume reservations for orders that are in sold status but still have active reservations';

    protected OrderStockService $orderStockService;

    public function __construct()
    {
        parent::__construct();
        $this->orderStockService = new OrderStockService();
    }

    public function handle(): int
    {
        $marketplace = $this->option('marketplace');
        $dryRun = $this->option('dry-run');

        $this->info('Processing orders with sold status but active reservations...');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - no changes will be made');
        }

        $consumed = 0;
        $failed = 0;

        // Process Uzum orders
        if (!$marketplace || $marketplace === 'uzum') {
            $result = $this->processMarketplace('uzum', UzumOrder::class, $dryRun);
            $consumed += $result['consumed'];
            $failed += $result['failed'];
        }

        // Process WB orders
        if (!$marketplace || $marketplace === 'wb') {
            $result = $this->processMarketplace('wb', WbOrder::class, $dryRun);
            $consumed += $result['consumed'];
            $failed += $result['failed'];
        }

        // Process Ozon orders
        if (!$marketplace || $marketplace === 'ozon') {
            $result = $this->processMarketplace('ozon', OzonOrder::class, $dryRun);
            $consumed += $result['consumed'];
            $failed += $result['failed'];
        }

        // Process Yandex Market orders
        if (!$marketplace || $marketplace === 'ym') {
            $result = $this->processMarketplace('ym', YandexMarketOrder::class, $dryRun);
            $consumed += $result['consumed'];
            $failed += $result['failed'];
        }

        $this->newLine();
        $this->info('Summary:');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Orders Consumed', $consumed],
                ['Failed', $failed],
            ]
        );

        return Command::SUCCESS;
    }

    protected function processMarketplace(string $marketplace, string $modelClass, bool $dryRun): array
    {
        $soldStatuses = OrderStockService::SOLD_STATUSES[$marketplace] ?? [];

        if (empty($soldStatuses)) {
            $this->line("No sold statuses defined for {$marketplace}");
            return ['consumed' => 0, 'failed' => 0];
        }

        // Check if model has status_normalized column
        $hasStatusNormalized = in_array($marketplace, ['uzum', 'wb']);

        // Find orders that are in sold status but have stock_status = reserved
        $query = $modelClass::query()
            ->where('stock_status', 'reserved')
            ->where(function ($q) use ($soldStatuses, $hasStatusNormalized) {
                foreach ($soldStatuses as $status) {
                    $q->orWhere('status', $status)
                      ->orWhere('status', strtolower($status));

                    if ($hasStatusNormalized) {
                        $q->orWhere('status_normalized', $status)
                          ->orWhere('status_normalized', strtolower($status));
                    }
                }
            });

        $orders = $query->get();

        $this->info("Found {$orders->count()} {$marketplace} orders with sold status but still reserved");

        $stats = ['consumed' => 0, 'failed' => 0];

        foreach ($orders as $order) {
            $externalId = $order->external_order_id ?? $order->order_id ?? $order->posting_number ?? $order->id;

            // Check if there are active reservations
            $activeReservations = StockReservation::where('source_type', 'marketplace_order')
                ->where('source_id', $order->id)
                ->where('status', StockReservation::STATUS_ACTIVE)
                ->count();

            if ($activeReservations === 0) {
                continue;
            }

            if ($dryRun) {
                $this->line("  [DRY] Would consume {$activeReservations} reservations for order #{$externalId}");
                $stats['consumed']++;
                continue;
            }

            try {
                // Consume reservations
                $updated = StockReservation::where('source_type', 'marketplace_order')
                    ->where('source_id', $order->id)
                    ->where('status', StockReservation::STATUS_ACTIVE)
                    ->update(['status' => StockReservation::STATUS_CONSUMED]);

                // Update order stock_status to sold
                $order->update([
                    'stock_status' => 'sold',
                    'stock_sold_at' => now(),
                ]);

                $this->line("  Consumed {$updated} reservations for order #{$externalId}");
                $stats['consumed']++;

                Log::info('ConsumeReservedSoldOrders: Consumed reservations for sold order', [
                    'marketplace' => $marketplace,
                    'order_id' => $order->id,
                    'external_order_id' => $externalId,
                    'reservations_consumed' => $updated,
                ]);

            } catch (\Throwable $e) {
                $stats['failed']++;
                $this->error("  Failed order #{$externalId}: {$e->getMessage()}");
                Log::error('ConsumeReservedSoldOrders: Failed to consume reservations', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $stats;
    }
}
