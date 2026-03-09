<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

/**
 * Сервис для отправки Web Push уведомлений
 */
final class PushNotificationService
{
    private ?WebPush $webPush = null;

    /**
     * Получить экземпляр WebPush с настройками VAPID
     */
    private function getWebPush(): WebPush
    {
        if ($this->webPush === null) {
            $auth = [
                'VAPID' => [
                    'subject' => config('services.vapid.subject'),
                    'publicKey' => config('services.vapid.public_key'),
                    'privateKey' => config('services.vapid.private_key'),
                ],
            ];

            $this->webPush = new WebPush($auth);
            $this->webPush->setAutomaticPadding(true);
        }

        return $this->webPush;
    }

    /**
     * Сохранить подписку пользователя
     *
     * @param  array<string, mixed>  $data  Данные подписки от браузера
     */
    public function subscribe(User $user, array $data): PushSubscription
    {
        $endpoint = $data['endpoint'];
        $keys = $data['keys'] ?? [];

        // Проверяем, существует ли уже подписка с таким endpoint
        $existing = PushSubscription::findByEndpoint($endpoint);

        if ($existing !== null) {
            // Обновляем существующую подписку
            $existing->update([
                'user_id' => $user->id,
                'public_key' => $keys['p256dh'] ?? '',
                'auth_token' => $keys['auth'] ?? '',
                'content_encoding' => $data['contentEncoding'] ?? 'aesgcm',
            ]);

            Log::info('Push подписка обновлена', [
                'user_id' => $user->id,
                'endpoint' => substr($endpoint, 0, 50).'...',
            ]);

            return $existing;
        }

        // Создаем новую подписку
        $subscription = PushSubscription::create([
            'user_id' => $user->id,
            'endpoint' => $endpoint,
            'public_key' => $keys['p256dh'] ?? '',
            'auth_token' => $keys['auth'] ?? '',
            'content_encoding' => $data['contentEncoding'] ?? 'aesgcm',
        ]);

        Log::info('Push подписка создана', [
            'user_id' => $user->id,
            'subscription_id' => $subscription->id,
        ]);

        return $subscription;
    }

    /**
     * Удалить подписку по endpoint
     */
    public function unsubscribe(string $endpoint): bool
    {
        $subscription = PushSubscription::findByEndpoint($endpoint);

        if ($subscription === null) {
            Log::warning('Push подписка не найдена для удаления', [
                'endpoint' => substr($endpoint, 0, 50).'...',
            ]);

            return false;
        }

        $userId = $subscription->user_id;
        $subscription->delete();

        Log::info('Push подписка удалена', [
            'user_id' => $userId,
            'endpoint' => substr($endpoint, 0, 50).'...',
        ]);

        return true;
    }

    /**
     * Отправить push уведомление конкретному пользователю
     *
     * @param  array<string, mixed>  $data  Дополнительные данные
     * @return array<string, mixed> Результат отправки
     */
    public function sendToUser(User $user, string $title, string $body, array $data = []): array
    {
        $subscriptions = PushSubscription::getByUser($user->id);

        if ($subscriptions->isEmpty()) {
            Log::info('У пользователя нет push подписок', ['user_id' => $user->id]);

            return [
                'success' => false,
                'sent' => 0,
                'failed' => 0,
                'message' => 'Нет активных подписок',
            ];
        }

        return $this->sendToSubscriptions($subscriptions, $title, $body, $data);
    }

    /**
     * Отправить push уведомление всем пользователям
     *
     * @param  array<string, mixed>  $data  Дополнительные данные
     * @return array<string, mixed> Результат отправки
     */
    public function sendToAll(string $title, string $body, array $data = []): array
    {
        $subscriptions = PushSubscription::all();

        if ($subscriptions->isEmpty()) {
            Log::info('Нет подписок для массовой отправки');

            return [
                'success' => false,
                'sent' => 0,
                'failed' => 0,
                'message' => 'Нет активных подписок',
            ];
        }

        return $this->sendToSubscriptions($subscriptions, $title, $body, $data);
    }

    /**
     * Отправить уведомления на указанные подписки
     *
     * @param  \Illuminate\Database\Eloquent\Collection<int, PushSubscription>  $subscriptions
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function sendToSubscriptions(
        \Illuminate\Database\Eloquent\Collection $subscriptions,
        string $title,
        string $body,
        array $data = []
    ): array {
        $webPush = $this->getWebPush();

        $payload = json_encode([
            'title' => $title,
            'body' => $body,
            'icon' => $data['icon'] ?? '/images/icons/icon-192x192.png',
            'badge' => $data['badge'] ?? '/images/icons/badge-72x72.png',
            'data' => $data,
            'timestamp' => now()->timestamp * 1000,
        ]);

        // Формируем очередь отправки
        foreach ($subscriptions as $pushSubscription) {
            $subscription = Subscription::create($pushSubscription->toWebPushFormat());
            $webPush->queueNotification($subscription, $payload);
        }

        // Отправляем все уведомления
        $sent = 0;
        $failed = 0;
        $expiredEndpoints = [];

        foreach ($webPush->flush() as $report) {
            $endpoint = $report->getRequest()->getUri()->__toString();

            if ($report->isSuccess()) {
                $sent++;
            } else {
                $failed++;
                $reason = $report->getReason();

                Log::warning('Ошибка отправки push уведомления', [
                    'endpoint' => substr($endpoint, 0, 50).'...',
                    'reason' => $reason,
                ]);

                // Если подписка истекла или недействительна - удаляем
                if ($report->isSubscriptionExpired()) {
                    $expiredEndpoints[] = $endpoint;
                }
            }
        }

        // Удаляем недействительные подписки
        if (! empty($expiredEndpoints)) {
            PushSubscription::whereIn('endpoint', $expiredEndpoints)->delete();

            Log::info('Удалены недействительные подписки', [
                'count' => count($expiredEndpoints),
            ]);
        }

        return [
            'success' => $sent > 0,
            'sent' => $sent,
            'failed' => $failed,
            'expired_removed' => count($expiredEndpoints),
        ];
    }

    /**
     * Получить публичный VAPID ключ
     */
    public function getVapidPublicKey(): ?string
    {
        return config('services.vapid.public_key');
    }

    /**
     * Проверить наличие VAPID ключей
     */
    public function hasVapidKeys(): bool
    {
        $publicKey = config('services.vapid.public_key');
        $privateKey = config('services.vapid.private_key');

        return ! empty($publicKey) && ! empty($privateKey);
    }
}
