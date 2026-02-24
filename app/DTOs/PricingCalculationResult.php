<?php

declare(strict_types=1);

namespace App\DTOs;

/**
 * Результат расчёта ценообразования для товара.
 *
 * Содержит все компоненты расходов, маржу, ROI и вспомогательные методы
 * для определения прибыльности и визуального отображения.
 */
final class PricingCalculationResult
{
    /** Маркетплейс (wildberries, ozon, yandex, uzum) */
    public string $marketplace = '';

    /** Тип фулфилмента (fbo, fbs, dbs, express) */
    public string $fulfillmentType = '';

    /** Цена продажи */
    public float $price = 0;

    /** Полная себестоимость (закупка + упаковка + доставка до склада + прочие) */
    public float $totalCost = 0;

    /** Комиссия маркетплейса (в абсолютном значении) */
    public float $commission = 0;

    /** Стоимость логистики */
    public float $logistics = 0;

    /** Эквайринг */
    public float $acquiring = 0;

    /** Стоимость хранения */
    public float $storage = 0;

    /** Суммарные расходы (себестоимость + все комиссии и сборы) */
    public float $totalExpenses = 0;

    /** Маржа в абсолютном значении */
    public float $marginAmount = 0;

    /** Маржа в процентах от цены */
    public float $marginPercent = 0;

    /** Рентабельность инвестиций (ROI) в процентах */
    public float $roi = 0;

    /** Минимальная цена (точка безубыточности) */
    public float $minPrice = 0;

    /** Рекомендованная цена (с учётом целевой маржи) */
    public ?float $recommendedPrice = null;

    /**
     * Преобразовать результат в массив для API-ответа
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'marketplace' => $this->marketplace,
            'fulfillment_type' => $this->fulfillmentType,
            'price' => round($this->price, 2),
            'total_cost' => round($this->totalCost, 2),
            'commission' => round($this->commission, 2),
            'logistics' => round($this->logistics, 2),
            'acquiring' => round($this->acquiring, 2),
            'storage' => round($this->storage, 2),
            'total_expenses' => round($this->totalExpenses, 2),
            'margin_amount' => round($this->marginAmount, 2),
            'margin_percent' => round($this->marginPercent, 2),
            'roi' => round($this->roi, 2),
            'min_price' => round($this->minPrice, 2),
            'recommended_price' => $this->recommendedPrice !== null
                ? round($this->recommendedPrice, 2)
                : null,
            'is_profitable' => $this->isProfitable(),
            'margin_color' => $this->getMarginColor(),
        ];
    }

    /**
     * Проверить, является ли товар прибыльным при текущей цене
     */
    public function isProfitable(): bool
    {
        return $this->marginAmount > 0;
    }

    /**
     * Получить цвет индикатора маржинальности для UI
     *
     * red    — убыточный (маржа < 0%)
     * orange — низкая маржа (0-15%)
     * yellow — средняя маржа (15-30%)
     * green  — хорошая маржа (>= 30%)
     */
    public function getMarginColor(): string
    {
        if ($this->marginPercent < 0) {
            return 'red';
        }

        if ($this->marginPercent < 15) {
            return 'orange';
        }

        if ($this->marginPercent < 30) {
            return 'yellow';
        }

        return 'green';
    }
}
