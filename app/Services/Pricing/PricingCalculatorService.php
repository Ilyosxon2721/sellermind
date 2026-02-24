<?php

declare(strict_types=1);

namespace App\Services\Pricing;

use App\DTOs\PricingCalculationResult;
use App\Models\Pricing\MarketplaceAcquiring;
use App\Models\Pricing\MarketplaceCommission;
use App\Models\Pricing\MarketplaceLogistics;
use Illuminate\Database\Eloquent\Model;

/**
 * Сервис расчёта ценообразования для товаров на маркетплейсах.
 *
 * Выполняет прямой расчёт (цена -> маржа) и обратный расчёт (маржа -> цена).
 * Использует алгебраический подход из PriceEngineService:
 *   Price = (FixedCosts) / (1 - PercentCosts)
 *
 * Зависит от справочных таблиц: marketplace_commissions, marketplace_logistics,
 * marketplace_acquiring. При отсутствии данных использует дефолтные ставки.
 */
final class PricingCalculatorService
{
    /**
     * Рассчитать все расходы и маржу для заданной цены
     *
     * Принимает модель ProductPricing (или любую Eloquent-модель с нужными полями)
     * и опциональную цену. Если цена не передана, берётся current_price или
     * recommended_price из модели.
     *
     * @param  Model  $pricing  Модель с полями: marketplace, fulfillment_type, cost_price,
     *                          packaging_cost, delivery_to_warehouse, other_costs,
     *                          marketplace_category_id, length_cm, width_cm, height_cm,
     *                          weight_kg, storage_cost, current_price, recommended_price,
     *                          target_margin_percent
     * @param  float|null  $price  Цена продажи (если null — берётся из модели)
     */
    public function calculate(Model $pricing, ?float $price = null): PricingCalculationResult
    {
        $result = new PricingCalculationResult;

        $marketplace = (string) ($pricing->marketplace ?? '');
        $fulfillmentType = (string) ($pricing->fulfillment_type ?? 'fbo');
        $categoryId = $pricing->marketplace_category_id ?? null;

        $result->marketplace = $marketplace;
        $result->fulfillmentType = $fulfillmentType;

        // Определяем цену: переданная > current_price > recommended_price > 0
        $price = $price
            ?? (float) ($pricing->current_price ?? 0)
            ?: (float) ($pricing->recommended_price ?? 0)
            ?: 0.0;
        $result->price = $price;

        // 1. Полная себестоимость (закупка + упаковка + доставка до склада + прочие)
        $result->totalCost = $this->getTotalCost($pricing);

        // 2. Комиссия маркетплейса
        $result->commission = $this->calculateCommission(
            $marketplace,
            $categoryId,
            $fulfillmentType,
            $price
        );

        // 3. Логистика
        $volumeLiters = $this->calculateVolumeLiters($pricing);
        $weightKg = $pricing->weight_kg !== null ? (float) $pricing->weight_kg : null;
        $result->logistics = $this->calculateLogistics(
            $marketplace,
            $fulfillmentType,
            $volumeLiters,
            $weightKg
        );

        // 4. Эквайринг
        $result->acquiring = $this->calculateAcquiring($marketplace, $price);

        // 5. Хранение
        $result->storage = (float) ($pricing->storage_cost ?? 0);

        // 6. Суммарные расходы
        $result->totalExpenses = $result->totalCost
            + $result->commission
            + $result->logistics
            + $result->acquiring
            + $result->storage;

        // 7. Маржа в абсолютном значении
        $result->marginAmount = $price - $result->totalExpenses;

        // 8. Маржа в процентах от цены
        $result->marginPercent = $price > 0
            ? round(($result->marginAmount / $price) * 100, 2)
            : 0.0;

        // 9. ROI (рентабельность инвестиций)
        $result->roi = $result->totalCost > 0
            ? round(($result->marginAmount / $result->totalCost) * 100, 2)
            : 0.0;

        // 10. Минимальная цена (точка безубыточности)
        $result->minPrice = $this->calculateMinPrice($pricing);

        // 11. Рекомендованная цена (с учётом целевой маржи)
        $targetMargin = (float) ($pricing->target_margin_percent ?? 30);
        $recommendedPrice = $this->calculatePriceForMargin($pricing, $targetMargin);
        $result->recommendedPrice = $recommendedPrice > 0 ? $recommendedPrice : null;

        return $result;
    }

