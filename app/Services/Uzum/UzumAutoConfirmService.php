<?php

declare(strict_types=1);

namespace App\Services\Uzum;

use App\Models\MarketplaceAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class UzumAutoConfirmService
{
    /**
     * Автоматически подтвердить новые заказы для аккаунта
     *
     * @return array{confirmed: int, failed: int, skipped: int}
     */
    public function processAccount(MarketplaceAccount $account): array
    {
        $stats = ['confirmed' => 0, 'failed' => 0, 'skipped' => 0];
        $token = $account->uzum_access_token ?? $account->oauth_token;

        if (! $token) {
            Log::warning("UzumAutoConfirm: нет токена для аккаунта #{$account->id}");

            return $stats;
        }

        // Получаем shop_ids привязанных магазинов
        $shopIds = DB::table('marketplace_shops')
            ->where('marketplace_account_id', $account->id)
            ->pluck('external_id')
            ->map(fn ($id) => (int) $id)
            ->toArray();

        if (empty($shopIds)) {
            Log::info("UzumAutoConfirm: нет магазинов для аккаунта #{$account->id}");

            return $stats;
        }

        $page = 0;
        do {
            $response = Http::withToken($token)->timeout(30)->get(
                'https://api-seller.uzum.uz/api/seller-openapi/v2/fbs/orders',
                [
                    'shopIds' => $shopIds,
                    'status' => 'CREATED',
                    'page' => $page,
                    'size' => 50,
                ]
            );

            if (! $response->successful()) {
                break;
            }

            $orders = $response->json('payload.sellerOrders', []);
            if (empty($orders)) {
                break;
            }

            foreach ($orders as $order) {
                $orderId = $order['id'] ?? null;
                if (! $orderId) {
                    continue;
                }

                // Отправляем запрос на подтверждение заказа
                $confirmResponse = Http::withToken($token)->timeout(30)
                    ->post("https://api-seller.uzum.uz/api/seller-openapi/v1/fbs/order/{$orderId}/confirm");

                $success = $confirmResponse->successful();

                DB::table('uzum_order_confirm_logs')->insert([
                    'marketplace_account_id' => $account->id,
                    'uzum_order_id' => $orderId,
                    'status' => $success ? 'confirmed' : 'failed',
                    'error_message' => $success ? null : $confirmResponse->body(),
                    'error_code' => $success ? null : $confirmResponse->json('code'),
                    'confirmed_at' => $success ? now() : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $stats[$success ? 'confirmed' : 'failed']++;
                usleep(200_000); // Пауза 0.2 сек между запросами
            }

            $page++;
            if ($page > 20) {
                break;
            }
        } while (! empty($orders));

        Log::info("UzumAutoConfirm: аккаунт #{$account->id}", $stats);

        return $stats;
    }
}
