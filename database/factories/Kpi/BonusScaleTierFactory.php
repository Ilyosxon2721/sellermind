<?php

declare(strict_types=1);

namespace Database\Factories\Kpi;

use App\Models\Kpi\BonusScale;
use App\Models\Kpi\BonusScaleTier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Фабрика для модели BonusScaleTier
 *
 * @extends Factory<BonusScaleTier>
 */
final class BonusScaleTierFactory extends Factory
{
    protected $model = BonusScaleTier::class;

    public function definition(): array
    {
        return [
            'kpi_bonus_scale_id' => BonusScale::factory(),
            'min_percent' => 80,
            'max_percent' => 100,
            'bonus_type' => BonusScaleTier::TYPE_FIXED,
            'bonus_value' => fake()->numberBetween(100000, 1000000),
        ];
    }

    /**
     * Фиксированный бонус
     */
    public function fixed(float $value): static
    {
        return $this->state(fn (array $attributes) => [
            'bonus_type' => BonusScaleTier::TYPE_FIXED,
            'bonus_value' => $value,
        ]);
    }

    /**
     * Процент от оборота
     */
    public function percentRevenue(float $percent): static
    {
        return $this->state(fn (array $attributes) => [
            'bonus_type' => BonusScaleTier::TYPE_PERCENT_REVENUE,
            'bonus_value' => $percent,
        ]);
    }

    /**
     * Процент от маржи
     */
    public function percentMargin(float $percent): static
    {
        return $this->state(fn (array $attributes) => [
            'bonus_type' => BonusScaleTier::TYPE_PERCENT_MARGIN,
            'bonus_value' => $percent,
        ]);
    }

    /**
     * Указать диапазон процентов
     */
    public function range(int $min, ?int $max): static
    {
        return $this->state(fn (array $attributes) => [
            'min_percent' => $min,
            'max_percent' => $max,
        ]);
    }
}