    /**
     * Обратный расчёт: цена для достижения желаемой маржи
     *
     * Формула (алгебраическое решение уравнения маржи):
     *   Price = (TotalCost + Logistics + Storage) / (1 - CommissionRate/100 - AcquiringRate/100 - TargetMargin/100)
     *
     * Если знаменатель <= 0, достижение целевой маржи невозможно (возвращает 0).
     */
    public function calculatePriceForMargin(Model $pricing, float $targetMarginPercent): float
    {
        $marketplace = (string) ($pricing->marketplace ?? '');
        $fulfillmentType = (string) ($pricing->fulfillment_type ?? 'fbo');
        $categoryId = $pricing->marketplace_category_id ?? null;

        $totalCost = $this->getTotalCost($pricing);

        // Получаем процентные ставки
        $commissionRate = $this->getCommissionRate($marketplace, $categoryId, $fulfillmentType);
        $acquiringRate = $this->getAcquiringRate($marketplace);

        // Рассчитываем фиксированные расходы (логистика + хранение)
        $volumeLiters = $this->calculateVolumeLiters($pricing);
        $weightKg = $pricing->weight_kg !== null ? (float) $pricing->weight_kg : null;
        $logistics = $this->calculateLogistics($marketplace, $fulfillmentType, $volumeLiters, $weightKg);
        $storage = (float) ($pricing->storage_cost ?? 0);

        // Знаменатель: 1 - (комиссия% + эквайринг% + целевая маржа%) / 100
        $divisor = 1 - ($commissionRate / 100) - ($acquiringRate / 100) - ($targetMarginPercent / 100);

        // Если знаменатель <= 0, достижение маржи невозможно
        if ($divisor <= 0) {
            return 0.0;
        }

        // Числитель: себестоимость + логистика + хранение
        $numerator = $totalCost + $logistics + $storage;

        $price = $numerator / $divisor;

        return round($price, 2);
    }

    /**
     * Сравнить прибыльность товара на разных маркетплейсах
     *
     * Для каждого маркетплейса: рассчитывает рекомендованную цену,
     * полный расчёт расходов и маржу. Сортирует по маржинальности.
     *
     * @param  Model  $basePricing  Базовая модель с себестоимостью и габаритами
     * @param  array<int, string>  $marketplaces  Список маркетплейсов для сравнения
     * @return array<string, PricingCalculationResult> Результаты, отсортированные по маржинальности
     */
    public function compareMarketplaces(Model $basePricing, array $marketplaces): array
    {
        $results = [];

        foreach ($marketplaces as $marketplace) {
            // Клонируем модель и подставляем маркетплейс
            $clone = $basePricing->replicate();
            $clone->marketplace = $marketplace;

            // Рассчитываем рекомендованную цену для целевой маржи
            $targetMargin = (float) ($clone->target_margin_percent ?? 30);
            $recommendedPrice = $this->calculatePriceForMargin($clone, $targetMargin);

            // Если невозможно достичь маржи, считаем по текущей цене или себестоимости * 2
            if ($recommendedPrice <= 0) {
                $recommendedPrice = (float) ($clone->current_price ?? 0)
                    ?: $this->getTotalCost($clone) * 2;
            }

            // Полный расчёт по рекомендованной цене
            $result = $this->calculate($clone, $recommendedPrice);
            $results[$marketplace] = $result;
        }

        // Сортируем по маржинальности (убывание)
        uasort($results, function (PricingCalculationResult $a, PricingCalculationResult $b): int {
            return $b->marginPercent <=> $a->marginPercent;
        });

        return $results;
    }

