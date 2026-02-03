<?php

// file: app/Services/Marketplaces/Wildberries/WildberriesStickerService.php

namespace App\Services\Marketplaces\Wildberries;

use App\Models\MarketplaceAccount;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Service for managing Wildberries order stickers
 *
 * WB Marketplace API:
 * - POST /api/v3/orders/stickers - Get order stickers (PDF/PNG)
 * - POST /api/v3/orders/stickers/cross-border - Get cross-border stickers
 */
class WildberriesStickerService
{
    protected WildberriesHttpClient $httpClient;

    public function __construct(WildberriesHttpClient $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * Get stickers for orders
     *
     * @param  array  $orderIds  Array of order IDs (max 100)
     * @param  string  $type  Sticker type: 'code128' (default), 'svg', 'png'
     * @param  int  $width  Width in mm (default: 58)
     * @param  int  $height  Height in mm (default: 40)
     * @param  bool  $save  Save to storage (default: true)
     * @return array ['file_path' => string|null, 'content' => string|null, 'format' => string]
     */
    public function getStickers(
        MarketplaceAccount $account,
        array $orderIds,
        string $type = 'code128',
        int $width = 58,
        int $height = 40,
        bool $save = true
    ): array {
        if (empty($orderIds)) {
            throw new \InvalidArgumentException('Order IDs array cannot be empty');
        }

        if (count($orderIds) > 100) {
            throw new \InvalidArgumentException('Maximum 100 order IDs allowed per request');
        }

        try {
            $payload = [
                'orders' => array_map('intval', $orderIds),
                'type' => $type,
                'width' => $width,
                'height' => $height,
            ];

            // API returns binary data (PDF/PNG)
            $response = $this->httpClient->post('marketplace', '/api/v3/orders/stickers', $payload, [
                'raw_response' => true,
            ]);

            $format = $this->detectFormat($response, $type);
            $filePath = null;

            if ($save) {
                $filePath = $this->saveSticker($account, $orderIds, $response, $format);
            }

            Log::info('WB order stickers fetched', [
                'account_id' => $account->id,
                'order_count' => count($orderIds),
                'format' => $format,
                'saved' => $save,
            ]);

            return [
                'file_path' => $filePath,
                'content' => $response,
                'format' => $format,
                'order_ids' => $orderIds,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get WB order stickers', [
                'account_id' => $account->id,
                'order_ids' => $orderIds,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get cross-border stickers for orders
     *
     * @param  array  $orderIds  Array of order IDs (max 100)
     * @param  bool  $save  Save to storage (default: true)
     */
    public function getCrossBorderStickers(
        MarketplaceAccount $account,
        array $orderIds,
        bool $save = true
    ): array {
        if (empty($orderIds)) {
            throw new \InvalidArgumentException('Order IDs array cannot be empty');
        }

        if (count($orderIds) > 100) {
            throw new \InvalidArgumentException('Maximum 100 order IDs allowed per request');
        }

        try {
            $payload = [
                'orders' => array_map('intval', $orderIds),
            ];

            $response = $this->httpClient->post('marketplace', '/api/v3/orders/stickers/cross-border', $payload, [
                'raw_response' => true,
            ]);

            $format = 'pdf'; // Cross-border stickers are always PDF
            $filePath = null;

            if ($save) {
                $filePath = $this->saveSticker($account, $orderIds, $response, $format, 'cross-border');
            }

            Log::info('WB cross-border order stickers fetched', [
                'account_id' => $account->id,
                'order_count' => count($orderIds),
            ]);

            return [
                'file_path' => $filePath,
                'content' => $response,
                'format' => $format,
                'order_ids' => $orderIds,
                'type' => 'cross-border',
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get WB cross-border order stickers', [
                'account_id' => $account->id,
                'order_ids' => $orderIds,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get stickers in batches (for more than 100 orders)
     *
     * @param  array  $orderIds  Array of order IDs (any count)
     * @param  string  $type  Sticker type
     * @param  bool  $save  Save to storage
     * @return array Array of sticker results
     */
    public function getStickersInBatches(
        MarketplaceAccount $account,
        array $orderIds,
        string $type = 'code128',
        bool $save = true
    ): array {
        $batches = array_chunk($orderIds, 100);
        $results = [];

        foreach ($batches as $index => $batch) {
            try {
                $result = $this->getStickers($account, $batch, $type, 58, 40, $save);
                $results[] = $result;

                Log::info("WB stickers batch {$index} completed", [
                    'account_id' => $account->id,
                    'batch_size' => count($batch),
                ]);
            } catch (\Exception $e) {
                Log::error("WB stickers batch {$index} failed", [
                    'account_id' => $account->id,
                    'error' => $e->getMessage(),
                ]);

                $results[] = [
                    'error' => $e->getMessage(),
                    'order_ids' => $batch,
                ];
            }
        }

        return $results;
    }

    /**
     * Save sticker to storage
     *
     * @param  string  $content  Binary content
     * @param  string  $format  File format (pdf/png/svg)
     * @param  string  $prefix  Filename prefix
     * @return string File path
     */
    protected function saveSticker(
        MarketplaceAccount $account,
        array $orderIds,
        string $content,
        string $format,
        string $prefix = 'stickers'
    ): string {
        $timestamp = now()->format('Y-m-d_His');
        $orderIdsStr = implode('-', array_slice($orderIds, 0, 5)); // First 5 IDs

        if (count($orderIds) > 5) {
            $orderIdsStr .= '-and-more';
        }

        $filename = "wb-{$prefix}-{$orderIdsStr}-{$timestamp}.{$format}";
        $path = "marketplace/stickers/account-{$account->id}/{$filename}";

        Storage::disk('local')->put($path, $content);

        Log::info('WB sticker saved to storage', [
            'account_id' => $account->id,
            'path' => $path,
            'size' => strlen($content),
        ]);

        return $path;
    }

    /**
     * Detect file format from content
     */
    protected function detectFormat(string $content, string $requestedType): string
    {
        // Check magic bytes
        if (str_starts_with($content, '%PDF')) {
            return 'pdf';
        }

        if (str_starts_with($content, "\x89PNG")) {
            return 'png';
        }

        if (str_starts_with($content, '<svg') || str_starts_with($content, '<?xml')) {
            return 'svg';
        }

        // Fallback to requested type
        return match ($requestedType) {
            'svg' => 'svg',
            'png' => 'png',
            default => 'pdf',
        };
    }

    /**
     * Get sticker file URL for download
     *
     * @param  string  $filePath  Storage path
     */
    public function getStickerUrl(string $filePath): ?string
    {
        if (! Storage::disk('local')->exists($filePath)) {
            return null;
        }

        // Generate temporary URL (valid for 1 hour)
        return Storage::disk('local')->url($filePath);
    }

    /**
     * Delete old stickers (cleanup)
     *
     * @param  int  $daysOld  Delete stickers older than N days
     * @return int Number of deleted files
     */
    public function cleanupOldStickers(int $daysOld = 30): int
    {
        $deleted = 0;
        $path = 'marketplace/stickers';

        if (! Storage::disk('local')->exists($path)) {
            return 0;
        }

        $files = Storage::disk('local')->allFiles($path);
        $cutoffTime = now()->subDays($daysOld)->timestamp;

        foreach ($files as $file) {
            $lastModified = Storage::disk('local')->lastModified($file);

            if ($lastModified < $cutoffTime) {
                Storage::disk('local')->delete($file);
                $deleted++;
            }
        }

        Log::info('WB stickers cleanup completed', [
            'deleted' => $deleted,
            'days_old' => $daysOld,
        ]);

        return $deleted;
    }
}
