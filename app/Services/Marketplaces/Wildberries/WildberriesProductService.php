<?php
// file: app/Services/Marketplaces/Wildberries/WildberriesProductService.php

namespace App\Services\Marketplaces\Wildberries;

use App\Models\MarketplaceAccount;
use App\Models\WildberriesProduct;
use Illuminate\Support\Facades\Log;

/**
 * Service for syncing Wildberries product cards (Content API).
 *
 * WB Content API endpoints:
 * - POST /content/v2/get/cards/list - list cards with pagination
 * - POST /content/v2/cards/upload - create new cards
 * - POST /content/v2/cards/update - update existing cards
 */
class WildberriesProductService
{
    /**
     * Get or create HTTP client for specific account
     */
    protected function getHttpClient(MarketplaceAccount $account): WildberriesHttpClient
    {
        return new WildberriesHttpClient($account);
    }

    /**
     * Sync all product cards from WB to local database
     *
     * @param MarketplaceAccount $account
     * @param array $filters Optional filters (textSearch, withPhoto, etc.)
     * @return array Summary of sync results
     */
    public function syncProducts(MarketplaceAccount $account, array $filters = []): array
    {
        $synced = 0;
        $created = 0;
        $updated = 0;
        $errors = [];
        $cursor = null;
        $limit = config('wildberries.sync.products_per_page', 100);
        $maxIterations = 500; // safety guard to avoid infinite loops

        Log::info('Starting WB products sync', ['account_id' => $account->id]);

        for ($iteration = 0; $iteration < $maxIterations; $iteration++) {
            try {
                // Build request payload (aligned with WB docs)
                $payload = $this->buildRequestPayload($filters, $limit, $cursor);

                // Fetch cards from WB Content API
                $response = $this->getHttpClient($account)->post('content', '/content/v2/get/cards/list', $payload);

                $cards = $response['cards'] ?? [];
                $cursor = $response['cursor'] ?? null;

                // Debug log: payload + short response preview to inspect in console/logs
                \Log::info('WB products sync batch', [
                    'account_id' => $account->id,
                    'payload' => $payload,
                    'response_cursor' => $cursor,
                    'cards_preview' => array_slice(array_map(fn ($c) => [
                        'nmID' => $c['nmID'] ?? null,
                        'vendorCode' => $c['vendorCode'] ?? null,
                        'title' => $c['title'] ?? null,
                    ], $cards), 0, 5),
                    'batch_count' => count($cards),
                ]);

                // Process each card
                foreach ($cards as $cardData) {
                    try {
                        $result = $this->upsertProduct($account, $cardData);

                        $created += $result['created'];
                        $updated += $result['updated'];
                        $synced++;
                    } catch (\Exception $e) {
                        $errors[] = [
                            'nm_id' => $cardData['nmID'] ?? 'unknown',
                            'error' => $e->getMessage(),
                        ];
                    }
                }

                Log::debug('WB products batch synced', [
                    'account_id' => $account->id,
                    'batch_count' => count($cards),
                    'total_synced' => $synced,
                ]);

            } catch (\Exception $e) {
                Log::error('WB products sync batch failed', [
                    'account_id' => $account->id,
                    'error' => $e->getMessage(),
                ]);

                $errors[] = ['batch_error' => $e->getMessage()];
                break;
            }

            // Stop if less than page size or cursor is missing
            if (count($cards) < $limit || empty($cursor)) {
                break;
            }
        }

        Log::info('WB products sync completed', [
            'account_id' => $account->id,
            'synced' => $synced,
            'created' => $created,
            'updated' => $updated,
            'errors' => count($errors),
        ]);

        return [
            'synced' => $synced,
            'created' => $created,
            'updated' => $updated,
            'errors' => $errors,
        ];
    }

