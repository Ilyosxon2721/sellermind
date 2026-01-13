<?php

namespace App\Http\Controllers\Api\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\Channel;
use App\Models\Warehouse\ChannelOrder;
use App\Models\Warehouse\ChannelOrderItem;
use App\Models\Warehouse\ChannelSkuMap;
use App\Models\Warehouse\ProcessedEvent;
use App\Models\Warehouse\StockReservation;
use App\Services\Warehouse\ReservationService;
use App\Support\ApiResponder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ChannelImportController extends Controller
{
    use ApiResponder;

    /**
     * Get company ID with fallback to companies relationship
     */
    private function getCompanyId(): ?int
    {
        $user = Auth::user();
        if (!$user) {
            return null;
        }
        return $user->company_id ?? $user->companies()->first()?->id;
    }

    public function import(Request $request)
    {
        $data = $request->validate([
            'channel_code' => ['required', 'string'],
            'orders' => ['required', 'array'],
            'orders.*.external_order_id' => ['required', 'string'],
            'orders.*.status' => ['nullable', 'string'],
            'orders.*.created_at' => ['nullable', 'date'],
            'orders.*.items' => ['required', 'array'],
            'orders.*.items.*.external_sku_id' => ['required', 'string'],
            'orders.*.items.*.qty' => ['required', 'numeric'],
            'orders.*.items.*.price' => ['nullable', 'numeric'],
        ]);

        $companyId = $this->getCompanyId();
        if (!$companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $channel = Channel::where('company_id', $companyId)->where('code', strtoupper($data['channel_code']))->first();
        if (!$channel) {
            return $this->errorResponse('Channel not found', 'not_found', 'channel_code', 404);
        }

        $imported = 0;
        $updated = 0;
        $reservationsCreated = 0;
        $unmappedItems = [];

        foreach ($data['orders'] as $order) {
            // Idempotency via processed_events
            $eventKey = $order['external_order_id'];
            $existsEvent = ProcessedEvent::where('channel_id', $channel->id)
                ->where('external_event_id', $eventKey)
                ->where('type', 'order_import')
                ->first();
            if ($existsEvent) {
                continue;
            }

            $orderModel = ChannelOrder::updateOrCreate(
                ['channel_id' => $channel->id, 'external_order_id' => $order['external_order_id']],
                [
                    'status' => $order['status'] ?? null,
                    'payload_json' => $order,
                    'created_at_channel' => $order['created_at'] ?? null,
                ]
            );

            $orderModel->items()->delete();
            foreach ($order['items'] as $item) {
                $mappedSkuId = ChannelSkuMap::where('channel_id', $channel->id)
                    ->where('external_sku_id', $item['external_sku_id'])
                    ->value('sku_id');

                ChannelOrderItem::create([
                    'channel_order_id' => $orderModel->id,
                    'external_sku_id' => $item['external_sku_id'],
                    'sku_id' => $mappedSkuId,
                    'qty' => $item['qty'],
                    'price' => $item['price'] ?? 0,
                    'payload_json' => $item,
                ]);

                if (!$mappedSkuId) {
                    $unmappedItems[] = $item['external_sku_id'];
                } else {
                    // Create reservation for NEW/CREATED/CONFIRMED
                    $status = strtoupper($order['status'] ?? 'CREATED');
                    if (in_array($status, ['CREATED', 'CONFIRMED'])) {
                        $reservationService = app(ReservationService::class);
                        try {
                            $reservationService->reserve(
                                $companyId,
                                $channel->default_warehouse_id ?? $this->defaultWarehouse($companyId),
                                $mappedSkuId,
                                (float) $item['qty'],
                                'MARKETPLACE_ORDER',
                                'channel_order',
                                $orderModel->id,
                                $user?->id
                            );
                            $reservationsCreated++;
                        } catch (\Throwable $e) {
                            // ignore reservation failure but log
                            \Log::warning('Reservation failed', ['order' => $order['external_order_id'], 'error' => $e->getMessage()]);
                        }
                    }
                }
            }

            ProcessedEvent::create([
                'channel_id' => $channel->id,
                'external_event_id' => $eventKey,
                'type' => 'order_import',
                'processed_at' => now(),
                'payload_hash' => substr(hash('sha256', json_encode($order)), 0, 64),
            ]);

            $imported++;
        }

        return $this->successResponse([
            'imported' => $imported,
            'updated' => $updated,
            'reservations_created' => $reservationsCreated,
            'unmapped_items' => array_values(array_unique($unmappedItems)),
        ]);
    }

    private function defaultWarehouse(int $companyId): ?int
    {
        return DB::table('warehouses')->where('company_id', $companyId)->where('is_default', true)->value('id')
            ?? DB::table('warehouses')->where('company_id', $companyId)->value('id');
    }
}
