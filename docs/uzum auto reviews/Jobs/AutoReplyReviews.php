<?php

namespace App\Jobs;

use App\Models\UzumShop;
use App\Services\UzumSellerApi;
use App\Services\UzumSellerAuth;
use App\Services\ReviewAutoResponder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AutoReplyReviews implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $backoff = 30;

    public function handle(): void
    {
        $shops = UzumShop::where('auto_reply_enabled', true)
            ->where(function ($q) {
                $q->whereNotNull('session_token')
                  ->orWhereNotNull('seller_email');
            })
            ->get();

        if ($shops->isEmpty()) {
            Log::info('AutoReplyReviews: нет магазинов с включённым авто-ответом');
            return;
        }

        $auth = new UzumSellerAuth();

        foreach ($shops as $shop) {
            try {
                // Получаем валидный session token (auto-refresh)
                $token = $auth->getValidToken($shop);

                if (!$token) {
                    Log::error("AutoReplyReviews: нет токена для магазина #{$shop->uzum_shop_id}");
                    continue;
                }

                $api = UzumSellerApi::forToken($token);
                $responder = new ReviewAutoResponder($api);

                $stats = $responder->processShop($shop);

                Log::info("AutoReplyReviews: магазин #{$shop->uzum_shop_id}", $stats);

            } catch (\Throwable $e) {
                Log::error("AutoReplyReviews: ошибка для магазина #{$shop->uzum_shop_id}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
