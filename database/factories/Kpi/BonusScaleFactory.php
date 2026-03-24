<?php

declare(strict_types=1);

namespace Database\Factories\Kpi;

use App\Models\Company;
use App\Models\Kpi\BonusScale;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Фабрика для модели BonusScale
 *
 * @extends Factory<BonusScale>
 */
final class BonusScaleFactory extends Factory
{
    protected $model = BonusScale::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => fake()->randomElement(['Базовая', 'Продвинутая', 'Премиум']) . ' шкала',
            'is_default' => false,
        ];
    }

    /**
     * Шкала по умолчанию
     */
    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
        ]);
    }
}
