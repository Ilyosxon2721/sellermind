<?php

declare(strict_types=1);

namespace Database\Factories\Kpi;

use App\Models\Company;
use App\Models\Kpi\SalesSphere;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Фабрика для модели SalesSphere
 *
 * @extends Factory<SalesSphere>
 */
final class SalesSphereFactory extends Factory
{
    protected $model = SalesSphere::class;

    public function definition(): array
    {
        $names = ['Wildberries', 'Ozon', 'Uzum', 'Yandex Market', 'Розница', 'Опт', 'Instagram'];
        $name = fake()->unique()->randomElement($names);

        return [
            'company_id' => Company::factory(),
            'name' => $name,
            'code' => strtolower(str_replace(' ', '_', $name)),
            'description' => fake()->optional()->sentence(),
            'color' => fake()->hexColor(),
            'icon' => 'shopping-bag',
            'marketplace_account_id' => null,
            'marketplace_account_ids' => null,
            'offline_sale_types' => null,
            'is_active' => true,
            'sort_order' => fake()->numberBetween(0, 10),
        ];
    }

    /**
     * Сфера привязанная к маркетплейс-аккаунтам
     */
    public function withMarketplaceAccounts(array $accountIds): static
    {
        return $this->state(fn (array $attributes) => [
            'marketplace_account_ids' => $accountIds,
        ]);
    }

    /**
     * Сфера привязанная к ручным продажам
     */
    public function withOfflineSales(array $types = ['retail']): static
    {
        return $this->state(fn (array $attributes) => [
            'offline_sale_types' => $types,
        ]);
    }

    /**
     * Неактивная сфера
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
