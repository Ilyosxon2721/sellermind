<?php

namespace App\Models\Warehouse;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryDocumentLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'sku_id',
        'qty',
        'unit_id',
        'location_id',
        'location_to_id',
        'unit_cost',
        'total_cost',
        'meta_json',
    ];

    protected $casts = [
        'qty' => 'decimal:3',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'meta_json' => 'array',
    ];

    public function scopeByCompany($query, int $companyId)
    {
        return $query->whereHas('document', fn($q) => $q->where('company_id', $companyId));
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(InventoryDocument::class, 'document_id');
    }

    public function sku(): BelongsTo
    {
        return $this->belongsTo(Sku::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
