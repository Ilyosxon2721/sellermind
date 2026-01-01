<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplyBox extends Model
{
    protected $fillable = [
        'supply_id',
        'box_number',
        'sticker_path',
    ];

    /**
     * Получить поставку, к которой принадлежит короб
     */
    public function supply(): BelongsTo
    {
        return $this->belongsTo(Supply::class);
    }
}
