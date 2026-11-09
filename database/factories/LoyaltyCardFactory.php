<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\LoyaltyCard;
use App\Models\LoyaltyProgram;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<LoyaltyCard>
 */
class LoyaltyCardFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'loyalty_program_id' => LoyaltyProgram::factory(),
            'merchant_id' => fn (array $attributes) => LoyaltyProgram::find($attributes['loyalty_program_id'])->merchant_id,
            'customer_id' => fn (array $attributes) => Customer::factory()->create([
                'merchant_id' => $attributes['merchant_id'],
            ])->id,
            'code' => Str::upper(Str::random(12)),
        ];
    }

    public function withStamps(int $stamps): static
    {
        return $this->state(fn (array $attributes) => ['stamps' => $stamps]);
    }

    public function withRewards(int $rewards): static
    {
        return $this->state(fn (array $attributes) => ['rewards_available' => $rewards]);
    }
}
