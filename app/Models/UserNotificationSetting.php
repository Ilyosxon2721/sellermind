<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserNotificationSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'notify_low_stock',
        'notify_new_order',
        'notify_order_cancelled',
        'notify_price_changes',
        'notify_bulk_operations',
        'notify_marketplace_sync',
        'notify_critical_errors',
        'channel_telegram',
        'channel_email',
        'channel_database',
        'low_stock_threshold',
        'notify_only_business_hours',
        'business_hours_start',
        'business_hours_end',
    ];

    protected $casts = [
        'notify_low_stock' => 'boolean',
        'notify_new_order' => 'boolean',
        'notify_order_cancelled' => 'boolean',
        'notify_price_changes' => 'boolean',
        'notify_bulk_operations' => 'boolean',
        'notify_marketplace_sync' => 'boolean',
        'notify_critical_errors' => 'boolean',
        'channel_telegram' => 'boolean',
        'channel_email' => 'boolean',
        'channel_database' => 'boolean',
        'notify_only_business_hours' => 'boolean',
    ];

    /**
     * Get the user that owns the notification settings.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if notification should be sent based on business hours
     */
    public function shouldNotifyNow(): bool
    {
        if (!$this->notify_only_business_hours) {
            return true;
        }

        if (!$this->business_hours_start || !$this->business_hours_end) {
            return true;
        }

        $now = now()->format('H:i:s');
        return $now >= $this->business_hours_start && $now <= $this->business_hours_end;
    }

    /**
     * Check if a specific notification type is enabled
     */
    public function isNotificationEnabled(string $type): bool
    {
        $field = "notify_{$type}";
        return $this->{$field} ?? false;
    }
}
