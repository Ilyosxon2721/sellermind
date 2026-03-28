<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Модель воронки продаж
 *
 * Хранит параметры воронки (ручные или авто-рассчитанные).
 * Расчёт: Просмотры → Обращения → Встречи → Продажи → Доход → Прибыль → Бонус
 */
final class SalesFunnel extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'views',
        'inquiry_rate',
        'meeting_rate',
        'sale_rate',
        'average_check',
        'profit_margin',
        'bonus_rate',
        'currency',
        'is_auto',
        'source_filter',
        'period_from',
        'period_to',
        'auto_snapshot',
        'metadata',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'views' => 'integer',
            'inquiry_rate' => 'decimal:3',
            'meeting_rate' => 'decimal:3',
            'sale_rate' => 'decimal:3',
            'average_check' => 'decimal:2',
            'profit_margin' => 'decimal:3',
            'bonus_rate' => 'decimal:3',
            'is_auto' => 'boolean',
            'source_filter' => 'array',
            'period_from' => 'date',
            'period_to' => 'date',
            'auto_snapshot' => 'array',
            'metadata' => 'array',
        ];
    }

    /** Все доступные источники данных */
    public const SOURCES = [
        'wb' => 'Wildberries',
        'ozon' => 'Ozon',
        'uzum' => 'Uzum',
        'ym' => 'Yandex Market',
        'manual' => 'Ручные продажи',
        'retail' => 'Розница',
        'wholesale' => 'Опт',
        'direct' => 'Прямые продажи',
    ];

    // ========== Relationships ==========

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ========== Scopes ==========

    public function scopeByCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeAuto($query)
    {
        return $query->where('is_auto', true);
    }

    public function scopeManual($query)
    {
        return $query->where('is_auto', false);
    }

    // ========== Вычисляемые поля воронки ==========

    /**
     * Количество обращений = Просмотры × % обращений
     */
    public function getInquiries(): int
    {
        return (int) round($this->views * ($this->inquiry_rate / 100));
    }

    /**
     * Количество встреч = Обращения × % встреч
     */
    public function getMeetings(): int
    {
        return (int) round($this->getInquiries() * ($this->meeting_rate / 100));
    }

    /**
     * Количество продаж = Встречи × % конверсии
     */
    public function getSalesCount(): int
    {
        return (int) round($this->getMeetings() * ($this->sale_rate / 100));
    }

    /**
     * Доход = Продажи × Средний чек
     */
    public function getRevenue(): float
    {
        return round($this->getSalesCount() * (float) $this->average_check, 2);
    }

    /**
     * Чистая прибыль = Доход × % маржинальности
     */
    public function getNetProfit(): float
    {
        return round($this->getRevenue() * ((float) $this->profit_margin / 100), 2);
    }

    /**
     * Бонус = Чистая прибыль × % бонуса
     */
    public function getBonus(): float
    {
        return round($this->getNetProfit() * ((float) $this->bonus_rate / 100), 2);
    }

    /**
     * Полный расчёт воронки — все 8 этапов
     */
    public function calculateFunnel(): array
    {
        $inquiries = $this->getInquiries();
        $meetings = $this->getMeetings();
        $sales = $this->getSalesCount();
        $revenue = $this->getRevenue();
        $netProfit = $this->getNetProfit();
        $bonus = $this->getBonus();

        return [
            ['stage' => 'views', 'label' => 'Просмотры (Ko\'rdi)', 'value' => $this->views, 'rate' => null, 'unit' => 'та мижоз'],
            ['stage' => 'inquiries', 'label' => 'Обращения (Murojaat)', 'value' => $inquiries, 'rate' => (float) $this->inquiry_rate, 'unit' => 'та мижоз'],
            ['stage' => 'meetings', 'label' => 'Встречи (Uchrashuvga keldi)', 'value' => $meetings, 'rate' => (float) $this->meeting_rate, 'unit' => 'та мижоз'],
            ['stage' => 'sales', 'label' => 'Продажи (Sotuvlar)', 'value' => $sales, 'rate' => (float) $this->sale_rate, 'unit' => 'та'],
            ['stage' => 'average_check', 'label' => 'Средний чек (O\'rtacha chek)', 'value' => (float) $this->average_check, 'rate' => null, 'unit' => $this->currency],
            ['stage' => 'revenue', 'label' => 'Доход (Daromad)', 'value' => $revenue, 'rate' => null, 'unit' => $this->currency],
            ['stage' => 'net_profit', 'label' => 'Чистая прибыль (Sof foyda)', 'value' => $netProfit, 'rate' => (float) $this->profit_margin, 'unit' => $this->currency],
            ['stage' => 'bonus', 'label' => 'Бонус (Mukofot)', 'value' => $bonus, 'rate' => (float) $this->bonus_rate, 'unit' => $this->currency],
        ];
    }
}