    // =========================================================================
    // Вспомогательные методы расчёта
    // =========================================================================

    /**
     * Рассчитать комиссию маркетплейса для указанной цены
     *
     * Ищет подходящую запись в таблице marketplace_commissions по маркетплейсу,
     * категории и типу фулфилмента. Если не найдена — использует дефолтную ставку.
     */
    protected function calculateCommission(
        string $marketplace,
        ?int $categoryId,
        string $fulfillmentType,
        float $price
    ): float {
        if ($price <= 0 || $marketplace === '') {
            return 0.0;
        }

        // Ищем точное совпадение: маркетплейс + категория + фулфилмент
        $query = MarketplaceCommission::forMarketplace($marketplace)
            ->active()
            ->where('fulfillment_type', $fulfillmentType);

        if ($categoryId !== null) {
            $query->where('category_id', $categoryId);
        }

        $commission = $query->first();

        if ($commission !== null) {
            return $commission->calculateCommission($price);
        }

        // Ищем без привязки к фулфилменту (общая комиссия для маркетплейса и категории)
        if ($categoryId !== null) {
            $commission = MarketplaceCommission::forMarketplace($marketplace)
                ->active()
                ->where('category_id', $categoryId)
                ->first();

            if ($commission !== null) {
                return $commission->calculateCommission($price);
            }
        }

        // Фоллбэк: дефолтная ставка
        $defaultRate = $this->getDefaultCommissionRate($marketplace);

        return round($price * $defaultRate / 100, 2);
    }

    /**
     * Рассчитать стоимость логистики
     *
     * Ищет подходящий тариф в таблице marketplace_logistics по маркетплейсу,
     * типу фулфилмента, объёму и весу. Поддерживает 4 типа тарификации:
     * fixed, per_liter, per_kg, percent.
     */
    protected function calculateLogistics(
        string $marketplace,
        string $fulfillmentType,
        ?float $volumeLiters,
        ?float $weightKg
    ): float {
        if ($marketplace === '') {
            return 0.0;
        }

        // Ищем тариф доставки для данного маркетплейса и фулфилмента
        $query = MarketplaceLogistics::query()
            ->where('marketplace', $marketplace)
            ->where('fulfillment_type', $fulfillmentType)
            ->where('logistics_type', 'delivery')
            ->active();

        // Фильтр по объёму (если указан и есть диапазоны в таблице)
        if ($volumeLiters !== null) {
            $query->where(function ($q) use ($volumeLiters): void {
                $q->where(function ($inner) use ($volumeLiters): void {
                    $inner->whereNotNull('volume_from')
                        ->where('volume_from', '<=', $volumeLiters)
                        ->where(function ($bound) use ($volumeLiters): void {
                            $bound->whereNull('volume_to')
                                ->orWhere('volume_to', '>=', $volumeLiters);
                        });
                })->orWhere(function ($inner): void {
                    $inner->whereNull('volume_from')
                        ->whereNull('volume_to');
                });
            });
        }

        // Фильтр по весу (если указан и есть диапазоны в таблице)
        if ($weightKg !== null) {
            $query->where(function ($q) use ($weightKg): void {
                $q->where(function ($inner) use ($weightKg): void {
                    $inner->whereNotNull('weight_from')
                        ->where('weight_from', '<=', $weightKg)
                        ->where(function ($bound) use ($weightKg): void {
                            $bound->whereNull('weight_to')
                                ->orWhere('weight_to', '>=', $weightKg);
                        });
                })->orWhere(function ($inner): void {
                    $inner->whereNull('weight_from')
                        ->whereNull('weight_to');
                });
            });
        }

        $logistic = $query->first();

        if ($logistic !== null) {
            return $this->applyLogisticsRate($logistic, $volumeLiters, $weightKg);
        }

        // Пробуем найти без фильтра по объёму/весу (универсальный тариф)
        $fallbackLogistic = MarketplaceLogistics::query()
            ->where('marketplace', $marketplace)
            ->where('fulfillment_type', $fulfillmentType)
            ->where('logistics_type', 'delivery')
            ->active()
            ->whereNull('volume_from')
            ->whereNull('weight_from')
            ->first();

        if ($fallbackLogistic !== null) {
            return $this->applyLogisticsRate($fallbackLogistic, $volumeLiters, $weightKg);
        }

        // Фоллбэк: дефолтная ставка
        return $this->getDefaultLogisticsRate($marketplace);
    }

