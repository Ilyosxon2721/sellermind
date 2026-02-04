<?php

namespace App\Observers;

use App\Jobs\Risment\SendOrderToRisment;
use App\Jobs\Risment\SendReturnToRisment;
use App\Models\IntegrationLink;
use App\Models\MarketplaceAccount;
use App\Models\OzonOrder;
use App\Models\UzumOrder;
use App\Models\WbOrder;
use App\Models\YandexMarketOrder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * Observes all marketplace order models.
 * When an FBS order is created or its status changes,
 * dispatches it to RISMENT via Redis queue.
 */
class RismentOrderObserver
{
    /**
     * Handle order "created" event — send new FBS orders to RISMENT.
     */
    public function created(Model $order): void
    {
        if (!$this->isFbs($order)) {
            return;
        }

        $companyId = $this->getCompanyId($order);
        if (!$companyId || !IntegrationLink::rismentForCompany($companyId)) {
            return;
        }

        $marketplace = $this->getMarketplaceCode($order);

        SendOrderToRisment::dispatch($companyId, 'order.created', [
            'order_id' => $order->id,
            'marketplace' => $marketplace,
            'external_order_id' => $this->getExternalOrderId($order),
            'status' => $order->status_normalized ?? $order->status ?? 'new',
            'customer_name' => $order->customer_name ?? null,
            'customer_phone' => $order->customer_phone ?? null,
            'total_amount' => $order->total_amount ?? $order->price ?? null,
            'currency' => $order->currency ?? $order->currency_code ?? null,
            'delivery_type' => $this->getDeliveryType($order),
            'ordered_at' => $order->ordered_at?->toIso8601String(),
            'items' => $this->extractItems($order, $marketplace),
        ]);

        Log::info('RismentOrderObserver: FBS order dispatched to RISMENT', [
            'order_id' => $order->id,
            'marketplace' => $marketplace,
            'company_id' => $companyId,
        ]);
    }

    /**
     * Handle order "updated" event — send status changes and cancellations.
     */
    public function updated(Model $order): void
    {
        // Only react to status changes
        $statusChanged = $order->wasChanged('status_normalized')
            || $order->wasChanged('status')
            || $order->wasChanged('wb_status');

        if (!$statusChanged) {
            return;
        }

        if (!$this->isFbs($order)) {
            return;
        }

        $companyId = $this->getCompanyId($order);
        if (!$companyId || !IntegrationLink::rismentForCompany($companyId)) {
            return;
        }

        $marketplace = $this->getMarketplaceCode($order);
        $newStatus = $order->status_normalized ?? $order->status;

        // Cancelled orders → risment:returns queue
        if ($this->isCancelled($newStatus)) {
            SendReturnToRisment::dispatch($companyId, 'order.cancelled', [
                'order_id' => $order->id,
                'marketplace' => $marketplace,
                'external_order_id' => $this->getExternalOrderId($order),
                'status' => $newStatus,
                'cancelled_at' => now()->toIso8601String(),
            ]);
            return;
        }

        // Regular status updates → risment:orders queue
        SendOrderToRisment::dispatch($companyId, 'order.status_changed', [
            'order_id' => $order->id,
            'marketplace' => $marketplace,
            'external_order_id' => $this->getExternalOrderId($order),
            'old_status' => $order->getOriginal('status_normalized') ?? $order->getOriginal('status'),
            'new_status' => $newStatus,
            'updated_at' => now()->toIso8601String(),
        ]);
    }

    // ========== FBS Detection ==========

    protected function isFbs(Model $order): bool
    {
        if ($order instanceof WbOrder) {
            $deliveryType = strtolower($order->wb_delivery_type ?? '');
            return $deliveryType === 'fbs' || $deliveryType === '';
            // WB: empty delivery_type often means FBS (seller ships)
        }

        if ($order instanceof UzumOrder) {
            $deliveryType = strtolower($order->delivery_type ?? '');
            return str_contains($deliveryType, 'fbs') || $deliveryType === 'seller_delivery';
        }

        if ($order instanceof OzonOrder) {
            $deliveryMethod = strtolower($order->delivery_method ?? '');
            return str_contains($deliveryMethod, 'fbs')
                || str_contains($deliveryMethod, 'seller');
        }

        if ($order instanceof YandexMarketOrder) {
            $deliveryType = strtolower($order->delivery_type ?? '');
            return str_contains($deliveryType, 'fbs')
                || str_contains($deliveryType, 'delivery_by_seller');
        }

        return false;
    }

    // ========== Helpers ==========

    protected function getCompanyId(Model $order): ?int
    {
        $accountId = $order->marketplace_account_id ?? null;
        if (!$accountId) return null;

        return MarketplaceAccount::where('id', $accountId)->value('company_id');
    }

    protected function getMarketplaceCode(Model $order): string
    {
        return match (true) {
            $order instanceof WbOrder => 'wb',
            $order instanceof UzumOrder => 'uzum',
            $order instanceof OzonOrder => 'ozon',
            $order instanceof YandexMarketOrder => 'ym',
            default => 'unknown',
        };
    }

    protected function getExternalOrderId(Model $order): ?string
    {
        if ($order instanceof OzonOrder) {
            return $order->posting_number ?? $order->order_id ?? (string) $order->id;
        }
        if ($order instanceof YandexMarketOrder) {
            return $order->order_id ?? (string) $order->id;
        }
        return $order->external_order_id ?? (string) $order->id;
    }

    protected function getDeliveryType(Model $order): ?string
    {
        if ($order instanceof WbOrder) return $order->wb_delivery_type;
        if ($order instanceof UzumOrder) return $order->delivery_type;
        if ($order instanceof OzonOrder) return $order->delivery_method;
        if ($order instanceof YandexMarketOrder) return $order->delivery_type;
        return null;
    }

    protected function extractItems(Model $order, string $marketplace): array
    {
        // WB: each order row = 1 item
        if ($marketplace === 'wb') {
            return [[
                'sku' => $order->sku,
                'article' => $order->article,
                'nm_id' => $order->nm_id,
                'quantity' => 1,
                'price' => $order->price,
            ]];
        }

        // Ozon: items stored in products JSON column
        if ($marketplace === 'ozon' && $order instanceof OzonOrder) {
            $products = $order->products ?? [];
            return collect($products)->map(fn($p) => [
                'sku' => $p['offer_id'] ?? null,
                'quantity' => $p['quantity'] ?? 1,
                'price' => $p['price'] ?? null,
            ])->toArray();
        }

        // YM: items stored in order_data JSON column
        if ($marketplace === 'ym' && $order instanceof YandexMarketOrder) {
            $items = $order->order_data['items'] ?? [];
            return collect($items)->map(fn($i) => [
                'sku' => $i['offerId'] ?? null,
                'quantity' => $i['count'] ?? 1,
                'price' => $i['price'] ?? null,
            ])->toArray();
        }

        return [];
    }

    protected function isCancelled(?string $status): bool
    {
        return in_array(strtolower($status ?? ''), [
            'cancelled', 'canceled', 'cancel',
            'declined', 'defect', 'returned',
        ]);
    }
}
