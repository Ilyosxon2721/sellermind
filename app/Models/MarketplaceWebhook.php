<?php
// file: app/Models/MarketplaceWebhook.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceWebhook extends Model
{
    public const STATUS_NEW = 'new';
    public const STATUS_PROCESSED = 'processed';
    public const STATUS_ERROR = 'error';

    protected $fillable = [
        'marketplace',
        'marketplace_account_id',
        'event_type',
        'status',
        'payload',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(MarketplaceAccount::class, 'marketplace_account_id');
    }

    /**
     * Mark webhook as processed
     */
    public function markAsProcessed(): void
    {
        $this->update(['status' => self::STATUS_PROCESSED]);
    }

    /**
     * Mark webhook as error with message
     */
    public function markAsError(string $message): void
    {
        $this->update([
            'status' => self::STATUS_ERROR,
            'error_message' => $message,
        ]);
    }

    /**
     * Get status label
     */
    public function getStatusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_NEW => 'Новый',
            self::STATUS_PROCESSED => 'Обработан',
            self::STATUS_ERROR => 'Ошибка',
            default => $this->status,
        };
    }

    /**
     * Get status color for UI
     */
    public function getStatusColor(): string
    {
        return match ($this->status) {
            self::STATUS_NEW => 'yellow',
            self::STATUS_PROCESSED => 'green',
            self::STATUS_ERROR => 'red',
            default => 'gray',
        };
    }
}
