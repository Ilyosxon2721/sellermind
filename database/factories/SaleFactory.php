<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SaleFactory extends Factory
{
    protected $model = Sale::class;

    public function definition(): array
    {
        $subtotal = $this->faker->randomFloat(2, 100, 10000);
        $discountAmount = $this->faker->randomFloat(2, 0, $subtotal * 0.2);
        $taxAmount = $this->faker->randomFloat(2, 0, $subtotal * 0.1);
        $totalAmount = $subtotal - $discountAmount + $taxAmount;

        return [
            'company_id' => Company::factory(),
            'sale_number' => 'SALE-' . strtoupper($this->faker->bothify('??###')),
            'type' => $this->faker->randomElement(['marketplace', 'manual', 'pos']),
            'source' => $this->faker->randomElement(['uzum', 'wb', 'ozon', 'ym', 'manual', 'pos']),
            'counterparty_id' => null,
            'warehouse_id' => null,
            'marketplace_order_type' => null,
            'marketplace_order_id' => null,
            'subtotal' => $subtotal,
            'discount_amount' => $discountAmount,
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount,
            'currency' => 'UZS',
            'status' => 'draft',
            'confirmed_at' => null,
            'completed_at' => null,
            'cancelled_at' => null,
            'notes' => $this->faker->optional()->sentence(),
            'metadata' => null,
            'created_by' => User::factory(),
            'confirmed_by' => null,
        ];
    }

    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'confirmed_at' => now()->subDay(),
            'completed_at' => now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);
    }
}
