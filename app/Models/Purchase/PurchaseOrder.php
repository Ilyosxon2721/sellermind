<?php

namespace App\Models\Purchase;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'warehouse_id',
        'supplier_id',
        'po_no',
        'status',
        'expected_date',
        'total_amount',
    ];

    protected $casts = [
        'expected_date' => 'date',
        'total_amount' => 'float',
    ];

    public function lines(): HasMany
    {
        return $this->hasMany(PurchaseOrderLine::class);
    }

    public function scopeByCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }
}
