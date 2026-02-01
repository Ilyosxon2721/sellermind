<?php

namespace App\Models\Risment;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RismentWebhookLog extends Model
{
    protected $table = 'risment_webhook_logs';

    protected $fillable = [
        'webhook_endpoint_id',
        'event',
        'payload',
        'response_code',
        'response_body',
        'attempts',
        'next_retry_at',
        'delivered_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'attempts' => 'integer',
            'next_retry_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(RismentWebhookEndpoint::class, 'webhook_endpoint_id');
    }

    public function isDelivered(): bool
    {
        return $this->delivered_at !== null;
    }

    public function needsRetry(): bool
    {
        return !$this->isDelivered()
            && $this->attempts < 5
            && ($this->next_retry_at === null || $this->next_retry_at->isPast());
    }
}
