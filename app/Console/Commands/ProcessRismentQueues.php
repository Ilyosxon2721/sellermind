<?php

namespace App\Console\Commands;

use App\Models\IntegrationLink;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Warehouse\Sku;
use App\Models\Warehouse\StockLedger;
use App\Models\Warehouse\Warehouse;
use App\Models\WbOrder;
use App\Models\UzumOrder;
use App\Models\OzonOrder;
use App\Models\YandexMarketOrder;
use App\Models\MarketplaceAccount;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class ProcessRismentQueues extends Command
{
    protected $signature = 'integration:process-risment
                            {--timeout=60 : Seconds to listen before exiting}
                            {--sleep=1 : Seconds to sleep when queue is empty}';

    protected $description = 'Process incoming events from RISMENT via Redis queues (sellermind:products, sellermind:stock, sellermind:shipments)';

    protected const QUEUES = [
        'sellermind:products',
        'sellermind:stock',
        'sellermind:shipments',
    ];

    public function handle(): int
    {
        $timeout = (int) $this->option('timeout');
        $sleep = (int) $this->option('sleep');
        $startTime = time();

        $this->info("Listening on queues: " . implode(', ', self::QUEUES));
        $this->info("Timeout: {$timeout}s | Sleep: {$sleep}s");

        $redis = Redis::connection('integration');
        $processed = 0;

        while ((time() - $startTime) < $timeout) {
            $hadWork = false;

            foreach (self::QUEUES as $queue) {
                $raw = $redis->lpop($queue);

                if ($raw === null) {
                    continue;
                }

                $hadWork = true;
                $processed++;

                try {
                    $message = json_decode($raw, true);

                    if (!$message) {
                        Log::warning('ProcessRisment: Invalid JSON', [
                            'queue' => $queue,
                            'raw' => mb_substr($raw, 0, 500),
                        ]);
                        continue;
                    }

                    // Принимаем как 'event', так и 'action' от RISMENT
                    if (!isset($message['event']) && !isset($message['action'])) {
                        Log::warning('ProcessRisment: No event/action in message', [
                            'queue' => $queue,
                            'keys' => array_keys($message),
                            'raw' => mb_substr($raw, 0, 500),
                        ]);
                        continue;
                    }

                    $this->processMessage($queue, $message);

                } catch (\Exception $e) {
                    Log::error('ProcessRisment: Failed to process message', [
                        'queue' => $queue,
                        'error' => $e->getMessage(),
                        'raw' => mb_substr($raw, 0, 500),
                    ]);
                    $this->error("Error processing from {$queue}: {$e->getMessage()}");
                }
            }

            if (!$hadWork) {
                sleep($sleep);
            }
        }

        $this->info("Processed {$processed} messages. Exiting.");
        return self::SUCCESS;
    }

    protected function processMessage(string $queue, array $message): void
    {
        // Принимаем оба формата: event (внутренний) и action (RISMENT)
        $rawEvent = $message['event'] ?? $message['action'] ?? 'unknown';
        $linkToken = $message['link_token'] ?? null;
        $data = $message['data'] ?? [];
        $source = $message['source'] ?? 'risment';

        // Нормализация названий событий: "create" → "product.created" и т.д.
        $event = $this->normalizeEvent($rawEvent, $queue);

        Log::info('ProcessRisment: Received message', [
            'queue' => $queue,
            'raw_event' => $rawEvent,
            'normalized_event' => $event,
            'link_token' => $linkToken ? mb_substr($linkToken, 0, 8) . '...' : null,
            'data_keys' => array_keys($data),
            'risment_product_id' => $message['risment_product_id'] ?? null,
        ]);

        $this->line("[{$source}] {$queue} → {$rawEvent} (normalized: {$event})");

        // Resolve company by link_token
        $link = $linkToken
            ? IntegrationLink::where('link_token', $linkToken)->where('is_active', true)->first()
            : null;

        if (!$link) {
            Log::warning('ProcessRisment: Unknown or inactive link_token', [
                'event' => $event,
                'link_token' => $linkToken,
                'active_links_count' => IntegrationLink::where('link_token', $linkToken)->count(),
            ]);
            $this->warn("  Unknown link_token: {$linkToken}");
            return;
        }

        $companyId = $link->company_id;
        $this->line("  Resolved company_id: {$companyId}");

        // Передаём весь message для доступа к полям верхнего уровня (risment_product_id)
        match ($queue) {
            'sellermind:products' => $this->handleProductEvent($event, $data, $companyId, $message),
            'sellermind:stock' => $this->handleStockEvent($event, $data, $companyId),
            'sellermind:shipments' => $this->handleShipmentEvent($event, $data, $companyId),
            default => Log::warning("ProcessRisment: Unknown queue {$queue}"),
        };
    }

    /**
     * Нормализация событий: RISMENT шлёт "create"/"update"/"delete",
     * а внутренний формат — "product.created"/"stock.updated" и т.д.
     */
    protected function normalizeEvent(string $event, string $queue): string
    {
        // Если уже в формате "product.created" — вернуть как есть
        if (str_contains($event, '.')) {
            return $event;
        }

        // Маппинг коротких имён в полные
        $prefix = match ($queue) {
            'sellermind:products' => 'product',
            'sellermind:stock' => 'stock',
            'sellermind:shipments' => 'shipment',
            default => 'unknown',
        };

        $suffix = match ($event) {
            'create' => 'created',
            'update' => 'updated',
            'delete' => 'deleted',
            'set' => 'set',
            'adjust' => 'adjusted',
            'ship' => 'shipped',
            'deliver' => 'delivered',
            'cancel' => 'cancelled',
            default => $event,
        };

        return "{$prefix}.{$suffix}";
    }

    // ========== Product events ==========

    protected function handleProductEvent(string $event, array $data, int $companyId, array $message = []): void
    {
        match ($event) {
            'product.created' => $this->onProductCreated($data, $companyId, $message),
            'product.updated' => $this->onProductUpdated($data, $companyId, $message),
            'product.deleted' => $this->onProductDeleted($data, $companyId, $message),
            default => Log::info("ProcessRisment: Unhandled product event: {$event}"),
        };
    }

    /**
     * Извлекает risment_product_id из сообщения.
     * RISMENT шлёт его на верхнем уровне: $message['risment_product_id']
     * Внутренний формат: $data['product_id'] или $data['id']
     */
    protected function extractRismentProductId(array $data, array $message): ?int
    {
        $id = $message['risment_product_id']
            ?? $data['risment_product_id']
            ?? $data['product_id']
            ?? $data['id']
            ?? null;

        return $id !== null ? (int) $id : null;
    }

    protected function onProductCreated(array $data, int $companyId, array $message = []): void
    {
        $rismentId = $this->extractRismentProductId($data, $message);

        if (!$rismentId) {
            Log::warning('ProcessRisment: product.created — нет risment_product_id', [
                'data_keys' => array_keys($data),
                'message_keys' => array_keys($message),
            ]);
            $this->error('  No risment_product_id found in message');
            return;
        }

        // Маппинг полей: RISMENT → SellerMind
        // RISMENT шлёт: title, article, variants[]
        // SellerMind ожидает: name, article, brand_name
        $productName = $data['title'] ?? $data['name'] ?? "RISMENT #{$rismentId}";
        $article = $data['article'] ?? "RISMENT-{$rismentId}";

        Log::info('ProcessRisment: Создание/обновление товара', [
            'risment_id' => $rismentId,
            'company_id' => $companyId,
            'name' => $productName,
            'article' => $article,
            'has_variants' => isset($data['variants']),
            'variants_count' => isset($data['variants']) ? count($data['variants']) : 0,
        ]);

        DB::beginTransaction();
        try {
            $product = Product::updateOrCreate(
                [
                    'company_id' => $companyId,
                    'risment_product_id' => $rismentId,
                ],
                [
                    'name' => $productName,
                    'article' => $article,
                    'brand_name' => $data['brand'] ?? $data['brand_name'] ?? null,
                    'is_active' => true,
                    'is_archived' => false,
                ]
            );

            $wasRecentlyCreated = $product->wasRecentlyCreated;

            // Обработка вариантов
            $variants = $data['variants'] ?? [];
            if (empty($variants)) {
                // Нет вариантов — создаём один по умолчанию из плоских полей
                $variants = [[
                    'sku' => $data['sku'] ?? null,
                    'barcode' => $data['barcode'] ?? null,
                    'price' => $data['price'] ?? 0,
                ]];
            }

            foreach ($variants as $i => $variantData) {
                $sku = $variantData['sku']
                    ?? $variantData['article']
                    ?? "{$article}-V" . ($i + 1);

                $variant = ProductVariant::updateOrCreate(
                    [
                        'product_id' => $product->id,
                        'sku' => $sku,
                    ],
                    [
                        'company_id' => $companyId,
                        'barcode' => $variantData['barcode'] ?? null,
                        'price_default' => $variantData['price']
                            ?? $variantData['sell_price']
                            ?? $variantData['price_default']
                            ?? 0,
                        'purchase_price' => $variantData['purchase_price']
                            ?? $variantData['cost_price']
                            ?? null,
                        'option_values_summary' => $variantData['option_values_summary']
                            ?? $variantData['title']
                            ?? null,
                        'is_active' => true,
                    ]
                );

                Sku::updateOrCreate(
                    [
                        'company_id' => $companyId,
                        'sku_code' => $sku,
                    ],
                    [
                        'product_id' => $product->id,
                        'product_variant_id' => $variant->id,
                        'barcode_ean13' => $variantData['barcode'] ?? null,
                        'is_active' => true,
                    ]
                );

                $this->line("    Variant #{$variant->id}: sku={$sku}");
            }

            DB::commit();

            $action = $wasRecentlyCreated ? 'Created' : 'Updated';
            Log::info("ProcessRisment: Товар {$action}", [
                'product_id' => $product->id,
                'risment_id' => $rismentId,
                'variants_count' => count($variants),
                'action' => $action,
            ]);

            $this->info("  {$action} product #{$product->id} \"{$productName}\" (risment:{$rismentId}), variants: " . count($variants));

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ProcessRisment: product.created failed', [
                'risment_id' => $rismentId,
                'company_id' => $companyId,
                'error' => $e->getMessage(),
                'trace' => mb_substr($e->getTraceAsString(), 0, 500),
            ]);
            $this->error("  Failed to create product risment:{$rismentId}: {$e->getMessage()}");
        }
    }

    protected function onProductUpdated(array $data, int $companyId, array $message = []): void
    {
        $rismentId = $this->extractRismentProductId($data, $message);
        if (!$rismentId) {
            Log::warning('ProcessRisment: product.updated — нет risment_product_id');
            return;
        }

        $product = Product::where('company_id', $companyId)
            ->where('risment_product_id', $rismentId)
            ->first();

        if (!$product) {
            $this->warn("  Product risment:{$rismentId} not found, creating");
            $this->onProductCreated($data, $companyId, $message);
            return;
        }

        // Маппинг полей: title → name
        $fields = [];
        if (isset($data['title'])) $fields['name'] = $data['title'];
        if (isset($data['name'])) $fields['name'] = $data['name'];
        if (isset($data['article'])) $fields['article'] = $data['article'];
        if (isset($data['brand'])) $fields['brand_name'] = $data['brand'];
        if (isset($data['brand_name'])) $fields['brand_name'] = $data['brand_name'];

        if (!empty($fields)) {
            $product->update($fields);
        }

        // Обновление вариантов из массива или из плоских полей
        $variants = $data['variants'] ?? [];
        if (!empty($variants)) {
            foreach ($variants as $i => $variantData) {
                $existingVariant = $product->variants()->skip($i)->first();
                if ($existingVariant) {
                    $variantFields = [];
                    if (isset($variantData['sku'])) $variantFields['sku'] = $variantData['sku'];
                    if (isset($variantData['barcode'])) $variantFields['barcode'] = $variantData['barcode'];
                    if (isset($variantData['price'])) $variantFields['price_default'] = $variantData['price'];
                    if (isset($variantData['sell_price'])) $variantFields['price_default'] = $variantData['sell_price'];
                    if (isset($variantData['purchase_price'])) $variantFields['purchase_price'] = $variantData['purchase_price'];
                    if (isset($variantData['cost_price'])) $variantFields['purchase_price'] = $variantData['cost_price'];

                    if (!empty($variantFields)) {
                        $existingVariant->update($variantFields);
                    }
                }
            }
        } else {
            // Плоские поля — обновляем первый вариант
            $variant = $product->variants()->first();
            if ($variant) {
                $variantFields = [];
                if (isset($data['sku'])) $variantFields['sku'] = $data['sku'];
                if (isset($data['barcode'])) $variantFields['barcode'] = $data['barcode'];
                if (isset($data['price'])) $variantFields['price_default'] = $data['price'];

                if (!empty($variantFields)) {
                    $variant->update($variantFields);
                }
            }
        }

        $this->info("  Updated product #{$product->id} (risment:{$rismentId})");
    }

    protected function onProductDeleted(array $data, int $companyId, array $message = []): void
    {
        $rismentId = $this->extractRismentProductId($data, $message);
        if (!$rismentId) return;

        $product = Product::where('company_id', $companyId)
            ->where('risment_product_id', $rismentId)
            ->first();

        if ($product) {
            $product->update(['is_active' => false, 'is_archived' => true]);
            $this->info("  Archived product #{$product->id} (risment:{$rismentId})");
        } else {
            $this->warn("  Product risment:{$rismentId} not found for deletion");
        }
    }

    // ========== Stock events ==========

    protected function handleStockEvent(string $event, array $data, int $companyId): void
    {
        match ($event) {
            'stock.updated', 'stock.set' => $this->onStockUpdated($data, $companyId),
            'stock.adjusted' => $this->onStockAdjusted($data, $companyId),
            default => Log::info("ProcessRisment: Unhandled stock event: {$event}"),
        };
    }

    protected function onStockUpdated(array $data, int $companyId): void
    {
        $items = $data['items'] ?? [$data];

        foreach ($items as $item) {
            $this->setStock($item, $companyId);
        }
    }

    protected function onStockAdjusted(array $data, int $companyId): void
    {
        $items = $data['items'] ?? [$data];

        foreach ($items as $item) {
            $this->adjustStock($item, $companyId);
        }
    }

    protected function setStock(array $item, int $companyId): void
    {
        $variant = $this->resolveVariant($item, $companyId);
        if (!$variant) return;

        $quantity = (int) ($item['quantity'] ?? $item['stock'] ?? 0);
        $warehouse = $this->getDefaultWarehouse($companyId);
        if (!$warehouse) return;

        $sku = Sku::where('product_variant_id', $variant->id)->first();
        if (!$sku) return;

        $currentBalance = $sku->getCurrentBalance($warehouse->id);
        $delta = $quantity - $currentBalance;

        if ($delta != 0) {
            StockLedger::create([
                'company_id' => $companyId,
                'occurred_at' => now(),
                'warehouse_id' => $warehouse->id,
                'sku_id' => $sku->id,
                'qty_delta' => $delta,
                'source_type' => 'risment_stock_sync',
                'source_id' => $variant->id,
            ]);

            $variant->stock_default = max(0, $quantity);
            $variant->saveQuietly();
        }

        $this->info("  Stock set: variant #{$variant->id} → {$quantity} (delta: {$delta})");
    }

    protected function adjustStock(array $item, int $companyId): void
    {
        $variant = $this->resolveVariant($item, $companyId);
        if (!$variant) return;

        $delta = (int) ($item['delta'] ?? $item['quantity'] ?? 0);
        if ($delta == 0) return;

        $warehouse = $this->getDefaultWarehouse($companyId);
        if (!$warehouse) return;

        $sku = Sku::where('product_variant_id', $variant->id)->first();
        if (!$sku) return;

        StockLedger::create([
            'company_id' => $companyId,
            'occurred_at' => now(),
            'warehouse_id' => $warehouse->id,
            'sku_id' => $sku->id,
            'qty_delta' => $delta,
            'source_type' => 'risment_stock_adjust',
            'source_id' => $variant->id,
        ]);

        $variant->stock_default = max(0, $variant->stock_default + $delta);
        $variant->saveQuietly();

        $this->info("  Stock adjusted: variant #{$variant->id} delta={$delta}");
    }

    protected function resolveVariant(array $item, int $companyId): ?ProductVariant
    {
        // Try by risment_product_id
        if ($rid = ($item['risment_product_id'] ?? $item['product_id'] ?? null)) {
            $product = Product::where('company_id', $companyId)
                ->where('risment_product_id', $rid)
                ->first();
            if ($product) {
                return $product->variants()->first();
            }
        }

        // Try by SKU
        if ($sku = ($item['sku'] ?? null)) {
            return ProductVariant::where('company_id', $companyId)
                ->where('sku', $sku)
                ->first();
        }

        // Try by barcode
        if ($barcode = ($item['barcode'] ?? null)) {
            return ProductVariant::where('company_id', $companyId)
                ->where('barcode', $barcode)
                ->first();
        }

        // Try by variant_id
        if ($variantId = ($item['variant_id'] ?? null)) {
            return ProductVariant::where('id', $variantId)
                ->where('company_id', $companyId)
                ->first();
        }

        return null;
    }

    protected function getDefaultWarehouse(int $companyId): ?Warehouse
    {
        return Warehouse::where('company_id', $companyId)
            ->where('is_default', true)
            ->first()
            ?? Warehouse::where('company_id', $companyId)->first();
    }

    // ========== Shipment events ==========

    protected function handleShipmentEvent(string $event, array $data, int $companyId): void
    {
        match ($event) {
            'shipment.shipped' => $this->onShipmentShipped($data, $companyId),
            'shipment.delivered' => $this->onShipmentDelivered($data, $companyId),
            'shipment.cancelled' => $this->onShipmentCancelled($data, $companyId),
            default => Log::info("ProcessRisment: Unhandled shipment event: {$event}"),
        };
    }

    protected function onShipmentShipped(array $data, int $companyId): void
    {
        $order = $this->resolveOrder($data, $companyId);
        if (!$order) {
            $this->warn("  Order not found for shipment.shipped");
            return;
        }

        if (array_key_exists('status_normalized', $order->getAttributes())) {
            $order->status_normalized = 'shipped';
        } else {
            $order->status = 'shipped';
        }

        if (isset($data['tracking_number']) && method_exists($order, 'getAttribute') && array_key_exists('tracking_number', $order->getAttributes())) {
            $order->tracking_number = $data['tracking_number'];
        }

        $order->save();
        $this->info("  Marked order #{$order->id} as shipped");
    }

    protected function onShipmentDelivered(array $data, int $companyId): void
    {
        $order = $this->resolveOrder($data, $companyId);
        if (!$order) return;

        if (array_key_exists('status_normalized', $order->getAttributes())) {
            $order->status_normalized = 'delivered';
        } else {
            $order->status = 'delivered';
        }

        $order->delivered_at = $data['delivered_at'] ?? now();
        $order->save();
        $this->info("  Marked order #{$order->id} as delivered");
    }

    protected function onShipmentCancelled(array $data, int $companyId): void
    {
        $order = $this->resolveOrder($data, $companyId);
        if (!$order) return;

        if (array_key_exists('status_normalized', $order->getAttributes())) {
            $order->status_normalized = 'cancelled';
        } else {
            $order->status = 'cancelled';
        }

        $order->save();
        $this->info("  Marked order #{$order->id} as cancelled");
    }

    protected function resolveOrder(array $data, int $companyId)
    {
        $marketplace = $data['marketplace'] ?? null;
        $orderId = $data['order_id'] ?? $data['id'] ?? null;

        if (!$orderId) return null;

        $accountIds = MarketplaceAccount::where('company_id', $companyId)->pluck('id');

        return match ($marketplace) {
            'wb' => WbOrder::where('id', $orderId)->whereIn('marketplace_account_id', $accountIds)->first(),
            'uzum' => UzumOrder::where('id', $orderId)->whereIn('marketplace_account_id', $accountIds)->first(),
            'ozon' => OzonOrder::where('id', $orderId)->whereIn('marketplace_account_id', $accountIds)->first(),
            'ym' => YandexMarketOrder::where('id', $orderId)->whereIn('marketplace_account_id', $accountIds)->first(),
            default => null,
        };
    }
}
