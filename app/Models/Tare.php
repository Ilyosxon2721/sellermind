<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tare extends Model
{
    protected $fillable = [
        'supply_id',
        'external_tare_id',
        'barcode',
        'orders_count',
    ];

    protected $casts = [
        'orders_count' => 'integer',
    ];

    // ========== Relationships ==========

    public function supply(): BelongsTo
    {
        return $this->belongsTo(Supply::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(WbOrder::class, 'tare_id');
    }

    // ========== Methods ==========

    /**
     * Recalculate orders count
     */
    public function updateOrdersCount(): void
    {
        $this->orders_count = $this->orders()->count();
        $this->save();
    }
}
