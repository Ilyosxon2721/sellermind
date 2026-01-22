<?php

namespace App\Services;

use App\Models\Counterparty;
use App\Models\ProductVariant;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Services\Stock\StockSyncService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Сервис для работы с продажами
 */
class SaleService
{
    public function __construct(
        protected StockSyncService $stockSyncService,
        protected SaleReservationService $reservationService
    ) {}

    /**
     * Создать новую продажу
     *
     * @param array $data
     * @return Sale
     * @throws \Exception
     */
    public function createSale(array $data): Sale
    {
        return DB::transaction(function () use ($data) {
            // Генерируем номер продажи если не указан
            if (empty($data['sale_number'])) {
                $data['sale_number'] = Sale::generateSaleNumber($data['type'] ?? 'manual');
            }

            // Создаем продажу
            $sale = Sale::create([
                'company_id' => $data['company_id'] ?? $this->getDefaultCompanyId(),
                'sale_number' => $data['sale_number'],
                'type' => $data['type'] ?? 'manual',
                'source' => $data['source'] ?? 'manual',
                'counterparty_id' => $data['counterparty_id'] ?? null,
                'warehouse_id' => $data['warehouse_id'] ?? null,
                'currency' => $data['currency'] ?? 'UZS',
                'status' => $data['status'] ?? 'draft',
                'notes' => $data['notes'] ?? null,
                'metadata' => $data['metadata'] ?? null,
                'created_by' => $data['created_by'] ?? auth()->id(),
            ]);

            // Добавляем товары если они есть
            if (!empty($data['items'])) {
                foreach ($data['items'] as $itemData) {
                    $this->addItemToSale($sale, $itemData);
                }

                // Пересчитываем итоги
                $sale->recalculateTotals();
            }

            Log::info('Sale created', [
                'sale_id' => $sale->id,
                'sale_number' => $sale->sale_number,
                'type' => $sale->type,
                'total_amount' => $sale->total_amount,
            ]);

            return $sale->fresh(['items', 'counterparty']);
        });
    }

    /**
     * Добавить позицию в продажу
     *
     * @param Sale $sale
     * @param array $itemData
     * @return SaleItem
     */
    public function addItemToSale(Sale $sale, array $itemData): SaleItem
    {
        // Если указан product_variant_id, создаем позицию из варианта
        if (!empty($itemData['product_variant_id'])) {
            $variant = ProductVariant::findOrFail($itemData['product_variant_id']);

            $item = SaleItem::createFromVariant(
                $variant,
                $itemData['quantity'] ?? 1,
                $itemData['unit_price'] ?? null,
                $itemData['discount_percent'] ?? 0
            );

            $item->sale_id = $sale->id;
            $item->tax_percent = $itemData['tax_percent'] ?? 0;

            // Пересчитываем с учетом налога
            $item->calculateTotals();
            $item->save();
        } else {
            // Ручное добавление позиции без привязки к товару
            $item = new SaleItem($itemData);
            $item->sale_id = $sale->id;
            $item->calculateTotals();
            $item->save();
        }

        return $item;
    }

    /**
     * Обновить позицию продажи
     *
     * @param SaleItem $item
     * @param array $data
     * @return SaleItem
     */
    public function updateSaleItem(SaleItem $item, array $data): SaleItem
    {
        $item->fill($data);
        $item->calculateTotals();
        $item->save();

        // Пересчитываем итоги продажи
        $item->sale->recalculateTotals();

        return $item->fresh();
    }

    /**
     * Удалить позицию из продажи
     *
     * @param SaleItem $item
     * @return bool
     */
    public function removeSaleItem(SaleItem $item): bool
    {
        $sale = $item->sale;

        // Если остатки уже списаны, возвращаем их
        if ($item->stock_deducted) {
            $item->returnStock();
        }

        $result = $item->delete();

        // Пересчитываем итоги продажи
        $sale->recalculateTotals();

        return $result;
    }