    /**
     * Build request payload for WB Content API /content/v2/get/cards/list
     * to match documented structure (sort + filter + cursor).
     */
    protected function buildRequestPayload(array $filters, int $limit, ?array $cursor): array
    {
        $payload = [
            'settings' => [
                'sort' => [
                    'ascending' => false,
                ],
                'filter' => [
                    'withPhoto' => $filters['withPhoto'] ?? -1, // -1 = all, 0 = without, 1 = with
                    'allowedCategoriesOnly' => $filters['allowedCategoriesOnly'] ?? true,
                ],
                'cursor' => [
                    'limit' => $limit,
                ],
            ],
        ];

        // Pagination cursor from previous page
        if ($cursor) {
            $payload['settings']['cursor']['updatedAt'] = $cursor['updatedAt'] ?? null;
            $payload['settings']['cursor']['nmID'] = $cursor['nmID'] ?? null;
        }

        // Optional filters from caller
        $map = [
            'textSearch' => 'textSearch',
            'tagIDs' => 'tagIDs',
            'tag_ids' => 'tagIDs',
            'objectIDs' => 'objectIDs',
            'object_ids' => 'objectIDs',
            'brands' => 'brands',
            'imtID' => 'imtID',
            'imt_id' => 'imtID',
        ];

        foreach ($map as $key => $target) {
            if (isset($filters[$key]) && $filters[$key] !== '' && $filters[$key] !== null) {
                $payload['settings']['filter'][$target] = $filters[$key];
            }
        }

        // Clean up nulls in filter (keep withPhoto even if 0/-1)
        $payload['settings']['filter'] = array_filter(
            $payload['settings']['filter'],
            function ($value, $k) {
                if ($k === 'withPhoto') {
                    return true;
                }
                return $value !== null && $value !== '';
            },
            ARRAY_FILTER_USE_BOTH
        );

        return $payload;
    }

    /**
     * Create or update a single product from WB card data
     * Creates separate record for EACH size/variant (each barcode)
     */
    protected function upsertProduct(MarketplaceAccount $account, array $cardData): array
    {
        $nmId = $cardData['nmID'] ?? null;

        if (!$nmId) {
            throw new \RuntimeException('Card data missing nmID');
        }

        $created = 0;
        $updated = 0;
        $products = [];

        // Common card data
        $commonData = [
            'nm_id' => $nmId,
            'imt_id' => $cardData['imtID'] ?? null,
            'vendor_code' => $cardData['vendorCode'] ?? null,
            'supplier_article' => $cardData['vendorCode'] ?? null,
            'title' => $cardData['title'] ?? null,
            'description' => $cardData['description'] ?? null,
            'brand' => $cardData['brand'] ?? null,
            'subject_name' => $cardData['subjectName'] ?? null,
            'subject_id' => $cardData['subjectID'] ?? null,
            'photos' => $this->extractPhotos($cardData),
            'videos' => $cardData['video'] ?? null,
            'characteristics' => $cardData['characteristics'] ?? null,
            'is_active' => true,
            'raw_data' => $cardData,
            'synced_at' => now(),
        ];

        // Process each size/variant
        if (!empty($cardData['sizes'])) {
            foreach ($cardData['sizes'] as $sizeData) {
                $chrtId = $sizeData['chrtID'] ?? null;
                $barcode = $sizeData['skus'][0] ?? null;

                if (!$barcode) {
                    continue; // Skip sizes without barcode
                }

                // Find by barcode (most unique) or chrtID
                $product = WildberriesProduct::where('marketplace_account_id', $account->id)
                    ->where(function ($q) use ($barcode, $chrtId, $nmId) {
                        $q->where('barcode', $barcode);
                        if ($chrtId) {
                            $q->orWhere(function ($q2) use ($chrtId, $nmId) {
                                $q2->where('chrt_id', $chrtId)->where('nm_id', $nmId);
                            });
                        }
                    })
                    ->first();

                $isNew = !$product;

                if (!$product) {
                    $product = new WildberriesProduct();
                    $product->marketplace_account_id = $account->id;
                }

                // Update with common data + size-specific data
                $product->fill($commonData);
                $product->tech_size = $sizeData['techSize'] ?? null;
                $product->chrt_id = $chrtId;
                $product->barcode = $barcode;
                $product->price = isset($sizeData['price']) ? $sizeData['price'] / 100 : null;
                $product->discount_percent = $sizeData['discountedPercent'] ?? null;

                $product->save();

                $products[] = $product;

                if ($isNew) {
                    $created++;
                } else {
                    $updated++;
                }
            }
        } else {
            // No sizes array - create single product (fallback for old data)
            $product = WildberriesProduct::where('marketplace_account_id', $account->id)
                ->where('nm_id', $nmId)
                ->first();

            $isNew = !$product;

            if (!$product) {
                $product = new WildberriesProduct();
                $product->marketplace_account_id = $account->id;
            }

            $product->fill($commonData);
            $product->save();

            $products[] = $product;

            if ($isNew) {
                $created++;
            } else {
                $updated++;
            }
        }

        return [
            'products' => $products,
            'created' => $created,
            'updated' => $updated,
        ];
    }

