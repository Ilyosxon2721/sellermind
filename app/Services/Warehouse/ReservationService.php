<?php

namespace App\Services\Warehouse;

use App\Models\CompanySetting;
use App\Models\Warehouse\StockLedger;
use App\Models\Warehouse\StockReservation;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ReservationService
{
    public function reserve(
        int $companyId,
        int $warehouseId,
        int $skuId,
        float $qty,
        string $reason,
        ?string $sourceType,
        ?int $sourceId,
        ?int $userId
    ): StockReservation {
        if ($qty <= 0) {
            throw new InvalidArgumentException('Qty must be greater than 0');
        }

        $allowNegative = CompanySetting::where('company_id', $companyId)->value('allow_negative_stock') ?? false;

        return DB::transaction(function () use ($companyId, $warehouseId, $skuId, $qty, $reason, $sourceType, $sourceId, $userId, $allowNegative) {
            if (! $allowNegative) {
                $balanceService = app(StockBalanceService::class);
                $balance = $balanceService->balance($companyId, $warehouseId, $skuId);
                if ($balance['available'] < $qty) {
                    throw new \RuntimeException('Not enough available stock to reserve');
                }
            }

            return StockReservation::create([
                'company_id' => $companyId,
                'warehouse_id' => $warehouseId,
                'sku_id' => $skuId,
                'qty' => $qty,
                'status' => StockReservation::STATUS_ACTIVE,
                'reason' => $reason,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'created_by' => $userId,
            ]);
        });
    }

    public function release(int $reservationId, int $companyId): StockReservation
    {
        return $this->updateStatus($reservationId, $companyId, StockReservation::STATUS_RELEASED);
    }

    public function consume(int $reservationId, int $companyId): StockReservation
    {
        return DB::transaction(function () use ($reservationId, $companyId) {
            $reservation = $this->updateStatus($reservationId, $companyId, StockReservation::STATUS_CONSUMED);

            // Физическое списание со склада
            StockLedger::create([
                'company_id'    => $companyId,
                'warehouse_id'  => $reservation->warehouse_id,
                'sku_id'        => $reservation->sku_id,
                'occurred_at'   => now(),
                'qty_delta'     => -abs($reservation->qty),
                'cost_delta'    => 0,
                'currency_code' => 'UZS',
                'source_type'   => 'RESERVATION',
                'source_id'     => $reservation->id,
                'created_by'    => $reservation->created_by,
            ]);

            return $reservation;
        });
    }

    protected function updateStatus(int $reservationId, int $companyId, string $status): StockReservation
    {
        $reservation = StockReservation::where('company_id', $companyId)->findOrFail($reservationId);
        $reservation->update(['status' => $status]);

        return $reservation;
    }
}