    /**
     * Подтвердить продажу и зарезервировать остатки
     *
     * @param Sale $sale
     * @param bool $reserveStock Зарезервировать ли остатки
     * @return Sale
     * @throws \Exception
     */
    public function confirmSale(Sale $sale, bool $reserveStock = true): Sale
    {
        return DB::transaction(function () use ($sale, $reserveStock) {
            // Проверяем статус перед резервированием
            if ($sale->status !== 'draft') {
                throw new \Exception('Cannot confirm sale with status: ' . $sale->status);
            }

            // Сначала резервируем остатки (пока статус draft)
            if ($reserveStock) {
                $results = $this->reservationService->reserveStock($sale);

                if ($results['failed'] > 0) {
                    throw new \Exception('Failed to reserve stock: ' . json_encode($results['errors']));
                }
            }

            // Только после успешного резервирования меняем статус
            if (!$sale->confirm()) {
                throw new \Exception('Failed to confirm sale');
            }

            Log::info('Sale confirmed', [
                'sale_id' => $sale->id,
                'sale_number' => $sale->sale_number,
                'stock_reserved' => $reserveStock,
            ]);

            return $sale->fresh(['items']);
        });
    }

    /**
     * Отгрузить товары (финализировать резерв)
     * ВАЖНО: Именно здесь происходит синхронизация с маркетплейсами
     *
     * @param Sale $sale
     * @param array|null $itemIds Конкретные позиции для отгрузки (null = все)
     * @return array Результаты отгрузки
     * @throws \Exception
     */
    public function shipStock(Sale $sale, ?array $itemIds = null): array
    {
        return $this->reservationService->shipStock($sale, $itemIds);
    }

    /**
     * Списать остатки по всем позициям продажи (DEPRECATED - используйте reserveStock)
     * @deprecated Используйте reservationService->reserveStock() вместо этого
     *
     * @param Sale $sale
     * @return array Результаты списания
     */
    public function deductStockForSale(Sale $sale): array
    {
        // Теперь этот метод просто вызывает резервирование
        return $this->reservationService->reserveStock($sale);
    }

    /**
     * Завершить продажу
     *
     * @param Sale $sale
     * @return Sale
     */
    public function completeSale(Sale $sale): Sale
    {
        $sale->complete();

        Log::info('Sale completed', [
            'sale_id' => $sale->id,
            'sale_number' => $sale->sale_number,
        ]);

        return $sale->fresh();
    }

    /**
     * Отменить продажу и вернуть остатки
     *
     * @param Sale $sale
     * @return Sale
     * @throws \Exception
     */
    public function cancelSale(Sale $sale): Sale
    {
        return DB::transaction(function () use ($sale) {
            // Отменяем резервы и возвращаем товары на склад
            $results = $this->reservationService->cancelReservations($sale);

            if ($results['failed'] > 0) {
                Log::warning('Some reservations could not be cancelled', [
                    'sale_id' => $sale->id,
                    'errors' => $results['errors'],
                ]);
            }

            if (!$sale->cancel()) {
                throw new \Exception('Cannot cancel sale with status: ' . $sale->status);
            }

            Log::info('Sale cancelled', [
                'sale_id' => $sale->id,
                'sale_number' => $sale->sale_number,
                'reservations_cancelled' => $results['success'],
            ]);

            return $sale->fresh(['items']);
        });
    }

