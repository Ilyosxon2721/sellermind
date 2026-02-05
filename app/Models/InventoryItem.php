<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'inventory_id',
        'product_id',
        'expected_quantity',
        'actual_quantity',
        'difference',
        'unit_price',
        'difference_amount',
        'status',
        'discrepancy_reason',
        'notes',
    ];

    protected $casts = [
        'expected_quantity' => 'decimal:3',
        'actual_quantity' => 'decimal:3',
        'difference' => 'decimal:3',
        'unit_price' => 'decimal:2',
        'difference_amount' => 'decimal:2',
    ];

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function calculateDifference(): void
    {
        if ($this->actual_quantity !== null) {
            $this->difference = $this->actual_quantity - $this->expected_quantity;
            $this->difference_amount = $this->difference * $this->unit_price;
            $this->status = 'counted';
        }
    }

    public function getStatusLabel(): string
    {
        return match ($this->status) {
            'pending' => 'Ожидает',
            'counted' => 'Подсчитано',
            'verified' => 'Проверено',
            default => $this->status,
        };
    }

    public function getDiscrepancyType(): string
    {
        if ($this->difference > 0) {
            return 'surplus';
        }
        if ($this->difference < 0) {
            return 'shortage';
        }

        return 'match';
    }

    public function getDiscrepancyLabel(): string
    {
        if ($this->difference > 0) {
            return 'Излишек';
        }
        if ($this->difference < 0) {
            return 'Недостача';
        }

        return 'Совпадает';
    }
}
