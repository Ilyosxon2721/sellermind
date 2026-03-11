<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EntityType;
use App\Enums\EventStatus;
use App\Enums\EventType;
use App\Enums\MarketplaceType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

final class MarketplaceEvent extends Model
{
    protected $fillable = [
        'uuid', 'store_id', 'marketplace', 'event_type', 'external_id',
        'entity_type', 'entity_id', 'payload', 'normalized_data',
        'status', 'attempts', 'error_message', 'metadata', 'processed_at',
    ];

    protected $casts = [
        'payload'         => 'array',
        'normalized_data' => 'array',
        'metadata'        => 'array',
        'processed_at'    => 'datetime',
        'marketplace'     => MarketplaceType::class,
        'event_type'      => EventType::class,
        'status'          => EventStatus::class,
        'entity_type'     => EntityType::class,
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }
        });
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(MarketplaceAccount::class, 'store_id');
    }

    /**
     * Пометить событие как успешно обработанное
     */
    public function markProcessed(): void
    {
        $this->update([
            'status'       => EventStatus::PROCESSED,
            'processed_at' => now(),
        ]);
    }

    /**
     * Пометить событие как ошибочное с сохранением сообщения
     */
    public function markFailed(string $error): void
    {
        $this->update([
            'status'        => EventStatus::FAILED,
            'error_message' => $error,
        ]);
    }

    /**
     * Пометить событие как пропущенное (дубликат или неактуальное)
     */
    public function markSkipped(): void
    {
        $this->update(['status' => EventStatus::SKIPPED]);
    }
}
