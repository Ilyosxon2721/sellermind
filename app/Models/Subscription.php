<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'plan_id',
        'status',
        'starts_at',
        'ends_at',
        'trial_ends_at',
        'cancelled_at',
        'amount_paid',
        'payment_method',
        'payment_reference',
        'current_products_count',
        'current_orders_count',
        'current_ai_requests',
        'usage_reset_at',
        'notes',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'usage_reset_at' => 'datetime',
        'amount_paid' => 'decimal:2',
    ];

    /**
     * Компания подписки
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Тарифный план
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Активна ли подписка
     */
    public function isActive(): bool
    {
        return $this->status === 'active' && 
               ($this->ends_at === null || $this->ends_at->isFuture());
    }

    /**
     * На триале ли подписка
     */
    public function isTrial(): bool
    {
        return $this->status === 'trial' && 
               $this->trial_ends_at !== null && 
               $this->trial_ends_at->isFuture();
    }

    /**
     * Истекла ли подписка
     */
    public function isExpired(): bool
    {
        return $this->status === 'expired' || 
               ($this->ends_at !== null && $this->ends_at->isPast());
    }

    /**
     * Дней до окончания
     */
    public function daysRemaining(): ?int
    {
        if (!$this->ends_at) {
            return null;
        }
        
        return max(0, now()->diffInDays($this->ends_at, false));
    }

    /**
     * Проверка лимита товаров
     */
    public function canAddProducts(int $count = 1): bool
    {
        return ($this->current_products_count + $count) <= $this->plan->max_products;
    }

    /**
     * Проверка лимита заказов
     */
    public function canProcessOrders(int $count = 1): bool
    {
        return ($this->current_orders_count + $count) <= $this->plan->max_orders_per_month;
    }

    /**
     * Проверка лимита AI запросов
     */
    public function canUseAI(int $count = 1): bool
    {
        return ($this->current_ai_requests + $count) <= $this->plan->max_ai_requests;
    }

    /**
     * Увеличить счётчик использования
     */
    public function incrementUsage(string $type, int $count = 1): void
    {
        $field = match($type) {
            'products' => 'current_products_count',
            'orders' => 'current_orders_count',
            'ai' => 'current_ai_requests',
            default => null,
        };

        if ($field) {
            $this->increment($field, $count);
        }
    }

    /**
     * Сбросить месячные счётчики
     */
    public function resetMonthlyUsage(): void
    {
        $this->update([
            'current_orders_count' => 0,
            'current_ai_requests' => 0,
            'usage_reset_at' => now(),
        ]);
    }

    /**
     * Scope для активных подписок
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope для истекающих подписок
     */
    public function scopeExpiringSoon($query, int $days = 7)
    {
        return $query->where('status', 'active')
                     ->whereNotNull('ends_at')
                     ->where('ends_at', '<=', now()->addDays($days));
    }
}
