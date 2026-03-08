<?php

declare(strict_types=1);

namespace App\Services\Store;

use App\Models\ProductVariant;
use App\Models\Sale;
use App\Models\Store\StoreOrder;
use App\Models\Store\StoreOrderItem;
use App\Models\Store\StoreProduct;
use App\Models\Warehouse\Warehouse;
use App\Notifications\NewStoreOrderNotification;
use App\Services\SaleService;
use Illuminate\Support\Facades\Log;

/**
 * Оркестрация заказов витрины: Sale + остатки + уведомления
 */
final class StoreOrderService
{
    public function __construct(
        private readonly SaleService $saleService,
    ) {}

    /**
     * Синхронизировать заказ витрины с системой продаж SellerMind
     *
     * Создаёт Sale, резервирует остатки на складе, привязывает к StoreOrder.
     * Ошибки НЕ прерывают оформление заказа покупателя.
     */
    public function syncOrderToSale(StoreOrder $order): ?Sale
    {
        $store = $order->store;
        $companyId = $store->company_id;

        // Находим склад по умолчанию
        $warehouse = $this->getDefaultWarehouse($companyId);

        // Подготавливаем позиции для Sale
        $saleItems = [];
        foreach ($order->items as $item) {
            $saleItems[] = $this->buildSaleItem($item);
        }

        // Создаём Sale
        $sale = $this->saleService->createSale([
            'company_id' => $companyId,
            'type' => 'store',
            'source' => 'store',
            'warehouse_id' => $warehouse?->id,
            'currency' => $store->currency ?? 'UZS',
            'status' => 'draft',
            'notes' => "Заказ из магазина \"{$store->name}\" — {$order->order_number}",
            'metadata' => [
                'store_id' => $store->id,
                'store_order_id' => $order->id,
                'store_order_number' => $order->order_number,
                'customer_name' => $order->customer_name,
                'customer_phone' => $order->customer_phone,
            ],
            'created_by' => null,
            'items' => $saleItems,
        ]);

        // Подтверждаем Sale и резервируем остатки (только если есть позиции с product_variant_id)
        $hasVariants = collect($saleItems)->contains(fn ($item) => ! empty($item['product_variant_id']));

        if ($hasVariants) {
            try {
                $this->saleService->confirmSale($sale, reserveStock: true);
            } catch (\Throwable $e) {
                // Резервирование не удалось — Sale остаётся в draft, но не блокируем заказ
                Log::warning('StoreOrder: failed to reserve stock, Sale stays in draft', [
                    'sale_id' => $sale->id,
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }
        } else {
            // Нет привязок к вариантам — просто подтверждаем без резервирования
            try {
                $this->saleService->confirmSale($sale, reserveStock: false);
            } catch (\Throwable $e) {
                Log::warning('StoreOrder: failed to confirm sale without stock', [
                    'sale_id' => $sale->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Привязываем Sale к StoreOrder
        $order->update(['sellermind_order_id' => $sale->id]);

        Log::info('StoreOrder synced to Sale', [
            'store_order_id' => $order->id,
            'sale_id' => $sale->id,
            'sale_number' => $sale->sale_number,
        ]);

        return $sale;
    }

    /**
     * Отменить Sale при отмене заказа витрины
     */
    public function cancelOrderSale(StoreOrder $order): void
    {
        if (! $order->sellermind_order_id) {
            return;
        }

        $sale = Sale::find($order->sellermind_order_id);

        if (! $sale || $sale->status === 'cancelled') {
            return;
        }

        $this->saleService->cancelSale($sale);

        Log::info('StoreOrder Sale cancelled', [
            'store_order_id' => $order->id,
            'sale_id' => $sale->id,
        ]);
    }

    /**
     * Завершить Sale при оплате заказа
     */
    public function completeOrderSale(StoreOrder $order): void
    {
        if (! $order->sellermind_order_id) {
            return;
        }

        $sale = Sale::find($order->sellermind_order_id);

        if (! $sale || $sale->status === 'completed') {
            return;
        }

        $this->saleService->completeSale($sale);

        Log::info('StoreOrder Sale completed', [
            'store_order_id' => $order->id,
            'sale_id' => $sale->id,
        ]);
    }

    /**
     * Уведомить владельца магазина о новом заказе
     */
    public function notifyStoreOwner(StoreOrder $order): void
    {
        $store = $order->store;
        $company = $store->company;

        if (! $company) {
            return;
        }

        // Загружаем количество позиций для уведомления
        $order->loadCount('items');

        $users = $company->users()->with('notificationSettings')->get();

        foreach ($users as $user) {
            $user->notify(new NewStoreOrderNotification($order, $store->name));
        }

        Log::info('StoreOrder notification sent', [
            'store_order_id' => $order->id,
            'users_notified' => $users->count(),
        ]);
    }

    /**
     * Построить данные позиции для SaleService из StoreOrderItem
     *
     * Цепочка: StoreOrderItem.product_id → StoreProduct → Product → ProductVariant
     */
    private function buildSaleItem(StoreOrderItem $item): array
    {
        $variant = $this->resolveVariantForItem($item);

        if ($variant) {
            return [
                'product_variant_id' => $variant->id,
                'quantity' => $item->quantity,
                'unit_price' => (float) $item->price,
                'discount_percent' => 0,
                'tax_percent' => 0,
            ];
        }

        // Нет привязки к варианту — ручная позиция
        return [
            'product_name' => $item->name,
            'quantity' => $item->quantity,
            'unit_price' => (float) $item->price,
            'discount_percent' => 0,
            'tax_percent' => 0,
        ];
    }

    /**
     * Найти ProductVariant по StoreOrderItem
     *
     * StoreOrderItem.product_id → StoreProduct.id → StoreProduct.product_id → Product → первый активный ProductVariant
     */
    private function resolveVariantForItem(StoreOrderItem $item): ?ProductVariant
    {
        $storeProduct = StoreProduct::with('product.variantsActive')->find($item->product_id);

        if (! $storeProduct || ! $storeProduct->product) {
            return null;
        }

        // Если у StoreOrderItem указан variant_id — используем его напрямую
        if ($item->variant_id) {
            return ProductVariant::where('id', $item->variant_id)
                ->where('product_id', $storeProduct->product_id)
                ->where('is_active', true)
                ->first();
        }

        // Иначе берём первый активный вариант товара
        return $storeProduct->product->variantsActive->first();
    }

    /**
     * Получить склад по умолчанию для компании
     */
    private function getDefaultWarehouse(int $companyId): ?Warehouse
    {
        return Warehouse::where('company_id', $companyId)
            ->where('is_default', true)
            ->first()
            ?? Warehouse::where('company_id', $companyId)->first();
    }
}