    /**
     * Рассчитать эквайринг маркетплейса
     *
     * Ищет актуальную ставку эквайринга для маркетплейса.
     * Берёт последнюю по дате начала действия.
     */
    protected function calculateAcquiring(string $marketplace, float $price): float
    {
        if ($price <= 0 || $marketplace === '') {
            return 0.0;
        }

        $acquiring = MarketplaceAcquiring::query()
            ->where('marketplace', $marketplace)
            ->active()
            ->orderByDesc('effective_from')
            ->first();

        if ($acquiring !== null) {
            return round($price * (float) $acquiring->rate_percent / 100, 2);
        }

        // Фоллбэк: дефолтная ставка
        $defaultRate = $this->getDefaultAcquiringRate($marketplace);

        return round($price * $defaultRate / 100, 2);
    }

    /**
     * Рассчитать минимальную цену (точку безубыточности)
     *
     * Формула: MinPrice = (TotalCost + Logistics + Storage) / (1 - CommissionRate/100 - AcquiringRate/100)
     * При знаменателе <= 0 возвращает сумму всех фиксированных расходов (грубая оценка).
     */
    protected function calculateMinPrice(Model $pricing): float
    {
        $marketplace = (string) ($pricing->marketplace ?? '');
        $fulfillmentType = (string) ($pricing->fulfillment_type ?? 'fbo');
        $categoryId = $pricing->marketplace_category_id ?? null;

        $totalCost = $this->getTotalCost($pricing);
        $commissionRate = $this->getCommissionRate($marketplace, $categoryId, $fulfillmentType);
        $acquiringRate = $this->getAcquiringRate($marketplace);

        $volumeLiters = $this->calculateVolumeLiters($pricing);
        $weightKg = $pricing->weight_kg !== null ? (float) $pricing->weight_kg : null;
        $logistics = $this->calculateLogistics($marketplace, $fulfillmentType, $volumeLiters, $weightKg);
        $storage = (float) ($pricing->storage_cost ?? 0);

        $divisor = 1 - ($commissionRate / 100) - ($acquiringRate / 100);

        if ($divisor <= 0) {
            // Процентные расходы >= 100%, точный расчёт невозможен
            return round($totalCost + $logistics + $storage, 2);
        }

        $minPrice = ($totalCost + $logistics + $storage) / $divisor;

        return round($minPrice, 2);
    }

    /**
     * Получить процент комиссии маркетплейса (для алгебраических формул)
     *
     * Возвращает ставку в процентах (например, 25.0 означает 25%).
     */
    protected function getCommissionRate(string $marketplace, ?int $categoryId, string $fulfillmentType): float
    {
        if ($marketplace === '') {
            return 0.0;
        }

        // Ищем с привязкой к категории и фулфилменту
        $query = MarketplaceCommission::forMarketplace($marketplace)
            ->active()
            ->where('fulfillment_type', $fulfillmentType);

        if ($categoryId !== null) {
            $query->where('category_id', $categoryId);
        }

        $commission = $query->first();

        if ($commission !== null) {
            return (float) $commission->commission_percent;
        }

        // Ищем без привязки к фулфилменту
        if ($categoryId !== null) {
            $commission = MarketplaceCommission::forMarketplace($marketplace)
                ->active()
                ->where('category_id', $categoryId)
                ->first();

            if ($commission !== null) {
                return (float) $commission->commission_percent;
            }
        }

        return $this->getDefaultCommissionRate($marketplace);
    }

