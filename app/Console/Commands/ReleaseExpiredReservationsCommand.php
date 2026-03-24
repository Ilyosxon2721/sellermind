<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Warehouse\StockReservation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Команда для освобождения истёкших резервов остатков
 */
final class ReleaseExpiredReservationsCommand extends Command
{
    protected $signature = 'warehouse:release-expired-reservations';

    protected $description = 'Освободить истёкшие резервы остатков';

    public function handle(): int
    {
        $expired = StockReservation::query()
            ->where('status', StockReservation::STATUS_ACTIVE)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->get();

        $count = 0;
        foreach ($expired as $reservation) {
            $reservation->update(['status' => StockReservation::STATUS_RELEASED]);
            $count++;
        }

        if ($count > 0) {
            Log::info("Освобождено {$count} истёкших резервов");
        }

        $this->info("Освобождено резервов: {$count}");

        return self::SUCCESS;
    }
}
