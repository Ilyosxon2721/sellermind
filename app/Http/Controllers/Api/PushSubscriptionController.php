<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PushSubscribeRequest;
use App\Http\Requests\PushUnsubscribeRequest;
use App\Services\PushNotificationService;
use Illuminate\Http\JsonResponse;

/**
 * Контроллер для управления Web Push подписками
 */
final class PushSubscriptionController extends Controller
{
    public function __construct(
        private readonly PushNotificationService $pushService,
    ) {}

    /**
     * Подписаться на push уведомления
     *
     * POST /api/push/subscribe
     */
    public function subscribe(PushSubscribeRequest $request): JsonResponse
    {
        if (! $this->pushService->hasVapidKeys()) {
            return response()->json([
                'success' => false,
                'message' => 'VAPID ключи не настроены на сервере',
            ], 500);
        }

        $user = $request->user();
        $subscription = $this->pushService->subscribe($user, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Подписка на уведомления активирована',
            'subscription_id' => $subscription->id,
        ], 201);
    }

    /**
     * Отписаться от push уведомлений
     *
     * POST /api/push/unsubscribe
     */
    public function unsubscribe(PushUnsubscribeRequest $request): JsonResponse
    {
        $endpoint = $request->validated()['endpoint'];
        $result = $this->pushService->unsubscribe($endpoint);

        if (! $result) {
            return response()->json([
                'success' => false,
                'message' => 'Подписка не найдена',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Подписка на уведомления отключена',
        ]);
    }

    /**
     * Получить публичный VAPID ключ
     *
     * GET /api/push/vapid-public-key
     */
    public function getVapidPublicKey(): JsonResponse
    {
        $publicKey = $this->pushService->getVapidPublicKey();

        if (empty($publicKey)) {
            return response()->json([
                'success' => false,
                'message' => 'VAPID ключи не настроены',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'public_key' => $publicKey,
        ]);
    }
}
