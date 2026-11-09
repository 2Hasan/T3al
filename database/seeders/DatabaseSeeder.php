<?php

namespace Database\Seeders;

use App\Actions\AddStamp;
use App\Actions\IssueLoyaltyCard;
use App\Enums\MerchantRole;
use App\Models\Merchant;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seeds a demo café with an owner (owner@example.com / password),
     * a cashier (cashier@example.com / password) and a few customers.
     */
    public function run(IssueLoyaltyCard $issueLoyaltyCard, AddStamp $addStamp): void
    {
        $owner = User::factory()->create(['name' => 'صاحب المقهى', 'email' => 'owner@example.com']);
        $cashier = User::factory()->create(['name' => 'الكاشير', 'email' => 'cashier@example.com']);

        $merchant = Merchant::factory()->create(['name' => 'مقهى السيف', 'slug' => 'seef-cafe']);
        $merchant->users()->attach($owner, ['role' => MerchantRole::Owner->value]);
        $merchant->users()->attach($cashier, ['role' => MerchantRole::Staff->value]);

        $program = $merchant->loyaltyPrograms()->create([
            'name' => 'بطاقة القهوة',
            'reward_label' => 'قهوة مجانية',
            'stamps_required' => 10,
            'background_color' => '#3B2416',
            'foreground_color' => '#FFFFFF',
            'label_color' => '#E7D7C9',
            'terms' => 'ختم واحد لكل مشروب. المكافأة صالحة في جميع الفروع.',
        ]);

        $customers = [
            ['فاطمة', '36110001', 4],
            ['علي', '36110002', 9],
            ['مريم', '36110003', 12],
            ['حسن', '36110004', 1],
        ];

        foreach ($customers as [$name, $phone, $stamps]) {
            $card = $issueLoyaltyCard->handle($program, $name, $phone);
            $remaining = $stamps;

            while ($remaining > 0) {
                $batch = min($remaining, AddStamp::MAX_STAMPS_PER_SCAN);
                $addStamp->handle($card, $cashier, $batch);
                $remaining -= $batch;
            }
        }
    }
}
