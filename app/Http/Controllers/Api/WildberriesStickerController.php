<?php
// file: app/Http/Controllers/Api/WildberriesStickerController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceAccount;
use App\Services\Marketplaces\Wildberries\WildberriesStickerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class WildberriesStickerController extends Controller
{
    protected WildberriesStickerService $stickerService;

    public function __construct(WildberriesStickerService $stickerService)
    {
        $this->stickerService = $stickerService;
    }

    /**
     * Get stickers for orders
     */
    public function generate(Request $request, MarketplaceAccount $account): JsonResponse
    {
        if (!$request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        if (!$account->isWildberries()) {
            return response()->json(['message' => 'Аккаунт не является Wildberries.'], 400);
        }

        $validated = $request->validate([
            'order_ids' => 'required|array|min:1|max:100',
            'order_ids.*' => 'required|integer',
            'type' => 'sometimes|string|in:code128,svg,png',
            'width' => 'sometimes|integer|min:20|max:200',
            'height' => 'sometimes|integer|min:20|max:200',
        ]);

        $orderIds = $validated['order_ids'];
        $type = $validated['type'] ?? 'code128';
        $width = $validated['width'] ?? 58;
        $height = $validated['height'] ?? 40;

        try {
            $result = $this->stickerService->getStickers($account, $orderIds, $type, $width, $height, true);

            return response()->json([
                'success' => true,
                'message' => 'Стикеры успешно сгенерированы',
                'file_path' => $result['file_path'],
                'format' => $result['format'],
                'order_ids' => $result['order_ids'],
                'download_url' => $result['file_path'] ? route('api.wildberries.stickers.download', [
                    'account' => $account->id,
                    'path' => base64_encode($result['file_path']),
                ]) : null,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to generate WB stickers', [
                'account_id' => $account->id,
                'order_ids' => $orderIds,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Не удалось сгенерировать стикеры: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get cross-border stickers
     */
    public function crossBorder(Request $request, MarketplaceAccount $account): JsonResponse
    {
        if (!$request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        if (!$account->isWildberries()) {
            return response()->json(['message' => 'Аккаунт не является Wildberries.'], 400);
        }

        $validated = $request->validate([
            'order_ids' => 'required|array|min:1|max:100',
            'order_ids.*' => 'required|integer',
        ]);

        try {
            $result = $this->stickerService->getCrossBorderStickers($account, $validated['order_ids'], true);

            return response()->json([
                'success' => true,
                'message' => 'Кроссбордер стикеры успешно сгенерированы',
                'file_path' => $result['file_path'],
                'format' => $result['format'],
                'order_ids' => $result['order_ids'],
                'type' => $result['type'],
                'download_url' => $result['file_path'] ? route('api.wildberries.stickers.download', [
                    'account' => $account->id,
                    'path' => base64_encode($result['file_path']),
                ]) : null,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to generate WB cross-border stickers', [
                'account_id' => $account->id,
                'order_ids' => $validated['order_ids'],
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Не удалось сгенерировать кроссбордер стикеры: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download sticker file
     */
    public function download(Request $request, MarketplaceAccount $account): JsonResponse
    {
        if (!$request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        $encodedPath = $request->query('path');
        if (!$encodedPath) {
            return response()->json(['message' => 'Путь к файлу не указан.'], 400);
        }

        $filePath = base64_decode($encodedPath);

        if (!Storage::disk('local')->exists($filePath)) {
            return response()->json(['message' => 'Файл не найден.'], 404);
        }

        $content = Storage::disk('local')->get($filePath);
        $mimeType = Storage::disk('local')->mimeType($filePath);
        $filename = basename($filePath);

        return response($content, 200)
            ->header('Content-Type', $mimeType)
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    /**
     * Cleanup old stickers
     */
    public function cleanup(Request $request): JsonResponse
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        $daysOld = $request->input('days_old', 30);

        try {
            $deleted = $this->stickerService->cleanupOldStickers($daysOld);

            return response()->json([
                'success' => true,
                'message' => "Удалено старых стикеров: {$deleted}",
                'deleted_count' => $deleted,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to cleanup old stickers', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Не удалось очистить старые стикеры: ' . $e->getMessage(),
            ], 500);
        }
    }
}
