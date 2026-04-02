<?php

namespace Database\Factories;

use App\Models\MarketplaceAccount;
use App\Models\OzonOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

class OzonOrderFactory extends Factory
{
    protected $model = OzonOrder::class;

    public function definition(): array
    {
        return [
            'marketplace_account_id' => MarketplaceAccount::factory(),
            'order_id' => (string) $this->faker->unique()->randomNumber(8),
            'posting_number' => $this->faker->uuid(),
            'status' => 'delivered',
            'customer_name' => $this->faker->name(),
            'customer_phone' => $this->faker->phoneNumber(),
            'total_price' => $this->faker->randomFloat(2, 500, 50000),
            'currency' => 'RUB',
            'created_at_ozon' => now(),
        ];
    }
}