    /**
     * Получить процент эквайринга маркетплейса (для алгебраических формул)
     *
     * Возвращает ставку в процентах (например, 2.0 означает 2%).
     */
    protected function getAcquiringRate(string $marketplace): float
    {
        if ($marketplace === '') {
            return 0.0;
        }

        $acquiring = MarketplaceAcquiring::query()
            ->where('marketplace', $marketplace)
            ->active()
            ->orderByDesc('effective_from')
            ->first();

        if ($acquiring !== null) {
            return (float) $acquiring->rate_percent;
        }

        return $this->getDefaultAcquiringRate($marketplace);
    }

    // =========================================================================
    // Вспомогательные утилиты
    // =========================================================================

    /**
     * Получить полную себестоимость из модели
     *
     * Суммирует: cost_price + packaging_cost + delivery_to_warehouse + other_costs
     */
    protected function getTotalCost(Model $pricing): float
    {
        // Если модель имеет вычисляемый аксессор — используем его
        if (method_exists($pricing, 'getTotalCostPriceAttribute')) {
            return (float) $pricing->total_cost_price;
        }

        return (float) ($pricing->cost_price ?? 0)
            + (float) ($pricing->packaging_cost ?? 0)
            + (float) ($pricing->delivery_to_warehouse ?? 0)
            + (float) ($pricing->other_costs ?? 0);
    }

    /**
     * Рассчитать объём товара в литрах по габаритам (см -> литры)
     *
     * Формула: (длина * ширина * высота) / 1000
     */
    protected function calculateVolumeLiters(Model $pricing): ?float
    {
        $length = $pricing->length_cm ?? null;
        $width = $pricing->width_cm ?? null;
        $height = $pricing->height_cm ?? null;

        if ($length === null || $width === null || $height === null) {
            return null;
        }

        $volumeCm3 = (float) $length * (float) $width * (float) $height;

        return round($volumeCm3 / 1000, 3);
    }

    /**
     * Применить тариф логистики к параметрам товара
     *
     * Поддерживает 4 типа тарификации:
     * - fixed: фиксированная ставка
     * - per_liter: ставка за литр объёма
     * - per_kg: ставка за килограмм веса
     * - percent: процент от цены (не поддерживается без цены, возвращает rate)
     */
    protected function applyLogisticsRate(
        MarketplaceLogistics $logistic,
        ?float $volumeLiters,
        ?float $weightKg
    ): float {
        $rate = (float) $logistic->rate;
        $rateType = $logistic->rate_type ?? 'fixed';

        $cost = match ($rateType) {
            'per_liter' => $volumeLiters !== null
                ? round($rate * $volumeLiters, 2)
                : $rate,
            'per_kg' => $weightKg !== null
                ? round($rate * $weightKg, 2)
                : $rate,
            'percent' => $rate, // Процент от цены — в этом контексте возвращаем как фиксированную сумму
            default => $rate,   // fixed
        };

        return round($cost, 2);
    }

    // =========================================================================
    // Дефолтные ставки (используются при пустой БД)
    // =========================================================================

    /**
     * Дефолтная ставка комиссии маркетплейса (в процентах)
     */
    protected function getDefaultCommissionRate(string $marketplace): float
    {
        return match ($marketplace) {
            'wildberries' => 25.0,
            'ozon' => 18.0,
            'yandex' => 10.0,
            'uzum' => 15.0,
            default => 20.0,
        };
    }

    /**
     * Дефолтная ставка логистики маркетплейса (фиксированная сумма)
     */
    protected function getDefaultLogisticsRate(string $marketplace): float
    {
        return match ($marketplace) {
            'wildberries' => 50.0,
            'ozon' => 100.0,
            'yandex' => 75.0,
            'uzum' => 5000.0,
            default => 100.0,
        };
    }

    /**
     * Дефолтная ставка эквайринга маркетплейса (в процентах)
     */
    protected function getDefaultAcquiringRate(string $marketplace): float
    {
        return match ($marketplace) {
            'wildberries' => 2.0,
            'ozon' => 1.0,
            'yandex' => 1.5,
            'uzum' => 0.0,
            default => 1.5,
        };
    }
}
