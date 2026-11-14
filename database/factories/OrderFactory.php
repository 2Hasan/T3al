<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Merchant;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'merchant_id' => Merchant::factory(),
            'loyalty_card_id' => null,
            'number' => fake()->numberBetween(1, 99),
            'note' => null,
            'status' => OrderStatus::Preparing,
        ];
    }
}
