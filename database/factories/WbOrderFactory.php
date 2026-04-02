<?php

namespace Database\Factories;

use App\Models\MarketplaceAccount;
use App\Models\WbOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

class WbOrderFactory extends Factory
{
    protected $model = WbOrder::class;

    public function definition(): array
    {
        return [
            'marketplace_account_id' => MarketplaceAccount::factory(),
            'external_order_id' => (string) $this->faker->unique()->randomNumber(8),
            'status' => 'complete',
            'status_normalized' => 'delivered',
            'customer_name' => $this->faker->name(),
            'customer_phone' => $this->faker->phoneNumber(),
            'total_amount' => $this->faker->randomFloat(2, 500, 50000),
            'currency' => 'RUB',
            'ordered_at' => now(),
            'raw_payload' => [],
        ];
    }
}
