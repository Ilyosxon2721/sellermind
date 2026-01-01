<?php

namespace App\Models\Warehouse;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockLedger extends Model
{
    use HasFactory;

    protected $table = 'stock_ledger';

    protected $fillable = [
        'company_id',
        'occurred_at',
        'warehouse_id',
        'location_id',
        'sku_id',
        'qty_delta',
        'cost_delta',
        'document_id',
        'document_line_id',
        'source_type',
        'source_id',
        'created_by',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'qty_delta' => 'decimal:3',
        'cost_delta' => 'decimal:2',
    ];

    public function scopeByCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(InventoryDocument::class, 'document_id');
    }

    public function sku(): BelongsTo
    {
        return $this->belongsTo(Sku::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }
}
