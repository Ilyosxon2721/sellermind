<?php

namespace App\Http\Controllers\Api\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceAccount;
use App\Services\Marketplaces\YandexMarket\YandexMarketClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
        ]);
        
        // Use getDecryptedCredentials() to get array, not the encrypted string
        $credentials = $account->getDecryptedCredentials();
        
        // Only update api_key if it's a real token (not masked value)
        $apiKey = $validated['api_key'] ?? '';
        if (!empty($apiKey) && !str_starts_with($apiKey, '***') && !str_contains($apiKey, '...')) {
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
        
        if (!$request->user()->hasCompanyAccess($account->company_id)) {
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
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
