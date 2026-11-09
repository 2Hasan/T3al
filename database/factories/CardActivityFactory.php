<?php

namespace Database\Factories;

use App\Enums\CardActivityType;
use App\Models\CardActivity;
use App\Models\LoyaltyCard;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CardActivity>
 */
class CardActivityFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'loyalty_card_id' => LoyaltyCard::factory(),
            'merchant_id' => fn (array $attributes) => LoyaltyCard::find($attributes['loyalty_card_id'])->merchant_id,
            'type' => CardActivityType::Stamp,
            'quantity' => 1,
        ];
    }
}
