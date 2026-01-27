<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;

class BroadcastHelper
{
    /**
     * Safely broadcast an event without breaking the main flow
     * If broadcasting fails (e.g., Reverb not running), it just logs the error
     */
    public static function safe($event): void
    {
        try {
            broadcast($event)->toOthers();
        } catch (\Exception $e) {
            Log::debug('Broadcast failed', [
                'event' => get_class($event),
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Safely broadcast an event (without toOthers)
     */
    public static function safeAll($event): void
    {
        try {
            broadcast($event);
        } catch (\Exception $e) {
            Log::debug('Broadcast failed', [
                'event' => get_class($event),
                'error' => $e->getMessage(),
            ]);
        }
    }
}
