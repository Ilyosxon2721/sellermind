<?php

namespace App\Console\Commands;

use App\Models\MarketplaceAccount;
use App\Models\OzonOrder;
use App\Models\UzumOrder;
use App\Models\WbOrder;
use App\Models\YandexMarketOrder;
use App\Services\Stock\OrderStockService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessOrderStocks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:process-stocks
                            {--marketplace= : Filter by marketplace (wb, uzum, ozon, ym)}
                            {--account= : Filter by account ID}
                            {--status= : Filter by order status (new, in_assembly, etc)}
                            {--force : Force reprocessing even if already processed}
                            {--dry-run : Show what would be done without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process stock reservations for existing marketplace orders';

    protected OrderStockService $orderStockService;

    public function __construct()
    {
        parent::__construct();
        $this->orderStockService = new OrderStockService();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $marketplace = $this->option('marketplace');
        $accountId = $this->option('account');
        $status = $this->option('status');
        $force = $this->option('force');
        $dryRun = $this->option('dry-run');

        $this->info('Processing marketplace order stocks...');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - no changes will be made');
        }

        $processed = 0;
        $reserved = 0;
        $failed = 0;
        $skipped = 0;

        // Process Uzum orders
        if (!$marketplace || $marketplace === 'uzum') {
            $result = $this->processUzumOrders($accountId, $status, $force, $dryRun);
            $processed += $result['processed'];
            $reserved += $result['reserved'];
            $failed += $result['failed'];
            $skipped += $result['skipped'];
        }

        // Process WB orders
        if (!$marketplace || $marketplace === 'wb') {
            $result = $this->processWbOrders($accountId, $status, $force, $dryRun);
            $processed += $result['processed'];
            $reserved += $result['reserved'];
            $failed += $result['failed'];
            $skipped += $result['skipped'];
        }

        // Process Ozon orders
        if (!$marketplace || $marketplace === 'ozon') {
            $result = $this->processOzonOrders($accountId, $status, $force, $dryRun);
            $processed += $result['processed'];
            $reserved += $result['reserved'];
            $failed += $result['failed'];
            $skipped += $result['skipped'];
        }

        // Process Yandex Market orders
        if (!$marketplace || $marketplace === 'ym') {
            $result = $this->processYmOrders($accountId, $status, $force, $dryRun);
            $processed += $result['processed'];
            $reserved += $result['reserved'];
            $failed += $result['failed'];
            $skipped += $result['skipped'];
        }

        $this->newLine();
        $this->info('Summary:');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Orders Processed', $processed],
                ['Successfully Reserved', $reserved],
                ['Failed', $failed],
                ['Skipped (no variant)', $skipped],
            ]
        );

        return Command::SUCCESS;
    }

    protected function processUzumOrders(?int $accountId, ?string $status, bool $force, bool $dryRun): array
    {
        $query = UzumOrder::query()
            ->with(['account', 'items']);

        if ($accountId) {
            $query->where('marketplace_account_id', $accountId);
        }

        if ($status) {
            $query->where('status', $status);
        } else {
            // Process orders with reserve OR sold statuses (to handle historical orders)
            $query->whereIn('status', [
                // Reserve statuses
                'new', 'in_assembly', 'CREATED', 'PACKING',
                // Sold statuses (for historical orders that were never processed)
                'in_supply', 'accepted_uzum', 'waiting_pickup', 'issued',
                'PENDING_DELIVERY', 'DELIVERING', 'ACCEPTED_AT_DP',
                'DELIVERED_TO_CUSTOMER_DELIVERY_POINT', 'DELIVERED', 'COMPLETED',
            ]);
        }

        if (!$force) {
            $query->where('stock_status', 'none');
        }

        $orders = $query->get();

        $this->info("Found {$orders->count()} Uzum orders to process");

        $stats = ['processed' => 0, 'reserved' => 0, 'failed' => 0, 'skipped' => 0];

        $bar = $this->output->createProgressBar($orders->count());
        $bar->start();

        foreach ($orders as $order) {
            $stats['processed']++;

            if ($dryRun) {
                $this->line("\n  [DRY] Would process order #{$order->external_order_id} ({$order->status})");
                $bar->advance();
                continue;
            }

            try {
                $items = $this->orderStockService->getOrderItems($order, 'uzum');

                if (empty($items)) {
                    $this->line("\n  Skipped order #{$order->external_order_id}: No items found");
                    $stats['skipped']++;
                    $bar->advance();
                    continue;
                }

                $result = $this->orderStockService->processOrderStatusChange(
                    $order->account,
                    $order,
                    null, // old status - null to force processing
                    $order->status,
                    $items
                );

                if ($result['success'] && $result['action'] === 'reserve') {
                    $stats['reserved']++;
                    $this->line("\n  Reserved order #{$order->external_order_id}: {$result['items_processed']} items");
                } elseif ($result['action'] === 'none') {
                    $stats['skipped']++;
                }

            } catch (\Throwable $e) {
                $stats['failed']++;
                $this->error("\n  Failed order #{$order->external_order_id}: {$e->getMessage()}");
                Log::error('ProcessOrderStocks: Failed to process Uzum order', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        return $stats;
    }

    protected function processWbOrders(?int $accountId, ?string $status, bool $force, bool $dryRun): array
    {
        $query = WbOrder::query()
            ->with(['account', 'items']);

        if ($accountId) {
            $query->where('marketplace_account_id', $accountId);
        }

        if ($status) {
            $query->where('status', $status);
        } else {
            // By default, only process orders with reserve statuses
            $query->whereIn('status', ['new', 'confirm', 'assembly', 'in_assembly']);
        }

        if (!$force) {
            $query->where('stock_status', 'none');
        }

        $orders = $query->get();

        $this->info("Found {$orders->count()} WB orders to process");

        $stats = ['processed' => 0, 'reserved' => 0, 'failed' => 0, 'skipped' => 0];

        $bar = $this->output->createProgressBar($orders->count());
        $bar->start();

        foreach ($orders as $order) {
            $stats['processed']++;

            if ($dryRun) {
                $this->line("\n  [DRY] Would process order #{$order->external_order_id} ({$order->status})");
                $bar->advance();
                continue;
            }

            try {
                $items = $this->orderStockService->getOrderItems($order, 'wb');

                if (empty($items)) {
                    $this->line("\n  Skipped order #{$order->external_order_id}: No items found");
                    $stats['skipped']++;
                    $bar->advance();
                    continue;
                }

                $result = $this->orderStockService->processOrderStatusChange(
                    $order->account,
                    $order,
                    null, // old status - null to force processing
                    $order->status,
                    $items
                );

                if ($result['success'] && $result['action'] === 'reserve') {
                    $stats['reserved']++;
                    $this->line("\n  Reserved order #{$order->external_order_id}: {$result['items_processed']} items");
                } elseif ($result['action'] === 'none') {
                    $stats['skipped']++;
                }

            } catch (\Throwable $e) {
                $stats['failed']++;
                $this->error("\n  Failed order #{$order->external_order_id}: {$e->getMessage()}");
                Log::error('ProcessOrderStocks: Failed to process WB order', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        return $stats;
    }

    protected function processOzonOrders(?int $accountId, ?string $status, bool $force, bool $dryRun): array
    {
        $query = OzonOrder::query()
            ->with(['account']);

        if ($accountId) {
            $query->where('marketplace_account_id', $accountId);
        }

        if ($status) {
            $query->where('status', $status);
        } else {
            // By default, only process orders with reserve statuses
            $query->whereIn('status', ['awaiting_packaging', 'awaiting_deliver', 'acceptance_in_progress']);
        }

        if (!$force) {
            $query->where('stock_status', 'none');
        }

        $orders = $query->get();

        $this->info("Found {$orders->count()} Ozon orders to process");

        $stats = ['processed' => 0, 'reserved' => 0, 'failed' => 0, 'skipped' => 0];

        $bar = $this->output->createProgressBar($orders->count());
        $bar->start();

        foreach ($orders as $order) {
            $stats['processed']++;

            if ($dryRun) {
                $this->line("\n  [DRY] Would process order #{$order->posting_number} ({$order->status})");
                $bar->advance();
                continue;
            }

            try {
                $items = $this->orderStockService->getOrderItems($order, 'ozon');

                if (empty($items)) {
                    $this->line("\n  Skipped order #{$order->posting_number}: No items found");
                    $stats['skipped']++;
                    $bar->advance();
                    continue;
                }

                $result = $this->orderStockService->processOrderStatusChange(
                    $order->account,
                    $order,
                    null, // old status - null to force processing
                    $order->status,
                    $items
                );

                if ($result['success'] && $result['action'] === 'reserve') {
                    $stats['reserved']++;
                    $this->line("\n  Reserved order #{$order->posting_number}: {$result['items_processed']} items");
                } elseif ($result['action'] === 'none') {
                    $stats['skipped']++;
                }

            } catch (\Throwable $e) {
                $stats['failed']++;
                $this->error("\n  Failed order #{$order->posting_number}: {$e->getMessage()}");
                Log::error('ProcessOrderStocks: Failed to process Ozon order', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        return $stats;
    }

    protected function processYmOrders(?int $accountId, ?string $status, bool $force, bool $dryRun): array
    {
        $query = YandexMarketOrder::query()
            ->with(['account']);

        if ($accountId) {
            $query->where('marketplace_account_id', $accountId);
        }

        if ($status) {
            $query->where('status', $status);
        } else {
            // By default, only process orders with reserve statuses
            $query->whereIn('status', ['PROCESSING', 'RESERVED']);
        }

        if (!$force) {
            $query->where('stock_status', 'none');
        }

        $orders = $query->get();

        $this->info("Found {$orders->count()} Yandex Market orders to process");

        $stats = ['processed' => 0, 'reserved' => 0, 'failed' => 0, 'skipped' => 0];

        $bar = $this->output->createProgressBar($orders->count());
        $bar->start();

        foreach ($orders as $order) {
            $stats['processed']++;

            if ($dryRun) {
                $this->line("\n  [DRY] Would process order #{$order->order_id} ({$order->status})");
                $bar->advance();
                continue;
            }

            try {
                $items = $this->orderStockService->getOrderItems($order, 'ym');

                if (empty($items)) {
                    $this->line("\n  Skipped order #{$order->order_id}: No items found");
                    $stats['skipped']++;
                    $bar->advance();
                    continue;
                }

                $result = $this->orderStockService->processOrderStatusChange(
                    $order->account,
                    $order,
                    null, // old status - null to force processing
                    $order->status,
                    $items
                );

                if ($result['success'] && $result['action'] === 'reserve') {
                    $stats['reserved']++;
                    $this->line("\n  Reserved order #{$order->order_id}: {$result['items_processed']} items");
                } elseif ($result['action'] === 'none') {
                    $stats['skipped']++;
                }

            } catch (\Throwable $e) {
                $stats['failed']++;
                $this->error("\n  Failed order #{$order->order_id}: {$e->getMessage()}");
                Log::error('ProcessOrderStocks: Failed to process YM order', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        return $stats;
    }
}