    /**
     * Синхронизировать остатки товара с маркетплейсами
     *
     * @param ProductVariant $variant
     * @return void
     */
    protected function syncStockToMarketplaces(ProductVariant $variant): void
    {
        try {
            $results = $this->stockSyncService->syncVariantStock($variant);

            Log::info('Stock synced to marketplaces after sale', [
                'variant_id' => $variant->id,
                'sku' => $variant->sku,
                'new_stock' => $variant->stock_default,
                'sync_results' => $results,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to sync stock to marketplaces', [
                'variant_id' => $variant->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Создать продажу из заказа маркетплейса
     *
     * @param string $orderType Тип заказа (uzum_order, wb_order, ozon_order, ym_order)
     * @param int $orderId ID заказа
     * @param array $additionalData Дополнительные данные
     * @return Sale
     */
    public function createSaleFromMarketplaceOrder(string $orderType, int $orderId, array $additionalData = []): Sale
    {
        // Получаем модель заказа
        $orderModel = $this->getMarketplaceOrderModel($orderType);
        $order = $orderModel::findOrFail($orderId);

        $saleData = [
            'company_id' => $order->account->company_id ?? $this->getDefaultCompanyId(),
            'type' => 'marketplace',
            'source' => $this->extractSourceFromOrderType($orderType),
            'marketplace_order_type' => $orderType,
            'marketplace_order_id' => $orderId,
            'currency' => $order->currency ?? 'UZS',
            'status' => 'confirmed', // Заказы с маркетплейсов сразу подтверждены
            'notes' => $additionalData['notes'] ?? null,
            'metadata' => array_merge(
                $additionalData['metadata'] ?? [],
                ['marketplace_order_data' => $order->toArray()]
            ),
        ];

        // Создаем товарные позиции из заказа
        $items = $this->extractItemsFromMarketplaceOrder($order);
        $saleData['items'] = $items;

        return $this->createSale($saleData);
    }

    /**
     * Получить модель заказа маркетплейса
     */
    protected function getMarketplaceOrderModel(string $orderType): string
    {
        return match($orderType) {
            'uzum_order' => \App\Models\UzumOrder::class,
            'wb_order' => \App\Models\WbOrder::class,
            'ozon_order' => \App\Models\OzonOrder::class,
            'ym_order' => \App\Models\YandexMarketOrder::class,
            default => throw new \Exception("Unknown order type: {$orderType}"),
        };
    }

    /**
     * Извлечь источник из типа заказа
     */
    protected function extractSourceFromOrderType(string $orderType): string
    {
        return match($orderType) {
            'uzum_order' => 'uzum',
            'wb_order' => 'wb',
            'ozon_order' => 'ozon',
            'ym_order' => 'ym',
            default => 'manual',
        };
    }

    /**
     * Извлечь товарные позиции из заказа маркетплейса
     */
    protected function extractItemsFromMarketplaceOrder($order): array
    {
        $items = [];

        // Для разных маркетплейсов разная структура
        if (method_exists($order, 'items') && $order->items) {
            foreach ($order->items as $orderItem) {
                $items[] = [
                    'product_name' => $orderItem->name ?? $orderItem->product_name ?? 'Unknown',
                    'sku' => $orderItem->sku ?? null,
                    'quantity' => $orderItem->quantity ?? 1,
                    'unit_price' => $orderItem->price ?? $orderItem->unit_price ?? 0,
                    'discount_percent' => 0,
                    'tax_percent' => 0,
                ];
            }
        } else {
            // Если items нет, создаем одну позицию из общей суммы заказа
            $items[] = [
                'product_name' => 'Заказ ' . ($order->order_number ?? $order->external_order_id ?? ''),
                'quantity' => 1,
                'unit_price' => $order->total_amount ?? 0,
                'discount_percent' => 0,
                'tax_percent' => 0,
            ];
        }

        return $items;
    }

    /**
     * Получить ID компании по умолчанию
     */
    protected function getDefaultCompanyId(): int
    {
        if (auth()->check() && auth()->user()->company_id) {
            return auth()->user()->company_id;
        }

        return \App\Models\Company::query()->value('id') ?? 1;
    }

    /**
     * Получить статистику продаж
     *
     * @param int|null $companyId
     * @param array $filters
     * @return array
     */
    public function getSalesStatistics(?int $companyId = null, array $filters = []): array
    {
        $query = Sale::query();

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (!empty($filters['source'])) {
            $query->where('source', $filters['source']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $sales = $query->with('items')->get();

        return [
            'total_sales' => $sales->count(),
            'total_amount' => $sales->sum('total_amount'),
            'total_margin' => $sales->sum(fn($sale) => $sale->getMargin()),
            'by_status' => $sales->groupBy('status')->map->count(),
            'by_type' => $sales->groupBy('type')->map->count(),
            'by_source' => $sales->groupBy('source')->map->count(),
            'average_sale_amount' => $sales->count() > 0 ? $sales->avg('total_amount') : 0,
        ];
    }
}
