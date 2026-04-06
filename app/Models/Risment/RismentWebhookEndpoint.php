<?php

namespace App\Models\Risment;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RismentWebhookEndpoint extends Model
{
    protected $table = 'risment_webhook_endpoints';

    protected $fillable = [
        'company_id',
        'risment_client_id',
        'url',
        'secret',
        'events',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'events' => 'array',
            'is_active' => 'boolean',
        ];
    }

    protected $hidden = ['secret'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Company::class);
    }

    public function rismentClient(): BelongsTo
    {
        return $this->belongsTo(RismentClient::class, 'risment_client_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(RismentWebhookLog::class, 'webhook_endpoint_id');
    }

    public function listensTo(string $event): bool
    {
        return in_array($event, $this->events ?? [], true);
    }
}
