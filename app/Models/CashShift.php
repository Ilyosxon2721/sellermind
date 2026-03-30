<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Finance\CashAccount;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Модель кассовой смены для POS-терминала
 */
final class CashShift extends Model
{
    public const STATUS_OPEN = 'open';

    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'company_id',
        'cash_account_id',
        'warehouse_id',
        'opened_by',
        'closed_by',
        'status',
        'opening_balance',
        'closing_balance',
        'total_sales_count',
        'total_sales_amount',
        'total_cash_received',
        'total_card_received',
        'total_transfer_received',
        'total_refunds',
        'total_cash_in',
        'total_cash_out',
        'opened_at',
        'closed_at',
        'close_notes',
        'metadata',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'opening_balance' => 'float',
        'closing_balance' => 'float',
        'total_sales_count' => 'integer',
        'total_sales_amount' => 'float',
        'total_cash_received' => 'float',
        'total_card_received' => 'float',
        'total_transfer_received' => 'float',
        'total_refunds' => 'float',
        'total_cash_in' => 'float',
        'total_cash_out' => 'float',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'metadata' => 'array',
    ];

    // ---------------------------------------------------------------
    // Связи
    // ---------------------------------------------------------------

    /**
     * Компания, к которой относится смена
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Кассовый счёт
     */
    public function cashAccount(): BelongsTo
    {
        return $this->belongsTo(CashAccount::class);
    }

    /**
     * Склад (точка продаж)
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Пользователь, открывший смену
     */
    public function openedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    /**
     * Пользователь, закрывший смену
     */
    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    // ---------------------------------------------------------------
    // Скоупы
    // ---------------------------------------------------------------

    /**
     * Фильтр по компании
     *
     * @param  Builder<CashShift>  $query
     * @return Builder<CashShift>
     */
    public function scopeByCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Только открытые смены
     *
     * @param  Builder<CashShift>  $query
     * @return Builder<CashShift>
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_OPEN);
    }

    // ---------------------------------------------------------------
    // Методы бизнес-логики
    // ---------------------------------------------------------------

    /**
     * Проверить, открыта ли смена
     */
    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    /**
     * Закрыть смену
     */
    public function close(float $closingBalance, ?string $notes = null): void
    {
        $this->update([
            'status' => self::STATUS_CLOSED,
            'closing_balance' => $closingBalance,
            'close_notes' => $notes,
            'closed_by' => auth()->id(),
            'closed_at' => now(),
        ]);
    }

    /**
     * Добавить продажу — увеличить счётчики смены
     */
    public function addSale(float $amount, string $paymentMethod): void
    {
        $this->increment('total_sales_count');
        $this->increment('total_sales_amount', $amount);

        match ($paymentMethod) {
            'cash' => $this->increment('total_cash_received', $amount),
            'card' => $this->increment('total_card_received', $amount),
            'transfer' => $this->increment('total_transfer_received', $amount),
            default => $this->increment('total_cash_received', $amount),
        };
    }

    /**
     * Добавить возврат
     */
    public function addRefund(float $amount): void
    {
        $this->increment('total_refunds', $amount);
    }

    /**
     * Внесение наличных в кассу
     */
    public function addCashIn(float $amount): void
    {
        $this->increment('total_cash_in', $amount);
    }

    /**
     * Изъятие наличных из кассы
     */
    public function addCashOut(float $amount): void
    {
        $this->increment('total_cash_out', $amount);
    }

    /**
     * Рассчитать ожидаемый остаток в кассе
     *
     * Формула: начальный баланс + внесения - изъятия + наличные от продаж - возвраты
     */
    public function getExpectedBalance(): float
    {
        return (float) (
            $this->opening_balance
            + $this->total_cash_in
            - $this->total_cash_out
            + $this->total_cash_received
            - $this->total_refunds
        );
    }

    /**
     * Рассчитать разницу (недостача/излишек)
     *
     * Положительное значение — излишек, отрицательное — недостача
     */
    public function getDifference(): float
    {
        if ($this->closing_balance === null) {
            return 0.0;
        }

        return (float) ($this->closing_balance - $this->getExpectedBalance());
    }
}
