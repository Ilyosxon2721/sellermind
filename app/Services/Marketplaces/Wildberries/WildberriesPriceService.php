<?php

// file: app/Services/Marketplaces/Wildberries/WildberriesPriceService.php

namespace App\Services\Marketplaces\Wildberries;

use App\Models\MarketplaceAccount;
use App\Models\WildberriesProduct;
use Illuminate\Support\Facades\Log;

/**
 * Service for managing Wildberries prices and discounts.
 *
 * WB Prices & Discounts API:
 * - GET /api/v2/list/goods/filter - get current prices
 * - POST /api/v2/upload/task - create price upload task
 * - GET /api/v2/history/tasks - get task status
 */
class WildberriesPriceService
{
    protected WildberriesHttpClient $httpClient;

    public function __construct(WildberriesHttpClient $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * Get current prices from WB
     *
     * @param  int  $limit  Max items per request
     * @param  int  $offset  Offset for pagination
     */
    public function getCurrentPrices(MarketplaceAccount $account, int $limit = 1000, int $offset = 0): array
    {
        try {
            return $this->httpClient->get('prices', '/api/v2/list/goods/filter', [
                'limit' => $limit,
                'offset' => $offset,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get WB prices', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Sync prices from WB to local database
     */
    public function syncPrices(MarketplaceAccount $account): array
    {
        $synced = 0;
        $errors = [];
        $offset = 0;
        $limit = 1000;

        Log::info('Starting WB prices sync', ['account_id' => $account->id]);

        do {
            try {
                $response = $this->getCurrentPrices($account, $limit, $offset);

                $goods = $response['data']['listGoods'] ?? [];

                foreach ($goods as $good) {
                    try {
                        $this->updateProductPrice($account, $good);
                        $synced++;
                    } catch (\Exception $e) {
                        $errors[] = [
                            'nm_id' => $good['nmID'] ?? 'unknown',
                            'error' => $e->getMessage(),
                        ];
                    }
                }

                $offset += $limit;

                // Stop if we got less than limit (last page)
                if (count($goods) < $limit) {
                    break;
                }

            } catch (\Exception $e) {
                Log::error('WB prices sync batch failed', [
                    'account_id' => $account->id,
                    'offset' => $offset,
                    'error' => $e->getMessage(),
                ]);

                $errors[] = ['batch_error' => $e->getMessage()];
                break;
            }
        } while (true);

        Log::info('WB prices sync completed', [
            'account_id' => $account->id,
            'synced' => $synced,
            'errors' => count($errors),
        ]);

        return [
            'synced' => $synced,
            'errors' => $errors,
        ];
    }

    /**
     * Update product price from WB data
     */
    protected function updateProductPrice(MarketplaceAccount $account, array $priceData): void
    {
        $nmId = $priceData['nmID'] ?? null;

        if (! $nmId) {
            return;
        }

        $product = WildberriesProduct::where('marketplace_account_id', $account->id)
            ->where('nm_id', $nmId)
            ->first();

        if (! $product) {
            return;
        }

        // Update price fields
        $sizes = $priceData['sizes'] ?? [];
        if (! empty($sizes)) {
            $firstSize = $sizes[0];
            $product->price = $firstSize['price'] ?? null;
            $product->discount_percent = $firstSize['discountedPercent'] ?? null;
            $product->price_with_discount = isset($firstSize['price'], $firstSize['discountedPercent'])
                ? $firstSize['price'] * (1 - $firstSize['discountedPercent'] / 100)
                : null;
        }

        $product->save();
    }

    /**
     * Push price updates to WB
     *
     * @param  array  $priceUpdates  Array of ['nmId' => int, 'price' => int, 'discount' => int]
     * @return array Task creation result
     */
    public function pushPrices(MarketplaceAccount $account, array $priceUpdates = []): array
    {
        if (empty($priceUpdates)) {
            // If no updates provided, gather from local database
            $priceUpdates = $this->gatherPriceUpdates($account);
        }

        if (empty($priceUpdates)) {
            return [
                'success' => true,
                'message' => 'No price updates needed',
            ];
        }

        Log::info('Pushing prices to WB', [
            'account_id' => $account->id,
            'updates_count' => count($priceUpdates),
        ]);

        try {
            // POST /api/v2/upload/task
            $response = $this->httpClient->post('prices', '/api/v2/upload/task', [
                'data' => $priceUpdates,
            ]);

            $taskId = $response['data']['id'] ?? null;

            Log::info('WB price update task created', [
                'account_id' => $account->id,
                'task_id' => $taskId,
            ]);

            return [
                'success' => true,
                'task_id' => $taskId,
                'response' => $response,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to push prices to WB', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Check status of price update task
     *
     * @param  int  $taskId  Task ID from pushPrices()
     * @return array Task status
     */
    public function checkPriceTask(MarketplaceAccount $account, int $taskId): array
    {
        try {
            return $this->httpClient->get('prices', '/api/v2/history/tasks', [
                'uploadID' => $taskId,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to check WB price task', [
                'account_id' => $account->id,
                'task_id' => $taskId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get history of price update tasks
     */
    public function getPriceTasksHistory(MarketplaceAccount $account, int $limit = 100): array
    {
        try {
            return $this->httpClient->get('prices', '/api/v2/history/tasks', [
                'limit' => $limit,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get WB price tasks history', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Gather price updates from local products that need updating
     * Сравнивает локальные цены с ценами на WB и возвращает список для обновления
     */
    protected function gatherPriceUpdates(MarketplaceAccount $account): array
    {
        $updates = [];

        // Получаем все MarketplaceProduct для этого аккаунта WB,
        // у которых есть связанный Product с ценой
        $marketplaceProducts = \App\Models\MarketplaceProduct::where('marketplace_account_id', $account->id)
            ->whereNotNull('external_product_id') // Должен быть nmId
            ->whereHas('product', function ($query) {
                $query->whereNotNull('price')->where('price', '>', 0);
            })
            ->with('product')
            ->get();

        foreach ($marketplaceProducts as $mp) {
            $product = $mp->product;
            if (! $product) {
                continue;
            }

            $nmId = (int) $mp->external_product_id;
            $localPrice = (int) $product->price;
            $lastSyncedPrice = (int) ($mp->last_synced_price ?? 0);

            // Проверяем нужно ли обновлять цену
            // 1. Если цена никогда не синхронизировалась
            // 2. Если локальная цена отличается от последней синхронизированной
            if ($lastSyncedPrice === 0 || $localPrice !== $lastSyncedPrice) {
                // Получаем скидку если есть
                $discount = 0;
                if (! empty($product->old_price) && $product->old_price > $localPrice) {
                    $discount = (int) round((1 - $localPrice / $product->old_price) * 100);
                    $discount = min(max($discount, 0), 99); // WB допускает 0-99%
                }

                $updates[] = [
                    'nmID' => $nmId,
                    'price' => $localPrice,
                    'discount' => $discount,
                ];

                Log::debug('WB price update needed', [
                    'nm_id' => $nmId,
                    'local_price' => $localPrice,
                    'last_synced_price' => $lastSyncedPrice,
                    'discount' => $discount,
                ]);
            }
        }

        Log::info('WB gatherPriceUpdates complete', [
            'account_id' => $account->id,
            'products_checked' => $marketplaceProducts->count(),
            'updates_needed' => count($updates),
        ]);

        return $updates;
    }

    /**
     * Set discount for specific products
     *
     * @param  array  $discountData  Array of ['nmId' => int, 'discount' => int]
     */
    public function setDiscounts(MarketplaceAccount $account, array $discountData): array
    {
        // TODO: Implement using the appropriate WB API endpoint
        // This is a placeholder for discount-specific logic

        return $this->pushPrices($account, $discountData);
    }

    /**
     * Upload prices for specific sizes
     *
     * @param  array  $sizePrices  Array of size price data
     *                             Each item should contain:
     *                             - nmID: int
     *                             - sizeID: int
     *                             - price: int (in rubles)
     * @return array Upload task result
     */
    public function uploadSizePrices(MarketplaceAccount $account, array $sizePrices): array
    {
        if (empty($sizePrices)) {
            throw new \InvalidArgumentException('Size prices array cannot be empty');
        }

        // Format and validate
        $formattedPrices = [];
        foreach ($sizePrices as $priceData) {
            $nmId = $priceData['nmID'] ?? $priceData['nmId'] ?? null;
            $sizeId = $priceData['sizeID'] ?? $priceData['sizeId'] ?? null;
            $price = $priceData['price'] ?? null;

            if (! $nmId || ! $sizeId || $price === null) {
                throw new \InvalidArgumentException('Each item must have nmID, sizeID and price');
            }

            $formattedPrices[] = [
                'nmID' => (int) $nmId,
                'sizeID' => (int) $sizeId,
                'price' => (int) ($price * 100), // Convert to kopecks
            ];
        }

        try {
            $response = $this->httpClient->post('prices', '/api/v2/upload/task/size', [
                'data' => $formattedPrices,
            ]);

            Log::info('WB size prices upload task created', [
                'account_id' => $account->id,
                'task_id' => $response['id'] ?? null,
                'sizes_count' => count($formattedPrices),
            ]);

            return $response;
        } catch (\Exception $e) {
            Log::error('Failed to upload WB size prices', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Upload WB Club discounts
     *
     * @param  array  $clubDiscounts  Array of club discount data
     *                                Each item should contain:
     *                                - nmID: int
     *                                - discount: int (discount percentage 0-50)
     * @return array Upload task result
     */
    public function uploadClubDiscounts(MarketplaceAccount $account, array $clubDiscounts): array
    {
        if (empty($clubDiscounts)) {
            throw new \InvalidArgumentException('Club discounts array cannot be empty');
        }

        // Validate discounts
        $formattedDiscounts = [];
        foreach ($clubDiscounts as $discountData) {
            $nmId = $discountData['nmID'] ?? $discountData['nmId'] ?? null;
            $discount = $discountData['discount'] ?? null;

            if (! $nmId || $discount === null) {
                throw new \InvalidArgumentException('Each item must have nmID and discount');
            }

            if ($discount < 0 || $discount > 50) {
                throw new \InvalidArgumentException('WB Club discount must be between 0 and 50%');
            }

            $formattedDiscounts[] = [
                'nmID' => (int) $nmId,
                'discount' => (int) $discount,
            ];
        }

        try {
            $response = $this->httpClient->post('prices', '/api/v2/upload/task/club-discount', [
                'data' => $formattedDiscounts,
            ]);

            Log::info('WB club discounts upload task created', [
                'account_id' => $account->id,
                'task_id' => $response['id'] ?? null,
                'products_count' => count($formattedDiscounts),
            ]);

            return $response;
        } catch (\Exception $e) {
            Log::error('Failed to upload WB club discounts', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get pending upload tasks
     *
     * @return array Pending tasks
     */
    public function getPendingTasks(MarketplaceAccount $account): array
    {
        try {
            $response = $this->httpClient->get('prices', '/api/v2/buffer/tasks');

            Log::info('WB pending tasks fetched', [
                'account_id' => $account->id,
                'count' => count($response ?? []),
            ]);

            return $response ?? [];
        } catch (\Exception $e) {
            Log::error('Failed to get WB pending tasks', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get product sizes with prices
     *
     * @param  int  $nmId  Product ID
     * @return array Sizes with prices
     */
    public function getProductSizesWithPrices(MarketplaceAccount $account, int $nmId): array
    {
        try {
            $response = $this->httpClient->get('prices', '/api/v2/list/goods/size/nm', [
                'nm' => $nmId,
            ]);

            Log::info('WB product sizes with prices fetched', [
                'account_id' => $account->id,
                'nm_id' => $nmId,
                'sizes_count' => count($response ?? []),
            ]);

            return $response ?? [];
        } catch (\Exception $e) {
            Log::error('Failed to get WB product sizes with prices', [
                'account_id' => $account->id,
                'nm_id' => $nmId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get products in quarantine
     *
     * @return array Products in quarantine
     */
    public function getQuarantineProducts(MarketplaceAccount $account): array
    {
        try {
            $response = $this->httpClient->get('prices', '/api/v2/quarantine/goods');

            Log::info('WB quarantine products fetched', [
                'account_id' => $account->id,
                'count' => count($response ?? []),
            ]);

            return $response ?? [];
        } catch (\Exception $e) {
            Log::error('Failed to get WB quarantine products', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
