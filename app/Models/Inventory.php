<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Inventory extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'warehouse_id',
        'created_by',
        'number',
        'date',
        'status',
        'type',
        'total_items',
        'matched_items',
        'surplus_items',
        'shortage_items',
        'surplus_amount',
        'shortage_amount',
        'is_applied',
        'applied_at',
        'applied_by',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'applied_at' => 'datetime',
        'is_applied' => 'boolean',
        'surplus_amount' => 'decimal:2',
        'shortage_amount' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (!$model->number) {
                $model->number = 'INV-' . date('Ymd') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse\Warehouse::class);
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function appliedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applied_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InventoryItem::class);
    }

    public function getStatusLabel(): string
    {
        return match($this->status) {
            'draft' => 'Черновик',
            'in_progress' => 'В процессе',
            'completed' => 'Завершена',
            'cancelled' => 'Отменена',
            default => $this->status,
        };
    }

    public function getTypeLabel(): string
    {
        return $this->type === 'full' ? 'Полная' : 'Частичная';
    }

    public function calculateResults(): void
    {
        $items = $this->items()->whereNotNull('actual_quantity')->get();
        
        $this->total_items = $items->count();
        $this->matched_items = $items->where('difference', 0)->count();
        $this->surplus_items = $items->where('difference', '>', 0)->count();
        $this->shortage_items = $items->where('difference', '<', 0)->count();
        
        $this->surplus_amount = $items->where('difference', '>', 0)->sum('difference_amount');
        $this->shortage_amount = abs($items->where('difference', '<', 0)->sum('difference_amount'));
        
        $this->save();
    }

    public function applyResults(): bool
    {
        if ($this->is_applied || $this->status !== 'completed') {
            return false;
        }

        foreach ($this->items as $item) {
            if ($item->actual_quantity !== null && $item->difference != 0) {
                // Корректировка остатков на складе
                $stock = WarehouseStock::where('warehouse_id', $this->warehouse_id)
                    ->where('product_id', $item->product_id)
                    ->first();
                
                if ($stock) {
                    $stock->quantity = $item->actual_quantity;
                    $stock->save();
                }
            }
        }

        $this->is_applied = true;
        $this->applied_at = now();
        $this->applied_by = auth()->id();
        $this->save();

        return true;
    }

    public function scopeByCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeByWarehouse($query, int $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }
}
