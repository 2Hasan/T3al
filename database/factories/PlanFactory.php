<?php

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Plan>
 */
class PlanFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'name' => ucfirst($name),
            'slug' => $name.'-'.fake()->unique()->numerify('###'),
            'price_per_branch' => '3.000',
            'features' => ['loyalty_cards', 'cashier_scan'],
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
