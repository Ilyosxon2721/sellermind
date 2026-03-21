<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MarketplaceType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

final class MarketplaceWebhookConfig extends Model
{
    protected $table = 'marketplace_webhook_configs';

    protected $fillable = [
        'store_id', 'marketplace', 'webhook_uuid', 'secret_key',
        'is_active', 'last_received_at', 'events_count',
    ];

    protected $casts = [
        'marketplace' => MarketplaceType::class,
        'is_active' => 'boolean',
        'last_received_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        self::creating(function (self $model) {
            if (empty($model->webhook_uuid)) {
                $model->webhook_uuid = Str::uuid()->toString();
            }
            if (empty($model->secret_key)) {
                $model->secret_key = Str::random(64);
            }
        });
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(MarketplaceAccount::class, 'store_id');
    }

    /**
     * Зафиксировать получение события: счётчик и timestamp
     */
    public function recordEvent(): void
    {
        $this->increment('events_count');
        $this->update(['last_received_at' => now()]);
    }
}
