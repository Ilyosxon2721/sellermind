<?php

namespace App\Models\Warehouse;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryDocument extends Model
{
    use HasFactory;

    public const TYPE_IN = 'IN';
    public const TYPE_OUT = 'OUT';
    public const TYPE_MOVE = 'MOVE';
    public const TYPE_WRITE_OFF = 'WRITE_OFF';
    public const TYPE_INVENTORY = 'INVENTORY';
    public const TYPE_REVERSAL = 'REVERSAL';

    public const STATUS_DRAFT = 'DRAFT';
    public const STATUS_POSTED = 'POSTED';
    public const STATUS_CANCELLED = 'CANCELLED';

    protected $fillable = [
        'company_id',
        'doc_no',
        'type',
        'status',
        'warehouse_id',
        'warehouse_to_id',
        'reason',
        'source_type',
        'source_id',
        'reversed_document_id',
        'comment',
        'created_by',
        'posted_at',
        'supplier_id',
        'source_doc_no',
    ];

    protected $casts = [
        'posted_at' => 'datetime',
    ];

    public function scopeByCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(\App\Models\AP\Supplier::class, 'supplier_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(InventoryDocumentLine::class, 'document_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function warehouseTo(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_to_id');
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(StockLedger::class, 'document_id');
    }
}
