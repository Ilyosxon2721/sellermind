<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TelegramLinkCode;
use App\Models\User;
use App\Models\UserNotificationSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TelegramController extends Controller
{
    /**
     * Get Telegram connection status
     */
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'connected' => !empty($user->telegram_id),
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
                'step_1' => 'Откройте Telegram и найдите бота @' . config('telegram.bot_username'),
                'step_2' => 'Отправьте команду: /link ' . $linkCode->code,
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
     * Update notification settings
     */
    public function updateSettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'telegram_notifications_enabled' => 'sometimes|boolean',
            'notify_low_stock' => 'sometimes|boolean',
            'notify_new_order' => 'sometimes|boolean',
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

        if (!$settings) {
            // Return defaults
            return response()->json([
                'notify_low_stock' => true,
                'notify_new_order' => true,
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