    /**
     * Extract photos array from card data
     */
    protected function extractPhotos(array $cardData): array
    {
        $photos = [];

        if (!empty($cardData['photos'])) {
            foreach ($cardData['photos'] as $index => $photo) {
                $photos[] = [
                    'url' => $photo['big'] ?? $photo['c246x328'] ?? null,
                    'thumb' => $photo['tm'] ?? $photo['c516x688'] ?? null,
                    'is_main' => $index === 0,
                ];
            }
        }

        return $photos;
    }

    /**
     * Get single product card from WB
     */
    public function getProductCard(MarketplaceAccount $account, int $nmId): ?array
    {
        try {
            $response = $this->getHttpClient($account)->post('content', '/content/v2/get/cards/list', [
                'settings' => [
                    'cursor' => ['limit' => 1],
                    'filter' => ['nmID' => [$nmId]],
                ],
            ]);

            return $response['cards'][0] ?? null;
        } catch (\Exception $e) {
            Log::error('Failed to get WB product card', [
                'nm_id' => $nmId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Create a new product card on WB
     *
     * @param MarketplaceAccount $account
     * @param array $cardData Card data according to WB API format
     * @return array API response
     */
    public function createProductCard(MarketplaceAccount $account, array $cardData): array
    {
        // TODO: Implement full card creation logic
        // POST /content/v2/cards/upload
        // Requires: vendorCode, characteristics, sizes with skus

        return $this->getHttpClient($account)->post('content', '/content/v2/cards/upload', [$cardData]);
    }

    /**
     * Update existing product card on WB
     *
     * @param MarketplaceAccount $account
     * @param array $cardData Card data with nmID
     * @return array API response
     */
    public function updateProductCard(MarketplaceAccount $account, array $cardData): array
    {
        // TODO: Implement full card update logic
        // POST /content/v2/cards/update

        return $this->getHttpClient($account)->post('content', '/content/v2/cards/update', [$cardData]);
    }

    /**
     * Generate barcodes for products
     *
     * @param MarketplaceAccount $account
     * @param int $count Number of barcodes to generate (max 1000)
     * @return array Array of generated barcodes
     */
    public function generateBarcodes(MarketplaceAccount $account, int $count = 1): array
    {
        if ($count < 1 || $count > 1000) {
            throw new \InvalidArgumentException('Count must be between 1 and 1000');
        }

        try {
            $response = $this->getHttpClient($account)->post('content', '/content/v2/barcodes', [
                'count' => $count,
            ]);

            Log::info('WB barcodes generated', [
                'account_id' => $account->id,
                'count' => $count,
                'generated' => count($response['barcodes'] ?? []),
            ]);

            return $response['barcodes'] ?? [];
        } catch (\Exception $e) {
            Log::error('Failed to generate WB barcodes', [
                'account_id' => $account->id,
                'count' => $count,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Upload media file (photo/video)
     *
     * @param MarketplaceAccount $account
     * @param string $filePath Local file path or URL
     * @return array Upload result with media ID
     */
    public function uploadMediaFile(MarketplaceAccount $account, string $filePath): array
    {
        try {
            // If it's a URL, download first
            if (filter_var($filePath, FILTER_VALIDATE_URL)) {
                return $this->uploadMediaByUrl($account, $filePath);
            }

            // Upload local file
            $fileContent = file_get_contents($filePath);
            if ($fileContent === false) {
                throw new \RuntimeException("Failed to read file: {$filePath}");
            }

            $response = $this->httpClient->post('content', '/content/v3/media/file', [
                'file' => base64_encode($fileContent),
                'filename' => basename($filePath),
            ]);

            Log::info('WB media file uploaded', [
                'account_id' => $account->id,
                'filename' => basename($filePath),
            ]);

            return $response;
        } catch (\Exception $e) {
            Log::error('Failed to upload WB media file', [
                'account_id' => $account->id,
                'file' => $filePath,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Upload media files by URLs
     *
     * @param MarketplaceAccount $account
     * @param array $urls Array of image/video URLs
     * @return array Upload results
     */
    public function uploadMediaByUrls(MarketplaceAccount $account, array $urls): array
    {
        try {
            $response = $this->httpClient->post('content', '/content/v3/media/save', [
                'urls' => $urls,
            ]);

            Log::info('WB media uploaded by URLs', [
                'account_id' => $account->id,
                'count' => count($urls),
            ]);

            return $response;
        } catch (\Exception $e) {
            Log::error('Failed to upload WB media by URLs', [
                'account_id' => $account->id,
                'urls_count' => count($urls),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Upload single media by URL
     */
    protected function uploadMediaByUrl(MarketplaceAccount $account, string $url): array
    {
        return $this->uploadMediaByUrls($account, [$url]);
    }

    /**
     * Get all tags (labels)
     *
     * @param MarketplaceAccount $account
     * @return array Tags list
     */
    public function getTags(MarketplaceAccount $account): array
    {
        try {
            $response = $this->httpClient->get('content', '/content/v2/tags');

            Log::info('WB tags fetched', [
                'account_id' => $account->id,
                'count' => count($response['tags'] ?? []),
            ]);

            return $response['tags'] ?? [];
        } catch (\Exception $e) {
            Log::error('Failed to get WB tags', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Create tag (label)
     *
     * @param MarketplaceAccount $account
     * @param string $name Tag name
     * @param string|null $color Tag color (hex)
     * @return array Created tag
     */
    public function createTag(MarketplaceAccount $account, string $name, ?string $color = null): array
    {
        try {
            $payload = ['name' => $name];
            if ($color) {
                $payload['color'] = $color;
            }

            $response = $this->httpClient->post('content', '/content/v2/tag', $payload);

            Log::info('WB tag created', [
                'account_id' => $account->id,
                'tag_name' => $name,
            ]);

            return $response;
        } catch (\Exception $e) {
            Log::error('Failed to create WB tag', [
                'account_id' => $account->id,
                'tag_name' => $name,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Update tag
     *
     * @param MarketplaceAccount $account
     * @param int $tagId
     * @param string $name
     * @param string|null $color
     * @return array
     */
    public function updateTag(MarketplaceAccount $account, int $tagId, string $name, ?string $color = null): array
    {
        try {
            $payload = ['name' => $name];
            if ($color) {
                $payload['color'] = $color;
            }

            $response = $this->httpClient->patch('content', "/content/v2/tag/{$tagId}", $payload);

            Log::info('WB tag updated', [
                'account_id' => $account->id,
                'tag_id' => $tagId,
            ]);

            return $response;
        } catch (\Exception $e) {
            Log::error('Failed to update WB tag', [
                'account_id' => $account->id,
                'tag_id' => $tagId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Delete tag
     *
     * @param MarketplaceAccount $account
     * @param int $tagId
     * @return bool
     */
    public function deleteTag(MarketplaceAccount $account, int $tagId): bool
    {
        try {
            $this->httpClient->delete('content', "/content/v2/tag/{$tagId}");

            Log::info('WB tag deleted', [
                'account_id' => $account->id,
                'tag_id' => $tagId,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to delete WB tag', [
                'account_id' => $account->id,
                'tag_id' => $tagId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Link tags to product card
     *
     * @param MarketplaceAccount $account
     * @param int $nmId Product ID
     * @param array $tagIds Array of tag IDs
     * @return bool
     */
    public function linkTagsToProduct(MarketplaceAccount $account, int $nmId, array $tagIds): bool
    {
        try {
            $this->httpClient->post('content', '/content/v2/tag/nomenclature/link', [
                'nmID' => $nmId,
                'tagIDs' => $tagIds,
            ]);

            Log::info('WB tags linked to product', [
                'account_id' => $account->id,
                'nm_id' => $nmId,
                'tags_count' => count($tagIds),
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to link WB tags to product', [
                'account_id' => $account->id,
                'nm_id' => $nmId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get WB directories (справочники)
     */

    /**
     * Get parent categories
     *
     * @param MarketplaceAccount $account
     * @return array
     */
    public function getParentCategories(MarketplaceAccount $account): array
    {
        try {
            $response = $this->httpClient->get('content', '/content/v2/object/parent/all');

            Log::info('WB parent categories fetched', [
                'account_id' => $account->id,
                'count' => count($response['data'] ?? []),
            ]);

            return $response['data'] ?? [];
        } catch (\Exception $e) {
            Log::error('Failed to get WB parent categories', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get all subjects (предметы)
     *
     * @param MarketplaceAccount $account
     * @return array
     */
    public function getSubjects(MarketplaceAccount $account): array
    {
        try {
            $response = $this->httpClient->get('content', '/content/v2/object/all');

            Log::info('WB subjects fetched', [
                'account_id' => $account->id,
                'count' => count($response['data'] ?? []),
            ]);

            return $response['data'] ?? [];
        } catch (\Exception $e) {
            Log::error('Failed to get WB subjects', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get subject characteristics
     *
     * @param MarketplaceAccount $account
     * @param int $subjectId
     * @return array
     */
    public function getSubjectCharacteristics(MarketplaceAccount $account, int $subjectId): array
    {
        try {
            $response = $this->httpClient->get('content', "/content/v2/object/charcs/{$subjectId}");

            Log::info('WB subject characteristics fetched', [
                'account_id' => $account->id,
                'subject_id' => $subjectId,
            ]);

            return $response['data'] ?? [];
        } catch (\Exception $e) {
            Log::error('Failed to get WB subject characteristics', [
                'account_id' => $account->id,
                'subject_id' => $subjectId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get colors directory
     */
    public function getColors(MarketplaceAccount $account): array
    {
        return $this->getDirectory($account, 'colors');
    }

    /**
     * Get kinds directory (genders)
     */
    public function getKinds(MarketplaceAccount $account): array
    {
        return $this->getDirectory($account, 'kinds');
    }

    /**
     * Get countries directory
     */
    public function getCountries(MarketplaceAccount $account): array
    {
        return $this->getDirectory($account, 'countries');
    }

    /**
     * Get seasons directory
     */
    public function getSeasons(MarketplaceAccount $account): array
    {
        return $this->getDirectory($account, 'seasons');
    }

    /**
     * Get VAT rates directory
     */
    public function getVatRates(MarketplaceAccount $account): array
    {
        return $this->getDirectory($account, 'vat');
    }

    /**
     * Get TNVED codes directory
     */
    public function getTnvedCodes(MarketplaceAccount $account): array
    {
        return $this->getDirectory($account, 'tnved');
    }

    /**
     * Generic directory getter
     */
    protected function getDirectory(MarketplaceAccount $account, string $type): array
    {
        try {
            $response = $this->httpClient->get('content', "/content/v2/directory/{$type}");

            Log::info("WB {$type} directory fetched", [
                'account_id' => $account->id,
                'count' => count($response['data'] ?? []),
            ]);

            return $response['data'] ?? [];
        } catch (\Exception $e) {
            Log::error("Failed to get WB {$type} directory", [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Move cards to trash
     *
     * @param MarketplaceAccount $account
     * @param array $nmIds Product IDs to delete
     * @return array
     */
    public function moveCardsToTrash(MarketplaceAccount $account, array $nmIds): array
    {
        try {
            $response = $this->httpClient->post('content', '/content/v2/cards/delete/trash', [
                'nmIDs' => array_map('intval', $nmIds),
            ]);

            Log::info('WB cards moved to trash', [
                'account_id' => $account->id,
                'count' => count($nmIds),
            ]);

            return $response;
        } catch (\Exception $e) {
            Log::error('Failed to move WB cards to trash', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Recover cards from trash
     *
     * @param MarketplaceAccount $account
     * @param array $nmIds Product IDs to recover
     * @return array
     */
    public function recoverCardsFromTrash(MarketplaceAccount $account, array $nmIds): array
    {
        try {
            $response = $this->httpClient->post('content', '/content/v2/cards/recover', [
                'nmIDs' => array_map('intval', $nmIds),
            ]);

            Log::info('WB cards recovered from trash', [
                'account_id' => $account->id,
                'count' => count($nmIds),
            ]);

            return $response;
        } catch (\Exception $e) {
            Log::error('Failed to recover WB cards from trash', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get cards in trash
     *
     * @param MarketplaceAccount $account
     * @return array
     */
    public function getTrashCards(MarketplaceAccount $account): array
    {
        try {
            $response = $this->httpClient->post('content', '/content/v2/get/cards/trash', [
                'settings' => [
                    'cursor' => ['limit' => 1000],
                ],
            ]);

            Log::info('WB trash cards fetched', [
                'account_id' => $account->id,
                'count' => count($response['cards'] ?? []),
            ]);

            return $response['cards'] ?? [];
        } catch (\Exception $e) {
            Log::error('Failed to get WB trash cards', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get card limits
     *
     * @param MarketplaceAccount $account
     * @return array
     */
    public function getCardLimits(MarketplaceAccount $account): array
    {
        try {
            $response = $this->httpClient->get('content', '/content/v2/cards/limits');

            Log::info('WB card limits fetched', [
                'account_id' => $account->id,
            ]);

            return $response;
        } catch (\Exception $e) {
            Log::error('Failed to get WB card limits', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
