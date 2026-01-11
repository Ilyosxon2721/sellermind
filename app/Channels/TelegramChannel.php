<?php

namespace App\Channels;

use App\Telegram\TelegramService;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class TelegramChannel
{
    /**
     * Send the given notification.
     */
    public function send(object $notifiable, Notification $notification): void
    {
        // Check if user has Telegram configured
        if (!$notifiable->telegram_id) {
            return;
        }

        // Check if Telegram notifications are enabled for this user
        if (!$notifiable->telegram_notifications_enabled) {
            return;
        }

        // Check if user has notification settings
        if ($notifiable->notificationSettings && !$notifiable->notificationSettings->channel_telegram) {
            return;
        }

        // Get the Telegram message from the notification
        if (!method_exists($notification, 'toTelegram')) {
            return;
        }

        $message = $notification->toTelegram($notifiable);

        if (empty($message)) {
            return;
        }

        try {
            $telegram = app(TelegramService::class);

            // If message is an array with options
            if (is_array($message)) {
                $text = $message['text'] ?? '';
                $options = $message['options'] ?? [];

                $telegram->sendMessage(
                    $notifiable->telegram_id,
                    $text,
                    $options
                );
            } else {
                // Simple text message
                $telegram->sendMessage(
                    $notifiable->telegram_id,
                    $message
                );
            }

            Log::info('Telegram notification sent', [
                'user_id' => $notifiable->id,
                'telegram_id' => $notifiable->telegram_id,
                'notification' => get_class($notification),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send Telegram notification', [
                'user_id' => $notifiable->id,
                'telegram_id' => $notifiable->telegram_id,
                'notification' => get_class($notification),
                'error' => $e->getMessage(),
            ]);
        }
    }
}
