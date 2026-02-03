<?php

namespace App\Console\Commands;

use App\Models\UzumOrder;
use App\Models\WbOrder;
use App\Services\Stock\OrderStockService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessOrderStockRetroactive extends Command
{
    protected $signature = 'orders:process-stock-retroactive
                            {--marketplace= : wb, uzum or all}
                            {--dry-run : Preview changes without making them}
                            {--limit=0 : Process only first N orders (0 = all)}';

    protected $description = 'Ретроактивно обработать остатки для существующих заказов с stock_status=none';

    protected OrderStockService $orderStockService;

    public function __construct()
    {
        parent::__construct();
        $this->orderStockService = new OrderStockService;
    }

    public function handle(): int
    {
        $marketplace = $this->option('marketplace') ?: 'all';
        $dryRun = $this->option('dry-run');
        $limit = (int) $this->option('limit');

        $this->info('=== Ретроактивная обработка остатков ===');
        $this->info("Маркетплейс: {$marketplace}");
        $this->info('Dry run: '.($dryRun ? 'Да' : 'Нет'));
        if ($limit > 0) {
            $this->info("Лимит: {$limit}");
        }
        $this->newLine();

        $stats = [
            'wb' => ['processed' => 0, 'reserved' => 0, 'sold' => 0, 'skipped' => 0, 'errors' => 0],
            'uzum' => ['processed' => 0, 'reserved' => 0, 'sold' => 0, 'skipped' => 0, 'errors' => 0],
        ];

        if ($marketplace === 'all' || $marketplace === 'wb') {
            $stats['wb'] = $this->processWbOrders($dryRun, $limit);
        }

        if ($marketplace === 'all' || $marketplace === 'uzum') {
            $stats['uzum'] = $this->processUzumOrders($dryRun, $limit);
        }

        // Print summary
        $this->newLine();
        $this->info('=== Итого ===');
        $this->table(
            ['Маркетплейс', 'Обработано', 'Reserved', 'Sold', 'Пропущено', 'Ошибки'],
            [
                ['WB', $stats['wb']['processed'], $stats['wb']['reserved'], $stats['wb']['sold'], $stats['wb']['skipped'], $stats['wb']['errors']],
                ['Uzum', $stats['uzum']['processed'], $stats['uzum']['reserved'], $stats['uzum']['sold'], $stats['uzum']['skipped'], $stats['uzum']['errors']],
            ]
        );

        return Command::SUCCESS;
    }

    protected function processWbOrders(bool $dryRun, int $limit): array
    {
        $stats = ['processed' => 0, 'reserved' => 0, 'sold' => 0, 'skipped' => 0, 'errors' => 0];

        $query = WbOrder::where('stock_status', 'none')
            ->whereIn('status', array_merge(
                OrderStockService::RESERVE_STATUSES['wb'],
                OrderStockService::SOLD_STATUSES['wb']
            ))
            ->with(['account', 'items']);

        if ($limit > 0) {
            $query->limit($limit);
        }

        $orders = $query->get();
        $this->info("WB: Найдено {$orders->count()} заказов для обработки");

        $bar = $this->output->createProgressBar($orders->count());
        $bar->start();

        foreach ($orders as $order) {
            try {
                $account = $order->account;
                if (! $account) {
                    $stats['skipped']++;
                    $bar->advance();

                    continue;
                }

                $status = $order->status;
                $items = $this->orderStockService->getOrderItems($order, 'wb');

                if (empty($items)) {
                    // Fallback: WB orders have SKU directly
                    $items = [[
                        'sku_id' => $order->chrt_id ?? null,
                        'nm_id' => $order->nm_id,
                        'barcode' => $order->sku,
                        'offer_id' => $order->article,
                        'quantity' => 1,
                    ]];
                }

                if ($dryRun) {
                    $isReserve = $this->orderStockService->isReserveStatus('wb', $status);
                    $isSold = $this->orderStockService->isSoldStatus('wb', $status);

                    if ($isReserve) {
                        $stats['reserved']++;
                    } elseif ($isSold) {
                        $stats['sold']++;
                    }
                    $stats['processed']++;
                } else {
                    $result = $this->orderStockService->processOrderStatusChange(
                        $account,
                        $order,
                        null, // old status unknown for retroactive
                        $status,
                        $items
                    );

                    if ($result['success']) {
                        $stats['processed']++;
                        if ($result['action'] === 'reserve') {
                            $stats['reserved']++;
                        } elseif ($result['action'] === 'sold') {
                            $stats['sold']++;
                        }
                    } else {
                        $stats['errors']++;
                    }
                }
            } catch (\Throwable $e) {
                $stats['errors']++;
                Log::error('ProcessOrderStockRetroactive WB error', [
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

    protected function processUzumOrders(bool $dryRun, int $limit): array
    {
        $stats = ['processed' => 0, 'reserved' => 0, 'sold' => 0, 'skipped' => 0, 'errors' => 0];

        $query = UzumOrder::where('stock_status', 'none')
            ->whereIn('status', array_merge(
                OrderStockService::RESERVE_STATUSES['uzum'],
                OrderStockService::SOLD_STATUSES['uzum']
            ))
            ->with(['account', 'items']);

        if ($limit > 0) {
            $query->limit($limit);
        }

        $orders = $query->get();
        $this->info("Uzum: Найдено {$orders->count()} заказов для обработки");

        $bar = $this->output->createProgressBar($orders->count());
        $bar->start();

        foreach ($orders as $order) {
            try {
                $account = $order->account;
                if (! $account) {
                    $stats['skipped']++;
                    $bar->advance();

                    continue;
                }

                $status = $order->status;
                $items = $this->orderStockService->getOrderItems($order, 'uzum');

                if ($dryRun) {
                    $isReserve = $this->orderStockService->isReserveStatus('uzum', $status);
                    $isSold = $this->orderStockService->isSoldStatus('uzum', $status);

                    if ($isReserve) {
                        $stats['reserved']++;
                    } elseif ($isSold) {
                        $stats['sold']++;
                    }
                    $stats['processed']++;
                } else {
                    $result = $this->orderStockService->processOrderStatusChange(
                        $account,
                        $order,
                        null, // old status unknown for retroactive
                        $status,
                        $items
                    );

                    if ($result['success']) {
                        $stats['processed']++;
                        if ($result['action'] === 'reserve') {
                            $stats['reserved']++;
                        } elseif ($result['action'] === 'sold') {
                            $stats['sold']++;
                        }
                    } else {
                        $stats['errors']++;
                    }
                }
            } catch (\Throwable $e) {
                $stats['errors']++;
                Log::error('ProcessOrderStockRetroactive Uzum error', [
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
