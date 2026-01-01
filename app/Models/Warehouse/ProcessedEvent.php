<?php

namespace App\Models\Warehouse;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProcessedEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'channel_id',
        'external_event_id',
        'type',
        'payload_hash',
        'processed_at',
    ];

    protected $casts = [
        'processed_at' => 'datetime',
    ];

    public function scopeByCompany($query, int $companyId)
    {
        // channels привязаны к company; join по связи в сервисах
        return $query;
    }
}
