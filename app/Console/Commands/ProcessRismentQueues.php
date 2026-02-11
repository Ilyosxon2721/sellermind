<?php

namespace App\Console\Commands;

use App\Models\IntegrationLink;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductImage;
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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
        'sellermind:marketplaces',
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
        $this->line("  Resolved company_id: {$companyId}" . ($link->warehouse_id ? ", warehouse_id: {$link->warehouse_id}" : ''));

        // Передаём весь message для доступа к полям верхнего уровня (risment_product_id)
        match ($queue) {
            'sellermind:products' => $this->handleProductEvent($event, $data, $companyId, $message, $link),
            'sellermind:stock' => $this->handleStockEvent($event, $data, $companyId, $link),
            'sellermind:shipments' => $this->handleShipmentEvent($event, $data, $companyId),
            'sellermind:marketplaces' => $this->handleMarketplaceEvent($event, $data, $companyId, $link),
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
            'sellermind:marketplaces' => 'marketplace',
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

    protected function handleProductEvent(string $event, array $data, int $companyId, array $message = [], ?IntegrationLink $link = null): void
    {
        match ($event) {
            'product.created', 'product.sync_product' => $this->onProductCreated($data, $companyId, $message, $link),
            'product.updated' => $this->onProductUpdated($data, $companyId, $message, $link),
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

    protected function onProductCreated(array $data, int $companyId, array $message = [], ?IntegrationLink $link = null): void
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
        $productName = $data['title'] ?? $data['name'] ?? "RISMENT #{$rismentId}";
        $article = $data['article'] ?? "RISMENT-{$rismentId}";

        Log::info('ProcessRisment: Создание/обновление товара', [
            'risment_id' => $rismentId,
            'company_id' => $companyId,
            'name' => $productName,
            'article' => $article,
            'has_variants' => isset($data['variants']),
            'variants_count' => isset($data['variants']) ? count($data['variants']) : 0,
            'has_images' => isset($data['images']),
            'has_category' => isset($data['category']),
        ]);

        DB::beginTransaction();
        try {
            // Найти или создать категорию
            $categoryId = $this->resolveCategory($data, $companyId);

            // ШАГ 1: Ищем по risment_product_id (точное совпадение)
            $product = Product::where('company_id', $companyId)
                ->where('risment_product_id', $rismentId)
                ->first();

            // ШАГ 2: Если не нашли — ищем по артикулу (товар мог быть создан вручную)
            if (!$product && !empty($article) && $article !== "RISMENT-{$rismentId}") {
                $product = Product::where('company_id', $companyId)
                    ->where('article', $article)
                    ->whereNull('risment_product_id')
                    ->first();
            }

            if ($product) {
                // Обновляем найденный товар, привязываем risment_product_id
                $product->update([
                    'risment_product_id' => $rismentId,
                    'name' => $productName,
                    'article' => $article,
                    'brand_name' => $data['brand'] ?? $data['brand_name'] ?? $product->brand_name,
                    'description_full' => $data['description'] ?? $product->description_full,
                    'description_short' => $data['short_description'] ?? $product->description_short,
                    'category_id' => $categoryId ?? $product->category_id,
                    'is_active' => $data['is_active'] ?? true,
                    'is_archived' => false,
                ]);
                $wasRecentlyCreated = false;
            } else {
                // Создаём новый товар
                $product = Product::create([
                    'company_id' => $companyId,
                    'risment_product_id' => $rismentId,
                    'name' => $productName,
                    'article' => $article,
                    'brand_name' => $data['brand'] ?? $data['brand_name'] ?? null,
                    'description_full' => $data['description'] ?? null,
                    'description_short' => $data['short_description'] ?? null,
                    'category_id' => $categoryId,
                    'is_active' => $data['is_active'] ?? true,
                    'is_archived' => false,
                ]);
                $wasRecentlyCreated = true;
            }

            // Обработка вариантов
            $variants = $data['variants'] ?? [];
            if (empty($variants)) {
                // Нет вариантов — создаём один по умолчанию из плоских полей
                $variants = [[
                    'sku' => $data['sku'] ?? null,
                    'barcode' => $data['barcode'] ?? null,
                    'price' => $data['price'] ?? 0,
                    'cost_price' => $data['cost_price'] ?? $data['purchase_price'] ?? null,
                    'weight' => $data['weight'] ?? null,
                    'length' => $data['length'] ?? null,
                    'width' => $data['width'] ?? null,
                    'height' => $data['height'] ?? null,
                ]];
            }

            foreach ($variants as $i => $variantData) {
                $sku = $variantData['sku']
                    ?? $variantData['sku_code']
                    ?? $variantData['article']
                    ?? "{$article}-V" . ($i + 1);

                $variant = ProductVariant::updateOrCreate(
                    [
                        'product_id' => $product->id,
                        'sku' => $sku,
                    ],
                    [
                        'company_id' => $companyId,
                        'name' => $variantData['name'] ?? $variantData['variant_name'] ?? $variantData['title'] ?? null,
                        'barcode' => $variantData['barcode'] ?? null,
                        'price_default' => $variantData['price']
                            ?? $variantData['sell_price']
                            ?? $variantData['price_default']
                            ?? 0,
                        'purchase_price' => $variantData['purchase_price']
                            ?? $variantData['cost_price']
                            ?? null,
                        'weight_g' => $variantData['weight'] ?? $variantData['weight_g'] ?? null,
                        'length_mm' => $variantData['length'] ?? $variantData['length_mm'] ?? $variantData['dims_l'] ?? null,
                        'width_mm' => $variantData['width'] ?? $variantData['width_mm'] ?? $variantData['dims_w'] ?? null,
                        'height_mm' => $variantData['height'] ?? $variantData['height_mm'] ?? $variantData['dims_h'] ?? null,
                        'risment_variant_id' => $variantData['risment_variant_id']
                            ?? $variantData['variant_id']
                            ?? null,
                        'option_values_summary' => $variantData['option_values_summary']
                            ?? $variantData['title']
                            ?? null,
                        'is_active' => $variantData['is_active'] ?? true,
                    ]
                );

                $skuData = [
                    'product_id' => $product->id,
                    'product_variant_id' => $variant->id,
                    'barcode_ean13' => $variantData['barcode'] ?? null,
                    'is_active' => true,
                ];

                // Привязать SKU к складу из настроек интеграции
                if ($link?->warehouse_id) {
                    $skuData['warehouse_id'] = $link->warehouse_id;
                }

                Sku::updateOrCreate(
                    [
                        'company_id' => $companyId,
                        'sku_code' => $sku,
                    ],
                    $skuData
                );

                $this->line("    Variant #{$variant->id}: sku={$sku}");
            }

            // Обработка изображений
            if (!empty($data['images'])) {
                $this->syncProductImages($product, $data['images'], $companyId);
            }

            DB::commit();

            $action = $wasRecentlyCreated ? 'Created' : 'Updated';
            Log::info("ProcessRisment: Товар {$action}", [
                'product_id' => $product->id,
                'risment_id' => $rismentId,
                'variants_count' => count($variants),
                'images_count' => count($data['images'] ?? []),
                'category_id' => $categoryId,
                'action' => $action,
            ]);

            $this->info("  {$action} product #{$product->id} \"{$productName}\" (risment:{$rismentId}), variants: " . count($variants));

            // Отправить подтверждение успеха в RISMENT
            $this->sendProductConfirmation($message, $product, 'success');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ProcessRisment: product.created failed', [
                'risment_id' => $rismentId,
                'company_id' => $companyId,
                'error' => $e->getMessage(),
                'trace' => mb_substr($e->getTraceAsString(), 0, 500),
            ]);
            $this->error("  Failed to create product risment:{$rismentId}: {$e->getMessage()}");

            // Отправить ошибку в RISMENT с понятным описанием
            $humanError = $this->translateError($e->getMessage());
            $this->sendProductConfirmation($message, null, 'error', $humanError);
        }
    }

    protected function onProductUpdated(array $data, int $companyId, array $message = [], ?IntegrationLink $link = null): void
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
            $this->onProductCreated($data, $companyId, $message, $link);
            return;
        }

        try {
        // Полный маппинг полей товара
        $fields = [];
        if (isset($data['title'])) $fields['name'] = $data['title'];
        if (isset($data['name'])) $fields['name'] = $data['name'];
        if (isset($data['article'])) $fields['article'] = $data['article'];
        if (isset($data['brand'])) $fields['brand_name'] = $data['brand'];
        if (isset($data['brand_name'])) $fields['brand_name'] = $data['brand_name'];
        if (isset($data['description'])) $fields['description_full'] = $data['description'];
        if (isset($data['short_description'])) $fields['description_short'] = $data['short_description'];
        if (array_key_exists('is_active', $data)) $fields['is_active'] = (bool) $data['is_active'];

        // Обновить категорию
        if (isset($data['category'])) {
            $fields['category_id'] = $this->resolveCategory($data, $companyId);
        }

        if (!empty($fields)) {
            $product->update($fields);
        }

        // Обновление вариантов из массива или из плоских полей
        $variants = $data['variants'] ?? [];
        if (!empty($variants)) {
            foreach ($variants as $i => $variantData) {
                // Поиск варианта: по risment_variant_id, по sku, или по индексу
                $existingVariant = null;

                if (!empty($variantData['risment_variant_id'])) {
                    $existingVariant = $product->variants()
                        ->where('risment_variant_id', $variantData['risment_variant_id'])
                        ->first();
                }

                $variantSku = $variantData['sku'] ?? $variantData['sku_code'] ?? null;
                if (!$existingVariant && !empty($variantSku)) {
                    $existingVariant = $product->variants()
                        ->where('sku', $variantSku)
                        ->first();
                }

                if (!$existingVariant) {
                    $existingVariant = $product->variants()->skip($i)->first();
                }

                if ($existingVariant) {
                    $variantFields = [];
                    if (isset($variantData['name'])) $variantFields['name'] = $variantData['name'];
                    if (isset($variantData['variant_name'])) $variantFields['name'] = $variantData['variant_name'];
                    if (isset($variantData['title'])) $variantFields['name'] = $variantData['title'];
                    if (isset($variantData['sku'])) $variantFields['sku'] = $variantData['sku'];
                    if (isset($variantData['sku_code'])) $variantFields['sku'] = $variantData['sku_code'];
                    if (isset($variantData['barcode'])) $variantFields['barcode'] = $variantData['barcode'];
                    if (isset($variantData['price'])) $variantFields['price_default'] = $variantData['price'];
                    if (isset($variantData['sell_price'])) $variantFields['price_default'] = $variantData['sell_price'];
                    if (isset($variantData['purchase_price'])) $variantFields['purchase_price'] = $variantData['purchase_price'];
                    if (isset($variantData['cost_price'])) $variantFields['purchase_price'] = $variantData['cost_price'];
                    if (isset($variantData['weight'])) $variantFields['weight_g'] = $variantData['weight'];
                    if (isset($variantData['weight_g'])) $variantFields['weight_g'] = $variantData['weight_g'];
                    if (isset($variantData['length'])) $variantFields['length_mm'] = $variantData['length'];
                    if (isset($variantData['length_mm'])) $variantFields['length_mm'] = $variantData['length_mm'];
                    if (isset($variantData['width'])) $variantFields['width_mm'] = $variantData['width'];
                    if (isset($variantData['width_mm'])) $variantFields['width_mm'] = $variantData['width_mm'];
                    if (isset($variantData['height'])) $variantFields['height_mm'] = $variantData['height'];
                    if (isset($variantData['height_mm'])) $variantFields['height_mm'] = $variantData['height_mm'];
                    if (isset($variantData['dims_l'])) $variantFields['length_mm'] = $variantData['dims_l'];
                    if (isset($variantData['dims_w'])) $variantFields['width_mm'] = $variantData['dims_w'];
                    if (isset($variantData['dims_h'])) $variantFields['height_mm'] = $variantData['dims_h'];
                    if (isset($variantData['risment_variant_id'])) $variantFields['risment_variant_id'] = $variantData['risment_variant_id'];
                    if (isset($variantData['variant_id'])) $variantFields['risment_variant_id'] = $variantData['variant_id'];
                    if (array_key_exists('is_active', $variantData)) $variantFields['is_active'] = (bool) $variantData['is_active'];

                    if (!empty($variantFields)) {
                        $existingVariant->update($variantFields);
                        $this->line("    Updated variant #{$existingVariant->id}: sku={$existingVariant->sku}");
                    }
                } else {
                    // Вариант не найден — создаём новый через onProductCreated логику
                    $sku = $variantData['sku'] ?? $variantData['sku_code'] ?? $variantData['article'] ?? "{$product->article}-V" . ($i + 1);
                    $variant = ProductVariant::create([
                        'company_id' => $companyId,
                        'product_id' => $product->id,
                        'name' => $variantData['name'] ?? $variantData['variant_name'] ?? $variantData['title'] ?? null,
                        'sku' => $sku,
                        'barcode' => $variantData['barcode'] ?? null,
                        'price_default' => $variantData['price'] ?? $variantData['sell_price'] ?? 0,
                        'purchase_price' => $variantData['purchase_price'] ?? $variantData['cost_price'] ?? null,
                        'weight_g' => $variantData['weight'] ?? $variantData['weight_g'] ?? null,
                        'length_mm' => $variantData['length'] ?? $variantData['length_mm'] ?? $variantData['dims_l'] ?? null,
                        'width_mm' => $variantData['width'] ?? $variantData['width_mm'] ?? $variantData['dims_w'] ?? null,
                        'height_mm' => $variantData['height'] ?? $variantData['height_mm'] ?? $variantData['dims_h'] ?? null,
                        'risment_variant_id' => $variantData['risment_variant_id'] ?? $variantData['variant_id'] ?? null,
                        'is_active' => $variantData['is_active'] ?? true,
                    ]);

                    $skuData = [
                        'product_id' => $product->id,
                        'product_variant_id' => $variant->id,
                        'barcode_ean13' => $variantData['barcode'] ?? null,
                        'is_active' => true,
                    ];
                    if ($link?->warehouse_id) {
                        $skuData['warehouse_id'] = $link->warehouse_id;
                    }
                    Sku::updateOrCreate(
                        ['company_id' => $companyId, 'sku_code' => $sku],
                        $skuData
                    );

                    $this->line("    Created new variant #{$variant->id}: sku={$sku}");
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
                if (isset($data['cost_price'])) $variantFields['purchase_price'] = $data['cost_price'];
                if (isset($data['weight'])) $variantFields['weight_g'] = $data['weight'];
                if (isset($data['length'])) $variantFields['length_mm'] = $data['length'];
                if (isset($data['width'])) $variantFields['width_mm'] = $data['width'];
                if (isset($data['height'])) $variantFields['height_mm'] = $data['height'];

                if (!empty($variantFields)) {
                    $variant->update($variantFields);
                }
            }
        }

        // Обновление изображений
        if (!empty($data['images'])) {
            $this->syncProductImages($product, $data['images'], $companyId);
        }

        Log::info("ProcessRisment: Товар обновлён", [
            'product_id' => $product->id,
            'risment_id' => $rismentId,
            'updated_fields' => array_keys($fields),
            'variants_count' => count($variants),
        ]);

        $this->info("  Updated product #{$product->id} (risment:{$rismentId})");

        // Отправить подтверждение успеха в RISMENT
        $this->sendProductConfirmation($message, $product, 'success');

        } catch (\Exception $e) {
            Log::error('ProcessRisment: product.updated failed', [
                'risment_id' => $rismentId,
                'company_id' => $companyId,
                'error' => $e->getMessage(),
            ]);
            $this->error("  Failed to update product risment:{$rismentId}: {$e->getMessage()}");

            // Отправить ошибку в RISMENT
            $humanError = $this->translateError($e->getMessage());
            $this->sendProductConfirmation($message, null, 'error', $humanError);
        }
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

    // ========== Helpers: category & images ==========

    /**
     * Найти или создать категорию по имени из данных RISMENT
     */
    protected function resolveCategory(array $data, int $companyId): ?int
    {
        $categoryName = $data['category'] ?? $data['category_name'] ?? null;

        if (empty($categoryName)) {
            return null;
        }

        $category = ProductCategory::firstOrCreate(
            [
                'company_id' => $companyId,
                'name' => $categoryName,
            ],
            [
                'slug' => Str::slug($categoryName),
                'is_active' => true,
            ]
        );

        return $category->id;
    }

    /**
     * Скачать и сохранить изображения товара из RISMENT
     */
    protected function syncProductImages(Product $product, array $imageUrls, int $companyId): void
    {
        foreach ($imageUrls as $index => $url) {
            if (empty($url) || !is_string($url)) {
                continue;
            }

            try {
                $contents = @file_get_contents($url);

                if ($contents === false) {
                    Log::warning('ProcessRisment: Не удалось скачать изображение', [
                        'product_id' => $product->id,
                        'url' => $url,
                    ]);
                    continue;
                }

                $extension = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';
                $filename = "products/{$product->id}/" . Str::uuid() . ".{$extension}";

                Storage::disk('public')->put($filename, $contents);

                ProductImage::updateOrCreate(
                    [
                        'company_id' => $companyId,
                        'product_id' => $product->id,
                        'file_path' => $filename,
                    ],
                    [
                        'sort_order' => $index,
                        'is_main' => $index === 0,
                    ]
                );

                $this->line("    Image #{$index}: {$filename}");

            } catch (\Exception $e) {
                Log::warning('ProcessRisment: Ошибка при загрузке изображения', [
                    'product_id' => $product->id,
                    'url' => $url,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    // ========== Stock events ==========

    protected function handleStockEvent(string $event, array $data, int $companyId, ?IntegrationLink $link = null): void
    {
        match ($event) {
            'stock.updated', 'stock.set' => $this->onStockUpdated($data, $companyId, $link),
            'stock.adjusted' => $this->onStockAdjusted($data, $companyId, $link),
            default => Log::info("ProcessRisment: Unhandled stock event: {$event}"),
        };
    }

    protected function onStockUpdated(array $data, int $companyId, ?IntegrationLink $link = null): void
    {
        // Поддержка обоих форматов: stocks[] (RISMENT) и items[] (внутренний)
        $items = $data['stocks'] ?? $data['items'] ?? [$data];

        $reason = $data['reason'] ?? null;
        if ($reason) {
            $this->line("  Stock update reason: {$reason}");
        }

        foreach ($items as $item) {
            $this->setStock($item, $companyId, $link);
        }
    }

    protected function onStockAdjusted(array $data, int $companyId, ?IntegrationLink $link = null): void
    {
        $items = $data['stocks'] ?? $data['items'] ?? [$data];

        foreach ($items as $item) {
            $this->adjustStock($item, $companyId, $link);
        }
    }

    protected function setStock(array $item, int $companyId, ?IntegrationLink $link = null): void
    {
        $variant = $this->resolveVariant($item, $companyId);
        if (!$variant) {
            Log::warning('ProcessRisment: Вариант не найден для обновления остатков', [
                'company_id' => $companyId,
                'item_keys' => array_keys($item),
                'sku' => $item['sku'] ?? null,
                'risment_variant_id' => $item['risment_variant_id'] ?? null,
            ]);
            $this->warn("  Variant not found for stock update (sku: " . ($item['sku'] ?? 'n/a') . ")");
            return;
        }

        // RISMENT шлёт quantity (общее), available (доступно), reserved (зарезервировано)
        $quantity = (int) ($item['quantity'] ?? $item['available'] ?? $item['stock'] ?? 0);
        $warehouse = $this->resolveWarehouse($companyId, $link);
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

        Log::info('ProcessRisment: Остаток обновлён', [
            'variant_id' => $variant->id,
            'sku' => $variant->sku,
            'quantity' => $quantity,
            'reserved' => $item['reserved'] ?? null,
            'available' => $item['available'] ?? null,
            'delta' => $delta,
        ]);

        $this->info("  Stock set: variant #{$variant->id} (sku: {$variant->sku}) → {$quantity} (delta: {$delta})");
    }

    protected function adjustStock(array $item, int $companyId, ?IntegrationLink $link = null): void
    {
        $variant = $this->resolveVariant($item, $companyId);
        if (!$variant) return;

        $delta = (int) ($item['delta'] ?? $item['quantity'] ?? 0);
        if ($delta == 0) return;

        $warehouse = $this->resolveWarehouse($companyId, $link);
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
        // 1. Try by risment_variant_id (самый точный)
        if ($rvid = ($item['risment_variant_id'] ?? null)) {
            $variant = ProductVariant::where('company_id', $companyId)
                ->where('risment_variant_id', $rvid)
                ->first();
            if ($variant) return $variant;
        }

        // 2. Try by risment_product_id + optional risment_variant_id
        if ($rid = ($item['risment_product_id'] ?? $item['product_id'] ?? null)) {
            $product = Product::where('company_id', $companyId)
                ->where('risment_product_id', $rid)
                ->first();
            if ($product) {
                // Если есть SKU — найти конкретный вариант
                if (!empty($item['sku'])) {
                    $variant = $product->variants()->where('sku', $item['sku'])->first();
                    if ($variant) return $variant;
                }
                // Иначе — первый вариант
                return $product->variants()->first();
            }
        }

        // 3. Try by SKU
        if ($sku = ($item['sku'] ?? null)) {
            return ProductVariant::where('company_id', $companyId)
                ->where('sku', $sku)
                ->first();
        }

        // 4. Try by barcode
        if ($barcode = ($item['barcode'] ?? null)) {
            return ProductVariant::where('company_id', $companyId)
                ->where('barcode', $barcode)
                ->first();
        }

        // 5. Try by variant_id
        if ($variantId = ($item['variant_id'] ?? null)) {
            return ProductVariant::where('id', $variantId)
                ->where('company_id', $companyId)
                ->first();
        }

        return null;
    }

    /**
     * Определить склад: сначала из настроек интеграции, затем дефолтный
     */
    protected function resolveWarehouse(int $companyId, ?IntegrationLink $link = null): ?Warehouse
    {
        if ($link?->warehouse_id) {
            $warehouse = Warehouse::find($link->warehouse_id);
            if ($warehouse) {
                return $warehouse;
            }
        }

        return $this->getDefaultWarehouse($companyId);
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

    // ========== Marketplace events ==========

    protected function handleMarketplaceEvent(string $event, array $data, int $companyId, ?IntegrationLink $link = null): void
    {
        match ($event) {
            'marketplace.created', 'marketplace.updated' => $this->onMarketplaceCreatedOrUpdated($data, $companyId, $link),
            'marketplace.deleted' => $this->onMarketplaceDeleted($data, $companyId),
            default => Log::info("ProcessRisment: Unhandled marketplace event: {$event}"),
        };
    }

    /**
     * Создать или обновить аккаунт маркетплейса из RISMENT
     */
    protected function onMarketplaceCreatedOrUpdated(array $data, int $companyId, ?IntegrationLink $link = null): void
    {
        $marketplace = $data['marketplace'] ?? null;
        $rismentCredentialId = $data['risment_credential_id'] ?? null;

        if (!$marketplace || !$rismentCredentialId) {
            Log::warning('ProcessRisment: marketplace event без marketplace или risment_credential_id', [
                'data_keys' => array_keys($data),
            ]);
            return;
        }

        // Нормализация имени маркетплейса
        $marketplaceCode = match (strtolower($marketplace)) {
            'wildberries', 'wb' => 'wb',
            'ozon' => 'ozon',
            'uzum', 'uzum_market' => 'uzum',
            'yandex_market', 'ym', 'yandex' => 'ym',
            default => null,
        };

        if (!$marketplaceCode) {
            Log::warning('ProcessRisment: Неизвестный маркетплейс', [
                'marketplace' => $marketplace,
            ]);
            $this->warn("  Unknown marketplace: {$marketplace}");
            return;
        }

        $credentials = $data['credentials'] ?? [];
        $accountName = $data['name'] ?? "{$marketplace} (RISMENT)";

        DB::beginTransaction();
        try {
            $account = MarketplaceAccount::updateOrCreate(
                [
                    'company_id' => $companyId,
                    'risment_credential_id' => $rismentCredentialId,
                ],
                array_filter([
                    'marketplace' => $marketplaceCode,
                    'name' => $accountName,
                    'source' => 'risment',
                    'is_active' => $data['is_active'] ?? true,
                    'connected_at' => now(),
                    // Общие поля
                    'api_key' => !empty($credentials['api_token']) ? encrypt($credentials['api_token']) : null,
                    'client_id' => $credentials['client_id'] ?? $credentials['supplier_id'] ?? null,
                    'client_secret' => !empty($credentials['client_secret']) ? encrypt($credentials['client_secret']) : null,
                    'shop_id' => $credentials['shop_id'] ?? $credentials['supplier_id'] ?? null,
                    // WB-специфичные токены
                    'wb_content_token' => !empty($credentials['content_token']) ? encrypt($credentials['content_token']) : null,
                    'wb_marketplace_token' => !empty($credentials['marketplace_token']) ? encrypt($credentials['marketplace_token']) : null,
                    'wb_prices_token' => !empty($credentials['prices_token']) ? encrypt($credentials['prices_token']) : null,
                    'wb_statistics_token' => !empty($credentials['statistics_token']) ? encrypt($credentials['statistics_token']) : null,
                    // Uzum-специфичные
                    'uzum_client_id' => $credentials['uzum_client_id'] ?? null,
                    'uzum_client_secret' => !empty($credentials['uzum_client_secret']) ? encrypt($credentials['uzum_client_secret']) : null,
                    'uzum_api_key' => !empty($credentials['uzum_api_key']) ? encrypt($credentials['uzum_api_key']) : null,
                ], fn($v) => $v !== null)
            );

            DB::commit();

            // Отправить подтверждение в RISMENT
            if ($link) {
                $confirmPayload = json_encode([
                    'event' => 'marketplace.confirmed',
                    'timestamp' => now()->toIso8601String(),
                    'source' => 'sellermind',
                    'link_token' => $link->link_token,
                    'data' => [
                        'risment_credential_id' => $rismentCredentialId,
                        'sellermind_account_id' => $account->id,
                        'marketplace' => $marketplaceCode,
                    ],
                ], JSON_UNESCAPED_UNICODE);

                Redis::connection('integration')->rpush('risment:marketplace_confirm', $confirmPayload);
            }

            $action = $account->wasRecentlyCreated ? 'Created' : 'Updated';
            Log::info("ProcessRisment: Marketplace account {$action}", [
                'account_id' => $account->id,
                'marketplace' => $marketplaceCode,
                'risment_credential_id' => $rismentCredentialId,
                'company_id' => $companyId,
            ]);

            $this->info("  {$action} marketplace account #{$account->id} ({$marketplaceCode}) from RISMENT");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ProcessRisment: marketplace create/update failed', [
                'marketplace' => $marketplace,
                'risment_credential_id' => $rismentCredentialId,
                'error' => $e->getMessage(),
            ]);
            $this->error("  Failed to create marketplace account: {$e->getMessage()}");
        }
    }

    /**
     * Деактивировать аккаунт маркетплейса из RISMENT
     */
    protected function onMarketplaceDeleted(array $data, int $companyId): void
    {
        $rismentCredentialId = $data['risment_credential_id'] ?? null;
        if (!$rismentCredentialId) return;

        $account = MarketplaceAccount::where('company_id', $companyId)
            ->where('risment_credential_id', $rismentCredentialId)
            ->first();

        if ($account) {
            $account->update(['is_active' => false]);
            Log::info('ProcessRisment: Marketplace account deactivated', [
                'account_id' => $account->id,
                'risment_credential_id' => $rismentCredentialId,
            ]);
            $this->info("  Deactivated marketplace account #{$account->id}");
        } else {
            $this->warn("  Marketplace account with risment_credential_id={$rismentCredentialId} not found");
        }
    }

    // ========== Подтверждения и ошибки (RISMENT sync) ==========

    /**
     * Перевод технических ошибок на понятный русский язык
     */
    protected function translateError(string $error): string
    {
        // Дубликат артикула
        if (str_contains($error, 'Duplicate entry') && str_contains($error, 'article')) {
            return 'Товар с таким артикулом уже существует в SellerMind. '
                 . 'Измените артикул в RISMENT или удалите дубликат в SellerMind.';
        }

        // Дубликат SKU
        if (str_contains($error, 'Duplicate entry') && str_contains($error, 'sku')) {
            return 'Вариант с таким SKU уже используется другим товаром в SellerMind. '
                 . 'Проверьте уникальность SKU-кодов.';
        }

        // Дубликат штрих-кода
        if (str_contains($error, 'Duplicate entry') && str_contains($error, 'barcode')) {
            return 'Штрих-код уже используется другим товаром в SellerMind.';
        }

        // Общий дубликат
        if (str_contains($error, 'Duplicate entry')) {
            return 'Запись с такими данными уже существует в SellerMind. Проверьте уникальность полей.';
        }

        // Ошибка категории
        if (str_contains($error, 'category')) {
            return 'Ошибка привязки категории товара. Проверьте настройки категорий.';
        }

        // Превышена длина поля
        if (str_contains($error, 'Data too long') || str_contains($error, 'too long')) {
            return 'Одно из полей товара слишком длинное. Сократите название или описание.';
        }

        // Обязательное поле не заполнено
        if (str_contains($error, 'cannot be null')) {
            return 'Не заполнено обязательное поле товара. Проверьте все поля и попробуйте снова.';
        }

        // Ошибка подключения к Redis
        if (str_contains($error, 'Connection refused') || str_contains($error, 'Redis')) {
            return 'Временная ошибка связи между системами. Попробуйте позже.';
        }

        // Общая ошибка базы данных
        if (str_contains($error, 'SQLSTATE')) {
            return 'Ошибка сохранения данных в SellerMind. Обратитесь в поддержку.';
        }

        // Таймаут
        if (str_contains($error, 'timeout') || str_contains($error, 'Timeout')) {
            return 'Превышено время ожидания. Попробуйте повторить синхронизацию позже.';
        }

        return 'Ошибка синхронизации: ' . mb_substr($error, 0, 200);
    }

    /**
     * Отправить подтверждение обработки товара в RISMENT через Redis
     */
    protected function sendProductConfirmation(array $originalMessage, ?Product $product, string $status, ?string $error = null): void
    {
        $data = $originalMessage['data'] ?? [];
        $rismentProductId = $data['risment_product_id']
            ?? $originalMessage['risment_product_id']
            ?? $data['product_id']
            ?? $data['id']
            ?? null;
        $linkToken = $originalMessage['link_token'] ?? null;

        if (!$rismentProductId || !$linkToken) {
            Log::warning('ProcessRisment: Cannot send product confirmation — missing risment_product_id or link_token', [
                'has_risment_product_id' => (bool) $rismentProductId,
                'has_link_token' => (bool) $linkToken,
            ]);
            return;
        }

        $confirmation = [
            'event' => $status === 'success' ? 'product.synced' : 'product.sync_error',
            'link_token' => $linkToken,
            'data' => [
                'risment_product_id' => (int) $rismentProductId,
                'status' => $status,
            ],
        ];

        if ($status === 'success' && $product) {
            $confirmation['data']['sellermind_product_id'] = $product->id;
        }

        if ($status === 'error' && $error) {
            $confirmation['data']['error'] = $error;
        }

        try {
            Redis::connection('integration')->rpush(
                'risment:product_confirm',
                json_encode($confirmation, JSON_UNESCAPED_UNICODE)
            );

            Log::info('ProcessRisment: Product confirmation sent', [
                'event' => $confirmation['event'],
                'risment_product_id' => $rismentProductId,
                'status' => $status,
            ]);
        } catch (\Exception $e) {
            Log::error('ProcessRisment: Failed to send product confirmation to RISMENT', [
                'risment_product_id' => $rismentProductId,
                'status' => $status,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
