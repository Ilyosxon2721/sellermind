<?php

namespace App\Console\Commands;

use App\Models\WbOrder;
use App\Models\UzumOrder;
use Illuminate\Console\Command;

class RecalculateOrderStatuses extends Command
{
    protected $signature = 'orders:recalculate-statuses
                            {--marketplace= : Filter by marketplace (wb, uzum)}
                            {--dry-run : Show what would be changed without updating}';

    protected $description = 'Recalculate order statuses and status groups for WB and Uzum orders';

    public function handle()
    {
        $this->info('🔄 Recalculating order statuses...');
        $this->newLine();

        $marketplace = $this->option('marketplace');
        $updated = 0;
        $unchanged = 0;

        // Process WB orders
        if (!$marketplace || $marketplace === 'wb') {
            $this->info('Processing WB orders...');
            $result = $this->processWbOrders();
            $updated += $result['updated'];
            $unchanged += $result['unchanged'];
        }

        // Process Uzum orders
        if (!$marketplace || $marketplace === 'uzum') {
            $this->info('Processing Uzum orders...');
            $result = $this->processUzumOrders();
            $updated += $result['updated'];
            $unchanged += $result['unchanged'];
        }

        // Summary
        $this->newLine();
        $this->info('═══════════════════════════════════════════');
        $this->info('📊 Summary:');
        $this->info("   ✓ Updated: {$updated}");
        $this->info("   → Unchanged: {$unchanged}");

        if ($this->option('dry-run')) {
            $this->warn('   ⚠️  DRY RUN - No changes were saved');
        }

        $this->info('═══════════════════════════════════════════');

        return self::SUCCESS;
    }

    protected function processWbOrders(): array
    {
        $orders = WbOrder::all();
        $updated = 0;
        $unchanged = 0;
        $changes = [];

        foreach ($orders as $order) {
            $oldStatus = $order->status;
            $oldStatusGroup = $order->wb_status_group;

            // Recalculate status
            $newStatus = $this->mapWbStatusToInternal(
                $order->wb_supplier_status,
                $order->wb_status
            );

            // Recalculate status group
            $newStatusGroup = $this->mapWbStatusGroup(
                $order->wb_supplier_status,
                $order->wb_status
            );

            // Check if changed
            $statusChanged = $oldStatus !== $newStatus;
            $groupChanged = $oldStatusGroup !== $newStatusGroup;

            if ($statusChanged || $groupChanged) {
                $changes[] = [
                    'id' => $order->id,
                    'external_id' => $order->external_order_id,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'old_group' => $oldStatusGroup,
                    'new_group' => $newStatusGroup,
                ];

                if (!$this->option('dry-run')) {
                    $order->status = $newStatus;
                    $order->wb_status_group = $newStatusGroup;
                    $order->save();
                }

                $updated++;
            } else {
                $unchanged++;
            }
        }

        if (!empty($changes)) {
            $this->line('  WB changes:');
            foreach ($changes as $change) {
                $this->line("    Order #{$change['external_id']}: {$change['old_status']} → {$change['new_status']}");
            }
        }

        return ['updated' => $updated, 'unchanged' => $unchanged];
    }

    protected function processUzumOrders(): array
    {
        $orders = UzumOrder::all();
        $updated = 0;
        $unchanged = 0;
        $changes = [];

        foreach ($orders as $order) {
            $oldStatus = $order->status;

            // Recalculate status based on uzum_status
            $newStatus = $this->mapUzumStatusToInternal($order->uzum_status);

            if ($oldStatus !== $newStatus) {
                $changes[] = [
                    'id' => $order->id,
                    'external_id' => $order->external_order_id,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                ];

                if (!$this->option('dry-run')) {
                    $order->status = $newStatus;
                    $order->save();
                }

                $updated++;
            } else {
                $unchanged++;
            }
        }

        if (!empty($changes)) {
            $this->line('  Uzum changes:');
            foreach ($changes as $change) {
                $this->line("    Order #{$change['external_id']}: {$change['old_status']} → {$change['new_status']}");
            }
        }

        return ['updated' => $updated, 'unchanged' => $unchanged];
    }

    protected function mapWbStatusToInternal(?string $supplierStatus, ?string $wbStatus): string
    {
        // 1. Отменённые
        if (in_array($supplierStatus, ['cancel', 'reject']) ||
            in_array($wbStatus, ['canceled', 'canceled_by_client', 'declined_by_client', 'defect'])) {
            return 'cancelled';
        }

        // 2. Завершённые
        if (in_array($wbStatus, ['delivered', 'sold_from_store']) ||
            $supplierStatus === 'receive') {
            return 'completed';
        }

        // 3. В доставке
        if ($supplierStatus === 'complete' ||
            in_array($wbStatus, ['sold', 'on_way_to_client', 'on_way_from_client', 'ready_for_pickup'])) {
            return 'in_delivery';
        }

        // 4. На сборке
        if ($supplierStatus === 'confirm' ||
            in_array($wbStatus, ['sorted'])) {
            return 'in_assembly';
        }

        // 5. Новые
        if ($supplierStatus === 'new' ||
            $wbStatus === 'waiting') {
            return 'new';
        }

        return 'new';
    }

    protected function mapWbStatusGroup(?string $supplierStatus, ?string $wbStatus): string
    {
        if (in_array($supplierStatus, ['cancel', 'reject']) ||
            in_array($wbStatus, ['canceled', 'canceled_by_client', 'declined_by_client', 'defect'])) {
            return 'canceled';
        }

        if (in_array($wbStatus, ['delivered', 'sold_from_store']) ||
            $wbStatus === 'sold' ||
            $supplierStatus === 'receive') {
            return 'archive';
        }

        if ($supplierStatus === 'complete' ||
            in_array($wbStatus, ['on_way_to_client', 'on_way_from_client', 'ready_for_pickup'])) {
            return 'shipping';
        }

        if ($supplierStatus === 'confirm' ||
            in_array($wbStatus, ['sorted', 'sold'])) {
            return 'assembling';
        }

        if ($supplierStatus === 'new' || $wbStatus === 'waiting') {
            return 'new';
        }

        return 'new';
    }

    protected function mapUzumStatusToInternal(?string $uzumStatus): string
    {
        return match ($uzumStatus) {
            'NEW' => 'new',
            'IN_ASSEMBLY' => 'in_assembly',
            'IN_SUPPLY' => 'in_supply',
            'ACCEPTED_UZUM' => 'accepted_uzum',
            'WAITING_FOR_PICKUP' => 'waiting_pickup',
            'ISSUED' => 'issued',
            'CANCELLED' => 'cancelled',
            'RETURNS' => 'returns',
            default => 'new',
        };
    }
}
