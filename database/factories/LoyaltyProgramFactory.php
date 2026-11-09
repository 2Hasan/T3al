<?php

namespace Database\Factories;

use App\Models\LoyaltyProgram;
use App\Models\Merchant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LoyaltyProgram>
 */
class LoyaltyProgramFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'merchant_id' => Merchant::factory(),
            'name' => 'بطاقة القهوة',
            'stamps_required' => 10,
            'reward_label' => 'قهوة مجانية',
            'background_color' => '#3B2416',
            'foreground_color' => '#FFFFFF',
            'label_color' => '#E7D7C9',
            'is_active' => true,
        ];
    }

    public function stampsRequired(int $stamps): static
    {
        return $this->state(fn (array $attributes) => ['stamps_required' => $stamps]);
    }
}
