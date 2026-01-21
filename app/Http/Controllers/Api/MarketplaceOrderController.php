<?php
// file: app/Http/Controllers/Api/MarketplaceOrderController.php

namespace App\Http\Controllers\Api;

use App\Helpers\CurrencyHelper;
use App\Http\Controllers\Controller;
use App\Models\MarketplaceAccount;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceShop;
use App\Services\Marketplaces\Wildberries\WildberriesHttpClient;
use App\Services\Marketplaces\Wildberries\WildberriesOrderService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MarketplaceOrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'marketplace_account_id' => ['nullable', 'exists:marketplace_accounts,id'],
            'company_id' => ['required', 'exists:companies,id'],
            'status' => ['nullable', 'string'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'shop_id' => ['nullable', 'string'],
        ]);

        if (!$request->user()->hasCompanyAccess($request->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        $account = null;
        if ($request->marketplace_account_id) {
            $account = MarketplaceAccount::find($request->marketplace_account_id);
        }

        // Загружаем заказы из соответствующих таблиц
        if ($account && $account->marketplace === 'wb') {
            $orders = $this->loadWbOrders($request, $account);
            return response()->json([
                'orders' => $orders,
                'meta' => ['total' => count($orders)],
            ]);
        }

        if ($account && $account->marketplace === 'uzum') {
            $orders = $this->loadUzumOrders($request, $account);
            return response()->json([
                'orders' => $orders,
                'meta' => ['total' => count($orders)],
            ]);
        }

        // Если маркетплейс не указан или не поддерживается
        return response()->json([
            'orders' => [],
            'meta' => ['total' => 0],
        ]);
    }

    public function show(Request $request, $orderId): JsonResponse
    {
        // Определяем маркетплейс по ID заказа
        $wbOrder = \App\Models\WbOrder::find($orderId);
        if ($wbOrder) {
            if (!$request->user()->hasCompanyAccess($wbOrder->account->company_id)) {
                return response()->json(['message' => 'Доступ запрещён.'], 403);
            }

            $wbOrder->load(['account', 'items']);

            // Извлекаем бренд и характеристики
            $brand = $wbOrder->raw_payload['brand'] ?? null;
            $characteristics = $wbOrder->raw_payload['characteristics'] ?? null;
            if (!$characteristics && isset($wbOrder->raw_payload['colorCode']) && !empty($wbOrder->raw_payload['colorCode'])) {
                $characteristics = 'Цвет: ' . $wbOrder->raw_payload['colorCode'];
            }

            $meta = array_filter([$brand, $wbOrder->article, $characteristics], function($value) {
                return !empty($value) && trim($value) !== '';
            });
            $metaString = implode(' - ', $meta);

            // Перевод статусов на русский
            $statusTranslations = [
                'new' => 'Новый',
                'in_assembly' => 'На сборке',
                'in_delivery' => 'В доставке',
                'completed' => 'Завершён',
                'canceled' => 'Отменён',
            ];

            $statusGroupTranslations = [
                'new' => 'Новые',
                'assembling' => 'На сборке',
                'shipping' => 'В доставке',
                'archive' => 'Архив',
                'canceled' => 'Отменённые',
            ];

            // Форматируем ответ с детальной информацией на русском
            return response()->json([
                'order' => [
                    // ОСНОВНАЯ ИНФОРМАЦИЯ
                    'id' => $wbOrder->id,
                    'Номер заказа' => $wbOrder->external_order_id,
                    'Фото товара' => $wbOrder->photo_url,
                    'Артикул' => $wbOrder->article,
                    'Название товара' => $wbOrder->product_name,
                    'Метаинформация' => $metaString,
                    'Бренд' => $brand,
                    'Характеристики' => $characteristics,

                    // ФИНАНСЫ
                    'Сумма заказа' => number_format($wbOrder->total_amount, 2, '.', ' ') . ' ' . $wbOrder->currency,
                    'Цена' => $wbOrder->price ? CurrencyHelper::formatPrice(CurrencyHelper::fromKopecks($wbOrder->price), $wbOrder->currency_code) : null,
                    'Цена сканирования' => $wbOrder->scan_price ? CurrencyHelper::formatPrice(CurrencyHelper::fromKopecks($wbOrder->scan_price), $wbOrder->currency_code) : null,
                    'Конвертированная цена' => $wbOrder->converted_price ? CurrencyHelper::formatPrice(CurrencyHelper::fromKopecks($wbOrder->converted_price), $wbOrder->converted_currency_code) : null,
                    'Валюта' => CurrencyHelper::getCurrencyName($wbOrder->currency_code) . ' (' . CurrencyHelper::getCurrencyCode($wbOrder->currency_code) . ')',
                    'Конвертированная валюта' => $wbOrder->converted_currency_code ? (CurrencyHelper::getCurrencyName($wbOrder->converted_currency_code) . ' (' . CurrencyHelper::getCurrencyCode($wbOrder->converted_currency_code) . ')') : null,

                    // ЛОГИСТИКА
                    'Поставка' => $wbOrder->supply_id,
                    'Склад' => $wbOrder->warehouse_id,
                    'Офис доставки' => $wbOrder->office,
                    'Тип доставки' => $wbOrder->wb_delivery_type === 'fbs' ? 'FBS (со склада продавца)' : $wbOrder->wb_delivery_type,
                    'Тип груза' => $wbOrder->cargo_type,

                    // СТАТУСЫ
                    'Статус' => $statusTranslations[$wbOrder->status] ?? $wbOrder->status,
                    'Группа статусов' => $statusGroupTranslations[$wbOrder->wb_status_group] ?? $wbOrder->wb_status_group,
                    'Статус WB' => $wbOrder->wb_status,
                    'Статус поставщика' => $wbOrder->wb_supplier_status,

                    // ТЕХНИЧЕСКИЕ ДАННЫЕ
                    'RID' => $wbOrder->rid,
                    'Order UID' => $wbOrder->order_uid,
                    'NM ID' => $wbOrder->nm_id,
                    'CHRT ID' => $wbOrder->chrt_id,
                    'SKU' => $wbOrder->sku,
                    'B2B заказ' => $wbOrder->is_b2b ? 'Да' : 'Нет',
                    'Нулевой заказ' => $wbOrder->is_zero_order ? 'Да' : 'Нет',

                    // ВРЕМЕННЫЕ МЕТКИ
                    'Дата заказа' => $wbOrder->ordered_at ? $wbOrder->ordered_at->format('d.m.Y H:i:s') : null,
                    'Время с момента заказа' => $wbOrder->time_elapsed,
                    'Дата доставки' => $wbOrder->delivered_at ? $wbOrder->delivered_at->format('d.m.Y H:i:s') : null,

                    // КЛИЕНТ (если есть)
                    'Имя клиента' => $wbOrder->customer_name,
                    'Телефон клиента' => $wbOrder->customer_phone,

                    // Товары
                    'Товары' => $wbOrder->items->map(function($item) {
                        return [
                            'Название' => $item->name,
                            'Артикул/SKU' => $item->external_offer_id,
                            'Количество' => $item->quantity,
                            'Цена' => number_format($item->price, 2, '.', ' ') . ' руб',
                            'Общая стоимость' => number_format($item->total_price, 2, '.', ' ') . ' руб',
                        ];
                    }),

                    // Сырые данные (для отладки)
                    '_raw' => [
                        'id' => $wbOrder->id,
                        'external_order_id' => $wbOrder->external_order_id,
                        'status' => $wbOrder->status,
                        'wb_status_group' => $wbOrder->wb_status_group,
                        'total_amount' => $wbOrder->total_amount,
                        'currency' => $wbOrder->currency,
                    ],
                ]
            ]);
        }

        $uzumOrder = \App\Models\UzumOrder::find($orderId);
        if ($uzumOrder) {
            if (!$request->user()->hasCompanyAccess($uzumOrder->account->company_id)) {
                return response()->json(['message' => 'Доступ запрещён.'], 403);
            }

            $uzumOrder->load(['account', 'items']);
            return response()->json(['order' => $uzumOrder]);
        }

        return response()->json(['message' => 'Заказ не найден.'], 404);
    }

    public function uzumShops(Request $request, MarketplaceAccount $account): JsonResponse
    {
        if (!$request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        $shops = MarketplaceShop::query()
            ->where('marketplace_account_id', $account->id)
            ->orderBy('name')
            ->get([
                'id',
                'external_id',
                'name',
                'raw_payload',
            ]);

        return response()->json([
            'shops' => $shops->map(function (MarketplaceShop $shop) {
                return [
                    'id' => $shop->external_id ?? (string) $shop->id,
                    'external_id' => $shop->external_id,
                    'name' => $shop->name,
                    'raw_payload' => $shop->raw_payload,
                ];
            }),
        ]);
    }

    public function stats(Request $request): JsonResponse
    {
        $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'marketplace_account_id' => ['nullable', 'exists:marketplace_accounts,id'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'shop_id' => ['nullable', 'string'],
        ]);

        if (!$request->user()->hasCompanyAccess($request->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        $account = null;
        if ($request->marketplace_account_id) {
            $account = MarketplaceAccount::find($request->marketplace_account_id);
        }

        if ($account && $account->marketplace === 'wb') {
            $stats = $this->statsWb($request, $account);
            return response()->json($stats);
        }

        if ($account && $account->marketplace === 'uzum') {
            $stats = $this->statsUzum($request, $account);
            return response()->json($stats);
        }

        // Если маркетплейс не указан или не поддерживается
        return response()->json([
            'total_orders' => 0,
            'total_amount' => 0,
            'by_status' => [],
        ])->header('Cache-Control', 'no-cache, no-store, must-revalidate')
          ->header('Pragma', 'no-cache')
          ->header('Expires', '0');
    }

    /**
     * Получить новые заказы FBS от Wildberries
     * GET /api/marketplace/orders/new
     */
    public function getNew(Request $request): JsonResponse
    {
        $request->validate([
            'marketplace_account_id' => ['required', 'exists:marketplace_accounts,id'],
        ]);

        $account = MarketplaceAccount::findOrFail($request->marketplace_account_id);

        if (!$request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        // Проверяем, что это Wildberries аккаунт
        if ($account->marketplace !== 'wb') {
            return response()->json([
                'message' => 'Получение новых заказов доступно только для Wildberries.',
            ], 422);
        }

        try {
            // Вызываем сервис для получения новых заказов
            $orderService = $this->getWbOrderService($account);
            $result = $orderService->fetchNewOrders($account);

            \Illuminate\Support\Facades\Log::info('New orders fetched via API', [
                'account_id' => $account->id,
                'user_id' => $request->user()->id,
                'result' => $result,
            ]);

            return response()->json([
                'message' => 'Новые заказы успешно получены',
                'synced' => $result['synced'],
                'created' => $result['created'],
                'errors' => $result['errors'],
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to fetch new orders', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Ошибка при получении новых заказов: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Получить стикеры для заказов
     * POST /api/marketplace/orders/stickers
     */
    public function getStickers(Request $request)
    {
        $request->validate([
            'marketplace_account_id' => ['required', 'exists:marketplace_accounts,id'],
            'order_ids' => ['required', 'array', 'min:1', 'max:100'],
            'order_ids.*' => ['required'],
            'type' => ['nullable', 'in:png,svg,zplv,zplh'],
            'width' => ['nullable', 'integer', 'min:20', 'max:200'],
            'height' => ['nullable', 'integer', 'min:20', 'max:200'],
            'size' => ['nullable', 'string', 'in:LARGE,BIG,large,big'],
        ]);

        $account = MarketplaceAccount::findOrFail($request->marketplace_account_id);

        if (!$request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        if ($account->marketplace === 'wb') {
            // Проверяем наличие токена
            if (empty($account->getWbToken('marketplace'))) {
                return response()->json([
                    'message' => 'API токен Wildberries не настроен. Пожалуйста, добавьте токен в настройках аккаунта.',
                    'action' => 'configure_token',
                ], 422);
            }

            return $this->generateWbStickers($request, $account);
        }

        if ($account->marketplace === 'uzum') {
            return $this->generateUzumLabels($request, $account);
        }

        return response()->json([
            'message' => 'Печать стикеров для этого маркетплейса не поддерживается.',
        ], 422);
    }

    protected function generateWbStickers(Request $request, MarketplaceAccount $account)
    {
        try {
            $orderService = $this->getWbOrderService($account);

            $type = $request->type ?? 'png';
            $width = $request->width ?? 58;
            $height = $request->height ?? 40;

            // Конвертируем order_ids в массив целых чисел
            $orderIds = array_map('intval', $request->order_ids);

            $binaryContent = $orderService->getOrdersStickers(
                $account,
                $orderIds,
                $type,
                $width,
                $height
            );

            $filename = "stickers_" . md5(implode('_', $orderIds)) . "_{$type}." . ($type === 'zplv' || $type === 'zplh' ? 'zpl' : $type);
            $path = "stickers/orders/{$account->id}/{$filename}";

            \Storage::disk('public')->put($path, $binaryContent);

            // Стикеры для WB не сохраняем в БД (используем raw_payload)
            \Illuminate\Support\Facades\Log::info('Order stickers generated', [
                'account_id' => $account->id,
                'user_id' => $request->user()->id,
                'orders_count' => count($orderIds),
                'order_ids' => $orderIds,
                'type' => $type,
                'file_size' => strlen($binaryContent),
            ]);

            return response()->json([
                'message' => 'Стикеры успешно сгенерированы',
                'stickers' => [[
                    'path' => $path,
                    'url' => \Storage::disk('public')->url($path),
                    'orders_count' => count($orderIds),
                ]],
                'count' => 1,
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to generate order stickers', [
                'account_id' => $account->id,
                'order_ids' => $request->order_ids,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Ошибка при генерации стикеров: ' . $e->getMessage(),
            ], 500);
        }
    }

    protected function generateUzumLabels(Request $request, MarketplaceAccount $account)
    {
        try {
            $size = strtoupper($request->input('size', 'LARGE'));
            if (!in_array($size, ['LARGE', 'BIG'])) {
                return response()->json(['message' => 'Размер этикетки должен быть LARGE или BIG'], 422);
            }

            $client = app(\App\Services\Marketplaces\UzumClient::class);

            $stickers = [];

            foreach ($request->order_ids as $orderId) {
                $label = $client->getOrderLabel($account, (string)$orderId, $size);
                if (!$label) {
                    continue;
                }
                $filename = "uzum_label_{$orderId}_{$size}.pdf";
                $path = "stickers/orders/{$account->id}/{$filename}";
                \Storage::disk('public')->put($path, $label['binary']);

                $stickers[] = [
                    'path' => $path,
                    'url' => \Storage::disk('public')->url($path),
                    'base64' => $label['base64'] ?? null,
                    'orders_count' => 1,
                ];
            }

            if (empty($stickers)) {
                return response()->json(['message' => 'Не удалось получить этикетки Uzum'], 422);
            }

            return response()->json([
                'message' => 'Этикетки Uzum успешно получены',
                'stickers' => $stickers,
                'count' => count($stickers),
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Failed to generate Uzum labels', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Ошибка при генерации этикеток Uzum: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Подтвердить заказ Uzum
     */
    public function confirm(Request $request, $orderId): JsonResponse
    {
        // Ищем заказ в uzum_orders
        $order = \App\Models\UzumOrder::find($orderId);

        if (!$order) {
            return response()->json(['message' => 'Заказ не найден.'], 404);
        }

        $account = $order->account;

        if (!$account) {
            return response()->json(['message' => 'Аккаунт маркетплейса не найден.'], 404);
        }

        if (!$request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        try {
            $client = app(\App\Services\Marketplaces\UzumClient::class);
            $data = $client->confirmOrder($account, $order->external_order_id);
            if (!$data) {
                return response()->json(['message' => 'Не удалось подтвердить заказ Uzum'], 422);
            }

            $orderedAt = $data['ordered_at'] ?? $order->ordered_at;
            $orderedAtParsed = $this->parseUzumTimestamp($orderedAt) ?? $order->ordered_at;

            // Обновляем заказ
            $order->update([
                'status' => $data['status'] ?? $order->status,
                'status_normalized' => $data['status_normalized'] ?? $data['status'] ?? $order->status,
                'raw_payload' => $data['raw_payload'] ?? $order->raw_payload,
                'ordered_at' => $orderedAtParsed,
                'total_amount' => $data['total_amount'] ?? $order->total_amount,
            ]);

            // Обновляем позиции
            if (!empty($data['items'])) {
                $order->items()->delete();
                foreach ($data['items'] as $item) {
                    $order->items()->create([
                        'external_offer_id' => $item['external_offer_id'] ?? null,
                        'name' => $item['name'] ?? null,
                        'quantity' => $item['quantity'] ?? 1,
                        'price' => $item['price'] ?? null,
                        'total_price' => $item['total_price'] ?? null,
                        'raw_payload' => $item['raw_payload'] ?? null,
                    ]);
                }
            }

            return response()->json([
                'message' => 'Заказ подтверждён',
                'order' => $order->fresh(['items']),
            ]);
        } catch (\Throwable $e) {
            \Log::error('Failed to confirm Uzum order', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'message' => 'Ошибка при подтверждении заказа: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Отменить заказ WB
     */
    public function cancel(Request $request, $orderId): JsonResponse
    {
        // Ищем заказ в wb_orders
        $order = \App\Models\WbOrder::find($orderId);

        if (!$order) {
            return response()->json(['message' => 'Заказ не найден.'], 404);
        }

        $account = $order->account;

        if (!$account) {
            return response()->json(['message' => 'Аккаунт маркетплейса не найден.'], 404);
        }

        // Проверка доступа
        if (!$request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        // Проверка, что заказ не в финальном статусе
        if (in_array($order->status, ['completed', 'cancelled'])) {
            return response()->json([
                'message' => 'Невозможно отменить заказ в статусе ' . $order->status,
            ], 422);
        }

        // Проверка наличия external_order_id
        if (!$order->external_order_id) {
            return response()->json([
                'message' => 'У заказа отсутствует внешний ID',
            ], 422);
        }

        try {
            $orderService = $this->getWbOrderService($account);

            $result = $orderService->cancelOrder(
                $account,
                (int) $order->external_order_id
            );

            // Обновляем заказ в БД
            $order->update([
                'status' => 'cancelled',
            ]);

            \Illuminate\Support\Facades\Log::info('Order canceled via API', [
                'order_id' => $order->id,
                'external_order_id' => $order->external_order_id,
                'account_id' => $account->id,
                'user_id' => $request->user()->id,
            ]);

            return response()->json([
                'message' => 'Заказ успешно отменён',
                'order' => $order->fresh(),
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to cancel order', [
                'order_id' => $order->id,
                'external_order_id' => $order->external_order_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Ошибка при отмене заказа: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Parse Uzum timestamp (seconds/ms; sometimes 14+ digits) to Carbon
     */
    private function parseUzumTimestamp($value): ?\DateTimeInterface
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            $str = (string) $value;
            // Trim overlong ms timestamps to 13 digits
            if (strlen($str) > 13) {
                $str = substr($str, 0, 13);
            }
            $num = (int) $str;
            try {
                return $num > 1e12
                    ? \Carbon\Carbon::createFromTimestampMs($num)
                    : \Carbon\Carbon::createFromTimestamp($num);
            } catch (\Throwable $e) {
                return null;
            }
        }

        try {
            return \Carbon\Carbon::parse((string) $value);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Сервис WB заказов с привязкой к конкретному аккаунту
     */
    private function getWbOrderService(MarketplaceAccount $account): WildberriesOrderService
    {
        $client = new WildberriesHttpClient($account);
        return new WildberriesOrderService($client);
    }

    /**
     * Подгрузка WB FBS заказов из таблицы wb_orders (Marketplace API)
     */
    private function loadWbOrders(Request $request, MarketplaceAccount $account): array
    {
        // FBS заказы из Marketplace API хранятся в wb_orders
        $query = \App\Models\WbOrder::query()->where('marketplace_account_id', $account->id);

        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->from) {
            $query->where('ordered_at', '>=', Carbon::parse($request->from)->startOfDay());
        }
        if ($request->to) {
            $query->where('ordered_at', '<=', Carbon::parse($request->to)->endOfDay());
        }

        $orders = $query->with('items')->orderByDesc('ordered_at')->limit(1000)->get();

        return $orders->map(function ($o) {
            // Получаем данные из raw_payload
            $rawPayload = $o->raw_payload ?? [];
            $brand = $rawPayload['brand'] ?? null;
            $productName = $o->product_name ?? $rawPayload['subject'] ?? null;
            $characteristics = $rawPayload['techSize'] ?? $rawPayload['colorCode'] ?? null;

            // Формируем метаинформацию
            $meta = array_filter([$brand, $o->article, $characteristics], function($value) {
                return !empty($value) && trim($value) !== '';
            });
            $metaString = implode(' - ', $meta);

            return [
                // Основные поля для списка
                'id' => $o->id,
                'marketplace_account_id' => $o->marketplace_account_id,
                'external_order_id' => $o->external_order_id,

                // Информация о товаре
                'photo_url' => $o->photo_url,
                'article' => $o->article,
                'product_name' => $productName,
                'meta_info' => $metaString,
                'brand' => $brand,
                'characteristics' => $characteristics,

                // Идентификаторы
                'nm_id' => $o->nm_id,
                'chrt_id' => $o->chrt_id,
                'sku' => $o->sku,

                // Статусы
                'status' => $o->status,
                'status_normalized' => $o->status_normalized ?? $o->status,
                'wb_status' => $o->wb_status,
                'wb_status_group' => $o->wb_status_group,
                'wb_supplier_status' => $o->wb_supplier_status,
                'wb_delivery_type' => $o->wb_delivery_type,

                // Логистика
                'supply_id' => $o->supply_id,
                'tare_id' => $o->tare_id,
                'warehouse_id' => $o->warehouse_id,
                'office' => $o->office,
                'cargo_type' => $o->cargo_type,

                // Финансы
                'total_amount' => $o->total_amount,
                'price' => $o->price,
                'scan_price' => $o->scan_price,
                'converted_price' => $o->converted_price,
                'currency' => $o->currency ?? 'RUB',
                'currency_code' => $o->currency_code,
                'converted_currency_code' => $o->converted_currency_code,

                // Флаги
                'is_b2b' => $o->is_b2b,
                'is_zero_order' => $o->is_zero_order,

                // Время
                'ordered_at' => $o->ordered_at,
                'delivered_at' => $o->delivered_at,
                'time_elapsed' => $o->time_elapsed,

                // Клиент
                'customer_name' => $o->customer_name,
                'customer_phone' => $o->customer_phone,

                // Сырые данные
                'raw_payload' => $o->raw_payload,

                // Позиции заказа
                'items' => $o->items->map(fn($item) => [
                    'id' => $item->id,
                    'external_offer_id' => $item->external_offer_id,
                    'name' => $item->name,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'total_price' => $item->total_price,
                ])->toArray(),
            ];
        })->all();
    }

    /**
     * Подгрузка Uzum заказов из новой таблицы
     */
    private function loadUzumOrders(Request $request, MarketplaceAccount $account): array
    {
        $query = \App\Models\UzumOrder::query()->where('marketplace_account_id', $account->id);

        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->from) {
            $query->where('ordered_at', '>=', Carbon::parse($request->from)->startOfDay());
        }
        if ($request->to) {
            $query->where('ordered_at', '<=', Carbon::parse($request->to)->endOfDay());
        }
        if ($request->shop_id) {
            $shopIds = collect(explode(',', $request->shop_id))->filter()->map(fn($v) => trim($v))->all();
            $query->whereIn('shop_id', $shopIds);
        }

        $orders = $query->orderByDesc('ordered_at')->limit(1000)->get();

        return $orders->map(function ($o) {
            return [
                'id' => $o->id,
                'marketplace_account_id' => $o->marketplace_account_id,
                'external_order_id' => $o->external_order_id,
                'status' => $o->status,
                'status_normalized' => $o->status_normalized,
                'total_amount' => $o->total_amount,
                'currency' => $o->currency,
                'ordered_at' => $o->ordered_at,
                'raw_payload' => $o->raw_payload,
            ];
        })->all();
    }

    private function statsWb(Request $request, MarketplaceAccount $account): array
    {
        $query = \App\Models\WbOrder::query()->where('marketplace_account_id', $account->id);
        if ($request->from) {
            $query->where('ordered_at', '>=', Carbon::parse($request->from)->startOfDay());
        }
        if ($request->to) {
            $query->where('ordered_at', '<=', Carbon::parse($request->to)->endOfDay());
        }

        $total = $query->count();
        if ($total === 0) {
            return [
                'total_orders' => 0,
                'total_amount' => 0,
                'by_status' => [
                    'new' => 0,
                    'in_assembly' => 0,
                    'in_delivery' => 0,
                    'completed' => 0,
                    'cancelled' => 0,
                ],
            ];
        }

        $byStatus = $query->selectRaw('
            SUM(CASE WHEN status = "new" THEN 1 ELSE 0 END) as new_count,
            SUM(CASE WHEN status = "in_assembly" THEN 1 ELSE 0 END) as in_assembly_count,
            SUM(CASE WHEN status = "in_delivery" THEN 1 ELSE 0 END) as in_delivery_count,
            SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed_count,
            SUM(CASE WHEN status = "cancelled" THEN 1 ELSE 0 END) as cancelled_count
        ')->first();

        return [
            'total_orders' => $total,
            'total_amount' => $query->sum('total_amount'),
            'by_status' => [
                'new' => (int) ($byStatus->new_count ?? 0),
                'in_assembly' => (int) ($byStatus->in_assembly_count ?? 0),
                'in_delivery' => (int) ($byStatus->in_delivery_count ?? 0),
                'completed' => (int) ($byStatus->completed_count ?? 0),
                'cancelled' => (int) ($byStatus->cancelled_count ?? 0),
            ],
        ];
    }

    private function statsUzum(Request $request, MarketplaceAccount $account): array
    {
        $query = \App\Models\UzumOrder::query()->where('marketplace_account_id', $account->id);
        if ($request->from) {
            $query->where('ordered_at', '>=', Carbon::parse($request->from)->startOfDay());
        }
        if ($request->to) {
            $query->where('ordered_at', '<=', Carbon::parse($request->to)->endOfDay());
        }
        if ($request->shop_id) {
            $shopIds = collect(explode(',', $request->shop_id))->filter()->map(fn($v) => trim($v))->all();
            $query->whereIn('shop_id', $shopIds);
        }

        $total = $query->count();
        if ($total === 0) {
            return [
                'total_orders' => 0,
                'total_amount' => 0,
                'by_status' => [
                    'new' => 0,
                    'in_assembly' => 0,
                    'in_supply' => 0,
                    'accepted_uzum' => 0,
                    'waiting_pickup' => 0,
                    'issued' => 0,
                    'cancelled' => 0,
                    'returns' => 0,
                ],
            ];
        }

        $byStatus = $query->selectRaw('
            SUM(CASE WHEN status = "new" THEN 1 ELSE 0 END) as new_count,
            SUM(CASE WHEN status = "in_assembly" THEN 1 ELSE 0 END) as in_assembly_count,
            SUM(CASE WHEN status = "in_supply" THEN 1 ELSE 0 END) as in_supply_count,
            SUM(CASE WHEN status = "accepted_uzum" THEN 1 ELSE 0 END) as accepted_uzum_count,
            SUM(CASE WHEN status = "waiting_pickup" THEN 1 ELSE 0 END) as waiting_pickup_count,
            SUM(CASE WHEN status = "issued" THEN 1 ELSE 0 END) as issued_count,
            SUM(CASE WHEN status = "cancelled" THEN 1 ELSE 0 END) as cancelled_count,
            SUM(CASE WHEN status = "returns" THEN 1 ELSE 0 END) as returns_count
        ')->first();

        return [
            'total_orders' => $total,
            'total_amount' => $query->sum('total_amount'),
            'by_status' => [
                'new' => (int) ($byStatus->new_count ?? 0),
                'in_assembly' => (int) ($byStatus->in_assembly_count ?? 0),
                'in_supply' => (int) ($byStatus->in_supply_count ?? 0),
                'accepted_uzum' => (int) ($byStatus->accepted_uzum_count ?? 0),
                'waiting_pickup' => (int) ($byStatus->waiting_pickup_count ?? 0),
                'issued' => (int) ($byStatus->issued_count ?? 0),
                'cancelled' => (int) ($byStatus->cancelled_count ?? 0),
                'returns' => (int) ($byStatus->returns_count ?? 0),
            ],
        ];
    }
}
