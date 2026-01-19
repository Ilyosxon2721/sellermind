<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'salary_calculation_id',
        'employee_id',
        'base_amount',
        'bonuses',
        'overtime',
        'gross_amount',
        'tax_amount',
        'pension_amount',
        'other_deductions',
        'net_amount',
        'is_paid',
        'paid_at',
        'transaction_id',
        'notes',
    ];

    protected $casts = [
        'base_amount' => 'float',
        'bonuses' => 'float',
        'overtime' => 'float',
        'gross_amount' => 'float',
        'tax_amount' => 'float',
        'pension_amount' => 'float',
        'other_deductions' => 'float',
        'net_amount' => 'float',
        'is_paid' => 'boolean',
        'paid_at' => 'datetime',
    ];

    public function calculation(): BelongsTo
    {
        return $this->belongsTo(SalaryCalculation::class, 'salary_calculation_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(FinanceTransaction::class, 'transaction_id');
    }

    public function calculateTotals(): void
    {
        $this->gross_amount = $this->base_amount + $this->bonuses + $this->overtime;
        $this->net_amount = $this->gross_amount - $this->tax_amount - $this->pension_amount - $this->other_deductions;
    }

    public function markAsPaid(int $transactionId): void
    {
        $this->update([
            'is_paid' => true,
            'paid_at' => now(),
            'transaction_id' => $transactionId,
        ]);
    }
}
