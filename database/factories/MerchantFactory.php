<?php

namespace Database\Factories;

use App\Models\Merchant;
use App\Models\Plan;
use App\Support\PlanFeatures;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Merchant>
 */
class MerchantFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(4)),
            'timezone' => 'Asia/Bahrain',
            'trial_ends_at' => now()->addDays(14),
            'plan_id' => Plan::factory()->state(['features' => PlanFeatures::keys()]),
        ];
    }

    /**
     * A shop with no plan: it has no features, so the panel hides everything that is sold per plan.
     */
    public function withoutPlan(): static
    {
        return $this->state(['plan_id' => null]);
    }
}
