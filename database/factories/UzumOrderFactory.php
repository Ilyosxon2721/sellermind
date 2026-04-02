<?php

namespace Database\Factories;

use App\Models\MarketplaceAccount;
use App\Models\UzumOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

class UzumOrderFactory extends Factory
{
    protected $model = UzumOrder::class;

    public function definition(): array
    {
        return [
            'marketplace_account_id' => MarketplaceAccount::factory(),
            'external_order_id' => (string) $this->faker->unique()->randomNumber(8),
            'status' => 'delivered',
            'status_normalized' => 'delivered',
            'delivery_type' => 'FBS',
            'customer_name' => $this->faker->name(),
            'customer_phone' => $this->faker->phoneNumber(),
            'total_amount' => $this->faker->randomFloat(2, 10000, 500000),
            'currency' => 'UZS',
            'ordered_at' => now(),
            'raw_payload' => [],
        ];
    }
}
