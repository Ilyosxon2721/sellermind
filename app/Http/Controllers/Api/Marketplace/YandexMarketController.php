<?php

namespace App\Http\Controllers\Api\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceAccount;
use App\Services\Marketplaces\YandexMarket\YandexMarketClient;
use App\Services\Marketplaces\YandexMarket\YandexMarketProductCopyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class YandexMarketController extends Controller
{
    public function __construct(
        protected YandexMarketClient $client
    ) {}

    /**
     * Проверить подключение
     */
    public function ping(Request $request, MarketplaceAccount $account): JsonResponse
    {
        $this->authorizeAccount($request, $account);

        $result = $this->client->ping($account);

        return response()->json($result);
    }

    /**
     * Получить список кампаний
     */
    public function campaigns(Request $request, MarketplaceAccount $account): JsonResponse
    {
        $this->authorizeAccount($request, $account);

        try {
            $campaigns = $this->client->getCampaigns($account);

            return response()->json([
                'success' => true,
                'campaigns' => $campaigns,
            ]);
        } catch (\Exception $e) {
            Log::error('Ошибка получения кампаний Yandex Market', ['account_id' => $account->id, 'error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Сохранить настройки аккаунта
     */
    public function saveSettings(Request $request, MarketplaceAccount $account): JsonResponse
    {
        $this->authorizeAccount($request, $account);

        $validated = $request->validate([
            'api_key' => 'nullable|string',
            'campaign_id' => 'nullable|string',
            'business_id' => 'nullable|string',
            'stock_sync_mode' => 'nullable|string|in:basic,aggregated',
            'warehouse_id' => 'nullable|integer',
            'source_warehouse_ids' => 'nullable|array',
            'source_warehouse_ids.*' => 'integer',
        ]);

        // Use getDecryptedCredentials() to get array, not the encrypted string
        $credentials = $account->getDecryptedCredentials();

        // Only update api_key if it's a real token (not masked value)
        $apiKey = $validated['api_key'] ?? '';
        if (! empty($apiKey) && ! str_starts_with($apiKey, '***') && ! str_contains($apiKey, '...')) {
            $credentials['api_key'] = $apiKey;
        }
        if (isset($validated['campaign_id'])) {
            $credentials['campaign_id'] = $validated['campaign_id'];
        }
        if (isset($validated['business_id'])) {
            $credentials['business_id'] = $validated['business_id'];
        }

        $account->update([
            'credentials' => $credentials,
        ]);

        // Handle stock sync settings in credentials_json
        if ($request->has('stock_sync_mode') || $request->has('warehouse_id') || $request->has('source_warehouse_ids')) {
            $credentialsJson = $account->credentials_json ?? [];
            if ($request->has('stock_sync_mode')) {
                $credentialsJson['stock_sync_mode'] = $validated['stock_sync_mode'];
            }
            if ($request->has('warehouse_id')) {
                $credentialsJson['warehouse_id'] = $validated['warehouse_id'];
            }
            if ($request->has('source_warehouse_ids')) {
                $credentialsJson['source_warehouse_ids'] = $validated['source_warehouse_ids'];
            }
            $account->update(['credentials_json' => $credentialsJson]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Настройки сохранены',
        ]);
    }

    /**
     * Синхронизировать каталог
     */
    public function syncCatalog(Request $request, MarketplaceAccount $account): JsonResponse
    {
        $this->authorizeAccount($request, $account);

        try {
            $result = $this->client->syncCatalog($account);

            return response()->json([
                'success' => true,
                'synced' => $result['synced'] ?? 0,
                'message' => 'Каталог синхронизирован',
            ]);
        } catch (\Exception $e) {
            Log::error('Ошибка синхронизации каталога Yandex Market', ['account_id' => $account->id, 'error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Получить заказы
     */
    public function orders(Request $request, MarketplaceAccount $account): JsonResponse
    {
        $this->authorizeAccount($request, $account);

        $from = $request->input('from', now()->subDays(30)->format('Y-m-d'));
        $to = $request->input('to', now()->format('Y-m-d'));

        try {
            $orders = $this->client->fetchOrders(
                $account,
                new \DateTime($from),
                new \DateTime($to)
            );

            return response()->json([
                'success' => true,
                'orders' => $orders,
                'total' => count($orders),
            ]);
        } catch (\Exception $e) {
            Log::error('Ошибка получения заказов Yandex Market', ['account_id' => $account->id, 'from' => $from, 'to' => $to, 'error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Синхронизировать заказы
     */
    public function syncOrders(Request $request, MarketplaceAccount $account): JsonResponse
    {
        $this->authorizeAccount($request, $account);

        $from = $request->input('from') ? new \DateTime($request->input('from')) : null;
        $to = $request->input('to') ? new \DateTime($request->input('to')) : null;

        try {
            $result = $this->client->syncOrders($account, $from, $to);

            return response()->json([
                'success' => true,
                'synced' => $result['synced'] ?? 0,
                'total' => $result['total'] ?? 0,
                'message' => "Синхронизировано {$result['synced']} заказов",
            ]);
        } catch (\Exception $e) {
            Log::error('Ошибка синхронизации заказов Yandex Market', ['account_id' => $account->id, 'error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Проверить доступ к аккаунту
     */
    protected function authorizeAccount(Request $request, MarketplaceAccount $account): void
    {
        if ($account->marketplace !== 'yandex_market' && $account->marketplace !== 'ym') {
            abort(400, 'Аккаунт не является Yandex Market');
        }

        if (! $request->user()->hasCompanyAccess($account->company_id)) {
            abort(403, 'Доступ запрещён');
        }
    }

    // ==================== ORDER ACTIONS ====================

    /**
     * Установить статус "Готов к отгрузке"
     */
    public function readyToShip(Request $request, MarketplaceAccount $account, string $orderId): JsonResponse
    {
        $this->authorizeAccount($request, $account);

        try {
            $result = $this->client->setOrderReadyToShip($account, $orderId);

            return response()->json([
                'success' => true,
                'message' => 'Заказ готов к отгрузке',
                'order' => $result['order'] ?? null,
            ]);
        } catch (\Exception $e) {
            Log::error('Ошибка установки статуса "Готов к отгрузке" YM', ['account_id' => $account->id, 'order_id' => $orderId, 'error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Скачать ярлыки для заказа (PDF)
     */
    public function downloadLabels(Request $request, MarketplaceAccount $account, string $orderId)
    {
        $this->authorizeAccount($request, $account);

        $format = $request->input('format', 'A7');

        try {
            $pdfContent = $this->client->getOrderLabels($account, $orderId, $format);

            return response($pdfContent, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => "attachment; filename=\"order-{$orderId}-labels.pdf\"",
            ]);
        } catch (\Exception $e) {
            Log::error('Ошибка скачивания ярлыков заказа YM', ['account_id' => $account->id, 'order_id' => $orderId, 'error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Установить грузоместа для заказа
     */
    public function setBoxes(Request $request, MarketplaceAccount $account, string $orderId): JsonResponse
    {
        $this->authorizeAccount($request, $account);

        $boxes = $request->input('boxes', []);

        if (empty($boxes)) {
            return response()->json([
                'success' => false,
                'message' => 'Необходимо указать грузоместа',
            ], 400);
        }

        try {
            $result = $this->client->setOrderBoxes($account, $orderId, $boxes);

            return response()->json([
                'success' => true,
                'message' => 'Грузоместа установлены',
                'result' => $result,
            ]);
        } catch (\Exception $e) {
            Log::error('Ошибка установки грузомест заказа YM', ['account_id' => $account->id, 'order_id' => $orderId, 'error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Отменить заказ
     */
    public function cancelOrder(Request $request, MarketplaceAccount $account, string $orderId): JsonResponse
    {
        $this->authorizeAccount($request, $account);

        $reason = $request->input('reason', 'SHOP_FAILED');

        try {
            $result = $this->client->cancelOrder($account, $orderId, $reason);

            return response()->json([
                'success' => true,
                'message' => 'Заказ отменён',
                'order' => $result['order'] ?? null,
            ]);
        } catch (\Exception $e) {
            Log::error('Ошибка отмены заказа YM', ['account_id' => $account->id, 'order_id' => $orderId, 'reason' => $reason, 'error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Получить детальную информацию о заказе
     */
    public function getOrderDetails(Request $request, MarketplaceAccount $account, string $orderId): JsonResponse
    {
        $this->authorizeAccount($request, $account);

        try {
            $order = $this->client->getOrder($account, $orderId);

            return response()->json([
                'success' => true,
                'order' => $order,
            ]);
        } catch (\Exception $e) {
            Log::error('Ошибка получения деталей заказа YM', ['account_id' => $account->id, 'order_id' => $orderId, 'error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Копировать карточки товаров из другого маркетплейса в Yandex Market
     *
     * POST /api/marketplace/yandex-market/accounts/{account}/copy-products
     * Body: { "source_account_id": 1, "product_ids": [1,2,3] }
     */
    public function copyProducts(Request $request, MarketplaceAccount $account): JsonResponse
    {
        $this->authorizeAccount($request, $account);

        $request->validate([
            'source_account_id' => ['required', 'exists:marketplace_accounts,id'],
            'product_ids' => ['nullable', 'array'],
            'product_ids.*' => ['integer'],
        ]);

        $sourceAccount = MarketplaceAccount::findOrFail($request->source_account_id);

        // Проверяем доступ к исходному аккаунту
        if ($sourceAccount->company_id !== $account->company_id) {
            return response()->json([
                'success' => false,
                'message' => 'Исходный и целевой аккаунты должны принадлежать одной компании',
            ], 403);
        }

        if ($account->marketplace !== 'ym') {
            return response()->json([
                'success' => false,
                'message' => 'Целевой аккаунт должен быть Yandex Market',
            ], 422);
        }

        if (! in_array($sourceAccount->marketplace, ['wb', 'ozon'])) {
            return response()->json([
                'success' => false,
                'message' => 'Копирование поддерживается из Wildberries и Ozon',
            ], 422);
        }

        try {
            $copyService = app(YandexMarketProductCopyService::class);

            $result = $copyService->copyFromAccount(
                $account,
                $sourceAccount,
                $request->input('product_ids', [])
            );

            $sourceName = match ($sourceAccount->marketplace) {
                'wb' => 'Wildberries',
                'ozon' => 'Ozon',
                default => $sourceAccount->marketplace,
            };

            return response()->json([
                'success' => true,
                'message' => "Копирование из {$sourceName} завершено: {$result['copied']} скопировано, {$result['skipped']} пропущено",
                'copied' => $result['copied'],
                'skipped' => $result['skipped'],
                'errors' => $result['errors'],
            ]);
        } catch (\Throwable $e) {
            Log::error('Ошибка копирования карточек в YM', [
                'ym_account_id' => $account->id,
                'source_account_id' => $sourceAccount->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка копирования: ' . $e->getMessage(),
            ], 500);
        }
    }
}
