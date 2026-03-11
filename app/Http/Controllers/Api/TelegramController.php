<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceAccount;
use App\Models\TelegramLinkCode;
use App\Models\User;
use App\Models\UserNotificationSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TelegramController extends Controller
{
    /**
     * Get Telegram connection status
     */
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'connected' => ! empty($user->telegram_id),
            'telegram_id' => $user->telegram_id,
            'telegram_username' => $user->telegram_username,
            'notifications_enabled' => $user->telegram_notifications_enabled,
        ]);
    }

    /**
     * Generate a new link code
     */
    public function generateLinkCode(Request $request): JsonResponse
    {
        $user = $request->user();
        $linkCode = TelegramLinkCode::generate($user->id);

        return response()->json([
            'code' => $linkCode->code,
            'expires_at' => $linkCode->expires_at->toIso8601String(),
            'instructions' => [
                'step_1' => 'Откройте Telegram и найдите бота @'.config('telegram.bot_username'),
                'step_2' => 'Отправьте команду: /link '.$linkCode->code,
                'step_3' => 'Дождитесь подтверждения',
            ],
        ]);
    }

    /**
     * Disconnect Telegram account
     */
    public function disconnect(Request $request): JsonResponse
    {
        $user = $request->user();

        $user->update([
            'telegram_id' => null,
            'telegram_username' => null,
        ]);

        return response()->json([
            'message' => 'Telegram аккаунт отключен',
        ]);
    }

    /**
     * Сгенерировать код привязки Telegram к аккаунту маркетплейса
     */
    public function generateAccountLinkCode(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'marketplace_account_id' => 'required|integer',
        ]);

        $user = $request->user();

        // Найти аккаунт и проверить владельца
        $account = MarketplaceAccount::findOrFail($validated['marketplace_account_id']);

        if ($account->user_id !== $user->id) {
            return response()->json(['message' => 'Доступ запрещён'], Response::HTTP_FORBIDDEN);
        }

        $linkCode = TelegramLinkCode::generateForAccount($user->id, $account->id);

        return response()->json([
            'code' => $linkCode->code,
            'expires_at' => $linkCode->expires_at->toIso8601String(),
            'bot_username' => config('telegram.bot_username'),
            'account_name' => $account->getDisplayName(),
        ]);
    }

    /**
     * Отключить Telegram от аккаунта маркетплейса
     */
    public function disconnectAccountTelegram(Request $request, int $accountId): JsonResponse
    {
        $user = $request->user();

        // Найти аккаунт и проверить владельца
        $account = MarketplaceAccount::findOrFail($accountId);

        if ($account->user_id !== $user->id) {
            return response()->json(['message' => 'Доступ запрещён'], Response::HTTP_FORBIDDEN);
        }

        $account->disconnectTelegram();

        return response()->json(['success' => true]);
    }

    /**
     * Получить статус привязки Telegram к аккаунту маркетплейса
     */
    public function getAccountTelegramStatus(Request $request, int $accountId): JsonResponse
    {
        $user = $request->user();

        // Найти аккаунт и проверить владельца
        $account = MarketplaceAccount::findOrFail($accountId);

        if ($account->user_id !== $user->id) {
            return response()->json(['message' => 'Доступ запрещён'], Response::HTTP_FORBIDDEN);
        }

        // Найти активный незарезервированный код для этого аккаунта
        $pendingCode = TelegramLinkCode::where('marketplace_account_id', $accountId)
            ->where('is_used', false)
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        return response()->json([
            'connected' => $account->isTelegramConnected(),
            'telegram_username' => $account->telegram_username,
            'pending_code_expires_at' => $pendingCode?->expires_at->toIso8601String(),
        ]);
    }

    /**
     * Update notification settings
     */
    public function updateSettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'telegram_notifications_enabled' => 'sometimes|boolean',
            'notify_low_stock' => 'sometimes|boolean',
            'notify_new_order' => 'sometimes|boolean',
            'notify_marketplace_order' => 'sometimes|boolean',
            'notify_offline_sale' => 'sometimes|boolean',
            'notify_order_cancelled' => 'sometimes|boolean',
            'notify_price_changes' => 'sometimes|boolean',
            'notify_bulk_operations' => 'sometimes|boolean',
            'notify_marketplace_sync' => 'sometimes|boolean',
            'notify_critical_errors' => 'sometimes|boolean',
            'channel_telegram' => 'sometimes|boolean',
            'channel_email' => 'sometimes|boolean',
            'channel_database' => 'sometimes|boolean',
            'low_stock_threshold' => 'sometimes|integer|min:1',
            'notify_only_business_hours' => 'sometimes|boolean',
            'business_hours_start' => 'sometimes|nullable|date_format:H:i',
            'business_hours_end' => 'sometimes|nullable|date_format:H:i',
        ]);

        $user = $request->user();

        // Update user telegram_notifications_enabled if provided
        if (isset($validated['telegram_notifications_enabled'])) {
            $user->update([
                'telegram_notifications_enabled' => $validated['telegram_notifications_enabled'],
            ]);
        }

        // Update or create notification settings
        $settings = $user->notificationSettings ?? new UserNotificationSetting(['user_id' => $user->id]);

        foreach ($validated as $key => $value) {
            if ($key !== 'telegram_notifications_enabled' && in_array($key, $settings->getFillable())) {
                $settings->{$key} = $value;
            }
        }

        $settings->save();

        return response()->json([
            'message' => 'Настройки обновлены',
            'settings' => $settings,
        ]);
    }

    /**
     * Get notification settings
     */
    public function getSettings(Request $request): JsonResponse
    {
        $user = $request->user();
        $settings = $user->notificationSettings;

        if (! $settings) {
            // Return defaults
            return response()->json([
                'notify_low_stock' => true,
                'notify_new_order' => true,
                'notify_marketplace_order' => true,
                'notify_offline_sale' => true,
                'notify_order_cancelled' => true,
                'notify_price_changes' => false,
                'notify_bulk_operations' => true,
                'notify_marketplace_sync' => true,
                'notify_critical_errors' => true,
                'channel_telegram' => true,
                'channel_email' => true,
                'channel_database' => true,
                'low_stock_threshold' => 10,
                'notify_only_business_hours' => false,
                'business_hours_start' => null,
                'business_hours_end' => null,
            ]);
        }

        return response()->json($settings);
    }
}
