<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\MarketplaceAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

class MarketplaceAccountFactory extends Factory
{
    protected $model = MarketplaceAccount::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'user_id' => null,
            'marketplace' => $this->faker->randomElement(['uzum', 'wb', 'ozon', 'ym']),
            'name' => $this->faker->company(),
            'is_active' => true,
            'connected_at' => now(),
        ];
    }
}
