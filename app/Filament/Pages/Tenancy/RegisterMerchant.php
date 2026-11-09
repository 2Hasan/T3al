<?php

namespace App\Filament\Pages\Tenancy;

use App\Enums\MerchantRole;
use App\Models\Merchant;
use App\Models\Plan;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Tenancy\RegisterTenant;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Shown right after sign-up: creates the shop, makes the user its owner and
 * gives it a ready-to-use stamp card so the first customer can join immediately.
 */
class RegisterMerchant extends RegisterTenant
{
    public static function getLabel(): string
    {
        return __('app.register_shop');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('app.shop_name'))
                    ->required()
                    ->maxLength(60),
                TextInput::make('reward_label')
                    ->label(__('app.reward_after_10'))
                    ->placeholder(__('app.free_coffee'))
                    ->required()
                    ->maxLength(40),
            ]);
    }

    /**
     * @param  array{name: string, reward_label: string}  $data
     */
    protected function handleRegistration(array $data): Merchant
    {
        return DB::transaction(function () use ($data): Merchant {
            $merchant = Merchant::create([
                'name' => $data['name'],
                'slug' => $this->uniqueSlug($data['name']),
                'trial_ends_at' => now()->addDays(14),
                'plan_id' => Plan::query()->where('is_active', true)->orderBy('sort_order')->value('id'),
            ]);

            $merchant->branches()->create(['name' => $data['name']]);

            $merchant->users()->attach(auth()->id(), ['role' => MerchantRole::Owner->value]);

            $merchant->loyaltyPrograms()->create([
                'name' => __('app.card_prefix').$data['name'],
                'reward_label' => $data['reward_label'],
                'stamps_required' => 10,
            ]);

            return $merchant;
        });
    }

    protected function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'shop';

        do {
            $slug = $base.'-'.Str::lower(Str::random(4));
        } while (Merchant::where('slug', $slug)->exists());

        return $slug;
    }
}
