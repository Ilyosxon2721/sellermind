<?php

namespace App\Jobs;

use App\Models\OrderConfirmLog;
use App\Models\UzumShop;
use App\Services\UzumSellerApi;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AutoConfirmFbsOrders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 10;

    public function handle(): void
    {
        // Получаем все магазины с включённым авто-подтверждением
        $shops = UzumShop::where('auto_confirm_enabled', true)
            ->whereNotNull('api_token')
            ->get();

        if ($shops->isEmpty()) {
            Log::info('AutoConfirm: нет магазинов с включённым авто-подтверждением');

            return;
        }

        // Группируем по токену (у одного seller может быть несколько магазинов)
        $grouped = $shops->groupBy('api_token');

        foreach ($grouped as $token => $tokenShops) {
            $this->processShops($token, $tokenShops);
        }
    }

    protected function processShops(string $token, $shops): void
    {
        $api = UzumSellerApi::forToken($token);
        $shopIds = $shops->pluck('uzum_shop_id')->toArray();

        Log::info('AutoConfirm: проверяю заказы', [
            'shop_ids' => $shopIds,
        ]);

        // Получаем все новые заказы (CREATED)
        $page = 0;
        $totalConfirmed = 0;
        $totalFailed = 0;

        do {
            $result = $api->getFbsOrders(
                shopIds: $shopIds,
                status: 'CREATED',
                page: $page,
                size: 50
            );

            if (! $result['success']) {
                Log::error('AutoConfirm: ошибка получения заказов', [
                    'error' => $result['error'] ?? 'unknown',
                ]);
                break;
            }

            $orders = $result['data']['payload']['sellerOrders'] ?? [];

            if (empty($orders)) {
                break;
            }

            foreach ($orders as $order) {
                $orderId = $order['id'] ?? null;

                if (! $orderId) {
                    continue;
                }

                $confirmResult = $this->confirmOrder($api, $orderId, $shops);

                if ($confirmResult) {
                    $totalConfirmed++;
                } else {
                    $totalFailed++;
                }

                // Небольшая пауза между запросами (rate limit)
                usleep(200_000); // 200ms
            }

            $page++;

            // Защита от бесконечного цикла
            if ($page > 20) {
                break;
            }

        } while (! empty($orders));

        Log::info('AutoConfirm: завершено', [
            'confirmed' => $totalConfirmed,
            'failed' => $totalFailed,
            'shop_ids' => $shopIds,
        ]);
    }

    protected function confirmOrder(UzumSellerApi $api, int $orderId, $shops): bool
    {
        $result = $api->confirmOrder($orderId);

        // Логируем в БД
        OrderConfirmLog::create([
            'uzum_order_id' => $orderId,
            'status' => $result['success'] ? 'confirmed' : 'failed',
            'error_message' => $result['success'] ? null : ($result['error'] ?? 'Unknown error'),
            'error_code' => $result['code'] ?? null,
            'confirmed_at' => $result['success'] ? now() : null,
        ]);

        if ($result['success']) {
            Log::info("AutoConfirm: заказ #{$orderId} подтверждён");
        } else {
            $errorCode = $result['code'] ?? '';

            // Специфичные ошибки, после которых не нужно повторять
            $skipErrors = [
                'seller-order-01', // заказ не найден
                'seller-order-02', // неподходящий статус
                'seller-order-03', // истёк срок
            ];

            if (in_array($errorCode, $skipErrors)) {
                Log::warning("AutoConfirm: заказ #{$orderId} — пропускаем ({$errorCode})");
            } else {
                Log::error("AutoConfirm: заказ #{$orderId} — ошибка", [
                    'error' => $result['error'] ?? 'unknown',
                    'code' => $errorCode,
                ]);
            }
        }

        return $result['success'];
    }
}
