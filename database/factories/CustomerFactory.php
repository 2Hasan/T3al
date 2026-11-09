<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Merchant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'merchant_id' => Merchant::factory(),
            'name' => fake()->name(),
            'phone' => '973'.fake()->unique()->numerify('3#######'),
        ];
    }
}
